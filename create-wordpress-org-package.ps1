# WordPress.org Submission Package Creator
# Creates Unix-compatible ZIP for asneris-seo-toolkit
#
# Usage:
#   .\create-wordpress-org-package.ps1                      # runs npm build + packages (auto-reads version)
#   .\create-wordpress-org-package.ps1 -Version 1.0.0       # override version
#   .\create-wordpress-org-package.ps1 -IncludeSource       # include src/ folder for source disclosure
# Note: npm run build is always executed automatically before packaging.

param(
    [string]$Version = "",
    [switch]$IncludeSource
)

$ErrorActionPreference = "Stop"

Write-Host "=== WordPress.org Package Creator ===" -ForegroundColor Cyan
Write-Host ""

# Paths - auto-detected from script location (no hardcoded paths)
$sourceDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$tempDir   = "$sourceDir\temp-package"

# Auto-detect main plugin file (first .php file with 'Plugin Name:' header)
$mainFile = Get-ChildItem -Path $sourceDir -Filter "*.php" -File |
    Where-Object { (Get-Content $_.FullName -TotalCount 10) -match "Plugin Name:" } |
    Select-Object -First 1

if (-not $mainFile) {
    Write-Host "ERROR: Could not find main plugin PHP file in: $sourceDir" -ForegroundColor Red
    exit 1
}

# Plugin slug from main PHP filename
$pluginSlug = [System.IO.Path]::GetFileNameWithoutExtension($mainFile.Name)

# Version: use -Version param if given, otherwise read from PHP plugin header
if ($Version -ne "") {
    $pluginVersion = $Version
} else {
    $versionLine   = Get-Content $mainFile.FullName | Select-String "^\s*\*\s*Version:\s*(.+)"
    $pluginVersion = if ($versionLine) { $versionLine.Matches[0].Groups[1].Value.Trim() } else { "" }
}

# ZIP filename: WITHOUT version (WordPress.org requirement)
$zipName   = "$pluginSlug.zip"
$pluginDir = "$tempDir\$pluginSlug"

Write-Host "Plugin:  $pluginSlug" -ForegroundColor Green
Write-Host "Version: $(if ($pluginVersion) { $pluginVersion } else { '(not found)' })" -ForegroundColor Green
Write-Host "Source:  $sourceDir" -ForegroundColor Green
Write-Host "Output:  $zipName" -ForegroundColor Green
Write-Host ""

# --- Pre-flight validation ---
Write-Host "Running pre-flight checks..." -ForegroundColor Yellow

# Validate required files exist before packaging
$requiredFiles = @("readme.txt", "uninstall.php", "help-content.json")
foreach ($file in $requiredFiles) {
    if (-not (Test-Path "$sourceDir\$file")) {
        Write-Host "ERROR: Missing required file: $file" -ForegroundColor Red
        exit 1
    }
}

# Run npm build automatically before packaging
Write-Host "  Running npm run build..." -ForegroundColor Yellow
Push-Location $sourceDir
try {
    npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: npm run build failed (exit code $LASTEXITCODE). Fix build errors before packaging." -ForegroundColor Red
        exit 1
    }
    Write-Host "  npm build completed." -ForegroundColor Green
} finally {
    Pop-Location
}

# Validate build output exists
if (-not (Test-Path "$sourceDir\build\index.js")) {
    Write-Host "ERROR: build/index.js missing after npm run build." -ForegroundColor Red
    exit 1
}

# Validate readme.txt contains source/build disclosure (WordPress.org requirement)
$readme = Get-Content "$sourceDir\readme.txt" -Raw
if ($readme -notmatch "github\.com" -or $readme -notmatch "npm run build") {
    Write-Host "ERROR: readme.txt is missing source repository URL or build instructions." -ForegroundColor Red
    Write-Host "       WordPress.org requires disclosure of build process for minified assets." -ForegroundColor Red
    exit 1
}

Write-Host "  Pre-flight checks passed." -ForegroundColor Green
Write-Host ""

# Clean all previous build artifacts
Write-Host "Cleaning previous build artifacts..." -ForegroundColor Yellow
if (Test-Path "$sourceDir\$zipName") {
    Remove-Item "$sourceDir\$zipName" -Force
}

# Clean previous temp directory
if (Test-Path $tempDir) {
    Remove-Item $tempDir -Recurse -Force
}

# Create temp directory structure
Write-Host "Creating package structure..." -ForegroundColor Yellow
New-Item -ItemType Directory -Path $pluginDir -Force | Out-Null

# Copy essential files
Write-Host "Copying plugin files..." -ForegroundColor Yellow

# Use detected main plugin file (not hardcoded filename)
Copy-Item $mainFile.FullName $pluginDir
Copy-Item "$sourceDir\readme.txt" $pluginDir
Copy-Item "$sourceDir\uninstall.php" $pluginDir
Copy-Item "$sourceDir\help-content.json" $pluginDir

