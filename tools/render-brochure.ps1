# Renders marketing/brochure/brochure.html → brochure-<lang>[-<cover>].pdf (A4).
# Usage: powershell -File tools\render-brochure.ps1 [-Cover dark|light|window|curve|custom]
#   -Cover picks the swappable front cover (default dark). The default keeps the plain
#   brochure-<lang>.pdf name; any other cover appends -<cover> to the file name.
#   custom = your own art at marketing\brochure\img\cover-custom.jpg (see COVER-CUSTOM.md).
param([ValidateSet('dark', 'light', 'window', 'curve', 'custom')] [string]$Cover = 'dark')
$edge = "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
$dir  = Join-Path (Split-Path $PSScriptRoot -Parent) 'marketing\brochure'
$url  = ([Uri](Join-Path $dir 'brochure.html')).AbsoluteUri
$suffix = if ($Cover -eq 'dark') { '' } else { "-$Cover" }
foreach ($lang in 'es', 'en') {
    $pdf = Join-Path $dir "Akdemy-Kpex-brochure-$lang$suffix.pdf"
    & $edge --headless=new --disable-gpu --no-pdf-header-footer --virtual-time-budget=8000 `
        --print-to-pdf="$pdf" "$url`?lang=$lang&cover=$Cover" 2>$null | Out-Null
    Write-Host ("OK {0} ({1} KB)" -f (Split-Path $pdf -Leaf), [math]::Round((Get-Item $pdf).Length / 1KB))
}
