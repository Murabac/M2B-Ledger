#Requires -RunAsAdministrator
<#
.SYNOPSIS
  Installs QbBalances.Agent as a Windows Service (QbBalancesAgent).

.PARAMETER InstallDir
  Publish output directory. Default: C:\Program Files\QbBalancesAgent

.PARAMETER Configuration
  Build configuration. Default: Release

.NOTES
  - Publish as win-x86: QuickBooks Desktop Request Processor is commonly 32-bit COM.
  - Copy/edit appsettings.Production.json in InstallDir with AgentToken, MockMode=false, CompanyFilePath.
  - Never commit plaintext tokens.
#>
param(
    [string]$InstallDir = "C:\Program Files\QbBalancesAgent",
    [string]$Configuration = "Release"
)

$ErrorActionPreference = "Stop"
$ServiceName = "QbBalancesAgent"
$DisplayName = "QB Balances Sync Agent"
$Root = Split-Path -Parent $PSScriptRoot
$Project = Join-Path $Root "src\QbBalances.Agent\QbBalances.Agent.csproj"

Write-Host "Publishing agent (win-x86) to $InstallDir ..."
New-Item -ItemType Directory -Force -Path $InstallDir | Out-Null

dotnet publish $Project `
    -c $Configuration `
    -r win-x86 `
    --self-contained false `
    -o $InstallDir

if ($LASTEXITCODE -ne 0) {
    throw "dotnet publish failed with exit code $LASTEXITCODE"
}

$exe = Join-Path $InstallDir "QbBalances.Agent.exe"
if (-not (Test-Path $exe)) {
    throw "Published exe not found: $exe"
}

$existing = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
if ($existing) {
    Write-Host "Stopping existing service $ServiceName ..."
    if ($existing.Status -ne "Stopped") {
        Stop-Service -Name $ServiceName -Force
    }
    Write-Host "Removing existing service $ServiceName ..."
    sc.exe delete $ServiceName | Out-Null
    Start-Sleep -Seconds 2
}

Write-Host "Creating service $ServiceName ..."
New-Service `
    -Name $ServiceName `
    -BinaryPathName "`"$exe`"" `
    -DisplayName $DisplayName `
    -Description "Pushes read-only QuickBooks balance snapshots to the QB Balances backend." `
    -StartupType Automatic | Out-Null

Write-Host @"

Installed.
Next:
  1. Edit $InstallDir\appsettings.Production.json (or set env Agent__* vars)
     - AgentToken from Filament (shown once)
     - MockMode = false
     - CompanyFilePath = '' for currently open company, or full .QBW path
  2. Authorize the app inside QuickBooks (integrated application, view-only user, unattended)
  3. Start-Service $ServiceName

"@
