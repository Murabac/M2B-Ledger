#Requires -RunAsAdministrator
<#
.SYNOPSIS
  Stops and removes the QbBalancesAgent Windows Service.
#>
param(
    [string]$ServiceName = "QbBalancesAgent"
)

$ErrorActionPreference = "Stop"

$existing = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
if (-not $existing) {
    Write-Host "Service $ServiceName is not installed."
    exit 0
}

if ($existing.Status -ne "Stopped") {
    Write-Host "Stopping $ServiceName ..."
    Stop-Service -Name $ServiceName -Force
}

Write-Host "Deleting $ServiceName ..."
sc.exe delete $ServiceName | Out-Null
Write-Host "Removed. Published files under the install directory were left in place; delete them manually if desired."
