using System.Text.Json.Serialization;

namespace QbBalances.Agent.Models;

public sealed class BalanceSnapshot
{
    [JsonPropertyName("company_id")]
    public long CompanyId { get; init; }

    [JsonPropertyName("synced_at")]
    public string SyncedAt { get; init; } = string.Empty;

    [JsonPropertyName("accounts")]
    public IReadOnlyList<AccountRow> Accounts { get; init; } = [];

    [JsonPropertyName("customers")]
    public IReadOnlyList<CustomerRow> Customers { get; init; } = [];
}

public sealed class AccountRow
{
    [JsonPropertyName("qb_list_id")]
    public string QbListId { get; init; } = string.Empty;

    [JsonPropertyName("full_name")]
    public string FullName { get; init; } = string.Empty;

    [JsonPropertyName("account_type")]
    public string AccountType { get; init; } = string.Empty;

    [JsonPropertyName("is_active")]
    public bool IsActive { get; init; }

    [JsonPropertyName("balance")]
    public decimal Balance { get; init; }

    [JsonPropertyName("total_balance")]
    public decimal TotalBalance { get; init; }
}

public sealed class CustomerRow
{
    [JsonPropertyName("qb_list_id")]
    public string QbListId { get; init; } = string.Empty;

    [JsonPropertyName("full_name")]
    public string FullName { get; init; } = string.Empty;

    [JsonPropertyName("is_active")]
    public bool IsActive { get; init; }

    [JsonPropertyName("balance")]
    public decimal Balance { get; init; }

    [JsonPropertyName("total_balance")]
    public decimal TotalBalance { get; init; }

    [JsonPropertyName("sales_rep_name")]
    public string? SalesRepName { get; init; }
}
