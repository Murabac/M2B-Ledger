# M2B Ledger (qb-balances)

Read-only mobile app that shows **Account Balances** and **Customer Balances** from QuickBooks Desktop Enterprise.

A Windows sync agent reads balances via qbXML (never writes) and pushes a JSON snapshot to a Laravel API. The Flutter app reads that snapshot through role-based REST endpoints. The phone **never** talks to QuickBooks. Data is a few-minutes-old snapshot; a “last synced” time is shown everywhere.

```
QB Desktop Enterprise (Windows)
        ↑ read-only qbXML
   Agent (.NET 8 Worker)
        ↓ HTTPS POST /api/agent/sync
   Backend (Laravel + Filament + MySQL/SQLite)
        ↓ REST + Sanctum
    Mobile (Flutter Android + iOS)
```

| Folder | What |
|--------|------|
| [`backend/`](backend/) | Laravel 12 API + Filament 3 admin |
| [`agent/`](agent/) | .NET 8 sync worker (mock or live COM) |
| [`mobile/`](mobile/) | Flutter app |
| [`docs/`](docs/) | Architecture, decisions, testing |
| [`WAVES.md`](WAVES.md) | Build plan (waves 0–8) |

---

## Prerequisites

- **PHP 8.5+** + Composer (backend)
- **.NET 8 SDK** (agent)
- **Flutter** stable 3.24+ (mobile)
- Optional: **MySQL 8** (local default is SQLite)
- Optional: **QuickBooks Desktop Enterprise** + qbXML Request Processor (live agent only)

---

## Quick start (no QuickBooks)

### 1. Backend

```powershell
cd backend
copy .env.example .env
php artisan key:generate
# If using SQLite (default): ensure database/database.sqlite exists
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Admin panel: [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)  
API base: `http://127.0.0.1:8000/api`

**Seed users** (password for all: `Password123!`)

| Role | Email |
|------|--------|
| owner | `owner@demo.test` |
| sales_rep | `sales@demo.test` |
| collections | `collections@demo.test` |
| admin | `admin@demo.test` |

Only **admin** can open Filament `/admin`.

### 2. Create an agent token

1. Log into Filament as `admin@demo.test`.
2. Open **Agent tokens** → Create.
3. Copy the plaintext token **once** (it is never shown again; only a hash is stored).
4. Use it as `Agent__AgentToken` when running the agent.

### 3. Agent (mock)

```powershell
cd agent/src/QbBalances.Agent
$env:Agent__AgentToken = "<paste-from-Filament>"
$env:Agent__BackendUrl = "http://127.0.0.1:8000"
$env:Agent__CompanyId = "1"
dotnet run -- --mock
```

Default poll interval is **180 seconds**. See [`agent/README.md`](agent/README.md).

### 4. Mobile

```powershell
cd mobile

# Physical Android phone over USB:
adb reverse tcp:8000 tcp:8000
flutter run -d <deviceId> --dart-define=API_BASE_URL=http://127.0.0.1:8000 --dart-define=CURRENCY=USD

# Android emulator:
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000 --dart-define=CURRENCY=USD
```

Log in with a seed user (e.g. `owner@demo.test` / `Password123!`).

Pull-to-refresh reloads the **API snapshot**. It does **not** trigger a QuickBooks sync — only the agent does that.

---

## Real QuickBooks path

See [`docs/TESTING.md`](docs/TESTING.md) § (b) and [`agent/README.md`](agent/README.md).

Summary:

1. Open the company file in QuickBooks Desktop Enterprise.
2. Set `Agent__MockMode=false` (and token / backend URL).
3. Authorize **QB Balances Sync Agent** as an integrated app (prefer a **view-only** QB user + unattended access).
4. Run the agent (console or Windows Service via `agent/scripts/install-service.ps1`).
5. Confirm a success row in Filament **Sync logs**, then refresh the mobile app.

---

## Environment variables

### Backend (`backend/.env`)

| Variable | Notes |
|----------|--------|
| `APP_KEY` | `php artisan key:generate` |
| `APP_URL` | e.g. `http://127.0.0.1:8000` |
| `APP_QUICK_LOGIN` | `true` only in local (Filament quick-login) |
| `DB_CONNECTION` | `sqlite` (local) or `mysql` |
| `DB_DATABASE` / host / user / password | MySQL when not using SQLite |

Full template: [`backend/.env.example`](backend/.env.example).

### Agent (`Agent` section / env)

| Variable | Notes |
|----------|--------|
| `Agent__BackendUrl` | e.g. `http://127.0.0.1:8000` |
| `Agent__AgentToken` | Filament one-time plaintext |
| `Agent__CompanyId` | Usually `1` after seed |
| `Agent__MockMode` | `true` / `false` |
| `Agent__PollIntervalSeconds` | Default `180` |
| `Agent__CompanyFilePath` | Empty = currently open company file |

Prefer `appsettings.Production.json` (gitignored) on the server.

### Mobile (`--dart-define`)

| Define | Default / notes |
|--------|------------------|
| `API_BASE_URL` | `http://127.0.0.1:8000` (emulator: `http://10.0.2.2:8000`) |
| `CURRENCY` | `USD` |

---

## Tests

```powershell
cd backend; php artisan test
cd agent;   dotnet test QbBalances.Agent.sln
cd mobile;  flutter test
```

Full checklists: [`docs/TESTING.md`](docs/TESTING.md).

---

## Security (production)

- **HTTPS only** for agent → API and mobile → API.
- Agent uses a dedicated **view-only** QuickBooks user; qbXML is query-only (`*QueryRq`).
- One **hashed** agent token per company; rotate by creating a new token and revoking the old.
- Mobile uses Sanctum personal access tokens; logout revokes the current token.
- **Never commit** secrets: `.env`, `appsettings.Production.json`, plaintext agent tokens, Flutter keystores (`*.jks`, `key.properties`).

More detail: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) (trust boundaries + security notes) and [`docs/DECISIONS.md`](docs/DECISIONS.md).

---

## Docs map

| Doc | Purpose |
|-----|---------|
| [`WAVES.md`](WAVES.md) | Wave plan and completion checklist |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Data flow, schema, roles, sync contract |
| [`docs/DECISIONS.md`](docs/DECISIONS.md) | Locked product/tech defaults |
| [`docs/TESTING.md`](docs/TESTING.md) | No-QB and real-QB verification |
| [`docs/DESIGN.md`](docs/DESIGN.md) | UI / branding notes (if present) |
