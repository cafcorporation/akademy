# ONE-TIME migration: packs the LocalWP site "AkdemyAI-Eduma" for the first install on the VPS.
#   build/migration/akdemy.sql         database (URLs still http://localhost:10016; the VPS script replaces them)
#   build/migration/wp-content.tar.gz  theme, plugins, uploads, languages (without akdemy-bridge: on the VPS it comes from git)
# Usage (Local site running):  powershell -File tools\export-akdemy-site.ps1
$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$site = "$env:USERPROFILE\Local Sites\akdemyai-eduma\app\public"
$out  = Join-Path $root 'build\migration'
New-Item -ItemType Directory -Force $out | Out-Null

& (Join-Path $root 'scripts\wp-eduma.ps1') db export (Join-Path $out 'akdemy.sql')
if ($LASTEXITCODE -ne 0) { throw 'Database export failed (is the Local site running?)' }

# Windows tar (bsdtar). akdemy-bridge is a junction to this repo: excluded, the VPS links it from the git checkout.
& tar.exe -czf (Join-Path $out 'wp-content.tar.gz') -C $site `
    --exclude 'wp-content/plugins/akdemy-bridge' --exclude 'wp-content/cache' --exclude 'wp-content/upgrade' --exclude '*.log' `
    wp-content
if ($LASTEXITCODE -ne 0) { throw 'wp-content archive failed' }

Get-ChildItem $out | ForEach-Object { Write-Host ("OK {0}  {1:N1} MB" -f $_.Name, ($_.Length / 1MB)) }
