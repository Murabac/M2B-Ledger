using QbBalances.Agent.QuickBooks;

namespace QbBalances.Agent.Tests;

public class QbXmlClientTests
{
    private readonly QbXmlClient _client = new();

    [Fact]
    public void BuildRequest_rejects_non_query_root()
    {
        var ex = Assert.Throws<InvalidOperationException>(() =>
            _client.BuildRequest("CustomerAddRq"));

        Assert.Contains("QueryRq", ex.Message, StringComparison.Ordinal);
        Assert.Contains("CustomerAddRq", ex.Message, StringComparison.Ordinal);
    }

    [Fact]
    public void EnsureQueryRequest_allows_query_roots()
    {
        QbXmlClient.EnsureQueryRequest("AccountQueryRq");
        QbXmlClient.EnsureQueryRequest("CustomerQueryRq");
    }

    [Fact]
    public void BuildAccountQuery_contains_ActiveStatus_All()
    {
        var xml = _client.BuildAccountQuery();

        Assert.Contains("AccountQueryRq", xml, StringComparison.Ordinal);
        Assert.Contains("<ActiveStatus>All</ActiveStatus>", xml, StringComparison.Ordinal);
        Assert.Contains("qbxml version=\"13.0\"", xml, StringComparison.Ordinal);
    }

    [Fact]
    public void BuildCustomerQuery_uses_iterator_attributes()
    {
        var start = _client.BuildCustomerQuery(maxReturned: 100);
        Assert.Contains("CustomerQueryRq", start, StringComparison.Ordinal);
        Assert.Contains("iterator=\"Start\"", start, StringComparison.Ordinal);
        Assert.Contains("<MaxReturned>100</MaxReturned>", start, StringComparison.Ordinal);
        Assert.DoesNotContain("<Iterator>", start, StringComparison.Ordinal);

        var next = _client.BuildCustomerQuery(maxReturned: 100, iteratorId: "ABC");
        Assert.Contains("iterator=\"Continue\"", next, StringComparison.Ordinal);
        Assert.Contains("iteratorID=\"ABC\"", next, StringComparison.Ordinal);
        Assert.DoesNotContain("iterator=\"Start\"", next, StringComparison.Ordinal);
    }
}
