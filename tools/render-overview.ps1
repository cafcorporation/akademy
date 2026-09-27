# Renders courses/<slug>/overview/dashboard.html -> overview/dashboard-<lang>.png (annotated Kpex dashboard for the course overview).
# Usage: powershell -File tools\render-overview.ps1 [-Slug kpex-user-course]
param([string]$Slug = 'kpex-user-course')
$edge = "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
$dir  = Join-Path (Split-Path $PSScriptRoot -Parent) "courses\$Slug\overview"
$url  = ([Uri](Join-Path $dir 'dashboard.html')).AbsoluteUri
foreach ($lang in 'es', 'en') {
    $png = Join-Path $dir "dashboard-$lang.png"
    if (Test-Path $png) { Remove-Item $png }
    # Edge occasionally exits without writing the file: retry.
    for ($i = 0; $i -lt 3 -and -not (Test-Path $png); $i++) {
        & $edge --headless=new --disable-gpu --hide-scrollbars --force-device-scale-factor=1 --virtual-time-budget=6000 `
            --window-size=1600,1440 --screenshot="$png" "$url`?lang=$lang" 2>$null | Out-Null
    }
    Write-Host ("OK dashboard-{0}.png ({1} KB)" -f $lang, [math]::Round((Get-Item $png).Length / 1KB))
}
