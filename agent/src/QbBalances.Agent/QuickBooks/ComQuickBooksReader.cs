using System.Globalization;
using System.Reflection;
using System.Runtime.InteropServices;
using Microsoft.Extensions.Options;
using QbBalances.Agent.Configuration;
using QbBalances.Agent.Models;

namespace QbBalances.Agent.QuickBooks;

/// <summary>
/// Late-bound COM reader for QuickBooks Desktop (QBXMLRP2). No SDK interop assemblies.
/// Runs only on Windows with the Request Processor registered.
/// </summary>
public sealed class ComQuickBooksReader : IQuickBooksReader
{
    public const string ProgId = "QBXMLRP2.RequestProcessor";

    /// <summary>Alternate ProgIDs seen across QB Desktop installs / docs.</summary>
    private static readonly string[] ProgIdCandidates =
    [
        "QBXMLRP2.RequestProcessor",
        "QBXMLRP2.RequestProcessor.2",
        "QBXMLRP2.RequestProcessor2", // older Intuit docs
        "QBXMLRP.RequestProcessor",
    ];

    /// <summary>qbXMLRPConnectionType.localQBD</summary>
    private const int ConnectionTypeLocalQbd = 1;

    /// <summary>QBFileMode / omDontCare</summary>
    private const int FileModeDoNotCare = 0;

    private readonly QbXmlClient _qbXml;
    private readonly QbXmlResponseParser _parser;
    private readonly AgentOptions _options;
    private readonly ILogger<ComQuickBooksReader> _logger;

    public ComQuickBooksReader(
        QbXmlClient qbXml,
        QbXmlResponseParser parser,
        IOptions<AgentOptions> options,
        ILogger<ComQuickBooksReader> logger)
    {
        _qbXml = qbXml;
        _parser = parser;
        _options = options.Value;
        _logger = logger;
    }

    public Task<BalanceSnapshot> ReadSnapshotAsync(long companyId, CancellationToken cancellationToken)
    {
        cancellationToken.ThrowIfCancellationRequested();

        if (!OperatingSystem.IsWindows())
        {
            throw new PlatformNotSupportedException(
                "ComQuickBooksReader requires Windows with QuickBooks Desktop Enterprise and QBXMLRP2.");
        }

        var snapshot = ReadSnapshot(companyId, cancellationToken);
        return Task.FromResult(snapshot);
    }

    private BalanceSnapshot ReadSnapshot(long companyId, CancellationToken cancellationToken)
    {
        object? processor = null;
        string? ticket = null;
        var connectionOpened = false;

        try
        {
            processor = CreateProcessor();
            Invoke(processor, "OpenConnection2", string.Empty, _options.AppName, ConnectionTypeLocalQbd);
            connectionOpened = true;

            var companyFile = _options.CompanyFilePath ?? string.Empty;
            ticket = (string?)Invoke(processor, "BeginSession", companyFile, FileModeDoNotCare)
                ?? throw new InvalidOperationException("BeginSession returned no ticket.");

            var qbXmlVersion = NegotiateQbXmlVersion(processor, ticket);
            _logger.LogInformation(
                "QB session open. Using qbXML version {Version}",
                qbXmlVersion);

            cancellationToken.ThrowIfCancellationRequested();

            var accountXml = _qbXml.BuildAccountQuery(qbXmlVersion);
            var accountResponse = (string?)Invoke(processor, "ProcessRequest", ticket, accountXml)
                ?? throw new InvalidOperationException("AccountQuery ProcessRequest returned null.");
            var accounts = _parser.ParseAccounts(accountResponse);
            _logger.LogInformation("AccountQuery returned {Count} accounts", accounts.Count);

            var customers = ReadAllCustomers(processor, ticket, qbXmlVersion, cancellationToken);
            _logger.LogInformation("CustomerQuery returned {Count} customers", customers.Count);

            return new BalanceSnapshot
            {
                CompanyId = companyId,
                SyncedAt = DateTime.UtcNow.ToString("yyyy-MM-dd'T'HH:mm:ss'Z'", CultureInfo.InvariantCulture),
                Accounts = accounts,
                Customers = customers,
            };
        }
        finally
        {
            if (processor is not null && ticket is not null)
            {
                try
                {
                    Invoke(processor, "EndSession", ticket);
                }
                catch (Exception ex)
                {
                    _logger.LogWarning(ex, "EndSession failed during cleanup");
                }
            }

            if (processor is not null && connectionOpened)
            {
                try
                {
                    Invoke(processor, "CloseConnection");
                }
                catch (Exception ex)
                {
                    _logger.LogWarning(ex, "CloseConnection failed during cleanup");
                }
            }

            if (processor is not null)
            {
                ReleaseComObject(processor);
            }
        }
    }

