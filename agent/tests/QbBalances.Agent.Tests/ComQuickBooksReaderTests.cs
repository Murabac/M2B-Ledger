using QbBalances.Agent.QuickBooks;

namespace QbBalances.Agent.Tests;

public class ComQuickBooksReaderTests
{
    [Fact]
    public void CreateProcessor_fails_clearly_when_QBXMLRP2_missing()
    {
        if (!OperatingSystem.IsWindows())
        {
            Assert.ThrowsAny<Exception>(() => ComQuickBooksReader.CreateProcessor());
            return;
        }

        var progIdType = Type.GetTypeFromProgID(ComQuickBooksReader.ProgId)
            ?? Type.GetTypeFromProgID("QBXMLRP2.RequestProcessor.2")
            ?? Type.GetTypeFromProgID("QBXMLRP2.RequestProcessor2");
        if (progIdType is not null)
        {
            // Enterprise + Request Processor present — construction should succeed.
            var processor = ComQuickBooksReader.CreateProcessor();
            Assert.NotNull(processor);
            return;
        }

        var ex = Assert.Throws<InvalidOperationException>(() => ComQuickBooksReader.CreateProcessor());
        Assert.Contains("Request Processor", ex.Message, StringComparison.OrdinalIgnoreCase);
        Assert.Contains("not", ex.Message, StringComparison.OrdinalIgnoreCase);
    }
}
