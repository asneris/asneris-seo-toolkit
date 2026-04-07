# WordPress.org Submission Package Creator
# Creates Unix-compatible ZIP for asneris-seo-toolkit

$ErrorActionPreference = "Stop"

Write-Host "=== WordPress.org Package Creator ===" -ForegroundColor Cyan
Write-Host ""

# Paths
$sourceDir = "D:\Dev\sco\seo-clarity-first-plugin"
$tempDir = "$sourceDir\temp-package"
$pluginDir = "$tempDir\asneris-seo-toolkit"

# Check source exists
if (-not (Test-Path $sourceDir)) {
    Write-Host "ERROR: Source directory not found: $sourceDir" -ForegroundColor Red
    exit 1
}

Write-Host "Source: $sourceDir" -ForegroundColor Green
Write-Host ""

# Clean all previous build artifacts
Write-Host "Cleaning previous build artifacts..." -ForegroundColor Yellow
if (Test-Path "$sourceDir\asneris-seo-toolkit-0.1.2.zip") {
    Remove-Item "$sourceDir\asneris-seo-toolkit-0.1.2.zip" -Force
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
$wslCommand = "cd '$wslTempDir' && zip -r asneris-seo-toolkit-0.1.2.zip asneris-seo-toolkit/"
wsl bash -c $wslCommand

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: ZIP creation failed!" -ForegroundColor Red
    exit 1
}

# Move ZIP to source directory
Move-Item "$tempDir\asneris-seo-toolkit-0.1.2.zip" "$sourceDir\asneris-seo-toolkit-0.1.2.zip" -Force

# Clean up temp directory
Write-Host "Cleaning up..." -ForegroundColor Yellow
Remove-Item $tempDir -Recurse -Force

Write-Host ""
Write-Host "=== SUCCESS! ===" -ForegroundColor Green
Write-Host ""
Write-Host "WordPress.org submission package created:" -ForegroundColor Cyan
Write-Host "$sourceDir\asneris-seo-toolkit-0.1.2.zip" -ForegroundColor White
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
