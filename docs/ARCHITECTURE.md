# qb-balances — Architecture

Read-only balance snapshot pipeline: QuickBooks Desktop Enterprise → Windows agent → Laravel API → Flutter app.

Defaults and product choices: [`DECISIONS.md`](DECISIONS.md). Execution plan: [`../WAVES.md`](../WAVES.md).

---

## Trust boundaries

```
┌─────────────────────────────────────────────┐
│  Windows server (trusted LAN)               │
│  ┌──────────────┐     ┌─────────────────┐   │
│  │ QB Desktop   │ qbXML (COM, query     │   │
│  │ Enterprise   │────►│ Agent (Worker)  │   │
│  │ view-only    │ only)│ MockMode or COM│   │
│  └──────────────┘     └────────┬────────┘   │
└────────────────────────────────┼────────────┘
                                 │ HTTPS POST
                                 │ Authorization: Bearer <agent token>
                                 ▼
                    ┌────────────────────────┐
                    │ Laravel API + MySQL    │
                    │ Filament /admin        │
                    └────────────┬───────────┘
                                 │ HTTPS REST
                                 │ Authorization: Bearer <Sanctum>
                                 ▼
                    ┌────────────────────────┐
                    │ Flutter (Android/iOS)  │
                    │ NEVER talks to QB      │
                    └────────────────────────┘
```

- The mobile app has **no** QuickBooks SDK, company file path, or qbXML.
- The agent has **no** user login; it authenticates as a company with a long random token.
- Filament admins are `users.role = admin` only.
- Production: HTTPS on every hop outside the Windows box. Local mock path may use HTTP.

---

## Data is a snapshot

The agent polls every `PollIntervalSeconds` (default 180). Each successful cycle replaces the company’s account/customer snapshot:

1. Upsert every row in the payload by `(company_id, qb_list_id)`.
2. Any existing account/customer **not** in the payload is marked `is_active = false` (not deleted).
3. A `sync_logs` row records status, counts, duration, and error text.

UI always shows **last successful sync time**. `is_stale = true` when that time is older than 15 minutes.

---

## Agent internals

```
Worker (hosted, optional Windows Service)
  └─ SyncCycle (single-flight)
       ├─ IQuickBooksReader.ReadSnapshot()
       │    ├─ MockQuickBooksReader        (any OS; --mock or MockMode)
       │    └─ ComQuickBooksReader         (Windows only)
       │         └─ QbXmlClient            (ONLY qbXML builder)
       │              • reject unless root ends with QueryRq
       │              • AccountQueryRq (ActiveStatus=All)
       │              • CustomerQueryRq (ActiveStatus=All, MaxReturned + IteratorID)
       └─ SnapshotPublisher.POST /api/agent/sync
            retry 2s / 4s / 8s on transient errors
```

### COM session (real mode)

`Type.GetTypeFromProgID("QBXMLRP2.RequestProcessor2")` — late-bound, no SDK interop DLLs.

`OpenConnection2` → `BeginSession(companyFilePath or "", DoNotCare)` → `QBXMLVersionsForSession` (fallback `13.0`) → `ProcessRequest` → `finally { EndSession; CloseConnection }`.

### Requested fields

| Query | Fields |
|-------|--------|
| AccountQueryRq | ListID, FullName, AccountType, IsActive, Balance, TotalBalance |
| CustomerQueryRq | ListID, FullName, IsActive, Balance, TotalBalance, SalesRepRef/FullName |

### Snapshot JSON

```json
{
  "company_id": 1,
  "synced_at": "2026-09-21T19:00:00Z",
  "accounts": [],
  "customers": []
}
```

Header: `Authorization: Bearer <AgentToken>`. Token is never written to logs.

---

## Backend internals

### Tables

**companies**

| Column | Type |
|--------|------|
| id | bigint PK |
| name | string |
| timestamps | |

**users**

| Column | Type |
|--------|------|
| id | bigint PK |
| company_id | FK companies |
| name | string |
| email | unique |
| password | hashed |
| role | enum: `owner`, `sales_rep`, `collections`, `admin` |
| qb_sales_rep_name | nullable string (matches Customer.SalesRepRef.FullName) |
| timestamps | |

**accounts**

| Column | Type |
|--------|------|
| id | bigint PK |
| company_id | FK |
| qb_list_id | string |
| full_name | string |
| account_type | string |
| is_active | boolean |
| balance | decimal(15,2) |
| total_balance | decimal(15,2) |
| timestamps | |
| unique | (company_id, qb_list_id) |

