# qb-balances — Decisions

Unspecified items from the product spec, locked so implementation never stalls. If a later wave must change a default, update this file in the same PR/commit as the code.

Last updated: 2026-09-21 (Wave 0)

---

## Product

| Item | Decision | Why |
|------|----------|-----|
| Product name | **M2B Ledger** (repo folder still qb-balances / M2B Ledger) | Owner rename during Wave 1 UI |
| Scope | Read-only Account + Customer balances | Spec; no invoices, payments, or writes to QB |
| Target QB | QuickBooks Desktop Enterprise | Spec; owner has purchased a license and is installing it for **live** testing (not mock-only) |
| Real-QB testing | **Required** acceptance path on the Windows machine that runs Enterprise | Wave 5 connects via COM; Wave 8 documents the checklist. Mock mode is only for building/CI without QB. |
| QB install option | **“I'll be using QuickBooks Desktop on this computer.”** (first radio, not the network-share options) | Agent needs the full Desktop app + qbXML Request Processor on this same machine. Option 2 is multi-user file hosting; option 3 is server-only and would break COM. |
| QB industry edition | **Enterprise Solutions General Business** | AccountQuery / CustomerQuery are the same in every edition. General Business has a standard chart of accounts + customers for the first live sync. Pick a different edition only if the purchased license is locked to that industry. |
| Missing PDF component warning | **Ignore (OK).** Not required for qb-balances. | Agent only runs AccountQuery / CustomerQuery. Printing, emailing forms, and Save as PDF are out of scope. |
| First company file | **Sample Rock Castle Construction** (Open a sample file → Sample product-based business). Confirmed open on 2026-09-21, Enterprise 24.0. | Full chart of accounts + customer balances for Wave 5 live sync. Leave this file open (or reopen it) when the agent first connects. |
| Mobile platforms | Android + iOS (no web, no desktop) | Spec |

---

## Backend

| Item | Decision | Why |
|------|----------|-----|
| PHP | **8.5.8** (installed on this machine; spec said 8.3) | Only PHP on PATH. Compatible with Laravel 12. |
| Framework | **Laravel 12** (spec said Laravel 11) | Laravel 11 security support ended March 2026. Composer 2.10 refuses to install `laravel/framework` 11.x (unpatched advisories). Laravel 12 is the current supported line; Filament 3.3+ supports it. |
| Admin | Filament 3 at `/admin` | Spec; conventional path |
| Auth (users) | Laravel Sanctum personal access tokens | Spec |
| Auth (agent) | Custom `Authorization: Bearer` lookup against hashed `agent_tokens` | Spec; not a Sanctum user |
| Database | **MySQL 8** is the target. Wave 1 local `.env` uses **SQLite** (`database/database.sqlite`) because this machine has no MySQL service. Schema is MySQL-compatible (role/status stored as strings, not native ENUM). | Spec allows MySQL or Postgres. Switch `DB_CONNECTION=mysql` + `DB_DATABASE=qb_balances` when MySQL 8 is installed. Tests always use SQLite `:memory:`. |
| DB name | `qb_balances` (MySQL) / `database/database.sqlite` (local Wave 1) | Matches product name |
| App timezone | `UTC` | All `synced_at` values are UTC ISO-8601 |
| App locale | `en` | MVP |
| App key / local URL | `http://127.0.0.1:8000` (`php artisan serve`) | Local default |
| Sanctum token expiry | **none** (tokens revoked only on logout or admin action) | Simplest MVP; revisit if devices are shared |
| Password hashing | bcrypt (Laravel default) | Framework default |
| User IDs | bigint auto-increment | Laravel default |
| Company IDs | bigint auto-increment | Agent payload uses integer `company_id` |
| Money | `decimal(15,2)` | Spec |
| Unique account/customer key | `(company_id, qb_list_id)` | Spec upsert rule |
| Inactive semantics | Set `is_active = false` when missing from snapshot; **do not delete** | Spec; history + Filament still see the row |
| Stale threshold | **15 minutes** after last *successful* sync | Spec |
| Stale check command | `php artisan sync:check-stale` scheduled every **5 minutes** | Frequent enough to catch 15-minute gaps; logs a warning (no email/SMS in MVP) |
| Alert channel | Laravel log (`warning`) only | No mail/Slack configured in MVP |
| Customer page size | **25** | Mobile-friendly; `?per_page=` ignored unless we add it later |
| Accounts filter | `?type=` matches QB `AccountType` string (e.g. `Bank`, `AccountsReceivable`) | Closest to qbXML |
| Customer search | Case-insensitive `LIKE %term%` on `full_name` | MVP |
| Customer sort | Default `full_name` ASC; `?sort=balance_desc` is the only extra sort | Spec |
| `only_with_balance` | `balance != 0` | Includes credits (negative) and debts |
| Summary “total A/R” | `SUM(customers.balance)` for the company (active + inactive with leftover balance) | Matches “sum of customer balances” |
| Summary “customers with balance” | Count where `balance != 0` | Same rule as filter |
| Summary top 5 | Highest `balance` descending, ties by `full_name` | Deterministic |
| Key account balances | Active accounts whose `account_type` is in `Bank`, `AccountsReceivable`, `AccountsPayable`, `Income`, `Expense`, `OtherCurrentAsset` — owner/admin only | Covers the mock chart and typical QB reports |
| Rate limit — login | 5 / minute / IP | Brute-force guard |
| Rate limit — agent sync | 20 / minute / token | Poll is every 180s; burst for retries |
| Rate limit — authed reads | 60 / minute / user | Mobile pull-to-refresh |
| CORS | Allow any origin in `local`; production set `FRONTEND_URL` later (mobile apps do not need CORS) | Mobile is native |
| Filament auth | Same `users` table; **admin role only** may access `/admin` | Least privilege |
| Quick login button | Filament login action `quickLogin()`. Visible only when `APP_ENV=local` **and** `APP_QUICK_LOGIN=true`. Otherwise hidden and the action `abort(404)`. | Wave 1 UI prompt. |
| Agent token format | 48-byte random → hex (96 chars), stored as `hash('sha256', $plain)` | Long enough; never store plaintext |
| Agent token show-once | Flash/session on Filament create page only | Spec |
| Validation | Form Requests on every write; query param validation on every GET | Spec |
| API prefix | `/api` (no `/v1`) | MVP; version later if needed |
| Login response | `{ token, token_type: "Bearer", user: { id, name, email, role, company_id, qb_sales_rep_name } }` | Mobile needs role immediately |

