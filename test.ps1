Param(
    [string]$Filter = "",     # Optional: run only tests matching this filter
    [switch]$NoFresh,         # Skip migrate:fresh --seed
    [switch]$NoClear          # Skip all cache/config clears
)

# Fail fast on unexpected errors (except where we explicitly guard)
$ErrorActionPreference = "Stop"

# --- helpers ---------------------------------------------------------------

function RunPhp([string[]]$ArgsList) {
    Write-Host "→ php $($ArgsList -join ' ')"
    & php @ArgsList
    if ($LASTEXITCODE -ne 0) { throw "Command failed: php $($ArgsList -join ' ')" }
}

function TryPhp([string[]]$ArgsList, [string]$Why = "optional step") {
    Write-Host "→ php $($ArgsList -join ' ')"
    try {
        & php @ArgsList
        if ($LASTEXITCODE -ne 0) { throw "exit $LASTEXITCODE" }
    } catch {
        Write-Warning "Non-fatal: php $($ArgsList -join ' ') failed ($Why). Continuing..."
    }
}

# Always run from repo root (script’s directory)
Set-Location -Path $PSScriptRoot

# --- pre-migrate clears (safe) --------------------------------------------
if (-not $NoClear) {
    # These never depend on DB
    RunPhp @("artisan","config:clear")
    RunPhp @("artisan","route:clear")

    # This CAN depend on DB if CACHE_STORE=database and 'cache' table isn't created yet.
    # Make it non-fatal before migrations to avoid “relation 'cache' does not exist”.
    TryPhp @("artisan","cache:clear") "database cache driver without cache table yet"
}

# --- migrate db ------------------------------------------------------------
if (-not $NoFresh) {
    RunPhp @("artisan","migrate:fresh","--seed","--force")

    # Now that tables exist, do a definitive cache clear (fatal if it fails here).
    if (-not $NoClear) {
        RunPhp @("artisan","cache:clear")
    }
}

# --- run tests -------------------------------------------------------------
$testArgs = @("artisan","test","--stop-on-failure","--testdox")
if ($Filter -ne "") {
    $testArgs += @("--filter",$Filter)
}
RunPhp $testArgs

Write-Host "`n✅ Done."