**customers**

| Column | Type |
|--------|------|
| id | bigint PK |
| company_id | FK |
| qb_list_id | string |
| full_name | string |
| is_active | boolean |
| balance | decimal(15,2) |
| total_balance | decimal(15,2) |
| sales_rep_name | nullable string |
| timestamps | |
| unique | (company_id, qb_list_id) |

**sync_logs**

| Column | Type |
|--------|------|
| id | bigint PK |
| company_id | FK |
| status | `success` \| `error` |
| accounts_count | int |
| customers_count | int |
| duration_ms | int |
| error | nullable text |
| synced_at | datetime UTC (from payload on success; now() on hard failure) |
| timestamps | |

**agent_tokens**

| Column | Type |
|--------|------|
| id | bigint PK |
| company_id | FK |
| name | string |
| token_hash | string (sha256 of plaintext) |
| last_used_at | nullable datetime |
| revoked_at | nullable datetime |
| timestamps | |

### Role matrix

| Capability | owner | admin | collections | sales_rep |
|------------|:-----:|:-----:|:-----------:|:---------:|
| Filament `/admin` | | ✓ | | |
| GET /api/status | ✓ | ✓ | ✓ | ✓ |
| GET /api/accounts | ✓ | ✓ | ✓ | |
| GET /api/customers (all) | ✓ | ✓ | ✓ | |
| GET /api/customers (own sales_rep_name only) | | | | ✓ |
| GET /api/summary totals + top 5 | ✓ | ✓ | ✓ | ✓ |
| GET /api/summary key account balances | ✓ | ✓ | | |
| POST /api/agent/sync | agent token, not a user role | | | |

Sales-rep visibility: `customers.sales_rep_name` **equals** `users.qb_sales_rep_name`. No match → empty list / 404 on detail.

### Stale monitor

Scheduled command every 5 minutes: if a company’s latest *successful* sync is older than 15 minutes (or missing), write a warning log. No email in MVP.

---

## Mobile internals

```
go_router (role-aware)
  ├─ /login
  ├─ /home          owner, admin          (collections: status-only home)
  ├─ /accounts      owner, admin, collections
  ├─ /customers     all roles
  └─ /customers/:id all roles (404 / pop if policy denies)

Riverpod
  ├─ authRepository (secure storage + login/logout)
  ├─ apiClient (Dio + Bearer)
  ├─ statusController (polling + pull-to-refresh)
  └─ cacheStore (last JSON, offline chip)

Banner (every main scaffold)
  last successful synced_at → "Last synced X min ago"
  is_stale → red + "Data may be out of date"
  serving cache → "Offline data"
```

Currency via `intl` and `--dart-define=CURRENCY=USD`.

---

## Local (no QuickBooks) vs production

| | No-QB path | Real-QB path |
|--|------------|--------------|
| Agent | `MockMode=true` or `--mock` | Windows Service, COM, view-only QB user, unattended app authorization |
| Backend | `php artisan serve` + MySQL | HTTPS + production `.env` |
| Mobile | `--dart-define=API_BASE_URL=...` against localhost | HTTPS production URL |
| Token | Created once in Filament | Per-company token, rotated as needed |

See `docs/TESTING.md` (Wave 8) for the step-by-step of both paths.

---

## Security notes

Locked for production documentation (also summarized in the root README):

| Rule | Detail |
|------|--------|
| Transport | **HTTPS only** outside the trusted Windows box. Local mock/dev may use HTTP (`127.0.0.1`). |
| QuickBooks user | Dedicated **view-only** (read-only) user; unattended integrated-app access for the agent |
| Agent qbXML | Built only in `QbXmlClient`; root element must end with `QueryRq` (no writes) |
| Agent credentials | One hashed token per company (`sha256` of 96-char hex). Rotate by creating a new Filament token and revoking the old. Never store plaintext after the one-time reveal. |
| Mobile credentials | Sanctum personal access tokens; logout revokes the current token |
| Secrets in git | Forbidden: `.env`, `appsettings.Production.json`, plaintext agent tokens, Flutter `*.jks` / `key.properties` |
| Mobile ↔ QB | Mobile never contains QB SDK, company paths, or COM |

Trust boundaries (diagram above) remain the source of truth for who talks to whom.

