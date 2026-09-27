# Publishes every built course package (build/*.json) to a local LocalWP site.
# Usage: npm run publish:local                 (main site: AkdemyAI-Eduma, http://localhost:10016)
#        powershell -File scripts\publish-local.ps1 -Site tt5   (original Twenty Twenty-Five site, :10010)
param([ValidateSet('eduma', 'tt5')][string]$Site = 'eduma')

$wp = Join-Path $PSScriptRoot $(if ($Site -eq 'tt5') { 'wp.ps1' } else { 'wp-eduma.ps1' })
Write-Host "Target: $Site"
Get-ChildItem (Join-Path $PSScriptRoot '..\build') -Filter *.json | ForEach-Object {
    Write-Host "-> $($_.Name)"
    & $wp akdemy import $_.FullName
    if ($LASTEXITCODE) { exit $LASTEXITCODE }
}