# Directories
Copy-Item "$sourceDir\includes" $pluginDir -Recurse
Copy-Item "$sourceDir\languages" $pluginDir -Recurse
Copy-Item "$sourceDir\assets" $pluginDir -Recurse
Copy-Item "$sourceDir\build" $pluginDir -Recurse
Copy-Item "$sourceDir\templates" $pluginDir -Recurse

# Optionally include source files for WordPress.org transparency
if ($IncludeSource -and (Test-Path "$sourceDir\src")) {
    Write-Host "  Including src/ folder for source transparency..." -ForegroundColor Cyan
    Copy-Item "$sourceDir\src" $pluginDir -Recurse
}

Write-Host "Files copied successfully!" -ForegroundColor Green

# Remove unwanted files from the package (dev artifacts, hidden files, nested archives)
$excludePatterns = @("node_modules", ".git", ".vscode", "*.log", "*.ps1", ".DS_Store", "*.zip")
Get-ChildItem $pluginDir -Recurse -Force | Where-Object {
    $item = $_
    ($excludePatterns | Where-Object { $item.FullName -like "*$_*" }) -or
    $item.Name.StartsWith('.') -or
    (($item.Attributes -band [IO.FileAttributes]::Hidden) -eq [IO.FileAttributes]::Hidden)
} | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue

# Keep only allowed markdown files in plugin root to satisfy directory checks.
$allowedRootMarkdown = @("readme.txt", "README.md", "LICENSE", "LICENSE.txt", "CHANGELOG.md")
Get-ChildItem $pluginDir -File -Filter "*.md" -Force | Where-Object {
    $allowedRootMarkdown -notcontains $_.Name
} | Remove-Item -Force -ErrorAction SilentlyContinue

Write-Host ""

# Dynamic WSL path conversion (supports any drive letter, not just D:)
$drive = $sourceDir.Substring(0, 1).ToLower()
$wslTempDir = $tempDir -replace '\\', '/' -replace '^[A-Za-z]:', "/mnt/$drive"

Write-Host "Creating Unix-compatible ZIP using WSL..." -ForegroundColor Yellow

# Create ZIP using WSL
$wslCommand = "cd '$wslTempDir' && zip -r $zipName $pluginSlug/"
wsl bash -c $wslCommand

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: ZIP creation failed!" -ForegroundColor Red
    exit 1
}

# Move ZIP to source directory
Move-Item "$tempDir\$zipName" "$sourceDir\$zipName" -Force

# Clean up temp directory
Write-Host "Cleaning up..." -ForegroundColor Yellow
Remove-Item $tempDir -Recurse -Force

# Validate ZIP is not suspiciously small (sanity check for broken builds)
$zipPath = "$sourceDir\$zipName"
$zipSize = (Get-Item $zipPath).Length
if ($zipSize -lt 10000) {
    Write-Host "ERROR: ZIP file is too small ($zipSize bytes). Build may be incomplete." -ForegroundColor Red
    exit 1
}
$zipSizeKB = [math]::Round($zipSize / 1KB, 0)
$zipSizeMB = [math]::Round($zipSize / 1MB, 2)

Write-Host ""
Write-Host "=== SUCCESS! ===" -ForegroundColor Green
Write-Host ""
Write-Host "WordPress.org submission package created:" -ForegroundColor Cyan
Write-Host "$sourceDir\$zipName" -ForegroundColor White
Write-Host ""
Write-Host "Package Contents:" -ForegroundColor Yellow
Write-Host "  - $($mainFile.Name) (main plugin file)" -ForegroundColor Gray
Write-Host "  - readme.txt (WordPress.org readme)" -ForegroundColor Gray
Write-Host "  - uninstall.php (clean uninstall handler)" -ForegroundColor Gray
Write-Host "  - help-content.json (help system content)" -ForegroundColor Gray
Write-Host "  - includes/ (PHP class files)" -ForegroundColor Gray
Write-Host "  - languages/ (i18n directory)" -ForegroundColor Gray
Write-Host "  - assets/ (CSS and JavaScript)" -ForegroundColor Gray
Write-Host "  - build/ (compiled React components)" -ForegroundColor Gray
Write-Host "  - templates/ (template files)" -ForegroundColor Gray
if ($IncludeSource -and (Test-Path "$sourceDir\src")) {
    Write-Host "  - src/ (source files for reviewer transparency)" -ForegroundColor Gray
}
Write-Host ""
Write-Host "Package size: $zipSizeMB MB ($zipSizeKB KB)" -ForegroundColor Green
Write-Host ""
Write-Host "Ready to upload to: https://wordpress.org/plugins/developers/add/" -ForegroundColor Yellow
Write-Host ""
