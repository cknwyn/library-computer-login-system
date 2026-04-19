# Publish script for Library Kiosk System
# This script creates a self-contained, single-file distribution for 64-bit Windows.

$distPath = Join-Path $PSScriptRoot "dist-wpf"
$runtime = "win-x64" # Target 64-bit Windows. Change to win-x86 if needed.

# Create dist directory if it doesn't exist
if (!(Test-Path $distPath)) {
    New-Item -ItemType Directory -Path $distPath
}

Write-Host "--- Publishing LibraryKiosk ---" -ForegroundColor Cyan
dotnet publish LibraryKiosk/LibraryKiosk.csproj `
    -c Release `
    -r $runtime `
    --self-contained true `
    -p:PublishSingleFile=true `
    -p:PublishReadyToRun=true `
    -p:IncludeNativeLibrariesForSelfExtract=true `
    -o (Join-Path $distPath "LibraryKiosk")

Write-Host "`n--- Publishing KioskGuard ---" -ForegroundColor Cyan
dotnet publish KioskGuard/KioskGuard.csproj `
    -c Release `
    -r $runtime `
    --self-contained true `
    -p:PublishSingleFile=true `
    -p:PublishReadyToRun=true `
    -p:IncludeNativeLibrariesForSelfExtract=true `
    -o (Join-Path $distPath "KioskGuard")

Write-Host "`nDistribution ready in: $distPath" -ForegroundColor Green
