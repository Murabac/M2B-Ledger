# qb-balances — Wave Plan

Master execution plan for the **qb-balances** MVP monorepo: a **READ-ONLY** mobile app that shows Account Balances and Customer Balances from QuickBooks Desktop Enterprise.

Build order (locked): **backend → agent (mock first, then real QuickBooks) → mobile → docs**. Run the test suite at the end of every wave. Ask no questions during implementation; unspecified items live in [`docs/DECISIONS.md`](docs/DECISIONS.md).

---

## Product in one paragraph

QuickBooks Desktop Enterprise runs on a Windows server. A local Windows sync agent reads balances via qbXML (never writes) and pushes a JSON snapshot over HTTPS to a Laravel API. A Flutter app (Android + iOS) reads that snapshot through role-based REST endpoints. The mobile app **never** talks to QuickBooks. Data is a few-minutes-old snapshot. A “last synced” time is shown everywhere.

```
QB Desktop Enterprise (Windows)
        ↑ read-only qbXML
   Agent (.NET 8 Worker)
        ↓ HTTPS POST /api/agent/sync
   Backend (Laravel 11 + Filament + MySQL)
        ↓ REST + Sanctum
   Mobile (Flutter)
```

---

## Monorepo layout (target)

```
/
├── agent/          Windows sync agent (C# .NET 8 Worker Service)
├── backend/        Laravel 11 API + Filament 3 admin (MySQL)
├── mobile/         Flutter app (Android + iOS)
├── docs/
│   ├── ARCHITECTURE.md
│   ├── DECISIONS.md
│   └── TESTING.md
├── README.md
└── WAVES.md        ← this file
```

---

## Wave map

| Wave | Name | What ships | Depends on |
|------|------|------------|------------|
| **0** | Plan & decisions | This file, architecture, locked defaults | — |
| **1** | Backend foundation | Laravel app, schema, models, seeders | Wave 0 |
| **2** | Backend API | Auth, agent sync, read APIs, policies, feature tests, stale-sync command | Wave 1 |
| **3** | Filament admin | Users, companies, agent tokens (plaintext once), read-only sync logs | Wave 2 |
| **4** | Agent — mock mode | .NET worker, read-only QbXmlClient, mock snapshot push, unit tests | Wave 2 |
| **5** | Agent — real QuickBooks | COM qbXML client, pagination, Windows Service install scripts | Wave 4 |
| **6** | Mobile foundation | Flutter shell, stack, login, API client, themes, last-synced banner | Wave 2 |
| **7** | Mobile screens | Dashboard, accounts, customers, detail, role nav, offline, widget tests | Wave 6 |
| **8** | Docs & hardening | Root README, TESTING.md, security notes, e2e checklist | Waves 3–7 |

**Do not start a wave until the previous required wave’s tests pass.** Wave 6 may start in parallel with Wave 4 once Wave 2 is green. Wave 3 and Wave 4 may run sequentially after Wave 2; do not skip Wave 2 tests.

---

## Wave 0 — Plan & decisions

**Goal:** Freeze scope, architecture, and defaults so later waves never stall on open questions.

**Deliverables**

- [x] `WAVES.md` (this file)
- [x] `docs/DECISIONS.md` — every unspecified default
- [x] `docs/ARCHITECTURE.md` — data flow, trust boundaries, sync contract

**Done when:** A later session can implement Wave 1 without asking the product owner anything.

---

## Wave 1 — Backend foundation

**Goal:** A bootable Laravel 11 app with the full schema, Eloquent models, and documented seed users. No public API yet beyond Laravel’s default health.

**Create**

- [x] Laravel project in `/backend` — **Laravel 12.69 + PHP 8.5** (spec said Laravel 11 / PHP 8.3; see DECISIONS.md)
- [x] Packages: `laravel/sanctum` v4, `filament/filament` v3.3
- [x] `.env.example` (no secrets)
- [x] Schema ready for MySQL 8 `qb_balances`; Wave 1 local runs on SQLite (no MySQL service on this machine)
- [x] Filament panel at `/admin` (admin role only via `User::canAccessPanel`)

**Tables / migrations** (exact columns in ARCHITECTURE.md)

