# Renders marketing/social/social.html → marketing/social/out/<lang>/<piece>-<fmt>.png at each network size.
# Usage: powershell -File tools\render-social.ps1
$edge  = "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
$dir   = Join-Path (Split-Path $PSScriptRoot -Parent) 'marketing\social'
$url   = ([Uri](Join-Path $dir 'social.html')).AbsoluteUri
$sizes = @{ square = '1080,1080'; portrait = '1080,1350'; story = '1080,1920'; landscape = '1200,627' }
foreach ($lang in 'es', 'en') {
    $out = Join-Path $dir "out\$lang"
    New-Item -ItemType Directory -Force $out | Out-Null
    foreach ($piece in 'launch', 'hands', 'cert') {
        foreach ($fmt in 'square', 'portrait', 'story', 'landscape') {
            $png = Join-Path $out "$piece-$fmt.png"
            & $edge --headless=new --disable-gpu --hide-scrollbars --force-device-scale-factor=1 --virtual-time-budget=6000 `
                --window-size=$($sizes[$fmt]) --screenshot="$png" "$url`?lang=$lang&piece=$piece&fmt=$fmt" 2>$null | Out-Null
        }
    }
    Write-Host "OK $lang -> $((Get-ChildItem $out -Filter *.png).Count) images"
}
