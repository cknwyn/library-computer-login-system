# Final Publish script for Library Kiosk System
# This script creates a self-contained, 32-bit (x86) distribution for maximum compatibility.

$runtime = "win-x86"
$distRoot = Join-Path $PSScriptRoot "dist-wpf"
$buildPath = Join-Path $distRoot "build-temp"
$zipPath = Join-Path $distRoot "LibraryKiosk_v1.zip"

# Clean up previous builds
if (Test-Path $distRoot) {
    Remove-Item -Recurse -Force $distRoot -ErrorAction SilentlyContinue
}
New-Item -ItemType Directory -Path $buildPath

Write-Host "--- Publishing LibraryKiosk (x86) ---" -ForegroundColor Cyan
dotnet publish LibraryKiosk/LibraryKiosk.csproj `
    -c Release `
    -r $runtime `
    --self-contained true `
    -p:PublishSingleFile=true `
    -p:PublishReadyToRun=true `
    -p:IncludeNativeLibrariesForSelfExtract=true `
    -o $buildPath

Write-Host "`n--- Publishing KioskGuard (x86) ---" -ForegroundColor Cyan
dotnet publish KioskGuard/KioskGuard.csproj `
    -c Release `
    -r $runtime `
    --self-contained true `
    -p:PublishSingleFile=true `
    -p:PublishReadyToRun=true `
    -p:IncludeNativeLibrariesForSelfExtract=true `
    -o $buildPath

# Ensure appsettings.json has KioskMode = true in the final build
$configPath = Join-Path $buildPath "appsettings.json"
if (Test-Path $configPath) {
    $json = Get-Content $configPath | ConvertFrom-Json
    $json.LibraryKiosk.KioskMode = $true
    $json | ConvertTo-Json | Set-Content $configPath
    Write-Host "Updated appsettings.json: KioskMode = true" -ForegroundColor Yellow
}

Write-Host "`n--- Creating ZIP Distribution ---" -ForegroundColor Cyan
if (Test-Path $zipPath) { Remove-Item $zipPath }
Compress-Archive -Path "$buildPath\*" -DestinationPath $zipPath -Force

Write-Host "`nDistribution ready!" -ForegroundColor Green
Write-Host "ZIP File: $zipPath" -ForegroundColor White
Write-Host "NOTE: Upload the .zip file to GitHub, NOT the individual .exe files." -ForegroundColor Red
