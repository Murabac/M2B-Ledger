using Microsoft.Extensions.Options;
using QbBalances.Agent.Configuration;
using QbBalances.Agent.Publishing;
using QbBalances.Agent.QuickBooks;

namespace QbBalances.Agent;

public sealed class SyncWorker : BackgroundService
{
    private readonly IQuickBooksReader _reader;
    private readonly SnapshotPublisher _publisher;
    private readonly AgentOptions _options;
    private readonly ILogger<SyncWorker> _logger;
    private readonly SemaphoreSlim _cycleLock = new(1, 1);

    public SyncWorker(
        IQuickBooksReader reader,
        SnapshotPublisher publisher,
        IOptions<AgentOptions> options,
        ILogger<SyncWorker> logger)
    {
        _reader = reader;
        _publisher = publisher;
        _options = options.Value;
        _logger = logger;
    }

    protected override async Task ExecuteAsync(CancellationToken stoppingToken)
    {
        var interval = TimeSpan.FromSeconds(Math.Max(5, _options.PollIntervalSeconds));
        _logger.LogInformation(
            "Sync worker started. MockMode={MockMode}, PollIntervalSeconds={PollIntervalSeconds}, CompanyId={CompanyId}, BackendUrl={BackendUrl}",
            _options.MockMode,
            _options.PollIntervalSeconds,
            _options.CompanyId,
            _options.BackendUrl);

        while (!stoppingToken.IsCancellationRequested)
        {
            await RunCycleAsync(stoppingToken).ConfigureAwait(false);

            try
            {
                await Task.Delay(interval, stoppingToken).ConfigureAwait(false);
            }
            catch (OperationCanceledException) when (stoppingToken.IsCancellationRequested)
            {
                break;
            }
        }
    }

    internal async Task RunCycleAsync(CancellationToken cancellationToken)
    {
        if (!await _cycleLock.WaitAsync(0, cancellationToken).ConfigureAwait(false))
        {
            _logger.LogWarning("Skipping sync cycle; previous cycle still running");
            return;
        }

        try
        {
            _logger.LogInformation("Starting sync cycle");
            var snapshot = await _reader.ReadSnapshotAsync(_options.CompanyId, cancellationToken).ConfigureAwait(false);
            await _publisher.PublishAsync(snapshot, cancellationToken).ConfigureAwait(false);
            _logger.LogInformation(
                "Sync cycle completed. synced_at={SyncedAt} accounts={Accounts} customers={Customers}",
                snapshot.SyncedAt,
                snapshot.Accounts.Count,
                snapshot.Customers.Count);
        }
        catch (OperationCanceledException) when (cancellationToken.IsCancellationRequested)
        {
            throw;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Sync cycle failed");
        }
        finally
        {
            _cycleLock.Release();
        }
    }

    public override void Dispose()
    {
        _cycleLock.Dispose();
        base.Dispose();
    }
}
