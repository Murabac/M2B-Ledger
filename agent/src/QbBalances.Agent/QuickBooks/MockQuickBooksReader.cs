using QbBalances.Agent.Models;

namespace QbBalances.Agent.QuickBooks;

public sealed class MockQuickBooksReader : IQuickBooksReader
{
    public const int CustomerCount = 60;
    public const int DefaultSeed = 42;

    private static readonly (string ListId, string FullName, string AccountType, decimal Balance)[] SeedAccounts =
    [
        ("80000001-MOCK", "Checking", "Bank", 12_450.75m),
        ("80000002-MOCK", "Savings", "Bank", 48_200.00m),
        ("80000003-MOCK", "Accounts Receivable", "AccountsReceivable", 0m),
        ("80000004-MOCK", "Sales", "Income", 215_300.50m),
        ("80000005-MOCK", "COGS", "CostOfGoodsSold", 98_110.25m),
        ("80000006-MOCK", "Office Supplies", "Expense", 3_420.10m),
        ("80000007-MOCK", "Opening Balance Equity", "Equity", 50_000.00m),
    ];

    private static readonly string[] CustomerNames =
    [
        "Acme LLC", "Beacon Foods", "Cedar Ridge LLC", "Delta Plumbing", "Evergreen Farms",
        "Frontier Electric", "Gulf Coast Supply", "Harbor Marine", "Ironwood Construction", "Jetstream Logistics",
        "Keystone Roofing", "Lakeside Dental", "Maple Leaf Catering", "Northwind Traders", "Oak & Pine Cabinetry",
        "Pacific Print Co", "Quarry Stone Works", "Riverbend Auto", "Summit Health Clinic", "Timberline Outfitters",
        "Urban Nest Realty", "Valley Vet Care", "Westbrook Hardware", "Yellowbird Media", "Zenith Solar",
        "Alpine Bakery", "Blue Heron Inn", "Copper Peak Mining", "Desert Bloom Nursery", "Eagle Eye Security",
        "Flatiron Fitness", "Granite State HVAC", "Highline Apparel", "Indigo Tea House", "Jasper Pet Supply",
        "Knollwood Schools", "Lumen Optics", "Meadowbrook Dairy", "Nova Tech Labs", "Orchard Gate Winery",
        "Pioneer Freight", "Quiet Harbor Marina", "Redwood Cabinets", "Silverline Insurance", "Twin Oaks Farm",
        "Upland Gear Co", "Vista Point Hotels", "Willow Creek Spa", "Xavier Design Studio", "Yarrow Botanicals",
        "Zephyr Wind Energy", "Anchor Bay Fisheries", "Brightpath Tutoring", "Canyon Ridge Builders", "Dove Tail Millwork",
        "Ember Coffee Roasters", "Foxglove Florals", "Golden Gate Imports", "Hearthstone Realty", "Ivory Coast Spices",
    ];

    private static readonly string[] SalesReps = ["Pat Lee", "Sam Rivera", "Jordan Kim", "Alex Morgan"];

    private readonly Random _random;
    private readonly Dictionary<string, decimal> _customerBalances = new(StringComparer.Ordinal);

    public MockQuickBooksReader(int? seed = null)
    {
        _random = new Random(seed ?? DefaultSeed);
        for (var i = 0; i < CustomerCount; i++)
        {
            var listId = $"9000{i:D4}-MOCK";
            var baseBalance = Math.Round((decimal)(_random.NextDouble() * 8_000), 2);
            if (_random.NextDouble() < 0.15)
            {
                baseBalance = 0m;
            }

            _customerBalances[listId] = baseBalance;
        }
    }

    public Task<BalanceSnapshot> ReadSnapshotAsync(long companyId, CancellationToken cancellationToken)
    {
        cancellationToken.ThrowIfCancellationRequested();

        DriftCustomerBalances();

        var accounts = SeedAccounts.Select(a =>
        {
            var balance = a.AccountType == "AccountsReceivable"
                ? _customerBalances.Values.Sum()
                : a.Balance;

            return new AccountRow
            {
                QbListId = a.ListId,
                FullName = a.FullName,
                AccountType = a.AccountType,
                IsActive = true,
                Balance = Math.Round(balance, 2),
                TotalBalance = Math.Round(balance, 2),
            };
        }).ToList();

        var customers = new List<CustomerRow>(CustomerCount);
        for (var i = 0; i < CustomerCount; i++)
        {
            var listId = $"9000{i:D4}-MOCK";
            var balance = _customerBalances[listId];
            customers.Add(new CustomerRow
            {
                QbListId = listId,
                FullName = CustomerNames[i],
                IsActive = true,
                Balance = balance,
                TotalBalance = balance,
                SalesRepName = SalesReps[i % SalesReps.Length],
            });
        }

        var snapshot = new BalanceSnapshot
        {
            CompanyId = companyId,
            SyncedAt = DateTime.UtcNow.ToString("yyyy-MM-dd'T'HH:mm:ss'Z'"),
            Accounts = accounts,
            Customers = customers,
        };

        return Task.FromResult(snapshot);
    }

    private void DriftCustomerBalances()
    {
        foreach (var key in _customerBalances.Keys.ToList())
        {
            var current = _customerBalances[key];
            if (current == 0m)
            {
                continue;
            }

            var factor = 1m + (decimal)((_random.NextDouble() * 0.04) - 0.02);
            _customerBalances[key] = Math.Round(current * factor, 2);
        }
    }
}
