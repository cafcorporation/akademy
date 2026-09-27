# Renders marketing/brochure/brochure.html → brochure-<lang>.pdf (A4) + a PNG preview of the cover.
# Usage: powershell -File tools\render-brochure.ps1
$edge = "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
$dir  = Join-Path (Split-Path $PSScriptRoot -Parent) 'marketing\brochure'
$url  = ([Uri](Join-Path $dir 'brochure.html')).AbsoluteUri
foreach ($lang in 'es', 'en') {
    $pdf = Join-Path $dir "Akdemy-Kpex-brochure-$lang.pdf"
    & $edge --headless=new --disable-gpu --no-pdf-header-footer --virtual-time-budget=8000 `
        --print-to-pdf="$pdf" "$url`?lang=$lang" 2>$null | Out-Null
    Write-Host ("OK {0} ({1} KB)" -f (Split-Path $pdf -Leaf), [math]::Round((Get-Item $pdf).Length / 1KB))
}
