<#
.SYNOPSIS
  Generate a manifest + RAW URL list for AI ingestion... overwrites a stable file.

.DESCRIPTION
  Walk selected repo paths, collect Git-tracked files, compute size and SHA256,
  and write a single file `_index/smutsuite_urls.txt` that begins with a commented
  manifest section followed by RAW GitHub URLs (one per line).

  You commit/push this file. I’ll read its RAW URL on command and fetch everything.

.REQUIREMENTS
  - Run from the Git repo root.
  - Git installed and in PATH.
  - Remote 'origin' points at GitHub.
#>

param(
    [string]$OutputPath = "_index/smutsuite_urls.txt",
    [string[]]$IncludePaths = @(
    "app",
    "routes",
    "config",
    "database",
    "resources",
    "composer.json",
    "composer.lock",
    "artisan"
)
)

# --- Step 1: Environment guards ------------------------------------------------
if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Error "git not found in PATH"; exit 1
}
$null = git rev-parse --git-dir 2>$null
if ($LASTEXITCODE -ne 0) {
    Write-Error "Not inside a Git repository. Run from the repo root."; exit 1
}

# --- Step 2: Repo coordinates --------------------------------------------------
$remoteUrl = (git remote get-url origin).Trim()
function Normalize-GitHubUrl([string]$u) {
    if ($u -match '^git@github\.com:(.+?)(?:\.git)?$') { return "https://github.com/$($Matches[1])" }
    if ($u -match '^https://github\.com/(.+?)(?:\.git)?$') { return "https://github.com/$($Matches[1])" }
    return $u
}
$repoHttp = Normalize-GitHubUrl $remoteUrl
$repoSlug = ($repoHttp -replace '^https://github\.com/','').TrimEnd('/')

$branch = (git rev-parse --abbrev-ref HEAD).Trim()
if (-not $branch) { $branch = "main" }

$commit = (git rev-parse --short HEAD).Trim()
$rawBase = "https://raw.githubusercontent.com/$repoSlug/$branch/"

# --- Step 3: Helpers -----------------------------------------------------------
function Test-GitTracked([string]$relPath) {
    git ls-files --error-unmatch -- "$relPath" 1>$null 2>$null
    return ($LASTEXITCODE -eq 0)
}

# --- Step 4: Collect entries as PSCustomObject ---------------------------------
$repoRoot = (Get-Location).Path
$entries  = New-Object System.Collections.Generic.List[object]

foreach ($p in $IncludePaths) {
    $full = Join-Path $repoRoot $p
    if (-not (Test-Path $full)) {
        Write-Warning "Path not found... skipping: $p"
        continue
    }

    if ((Get-Item $full).PSIsContainer) {
        Get-ChildItem $full -Recurse -File | ForEach-Object {
            $rel = $_.FullName.Substring($repoRoot.Length).TrimStart('\','/')
            $relUnix = $rel -replace '\\','/'
            if (Test-GitTracked $relUnix) {
                $sha = (Get-FileHash $_.FullName -Algorithm SHA256).Hash
                $entries.Add([pscustomobject]@{
                    Path   = $relUnix
                    Size   = $_.Length
                    Sha256 = $sha
                    Url    = "$rawBase$relUnix"
                })
            }
        }
    } else {
        $rel = $full.Substring($repoRoot.Length).TrimStart('\','/')
        $relUnix = $rel -replace '\\','/'
        if (Test-GitTracked $relUnix) {
            $fi  = Get-Item $full
            $sha = (Get-FileHash $full -Algorithm SHA256).Hash
            $entries.Add([pscustomobject]@{
                Path   = $relUnix
                Size   = $fi.Length
                Sha256 = $sha
                Url    = "$rawBase$relUnix"
            })
        } else {
            Write-Warning "Untracked file... skipping: $relUnix"
        }
    }
}

# --- Step 5: Prepare output file ----------------------------------------------
$outFull = Join-Path $repoRoot $OutputPath
$dir     = Split-Path $outFull -Parent
if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir | Out-Null }
if (Test-Path $outFull) { Remove-Item $outFull -Force }

# --- Step 6: Write header manifest + URL list ---------------------------------
$sb = New-Object System.Text.StringBuilder
[void]$sb.AppendLine([string]::Format("# repo: {0}",    $repoSlug))
[void]$sb.AppendLine([string]::Format("# branch: {0}",  $branch))
[void]$sb.AppendLine([string]::Format("# commit: {0}",  $commit))
[void]$sb.AppendLine([string]::Format("# generated: {0}", (Get-Date -Format 'yyyy-MM-dd HH:mm:ssK')))
[void]$sb.AppendLine([string]::Format("# count: {0}",   $entries.Count))
[void]$sb.AppendLine("# manifest: path | size | sha256")

foreach ($e in ($entries | Sort-Object Path)) {
    # Use string::Format to avoid -f quirks when any field is null
    [void]$sb.AppendLine([string]::Format("# {0} | {1} | {2}", $e.Path, $e.Size, $e.Sha256))
}

[void]$sb.AppendLine("")  # separator

foreach ($e in ($entries | Sort-Object Path)) {
    [void]$sb.AppendLine($e.Url)
}

$sb.ToString() | Set-Content -Encoding UTF8 -Path $outFull

Write-Output "✅ Wrote $($entries.Count) files to $OutputPath"
Write-Output "RAW link:"
Write-Output "  https://raw.githubusercontent.com/$repoSlug/$branch/$($OutputPath -replace '\\','/')"
