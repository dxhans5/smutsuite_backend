Param(
    [string]$Filter = "",
    [switch]$NoFresh,  # skip migrate:fresh --seed
    [switch]$NoClear   # skip cache clears
)

$ErrorActionPreference = "Stop"

function RunPhp([string[]]$ArgsList) {
    Write-Host "→ php $($ArgsList -join ' ')"
    & php @ArgsList
    if ($LASTEXITCODE -ne 0) { throw "Command failed: php $($ArgsList -join ' ')" }
}

# run from repo root
Set-Location -Path (Split-Path -Parent $MyInvocation.MyCommand.Path)

if (-not $NoClear) {
    RunPhp @("artisan","config:clear")
    RunPhp @("artisan","route:clear")
    RunPhp @("artisan","cache:clear")
}

if (-not $NoFresh) {
    RunPhp @("artisan","migrate:fresh","--seed","--force")
}

$testArgs = @("artisan","test","--stop-on-failure","--testdox")
if ($Filter -ne "") {
    $testArgs += @("--filter",$Filter)
}
RunPhp $testArgs

Write-Host "`n✅ Done."
