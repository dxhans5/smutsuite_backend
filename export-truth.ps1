# export-truth.ps1
# Packages essential project files for AI review.
# Produces two zips:
#   1) smutsuite_truth_<date>.zip  - project code and config
#   2) vixen_bible_<date>.zip      - VixenBible folder (Bible.txt + Images)

# Step 1: Set working variables
$Date = Get-Date -Format "yyyy-MM-dd"
$TruthZipName = "smutsuite_truth_$Date.zip"
$TruthZipPath = Join-Path -Path "." -ChildPath $TruthZipName

$BibleFolder  = "VixenBible"   # folder at same level as this script
$BibleZipName = "vixen_bible_$Date.zip"
$BibleZipPath = Join-Path -Path "." -ChildPath $BibleZipName

# Step 2: Delete old zips if they exist
foreach ($zip in @($TruthZipPath, $BibleZipPath)) {
    if (Test-Path $zip) { Remove-Item $zip -Force }
}

# Step 3: Define items to include for the project truth (repo-root relative)
# Note: includes ENTIRE database/ and resources/ as requested.
$itemsToInclude = @(
    "app",
    "routes",
    "config",
    "database",       # <-- added
    "resources",      # <-- added
    "composer.json",
    "composer.lock",
    "artisan",
    ".env"
)

# Optional: filter out paths that don't exist to avoid Compress-Archive errors
$existingItems = @()
foreach ($item in $itemsToInclude) {
    if (Test-Path $item) {
        $existingItems += $item
    } else {
        Write-Warning "Path not found... skipping: $item"
    }
}

# Step 4: Create project truth zip with full structure
# Compress-Archive preserves directory structure for folders included.
Compress-Archive -Path $existingItems -DestinationPath $TruthZipPath -CompressionLevel Optimal -Force

# Step 5: Create VixenBible zip as a separate archive
if (Test-Path $BibleFolder) {
    Compress-Archive -Path $BibleFolder -DestinationPath $BibleZipPath -CompressionLevel Optimal -Force
} else {
    Write-Warning "VixenBible folder not found. Skipping Vixen Bible zip."
}

Write-Output "✅ Project zipped as $TruthZipName in root directory."
Write-Output "✅ Vixen Bible zipped as $BibleZipName in root directory (if folder existed)."
