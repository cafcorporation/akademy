# Optimises every image in brand/backgrounds/ (PNG/JPG, ideally 2048x768, dark on the left)
# into wordpress/plugins/akdemy-bridge/assets/backgrounds/<name>.jpg (quality 82) for the rotating dark bands.
# Usage: powershell -File tools\prepare-backgrounds.ps1
Add-Type -AssemblyName System.Drawing
$root = Split-Path $PSScriptRoot -Parent
$src  = Join-Path $root 'brand\backgrounds'
$dst  = Join-Path $root 'wordpress\plugins\akdemy-bridge\assets\backgrounds'
New-Item -ItemType Directory -Force $dst | Out-Null

$jpeg = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() | Where-Object MimeType -eq 'image/jpeg'
$params = New-Object System.Drawing.Imaging.EncoderParameters 1
$params.Param[0] = New-Object System.Drawing.Imaging.EncoderParameter ([System.Drawing.Imaging.Encoder]::Quality), ([long]82)

Get-ChildItem $src -File | Where-Object { $_.Extension -match '^\.(png|jpe?g)$' } | ForEach-Object {
    $out = Join-Path $dst ($_.BaseName + '.jpg')
    $img = [System.Drawing.Image]::FromFile($_.FullName)
    try { $img.Save($out, $jpeg, $params) } finally { $img.Dispose() }
    Write-Host ("OK {0} -> {1} KB" -f $_.Name, [math]::Round((Get-Item $out).Length / 1KB))
}
