using Microsoft.Extensions.Options;
using QbBalances.Agent.Configuration;
using QbBalances.Agent.Publishing;
using QbBalances.Agent.QuickBooks;
using Serilog;

namespace QbBalances.Agent;

public class Program
{
    public static void Main(string[] args)
    {
        var forceMock = args.Any(a => string.Equals(a, "--mock", StringComparison.OrdinalIgnoreCase));

        Log.Logger = new LoggerConfiguration()
            .MinimumLevel.Information()
            .Enrich.FromLogContext()
            .WriteTo.Console()
            .WriteTo.File(
                path: Path.Combine("logs", "agent-.log"),
                rollingInterval: RollingInterval.Day,
                retainedFileCountLimit: 7,
                shared: true)
            .CreateLogger();

        try
        {
            var builder = Host.CreateApplicationBuilder(args);
            builder.Services.AddSerilog();
            builder.Services.AddWindowsService(options =>
            {
                options.ServiceName = "QbBalancesAgent";
            });

            builder.Services
                .AddOptions<AgentOptions>()
                .Bind(builder.Configuration.GetSection(AgentOptions.SectionName))
                .PostConfigure(options =>
                {
                    if (forceMock)
                    {
                        options.MockMode = true;
                    }
                })
                .Validate(o => !string.IsNullOrWhiteSpace(o.BackendUrl), "Agent:BackendUrl is required.")
                .Validate(o => o.CompanyId > 0, "Agent:CompanyId must be positive.")
                .Validate(o => o.PollIntervalSeconds >= 5, "Agent:PollIntervalSeconds must be >= 5.")
                .ValidateOnStart();

            builder.Services.AddSingleton<QbXmlClient>();
            builder.Services.AddSingleton<QbXmlResponseParser>();
            builder.Services.AddSingleton<IQuickBooksReader>(sp =>
            {
                var options = sp.GetRequiredService<IOptions<AgentOptions>>().Value;
                if (options.MockMode)
                {
                    return new MockQuickBooksReader();
                }

                return new ComQuickBooksReader(
                    sp.GetRequiredService<QbXmlClient>(),
                    sp.GetRequiredService<QbXmlResponseParser>(),
                    sp.GetRequiredService<IOptions<AgentOptions>>(),
                    sp.GetRequiredService<ILogger<ComQuickBooksReader>>());
            });

            builder.Services.AddHttpClient<SnapshotPublisher>(client =>
            {
                client.Timeout = TimeSpan.FromSeconds(60);
            });

            builder.Services.AddHostedService<SyncWorker>();

            var host = builder.Build();

            var options = host.Services.GetRequiredService<IOptions<AgentOptions>>().Value;
            if (string.IsNullOrWhiteSpace(options.AgentToken))
            {
                Log.Warning("Agent:AgentToken is empty. Create a token in Filament and set it before expecting a successful sync.");
            }

            if (!options.MockMode && !OperatingSystem.IsWindows())
            {
                Log.Warning("MockMode is false but OS is not Windows; COM reader will fail.");
            }

            host.Run();
        }
        catch (Exception ex)
        {
            Log.Fatal(ex, "Agent terminated unexpectedly");
            throw;
        }
        finally
        {
            Log.CloseAndFlush();
        }
    }
}