| Table | Purpose |
|-------|---------|
| `companies` | One QB company file mapping |
| `users` | `role` = `owner` \| `sales_rep` \| `collections` \| `admin`; nullable `qb_sales_rep_name`; `company_id` |
| `accounts` | QB chart-of-accounts snapshot |
| `customers` | QB customer snapshot |
| `sync_logs` | One row per agent push |
| `agent_tokens` | Hashed per-company agent bearer tokens |

Money columns: `decimal(15,2)`.

**Models + relationships**

- Company hasMany users, accounts, customers, sync_logs, agent_tokens
- User belongsTo company
- Account / Customer belongTo company; unique `(company_id, qb_list_id)`
- Soft-inactive via `is_active` (do not hard-delete on missing snapshot rows)

**Seeders** (passwords documented in README later; values locked in DECISIONS.md)

- [x] 1 company: “Demo Company”
- [x] 1 user per role: owner, sales_rep, collections, admin
- [x] Demo accounts + customers so Filament and later APIs have data before the agent runs

**Tests this wave**

- [x] Migration + seeder smoke test: `php artisan migrate:fresh --seed` succeeds
- [x] Model factory / relationship tests (`FoundationSeederTest`, `ModelRelationshipsTest`)

**Done when:** `php artisan migrate:fresh --seed` works and four role users can be retrieved from the DB. **Met 2026-09-21.** `php artisan test` — 7 passed.

---

## Wave 2 — Backend API

**Goal:** Every JSON endpoint, validated, rate-limited, policy-enforced, with a feature test for every endpoint and every role-permission rule.

**Auth**

- `POST /api/login` — email + password → Sanctum token + user (role, company)
- `POST /api/logout` — revoke current token
- Agent: `Authorization: Bearer <AgentToken>` on `POST /api/agent/sync` (lookup hashed token, not Sanctum)

**Endpoints**

| Method | Path | Who | Behavior |
|--------|------|-----|----------|
| POST | `/api/login` | public | Sanctum token |
| POST | `/api/logout` | any authed user | revoke token |
| POST | `/api/agent/sync` | agent token | upsert accounts/customers by `(company_id, qb_list_id)`; mark snapshot-absent rows `is_active=false`; write `sync_logs` (status, counts, duration, error) |
| GET | `/api/status` | any authed user | last successful sync UTC + `is_stale` (true if older than 15 minutes) |
| GET | `/api/accounts` | owner, admin, collections | filter by `?type=`; return `balance` + `total_balance` |
| GET | `/api/customers` | owner/admin/collections = all; sales_rep = only `sales_rep_name == user.qb_sales_rep_name` | `?search=` `?sort=balance_desc` `?only_with_balance=1` + pagination |
| GET | `/api/customers/{id}` | same visibility as list | 404 if hidden from role |
| GET | `/api/summary` | all authed; **key account balances owner/admin only** | total A/R (sum of customer balances), count of customers with balance, top 5 balances, key accounts (restricted) |

**Sync payload (agent → backend)**

```json
{
  "company_id": 1,
  "synced_at": "2026-09-21T19:00:00Z",
  "accounts": [
    {
      "qb_list_id": "...",
      "full_name": "Checking",
      "account_type": "Bank",
      "is_active": true,
      "balance": 12000.50,
      "total_balance": 12000.50
    }
  ],
  "customers": [
    {
      "qb_list_id": "...",
      "full_name": "Acme LLC",
      "is_active": true,
      "balance": 430.00,
      "total_balance": 430.00,
      "sales_rep_name": "Pat Lee"
    }
  ]
}
```

**Also this wave**

- Form Request validation on every endpoint
- Rate limiting (see DECISIONS.md)
- Policies for Account, Customer, Summary, Status
- Artisan command `sync:check-stale` + schedule every 5 minutes: log/alert when no successful sync in 15 minutes
- Feature tests: login/logout, agent sync upsert + inactive marking + sync_log, status stale/fresh, accounts forbidden for sales_rep, customers scoped for sales_rep, customer detail 404 for other-rep, summary key-accounts hidden from sales_rep/collections, pagination/search/sort/filter

**Done when:** `php artisan test` is green for every endpoint and every role rule.

---

## Wave 3 — Filament admin

**Goal:** Operators can manage the company, users, and agent tokens without touching the DB.