---

## Seed / demo data

| Item | Decision |
|------|----------|
| Company name | `Demo Company` (`id` will be `1` after fresh seed) |
| Dev password (all seed users) | `Password123!` |
| Seed users | `owner@demo.test`, `sales@demo.test`, `collections@demo.test`, `admin@demo.test` |
| Seed sales rep mapping | `sales@demo.test` → `qb_sales_rep_name` = `Pat Lee` |
| Seed demo customers | At least 8, two assigned to `Pat Lee`, others to `Alex Kim` or null |
| Seed demo accounts | At least one each of Bank, AccountsReceivable, Income, Expense |
| Seed agent token | **Not** pre-inserted; create in Filament so the show-once flow is used. A test helper may insert a known hash. |
| Documented in | Root README (Wave 8) and this file |

---

## Agent

| Item | Decision | Why |
|------|----------|-----|
| Runtime | .NET 8 Worker Service | Spec |
| Service name | `QbBalancesAgent` | Unique, obvious |
| Service display name | `QB Balances Sync Agent` | Event Log readable |
| Config file | `appsettings.json` + optional `appsettings.Production.json` (gitignored) | Standard |
| `PollIntervalSeconds` | **180** | Spec default |
| `MockMode` | `true` in committed `appsettings.json` (dev-safe); production file sets `false` | Accidental COM on a laptop must not happen |
| `--mock` flag | Forces mock regardless of config | Spec |
| `CompanyFilePath` | `""` (currently open company file) | Spec |
| `CompanyId` | Integer, default `1` (matches seeder) | Agent payload requires it |
| `BackendUrl` | `http://127.0.0.1:8000` in committed config | Local mock path |
| qbXML fallback version | `13.0` | Spec |
| Late-bound COM ProgID | Prefer `QBXMLRP2.RequestProcessor` (then `.2`, docs alias `RequestProcessor2`, legacy `QBXMLRP.RequestProcessor`) | Spec text said `RequestProcessor2`; Enterprise 24 registers `QBXMLRP2.RequestProcessor` |
| `QBXMLVersionsForSession` | Call when present; on DISP_E_MEMBERNOTFOUND fall back to `13.0` | Enterprise 24 typelib omits the method |
| Session mode | `DoNotCare` | Spec |
| Customer `MaxReturned` | **100** | Reasonable page; iterator continues until done |
| Cycle overlap | `SemaphoreSlim(1,1)` / compare-and-skip if previous cycle still running | Spec |
| HTTP retry | 3 attempts, backoff 2s → 4s → 8s, only on 5xx / timeout / network | Do not retry 401/403/422 |
| Logging | Serilog rolling file under `./logs/agent-.log`, daily, retain **7 days** | Spec “rolling file” |
| Token in logs | Forbidden — never write `AgentToken` or `Authorization` headers | Spec |
| Mock accounts | Checking (Bank), Savings (Bank), Accounts Receivable (AccountsReceivable), Sales (Income), COGS (CostOfGoodsSold), Office Supplies (Expense), Opening Balance Equity (Equity) | “Realistic” chart |
| Mock customers | **60** names from a fixed list + a seeded `Random` so tests are stable; balances drift ±2% each cycle in the worker, tests pin the seed | Spec “~60” + “randomly drifting” |
| Mock OS | COM types compiled only for `net8.0-windows` **or** stubbed behind `#if WINDOWS`; `IQuickBooksReader` is the seam | Spec: mock on non-Windows |
| Install scripts | `agent/scripts/install-service.ps1`, `uninstall-service.ps1` | Spec |
| Time | `synced_at` = `DateTime.UtcNow` round-trip ISO-8601 (`yyyy-MM-ddTHH:mm:ssZ`) | Spec |
| Project layout | `agent/src/QbBalances.Agent` + `agent/tests/QbBalances.Agent.Tests` + `agent/QbBalances.Agent.sln` | Standard .NET layout |
| Config section | JSON section `Agent` (bound to `AgentOptions`) | Groups agent keys; env override `Agent__AgentToken` |
| `AppName` | `QB Balances Sync Agent` | Passed to `OpenConnection2`; matches service display name |
| Publish RID (service) | `win-x86` | QB Request Processor is commonly 32-bit COM |

