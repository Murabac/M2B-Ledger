using QbBalances.Agent.QuickBooks;

namespace QbBalances.Agent.Tests;

public class QbXmlResponseParserTests
{
    private readonly QbXmlResponseParser _parser = new();

    [Fact]
    public void ParseAccounts_reads_fixture_fields()
    {
        var xml = Fixture("account_query_rs.xml");
        var accounts = _parser.ParseAccounts(xml);

        Assert.Equal(4, accounts.Count);
        Assert.Contains(accounts, a => a.AccountType == "Bank" && a.FullName == "Checking" && a.Balance == 12450.75m);
        Assert.Contains(accounts, a => a.AccountType == "AccountsReceivable");
        Assert.Contains(accounts, a => a.AccountType == "Income");
        Assert.Contains(accounts, a => a.AccountType == "Expense" && a.IsActive == false);
    }

    [Fact]
    public void ParseCustomers_page1_exposes_iterator_and_sales_rep()
    {
        var page = _parser.ParseCustomers(Fixture("customer_query_rs_page1.xml"));

        Assert.Equal(2, page.Customers.Count);
        Assert.True(page.HasMore);
        Assert.Equal(1, page.IteratorRemainingCount);
        Assert.Equal("ITER-PAGE-1", page.IteratorId);

        var acme = Assert.Single(page.Customers, c => c.FullName == "Acme LLC");
        Assert.Equal(430.00m, acme.Balance);
        Assert.Equal("Pat Lee", acme.SalesRepName);

        var beacon = Assert.Single(page.Customers, c => c.FullName == "Beacon Foods");
        Assert.Null(beacon.SalesRepName);
    }

    [Fact]
    public void ParseCustomers_page2_ends_iterator()
    {
        var page = _parser.ParseCustomers(Fixture("customer_query_rs_page2.xml"));

        Assert.Single(page.Customers);
        Assert.False(page.HasMore);
        Assert.Equal(0, page.IteratorRemainingCount);
        Assert.Equal("Cedar Ridge LLC", page.Customers[0].FullName);
        Assert.Equal("Sam Rivera", page.Customers[0].SalesRepName);
    }

    [Fact]
    public void ParseAccounts_throws_on_error_status()
    {
        var xml = Fixture("account_query_rs_error.xml");
        var ex = Assert.Throws<InvalidOperationException>(() => _parser.ParseAccounts(xml));
        Assert.Contains("statusCode=3120", ex.Message, StringComparison.Ordinal);
    }

    [Theory]
    [InlineData("16.0,15.0,13.0", "16.0")]
    [InlineData("", "13.0")]
    [InlineData(null, "13.0")]
    [InlineData("13.0", "13.0")]
    public void PreferQbXmlVersion_picks_highest_or_fallback(string? offered, string expected)
    {
        Assert.Equal(expected, QbXmlResponseParser.PreferQbXmlVersion(offered));
    }

    private static string Fixture(string fileName)
    {
        var path = Path.Combine(AppContext.BaseDirectory, "Fixtures", fileName);
        Assert.True(File.Exists(path), $"Missing fixture at {path}");
        return File.ReadAllText(path);
    }
}