**Resources**

- **Companies** — CRUD
- **Users** — role, `qb_sales_rep_name` mapping, company
- **Agent tokens** — create generates a random plaintext token, **shows it once**, stores only the hash; list view never shows plaintext
- **Sync logs** — read-only table (status, counts, duration, error, timestamps)

**Tests**

- Feature/livewire tests: token shown once on create and not persisted in plaintext; sales-rep mapping saves; sync log resource is read-only

**Done when:** Admin user can log into `/admin`, create an agent token, and see the one-time plaintext value.

---

## Wave 4 — Agent, mock mode first

**Goal:** A .NET 8 Worker that can run on **non-Windows** machines, produce a realistic snapshot, and POST it to the backend. No COM.

**Create `/agent`**

- Worker Service + `Microsoft.Extensions.Hosting.WindowsServices` (service registration is a no-op on non-Windows)
- `appsettings.json`: `BackendUrl`, `AgentToken`, `CompanyFilePath`, `PollIntervalSeconds` (180), `MockMode`, `CompanyId`
- Rolling file logs; **never log the token**
- `--mock` CLI flag **or** `MockMode=true` skips COM
- Single cycle lock: never overlapping cycles
- Retry with exponential backoff on POST failure
- `IQuickBooksReader` interface
  - `MockQuickBooksReader` — fake Bank / A/R / Income / Expense accounts + ~60 customers with randomly drifting balances
  - COM implementation is a stub/not-registered in this wave
- `QbXmlClient` — **the only place that builds qbXML**. Reject any request whose root element does not end in `QueryRq`. Unit test: a non-query request throws
- After each cycle: `POST {BackendUrl}/api/agent/sync` with `Authorization: Bearer <AgentToken>`
- Install/uninstall PowerShell scripts can be stubs that print “Wave 5” if needed; real scripts land in Wave 5

**Unit tests**

- Request builder rejects non-`QueryRq`
- Snapshot payload shape (`company_id`, `synced_at` UTC ISO-8601, accounts, customers)
- Mock generator produces required account types and ~60 customers

**Done when:** On a non-Windows machine, `dotnet test` passes and `dotnet run -- --mock` pushes a snapshot the Wave 2 backend accepts.

---

## Wave 5 — Agent, real QuickBooks + Windows Service

**Goal:** Talk to QuickBooks Desktop Enterprise via late-bound COM. Installable as a Windows Service.

**COM flow** (Windows-only, behind `IQuickBooksReader`)

1. `Type.GetTypeFromProgID("QBXMLRP2.RequestProcessor2")` — no SDK interop DLLs
2. `OpenConnection2`
3. `BeginSession` (company file from config; empty = currently open file; mode = `DoNotCare`)
4. Negotiate version via `QBXMLVersionsForSession`; fall back to `13.0`
5. `ProcessRequest` / `EndSession` / `CloseConnection`
6. Always clean up in `finally`

**Queries each cycle**

- `AccountQueryRq` (`ActiveStatus=All`) → `ListID`, `FullName`, `AccountType`, `IsActive`, `Balance`, `TotalBalance`
- `CustomerQueryRq` (`ActiveStatus=All`) → `ListID`, `FullName`, `IsActive`, `Balance`, `TotalBalance`, `SalesRepRef/FullName`  
  Paginate with `MaxReturned` + `IteratorID`

**Also**

- Sample qbXML response fixtures + parser unit tests
- `install-service.ps1` / `uninstall-service.ps1` using Windows Service hosting
- README notes for this folder: run as service, MockMode, token config

**Live test (required — owner has QB Desktop Enterprise)**

When Enterprise is installed on this Windows machine, Wave 5 also includes a real connect (not mock):

1. Company file open **or** `CompanyFilePath` set in config
2. Agent authorized inside QuickBooks as an integrated app
3. Unattended access for a **view-only** QB user
4. One successful `AccountQuery` + paginated `CustomerQuery` cycle
5. Snapshot lands in the backend; Filament sync log = success
6. Spot-check a few account and customer balances against QB reports

**Done when:** `dotnet test` includes fixture parse tests; install scripts exist; COM path is compiled only on Windows (or guarded so `dotnet build` still works on non-Windows); **and** if QB is installed, one live sync has succeeded.

