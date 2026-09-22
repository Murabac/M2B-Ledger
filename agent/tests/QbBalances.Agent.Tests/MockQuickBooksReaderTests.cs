using System.Text.Json;
using System.Text.RegularExpressions;
using QbBalances.Agent.Models;
using QbBalances.Agent.QuickBooks;

namespace QbBalances.Agent.Tests;

public class MockQuickBooksReaderTests
{
    private static readonly Regex IsoUtc = new(
        @"^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$",
        RegexOptions.Compiled);

    [Fact]
    public async Task ReadSnapshot_shape_matches_sync_contract()
    {
        var reader = new MockQuickBooksReader(seed: 42);
        var snapshot = await reader.ReadSnapshotAsync(companyId: 1, CancellationToken.None);

        Assert.Equal(1, snapshot.CompanyId);
        Assert.Matches(IsoUtc, snapshot.SyncedAt);
        Assert.NotEmpty(snapshot.Accounts);
        Assert.Equal(MockQuickBooksReader.CustomerCount, snapshot.Customers.Count);

        var json = JsonSerializer.Serialize(snapshot);
        using var doc = JsonDocument.Parse(json);
        var root = doc.RootElement;

        Assert.True(root.TryGetProperty("company_id", out _));
        Assert.True(root.TryGetProperty("synced_at", out _));
        Assert.True(root.TryGetProperty("accounts", out var accounts));
        Assert.True(root.TryGetProperty("customers", out var customers));

        var account = accounts[0];
        Assert.True(account.TryGetProperty("qb_list_id", out _));
        Assert.True(account.TryGetProperty("full_name", out _));
        Assert.True(account.TryGetProperty("account_type", out _));
        Assert.True(account.TryGetProperty("is_active", out _));
        Assert.True(account.TryGetProperty("balance", out _));
        Assert.True(account.TryGetProperty("total_balance", out _));

        var customer = customers[0];
        Assert.True(customer.TryGetProperty("qb_list_id", out _));
        Assert.True(customer.TryGetProperty("full_name", out _));
        Assert.True(customer.TryGetProperty("is_active", out _));
        Assert.True(customer.TryGetProperty("balance", out _));
        Assert.True(customer.TryGetProperty("total_balance", out _));
        Assert.True(customer.TryGetProperty("sales_rep_name", out _));
    }

    [Fact]
    public async Task Mock_generator_includes_required_account_types_and_sixty_customers()
    {
        var reader = new MockQuickBooksReader(seed: 7);
        var snapshot = await reader.ReadSnapshotAsync(1, CancellationToken.None);

        var types = snapshot.Accounts.Select(a => a.AccountType).ToHashSet(StringComparer.Ordinal);
        Assert.Contains("Bank", types);
        Assert.Contains("AccountsReceivable", types);
        Assert.Contains("Income", types);
        Assert.Contains("Expense", types);

        Assert.Equal(60, snapshot.Customers.Count);
        Assert.Equal(60, snapshot.Customers.Select(c => c.QbListId).Distinct().Count());
        Assert.All(snapshot.Customers, c => Assert.False(string.IsNullOrWhiteSpace(c.FullName)));
    }

    [Fact]
    public async Task Mock_balances_drift_across_cycles_with_stable_seed()
    {
        var reader = new MockQuickBooksReader(seed: 42);
        var first = await reader.ReadSnapshotAsync(1, CancellationToken.None);
        var second = await reader.ReadSnapshotAsync(1, CancellationToken.None);

        Assert.Equal(first.Customers.Count, second.Customers.Count);
        Assert.NotEqual(
            first.Customers.Sum(c => c.Balance),
            second.Customers.Sum(c => c.Balance));
    }
}
