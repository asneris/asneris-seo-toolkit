# WordPress.org Submission Package Creator
# Creates Unix-compatible ZIP for asneris-seo-toolkit
#
# Usage:
#   .\create-wordpress-org-package.ps1                 # auto-reads version from PHP header
#   .\create-wordpress-org-package.ps1 -Version 1.0.0  # override version

param(
    [string]$Version = ""
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

# ZIP filename: with version if available
$zipName   = if ($pluginVersion) { "$pluginSlug-$pluginVersion.zip" } else { "$pluginSlug.zip" }
$pluginDir = "$tempDir\$pluginSlug"

Write-Host "Plugin:  $pluginSlug" -ForegroundColor Green
Write-Host "Version: $(if ($pluginVersion) { $pluginVersion } else { '(not found)' })" -ForegroundColor Green
Write-Host "Source:  $sourceDir" -ForegroundColor Green
Write-Host "Output:  $zipName" -ForegroundColor Green
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

# Main files
Copy-Item "$sourceDir\asneris-seo-toolkit.php" $pluginDir
Copy-Item "$sourceDir\readme.txt" $pluginDir
Copy-Item "$sourceDir\uninstall.php" $pluginDir
Copy-Item "$sourceDir\help-content.json" $pluginDir

# Directories
Copy-Item "$sourceDir\includes" $pluginDir -Recurse
Copy-Item "$sourceDir\languages" $pluginDir -Recurse
Copy-Item "$sourceDir\assets" $pluginDir -Recurse
Copy-Item "$sourceDir\build" $pluginDir -Recurse
Copy-Item "$sourceDir\templates" $pluginDir -Recurse

Write-Host "Files copied successfully!" -ForegroundColor Green
Write-Host ""

# Convert to Unix paths for WSL
$wslTempDir = $tempDir -replace '\\', '/' -replace 'D:', '/mnt/d'

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

Write-Host ""
Write-Host "=== SUCCESS! ===" -ForegroundColor Green
Write-Host ""
Write-Host "WordPress.org submission package created:" -ForegroundColor Cyan
Write-Host "$sourceDir\$zipName" -ForegroundColor White
Write-Host ""
Write-Host "Package Contents:" -ForegroundColor Yellow
Write-Host "  - asneris-seo-toolkit.php (main plugin file)" -ForegroundColor Gray
Write-Host "  - readme.txt (WordPress.org readme)" -ForegroundColor Gray
Write-Host "  - help-content.json (help system content)" -ForegroundColor Gray
Write-Host "  - includes/ (12 PHP class files)" -ForegroundColor Gray
Write-Host "  - languages/ (i18n directory)" -ForegroundColor Gray
Write-Host "  - assets/ (CSS and JavaScript)" -ForegroundColor Gray
Write-Host "  - build/ (compiled React components)" -ForegroundColor Gray
Write-Host "  - templates/ (template files)" -ForegroundColor Gray
Write-Host ""
Write-Host "Ready to upload to: https://wordpress.org/plugins/developers/add/" -ForegroundColor Yellow
Write-Host ""
