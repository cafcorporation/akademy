# Renders courses/<slug>/cover/cover.html to cover-<lang>.png (1600x900) with headless Edge.
# Usage: powershell -File tools\render-covers.ps1
$edge = "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
$root = Split-Path $PSScriptRoot -Parent
Get-ChildItem (Join-Path $root 'courses') -Directory | ForEach-Object {
    $html = Join-Path $_.FullName 'cover\cover.html'
    if (-not (Test-Path $html)) { return }
    $url = ([Uri]$html).AbsoluteUri
    foreach ($lang in 'es', 'en') {
        $out = Join-Path $_.FullName "cover\cover-$lang.png"
        & $edge --headless=new --disable-gpu --hide-scrollbars --force-device-scale-factor=1 `
            --window-size=1600,900 --screenshot="$out" "$url`?lang=$lang" 2>$null | Out-Null
        Write-Host "OK $($_.Name)/cover/cover-$lang.png"
    }
}
