# QB Balances Sync Agent

.NET 8 Worker that reads QuickBooks Desktop balances (or a mock snapshot) and POSTs them to the Laravel backend at `POST /api/agent/sync`.

## Modes

| Mode | How | OS |
|------|-----|----|
| **Mock** (default) | `MockMode=true` or `dotnet run -- --mock` | Any |
| **Real QB** | `MockMode=false` + QuickBooks Desktop Enterprise + `QBXMLRP2` | Windows |

`--mock` always forces mock, regardless of config.

## Config (`Agent` section)

| Key | Default | Notes |
|-----|---------|-------|
| `BackendUrl` | `http://127.0.0.1:8000` | |
| `AgentToken` | `""` | From Filament → Agent tokens (shown **once**). Never commit. Never logged. |
| `CompanyId` | `1` | Demo Company seeder |
| `PollIntervalSeconds` | `180` | |
| `MockMode` | `true` | Set `false` only on the QB machine |
| `CompanyFilePath` | `""` | Empty = currently open company file |
| `AppName` | `QB Balances Sync Agent` | Shown in QB integrated-app prompt |
| `CustomerMaxReturned` | `100` | Iterator page size |

Prefer `appsettings.Production.json` (gitignored) or env vars (`Agent__AgentToken`, …).

Logs: console + rolling `./logs/agent-.log` (7 days).

## Mock run

```powershell
cd agent/src/QbBalances.Agent
$env:Agent__AgentToken = "<paste-from-Filament>"
dotnet run -- --mock
```

## Real QuickBooks (Wave 5)

Prerequisites:

1. QuickBooks Desktop **Enterprise** installed; company file open (or `CompanyFilePath` set).
2. `QBXMLRP2.RequestProcessor2` registered (qbXML Request Processor). Prefer a **32-bit** process (`win-x86` publish) if the ProgID is only visible to 32-bit apps.
3. Authorize **QB Balances Sync Agent** as an integrated application.
4. Grant **unattended** access for a **view-only** QuickBooks user.
5. Backend reachable; agent token created in Filament.

```powershell
$env:Agent__MockMode = "false"
$env:Agent__AgentToken = "<token>"
dotnet run --project agent/src/QbBalances.Agent
```

Each cycle: `AccountQueryRq` (ActiveStatus=All) + paginated `CustomerQueryRq`, then POST snapshot. COM cleanup always runs in `finally`.

### Authorize the agent in QuickBooks

If you see **“The QuickBooks user has denied access”**:

1. Keep the company file open in Enterprise.
2. Run the agent once (`MockMode=false`) so QB shows the **Application Certificate** / integrated-app prompt for **QB Balances Sync Agent**.
3. Choose **Yes, always; allow access even if QuickBooks is not running** (or equivalent unattended option).
4. Prefer a dedicated **view-only** QuickBooks user for that access.
5. Confirm under **Edit → Preferences → Integrated Applications** that the app is Allowed.

ProgID note: this install registers `QBXMLRP2.RequestProcessor` (agent also tries `.2` / docs alias `RequestProcessor2`).

## Windows Service

```powershell
# Elevated PowerShell
cd agent\scripts
.\install-service.ps1
# Edit C:\Program Files\QbBalancesAgent\appsettings.Production.json
Start-Service QbBalancesAgent

.\uninstall-service.ps1
```

Service name: `QbBalancesAgent` / display name: `QB Balances Sync Agent`. Publish RID: `win-x86`.

## Tests

```powershell
dotnet test agent/QbBalances.Agent.sln
```

Includes qbXML fixture parse tests under `agent/tests/Fixtures`.
