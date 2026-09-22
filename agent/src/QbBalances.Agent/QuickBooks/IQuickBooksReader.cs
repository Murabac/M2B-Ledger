using QbBalances.Agent.Models;

namespace QbBalances.Agent.QuickBooks;

public interface IQuickBooksReader
{
    Task<BalanceSnapshot> ReadSnapshotAsync(long companyId, CancellationToken cancellationToken);
}