---

## Wave 6 — Mobile foundation

**Goal:** A Flutter app that can log in, store a token, hit the API, and show theme + last-synced chrome. No full screen set yet.

**Stack (locked)**

- Flutter (Android + iOS)
- Riverpod, Dio, go_router, flutter_secure_storage
- intl currency formatting; currency code via `--dart-define=CURRENCY=USD`
- API base URL via `--dart-define=API_BASE_URL=http://10.0.2.2:8000`

**This wave**

- Light + dark professional theme
- Login screen
- Secure token storage
- Dio client with Bearer interceptor
- Persistent last-synced banner widget (green/neutral; red + warning when `is_stale=true`)
- Role-aware router **shell** (empty destinations ok)
- Cache last JSON responses for offline (flag “offline data”) — hook can be wired even if only status/login exist

**Widget tests**

- Stale banner turns red when `is_stale=true`

**Done when:** `flutter test` passes for the banner; app logs in against a running Wave 2 backend.

---

## Wave 7 — Mobile screens

**Goal:** Complete role-adapted UI with pull-to-refresh and offline cache.

**Screens**

| Screen | Roles | Contents |
|--------|-------|----------|
| Login | all | email / password |
| Home / Dashboard | owner, admin | total A/R, top customer balances, key account balances |
| Accounts | owner, admin, collections | grouped by account type, balances |
| Customers | all | search, sort by balance, “only with balance”; sales_rep list is already scoped by API |
| Customer detail | all (if visible) | name, balance, total balance, sales rep |

**Behavior**

- Persistent “Last synced X min ago” on every main screen; red + warning if stale
- Pull-to-refresh reloads from backend
- Last response cached; show “offline data” when serving cache
- Navigation hides Accounts / Dashboard for roles that cannot use them (sales_rep: Customers only + maybe a thin home)

**Widget tests**

- Role-based navigation: sales_rep does not see Accounts; owner sees Dashboard + Accounts + Customers
- Stale banner (if not already fully covered in Wave 6)

**Done when:** `flutter test` is green for nav + banner; manual role login works against seeded users.

---

## Wave 8 — Docs & hardening

**Goal:** A stranger can run the no-QB path and a Windows admin can run the real-QB path.

**Root `README.md`**

- What the system is
- How to run backend, agent (mock + service), mobile
- Environment variables
- How to create an agent token in Filament
- Link to TESTING.md and DECISIONS.md

**`docs/TESTING.md`**

- **(a) No-QuickBooks path:** backend + agent `--mock` + mobile vs localhost
- **(b) Real-QuickBooks path:**
  - Prerequisites: QB Desktop Enterprise + sample company file on the Windows machine
  - Authorize the agent as an integrated application
  - Unattended access for a **view-only** QB user
  - Checklist: balances match QB reports (A/R, Trial Balance / Account Balances, customer balances)

**Security notes (README + ARCHITECTURE)**

- HTTPS only in production
- View-only QuickBooks user for the agent
- Per-company agent tokens
- No secrets in git (`.env.example` only)

**Also**

- Confirm `.gitignore` covers `.env`, agent tokens, `appsettings.Production.json`, Flutter keystores
- Confirm all three test suites still pass

**Done when:** README + TESTING.md are accurate against the code that actually shipped.

---

## Cross-cutting rules (every wave)

1. **Read-only to QuickBooks.** The only qbXML builder is `QbXmlClient`. Root must end with `QueryRq`.
2. **Mobile never talks to QuickBooks.**
3. **Last synced everywhere** once status exists (backend field + mobile banner).
4. **No secrets in git.**
5. **Tests in the same wave as the code.** Do not leave “tests later.”
6. **If unspecified:** pick a default, append it to `docs/DECISIONS.md`, keep moving.
7. **Windows-only COM** stays behind an interface so mock mode works on any OS.

---

## Suggested session prompt (copy for the next chat)

> Execute **Wave 1** of qb-balances exactly as specified in `WAVES.md` and `docs/DECISIONS.md`. Do not start Wave 2. Run the Wave 1 tests. Update checkboxes in `WAVES.md` when done.

Then Wave 2, Wave 3, … through Wave 8.
