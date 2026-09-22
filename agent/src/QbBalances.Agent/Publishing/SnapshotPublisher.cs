using System.Net;
using System.Net.Http.Headers;
using System.Net.Http.Json;
using System.Text.Json;
using Microsoft.Extensions.Options;
using QbBalances.Agent.Configuration;
using QbBalances.Agent.Models;

namespace QbBalances.Agent.Publishing;

public sealed class SnapshotPublisher
{
    private static readonly JsonSerializerOptions JsonOptions = new()
    {
        PropertyNamingPolicy = null,
        DefaultIgnoreCondition = System.Text.Json.Serialization.JsonIgnoreCondition.WhenWritingNull,
    };

    private static readonly TimeSpan[] RetryDelays =
    [
        TimeSpan.FromSeconds(2),
        TimeSpan.FromSeconds(4),
        TimeSpan.FromSeconds(8),
    ];

    private readonly HttpClient _httpClient;
    private readonly AgentOptions _options;
    private readonly ILogger<SnapshotPublisher> _logger;

    public SnapshotPublisher(HttpClient httpClient, IOptions<AgentOptions> options, ILogger<SnapshotPublisher> logger)
    {
        _httpClient = httpClient;
        _options = options.Value;
        _logger = logger;
    }

    public async Task PublishAsync(BalanceSnapshot snapshot, CancellationToken cancellationToken)
    {
        var baseUrl = _options.BackendUrl.TrimEnd('/');
        var url = $"{baseUrl}/api/agent/sync";

        Exception? lastError = null;

        for (var attempt = 0; attempt <= RetryDelays.Length; attempt++)
        {
            cancellationToken.ThrowIfCancellationRequested();

            try
            {
                using var request = new HttpRequestMessage(HttpMethod.Post, url);
                request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", _options.AgentToken);
                request.Content = JsonContent.Create(snapshot, options: JsonOptions);

                _logger.LogInformation(
                    "Posting snapshot to {Url} (attempt {Attempt}/{Max}): {Accounts} accounts, {Customers} customers",
                    url,
                    attempt + 1,
                    RetryDelays.Length + 1,
                    snapshot.Accounts.Count,
                    snapshot.Customers.Count);

                using var response = await _httpClient.SendAsync(request, cancellationToken).ConfigureAwait(false);

                if (response.IsSuccessStatusCode)
                {
                    _logger.LogInformation("Snapshot accepted with HTTP {StatusCode}", (int)response.StatusCode);
                    return;
                }

                var body = await response.Content.ReadAsStringAsync(cancellationToken).ConfigureAwait(false);
                var status = response.StatusCode;

                if (!IsTransient(status))
                {
                    throw new HttpRequestException(
                        $"Agent sync rejected with HTTP {(int)status} {status}. Body: {Truncate(body)}");
                }

                lastError = new HttpRequestException(
                    $"Transient HTTP {(int)status} {status}. Body: {Truncate(body)}");
                _logger.LogWarning(lastError, "Transient sync failure on attempt {Attempt}", attempt + 1);
            }
            catch (OperationCanceledException) when (cancellationToken.IsCancellationRequested)
            {
                throw;
            }
            catch (HttpRequestException ex) when (IsNetworkFailure(ex))
            {
                lastError = ex;
                _logger.LogWarning(ex, "Network failure on sync attempt {Attempt}", attempt + 1);
            }
            catch (TaskCanceledException ex) when (!cancellationToken.IsCancellationRequested)
            {
                lastError = ex;
                _logger.LogWarning(ex, "Timeout on sync attempt {Attempt}", attempt + 1);
            }

            if (attempt < RetryDelays.Length)
            {
                await Task.Delay(RetryDelays[attempt], cancellationToken).ConfigureAwait(false);
            }
        }

        throw new HttpRequestException("Agent sync failed after retries.", lastError);
    }

    private static bool IsTransient(HttpStatusCode status) =>
        status == HttpStatusCode.RequestTimeout
        || status == HttpStatusCode.TooManyRequests
        || (int)status >= 500;

    private static bool IsNetworkFailure(HttpRequestException ex) =>
        ex.StatusCode is null or >= HttpStatusCode.InternalServerError;

    private static string Truncate(string value, int max = 400) =>
        value.Length <= max ? value : value[..max] + "…";
}