---

## Mobile

| Item | Decision | Why |
|------|----------|-----|
| App display name | `QB Balances` | Short, store-friendly |
| Application id | `com.qbbalances.app` | Reverse-DNS |
| State | Riverpod | Spec |
| HTTP | Dio | Spec |
| Routing | go_router | Spec |
| Token store | flutter_secure_storage | Spec |
| Currency | `USD` via `--dart-define=CURRENCY=USD` | US-centric QB Desktop default |
| API URL | `--dart-define=API_BASE_URL=http://127.0.0.1:8000` (iOS sim / desktop); Android emulator uses `http://10.0.2.2:8000` | Localhost mapping |
| Theme | Material 3, light + dark, seed color `#0F4C81` (ink blue) | Professional, not playful |
| Offline cache | Last successful JSON per endpoint in a local file (or Hive/shared_preferences JSON). Banner/chip: **“Offline data”**. Do not encrypt cache in MVP (token is already in secure storage). | Spec |
| Last-synced copy | “Last synced just now” / “Last synced X min ago” / “Last synced X hr ago”; stale: red banner + “Data may be out of date” | Spec |
| Pull-to-refresh | On Dashboard, Accounts, Customers | Spec |
| sales_rep nav | Customers list + Customer detail only (no Dashboard, no Accounts) | Backend already hides data; UI must match |
| collections nav | Accounts + Customers + thin status home (no key-account dashboard) | collections can see accounts but not owner-only key accounts |
| owner / admin nav | Dashboard + Accounts + Customers | Spec |
| Min Flutter | Stable 3.24+ | Current stable at plan time |

---

## Security (locked for Wave 8 docs)

| Item | Decision |
|------|----------|
| Production transport | HTTPS only (agent + mobile) |
| QuickBooks user | Dedicated **view-only** (read-only) user; unattended integrated-app access |
| Agent credentials | One hashed token per company; rotate by creating a new token and disabling the old |
| Git | Never commit `.env`, `appsettings.Production.json`, plaintext tokens, Flutter keystores, `*.jks` |
| `.env.example` | Placeholder values only |

---

## Testing

| Item | Decision |
|------|----------|
| Backend | PHPUnit feature tests per endpoint × role |
| Agent | xUnit (or NUnit — **xUnit** locked) + qbXML fixtures under `agent/tests/Fixtures` |
| Mobile | `flutter_test` widget tests for role nav + stale banner |
| No-QB path | Backend + agent `--mock` + Flutter vs local API | 
| Real-QB path | Manual on the Windows box with Enterprise installed (Wave 5 live connect + Wave 8 checklist). Not automated in CI. |

---

## Out of scope (explicit)

- Writing anything back to QuickBooks
- Multi-company switching in the mobile UI (user belongs to one company)
- Push notifications
- Password reset / email verification
- Web client
- Invoice / payment / aging detail screens
- Postgres (can be added later; schema stays generic enough)
