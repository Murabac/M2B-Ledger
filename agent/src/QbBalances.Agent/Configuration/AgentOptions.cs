namespace QbBalances.Agent.Configuration;

public sealed class AgentOptions
{
    public const string SectionName = "Agent";

    public string BackendUrl { get; set; } = "http://127.0.0.1:8000";

    public string AgentToken { get; set; } = string.Empty;

    public string CompanyFilePath { get; set; } = string.Empty;

    public int PollIntervalSeconds { get; set; } = 180;

    public bool MockMode { get; set; } = true;

    public long CompanyId { get; set; } = 1;

    /// <summary>Name shown in QuickBooks integrated-application prompts.</summary>
    public string AppName { get; set; } = "QB Balances Sync Agent";

    /// <summary>CustomerQueryRq MaxReturned per iterator page.</summary>
    public int CustomerMaxReturned { get; set; } = 100;
}