    private string NegotiateQbXmlVersion(object processor, string ticket)
    {
        try
        {
            var versions = (string?)Invoke(processor, "QBXMLVersionsForSession", ticket);
            return QbXmlResponseParser.PreferQbXmlVersion(versions, QbXmlClient.FallbackVersion);
        }
        catch (COMException ex) when (unchecked((uint)ex.HResult) is 0x80020003 or 0x80020006)
        {
            // DISP_E_MEMBERNOTFOUND / DISP_E_UNKNOWNNAME — older QBXMLRP2 typelibs omit this method.
            _logger.LogInformation(
                "QBXMLVersionsForSession not available on this Request Processor (0x{HResult:X8}); using fallback {Version}",
                ex.HResult,
                QbXmlClient.FallbackVersion);
            return QbXmlClient.FallbackVersion;
        }
        catch (Exception ex)
        {
            _logger.LogWarning(ex, "Version negotiation failed; using fallback {Version}", QbXmlClient.FallbackVersion);
            return QbXmlClient.FallbackVersion;
        }
    }

    private List<CustomerRow> ReadAllCustomers(
        object processor,
        string ticket,
        string qbXmlVersion,
        CancellationToken cancellationToken)
    {
        var all = new List<CustomerRow>();
        string? iteratorId = null;
        var page = 0;
        const int maxPages = 500;

        while (page < maxPages)
        {
            cancellationToken.ThrowIfCancellationRequested();
            page++;

            var request = _qbXml.BuildCustomerQuery(_options.CustomerMaxReturned, iteratorId, qbXmlVersion);
            var response = (string?)Invoke(processor, "ProcessRequest", ticket, request)
                ?? throw new InvalidOperationException("CustomerQuery ProcessRequest returned null.");

            var parsed = _parser.ParseCustomers(response);
            all.AddRange(parsed.Customers);

            _logger.LogInformation(
                "CustomerQuery page {Page}: {Count} rows, remaining={Remaining}",
                page,
                parsed.Customers.Count,
                parsed.IteratorRemainingCount);

            if (!parsed.HasMore)
            {
                break;
            }

            iteratorId = parsed.IteratorId;
        }

        if (page >= maxPages)
        {
            throw new InvalidOperationException($"CustomerQuery exceeded {maxPages} iterator pages.");
        }

        return all;
    }

    internal static object CreateProcessor()
    {
        if (!OperatingSystem.IsWindows())
        {
            throw new PlatformNotSupportedException(
                $"{ProgId} requires Windows with QuickBooks Desktop Enterprise.");
        }

        Type? type = null;
        string? resolvedProgId = null;
        foreach (var candidate in ProgIdCandidates)
        {
            type = Type.GetTypeFromProgID(candidate);
            if (type is not null)
            {
                resolvedProgId = candidate;
                break;
            }
        }

        if (type is null || resolvedProgId is null)
        {
            throw new InvalidOperationException(
                $"No qbXML Request Processor ProgID registered (tried: {string.Join(", ", ProgIdCandidates)}). Install QuickBooks Desktop Enterprise and ensure QBXMLRP2 is available.");
        }

        return Activator.CreateInstance(type)
            ?? throw new InvalidOperationException($"Failed to create COM instance for {resolvedProgId}.");
    }

    private static object? Invoke(object target, string method, params object?[] args)
    {
        try
        {
            return target.GetType().InvokeMember(
                method,
                BindingFlags.InvokeMethod | BindingFlags.Public | BindingFlags.Instance,
                binder: null,
                target: target,
                args: args,
                culture: CultureInfo.InvariantCulture);
        }
        catch (TargetInvocationException ex) when (ex.InnerException is not null)
        {
            throw ex.InnerException;
        }
    }

    private static void ReleaseComObject(object processor)
    {
        if (!OperatingSystem.IsWindows() || !Marshal.IsComObject(processor))
        {
            return;
        }

        try
        {
            Marshal.FinalReleaseComObject(processor);
        }
        catch
        {
            // ignore release failures
        }
    }
}
