using System.Globalization;
using System.Xml.Linq;
using QbBalances.Agent.Models;

namespace QbBalances.Agent.QuickBooks;

public sealed class CustomerQueryPage
{
    public IReadOnlyList<CustomerRow> Customers { get; init; } = [];

    public string? IteratorId { get; init; }

    public int IteratorRemainingCount { get; init; }

    public bool HasMore => IteratorRemainingCount > 0 && !string.IsNullOrWhiteSpace(IteratorId);
}

/// <summary>
/// Parses qbXML QueryRs documents into snapshot rows. Shared by COM reader and fixture tests.
/// </summary>
public sealed class QbXmlResponseParser
{
    public IReadOnlyList<AccountRow> ParseAccounts(string qbXmlResponse)
    {
        var rs = GetResponseElement(qbXmlResponse, "AccountQueryRs");
        EnsureSuccess(rs, "AccountQueryRs");

        return rs.Elements("AccountRet")
            .Select(ParseAccount)
            .ToList();
    }

    public CustomerQueryPage ParseCustomers(string qbXmlResponse)
    {
        var rs = GetResponseElement(qbXmlResponse, "CustomerQueryRs");
        EnsureSuccess(rs, "CustomerQueryRs");

        var remaining = ParseIntAttribute(rs, "iteratorRemainingCount");
        var iteratorId = (string?)rs.Attribute("iteratorID");

        var customers = rs.Elements("CustomerRet")
            .Select(ParseCustomer)
            .ToList();

        return new CustomerQueryPage
        {
            Customers = customers,
            IteratorId = iteratorId,
            IteratorRemainingCount = remaining,
        };
    }

    public static string PreferQbXmlVersion(string? versionsForSession, string fallback = QbXmlClient.FallbackVersion)
    {
        if (string.IsNullOrWhiteSpace(versionsForSession))
        {
            return fallback;
        }

        var parsed = versionsForSession
            .Split([',', ' ', ';'], StringSplitOptions.RemoveEmptyEntries | StringSplitOptions.TrimEntries)
            .Select(v => (Raw: v, Ok: decimal.TryParse(v, NumberStyles.Number, CultureInfo.InvariantCulture, out var n), Value: n))
            .Where(x => x.Ok)
            .OrderByDescending(x => x.Value)
            .Select(x => x.Raw)
            .FirstOrDefault();

        return parsed ?? fallback;
    }

    private static AccountRow ParseAccount(XElement ret)
    {
        var balance = ParseDecimal(ret.Element("Balance")?.Value);
        var total = ParseDecimal(ret.Element("TotalBalance")?.Value, balance);

        return new AccountRow
        {
            QbListId = Required(ret, "ListID"),
            FullName = Required(ret, "FullName"),
            AccountType = Required(ret, "AccountType"),
            IsActive = ParseBool(ret.Element("IsActive")?.Value, defaultValue: true),
            Balance = balance,
            TotalBalance = total,
        };
    }

    private static CustomerRow ParseCustomer(XElement ret)
    {
        var balance = ParseDecimal(ret.Element("Balance")?.Value);
        var total = ParseDecimal(ret.Element("TotalBalance")?.Value, balance);
        var salesRep = ret.Element("SalesRepRef")?.Element("FullName")?.Value;

        return new CustomerRow
        {
            QbListId = Required(ret, "ListID"),
            FullName = Required(ret, "FullName"),
            IsActive = ParseBool(ret.Element("IsActive")?.Value, defaultValue: true),
            Balance = balance,
            TotalBalance = total,
            SalesRepName = string.IsNullOrWhiteSpace(salesRep) ? null : salesRep,
        };
    }

    private static XElement GetResponseElement(string qbXmlResponse, string responseName)
    {
        if (string.IsNullOrWhiteSpace(qbXmlResponse))
        {
            throw new InvalidOperationException("Empty qbXML response.");
        }

        XDocument doc;
        try
        {
            doc = XDocument.Parse(qbXmlResponse, LoadOptions.PreserveWhitespace);
        }
        catch (Exception ex)
        {
            throw new InvalidOperationException("Failed to parse qbXML response XML.", ex);
        }

        var rs = doc.Descendants(responseName).FirstOrDefault()
            ?? throw new InvalidOperationException($"qbXML response missing '{responseName}'.");

        return rs;
    }

    private static void EnsureSuccess(XElement rs, string responseName)
    {
        var code = ParseIntAttribute(rs, "statusCode");
        var severity = (string?)rs.Attribute("statusSeverity") ?? string.Empty;
        var message = (string?)rs.Attribute("statusMessage") ?? string.Empty;

        // 0 = OK; 1 = no matching objects (empty result set is fine).
        if (code is 0 or 1)
        {
            return;
        }

        if (severity.Equals("Error", StringComparison.OrdinalIgnoreCase) || code > 1)
        {
            throw new InvalidOperationException(
                $"{responseName} failed: statusCode={code}, severity={severity}, message={message}");
        }
    }

    private static string Required(XElement parent, string name) =>
        parent.Element(name)?.Value
        ?? throw new InvalidOperationException($"Missing required element '{name}'.");

    private static decimal ParseDecimal(string? value, decimal defaultValue = 0m)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            return defaultValue;
        }

        return decimal.Parse(value, NumberStyles.Number, CultureInfo.InvariantCulture);
    }

    private static bool ParseBool(string? value, bool defaultValue)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            return defaultValue;
        }

        return value is "true" or "1" or "True";
    }

    private static int ParseIntAttribute(XElement element, string name)
    {
        var raw = (string?)element.Attribute(name);
        if (string.IsNullOrWhiteSpace(raw))
        {
            return 0;
        }

        return int.Parse(raw, CultureInfo.InvariantCulture);
    }
}
