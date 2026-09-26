# M2B Ledger — Testing

How to verify the no-QuickBooks path and the real QuickBooks Desktop path against the code that shipped.

Related: [`../README.md`](../README.md), [`ARCHITECTURE.md`](ARCHITECTURE.md), [`DECISIONS.md`](DECISIONS.md), [`../agent/README.md`](../agent/README.md).

---

## Automated suites

Run from the repo root (or each folder):

```powershell
cd backend
php artisan test

cd ..\agent
dotnet test QbBalances.Agent.sln

cd ..\mobile
flutter test
```

| Suite | What it covers |
|-------|----------------|
| Backend PHPUnit | Auth, agent sync, status/stale, accounts/customers/summary policies by role, Filament token show-once |
| Agent xUnit | qbXML query-only guard, mock snapshot shape, COM response fixture parsers |
| Flutter | Last-synced banner (fresh/stale), role nav destinations |

---

## (a) No-QuickBooks path

Goal: backend + mock agent + mobile against localhost, without Enterprise installed.

### Setup

1. **Backend**
   ```powershell
   cd backend
   copy .env.example .env   # if needed
   php artisan key:generate
   php artisan migrate:fresh --seed
   php artisan serve --host=127.0.0.1 --port=8000
   ```
2. **Agent token** — Filament `/admin` as `admin@demo.test` / `Password123!` → Agent tokens → Create → copy plaintext once.
3. **Mock agent**
   ```powershell
   cd agent/src/QbBalances.Agent
   $env:Agent__AgentToken = "<token>"
   $env:Agent__BackendUrl = "http://127.0.0.1:8000"
   $env:Agent__CompanyId = "1"
   dotnet run -- --mock
   ```
4. **Mobile**
   ```powershell
   cd mobile
   adb reverse tcp:8000 tcp:8000   # physical Android USB
   flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000
   ```
   Emulator: use `http://10.0.2.2:8000`.

### Checklist

- [ ] `php artisan test` green
- [ ] `dotnet test` green
- [ ] `flutter test` green
- [ ] Mock cycle logs **Snapshot accepted with HTTP 200** (accounts + customers counts)
- [ ] Filament **Sync logs** shows a **success** row with matching counts
- [ ] `GET /api/status` (authed) returns `synced_at` and `is_stale` (stale if older than 15 minutes)
- [ ] Mobile login works for each seed role
- [ ] **owner / admin**: Home shows Total A/R, top balances, key accounts; Accounts + Customers tabs
- [ ] **collections**: Accounts + Customers (no key-account dashboard)
- [ ] **sales_rep**: Customers only (scoped to `qb_sales_rep_name` = Pat Lee)
- [ ] Pull-to-refresh updates lists from API; last-synced badge updates after a new agent cycle + refresh
- [ ] With backend stopped, cached responses show **Offline data** when a prior successful fetch exists

---

## (b) Real-QuickBooks path

Goal: live COM sync from QuickBooks Desktop Enterprise into the same backend, then mobile shows matching balances.

### Prerequisites

- Windows machine with **QuickBooks Desktop Enterprise** installed
- Company file open (or `Agent__CompanyFilePath` set) — sample Rock Castle Construction is fine for a first live sync
- qbXML Request Processor registered (`QBXMLRP2.RequestProcessor` / related ProgIDs)
- Backend reachable from the agent host
- Agent token created in Filament
- Prefer a dedicated **view-only** QuickBooks user for integrated-app access

### Authorize the agent

1. Keep the company file open.
2. Run once with `Agent__MockMode=false` so QB prompts for **QB Balances Sync Agent**.
3. Choose **Yes, always; allow access even if QuickBooks is not running** (or equivalent unattended option).
4. Confirm **Edit → Preferences → Integrated Applications** lists the app as Allowed.
5. If access was denied previously, remove/re-add the app and retry.

Details: [`../agent/README.md`](../agent/README.md).

### Run live sync

```powershell
$env:Agent__MockMode = "false"
$env:Agent__AgentToken = "<token>"
$env:Agent__BackendUrl = "http://127.0.0.1:8000"
$env:Agent__CompanyId = "1"
# optional: $env:Agent__CompanyFilePath = "C:\path\to\company.qbw"
dotnet run --project agent/src/QbBalances.Agent
```

Or install as a Windows Service (`agent/scripts/install-service.ps1`), edit gitignored `appsettings.Production.json`, then `Start-Service QbBalancesAgent`. Publish RID: **win-x86** (32-bit COM is common).

### Checklist — balances vs QB reports

After a successful cycle (agent log HTTP 200 + Filament sync log success):

- [ ] Account count / sample Bank and A/R balances match **Chart of Accounts** / **Account Balances** (or Trial Balance) in QB for the same company file
- [ ] Customer count / sample open balances match **Customer Balance Summary** (or equivalent) in QB
- [ ] Mobile **Total A/R** matches sum of customer balances in the snapshot (and is consistent with QB A/R within expected rounding)
- [ ] Top customer balances on the dashboard match the highest open balances in QB
- [ ] Key accounts (owner/admin) show expected Bank / A/R / Income / Expense style accounts
- [ ] sales_rep user only sees customers assigned to their mapped sales-rep name
- [ ] Last-synced badge moves forward after each successful cycle + mobile refresh
- [ ] Stale warning appears if the agent is stopped for more than **15 minutes**

Spot-check at least three accounts and three customers by hand against QB. This path is **manual** (not CI).

---

## Common pitfalls

| Symptom | Likely cause |
|---------|----------------|
| Mobile “Cannot reach API” on physical phone | Missing `adb reverse tcp:8000 tcp:8000`, or API not on `127.0.0.1:8000` |
| Agent 401 on sync | Wrong/expired token; create a new one in Filament |
| Agent COM / access denied | Company file closed or integrated app not authorized |
| Mobile balances unchanged after refresh | Agent not running — refresh only reloads the last API snapshot |
| Last-synced “blank” green pill (dark mode) | Fixed in Wave 7/8 UI; update the app build if still blank |

---

## Security checks (smoke)

- [ ] No `.env`, `appsettings.Production.json`, or plaintext tokens in git (`git status` clean of secrets)
- [ ] Agent logs never print `AgentToken` or `Authorization` headers
- [ ] Production plan uses HTTPS for agent and mobile traffic
