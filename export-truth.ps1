# PowerShell script to package the essential project files for AI review

# Step 1: Set working variables
$Date = Get-Date -Format "yyyy-MM-dd"
$ZipName = "smutsuite_truth_$Date.zip"
$ZipPath = Join-Path -Path "." -ChildPath $ZipName

# Step 2: Delete old zip if it exists
if (Test-Path $ZipPath) {
    Remove-Item $ZipPath -Force
}

# Step 3: Define items to include (project root relative paths)
$itemsToInclude = @(
    "app",
    "routes",
    "config",
    "composer.json",
    "composer.lock",
    "artisan",
    ".env"
)

# Step 4: Create zip with full structure
Compress-Archive -Path $itemsToInclude -DestinationPath $ZipPath -Force
Write-Output "✅ Project zipped as $ZipName in root directory."
