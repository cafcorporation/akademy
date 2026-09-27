# Renders certificates/certificate.html (1684x1191) with headless Edge:
#   certificates/cert-bg-<lang>.png       template background for the LearnPress Certificates add-on
#   certificates/cert-preview-<lang>.png  dummy student preview (for approval)
# Usage: powershell -File tools\render-certificates.ps1
$edge = "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
$dir  = Join-Path (Split-Path $PSScriptRoot -Parent) 'certificates'
$url  = ([Uri](Join-Path $dir 'certificate.html')).AbsoluteUri
foreach ($lang in 'es', 'en') {
    foreach ($mode in 'bg', 'preview') {
        $name = if ($mode -eq 'bg') { "cert-bg-$lang.png" } else { "cert-preview-$lang.png" }
        & $edge --headless=new --disable-gpu --hide-scrollbars --force-device-scale-factor=1 --virtual-time-budget=4000 `
            --window-size=1684,1191 --screenshot="$(Join-Path $dir $name)" "$url`?lang=$lang&mode=$mode" 2>$null | Out-Null
        Write-Host "OK certificates/$name"
    }
}
