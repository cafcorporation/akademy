# ONE-TIME migration: packs the LocalWP site "AkdemyAI-Eduma" for the first install on the VPS.
#   build/migration/akdemy.sql         database (URLs still http://localhost:10016; the VPS script replaces them)
#   build/migration/wp-content.tar.gz  theme, plugins, uploads, languages (without akdemy-bridge: on the VPS it comes from git)
# Usage (Local site running):  powershell -File tools\export-akdemy-site.ps1
$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$site = "$env:USERPROFILE\Local Sites\akdemyai-eduma\app\public"
$out  = Join-Path $root 'build\migration'
New-Item -ItemType Directory -Force $out | Out-Null

# wp db export needs mysqldump: use the one bundled with Local for this site's MySQL (8.4).
$mysqlBin = Get-ChildItem "$env:APPDATA\Local\lightning-services" -Directory -Filter 'mysql-8.4*' |
    ForEach-Object { Join-Path $_.FullName 'bin\win64\bin' } | Select-Object -First 1
if (-not $mysqlBin) { throw 'mysqldump from Local (mysql-8.4) not found' }
$env:PATH = "$mysqlBin;$env:PATH"

$sql = Join-Path $out 'akdemy.sql'
# Local's MySQL listens on its own port (sites.json); plain "localhost" would hit another MySQL (e.g. XAMPP).
$port = ((Get-Content "$env:APPDATA\Local\sites.json" -Raw | ConvertFrom-Json).TtkwUj68O.services.mysql.ports.MYSQL)[0]
& (Join-Path $root 'scripts\wp-eduma.ps1') db export $sql --host=127.0.0.1 --port=$port
if ($LASTEXITCODE -ne 0) { throw 'Database export failed (is the Local site running?)' }
# The VPS runs MariaDB 10.11, which does not know MySQL 8's default collation.
$text = [IO.File]::ReadAllText($sql)
[IO.File]::WriteAllText($sql, $text.Replace('utf8mb4_0900_ai_ci', 'utf8mb4_unicode_ci'), (New-Object Text.UTF8Encoding $false))

# Windows tar (bsdtar). akdemy-bridge is a junction to this repo: excluded, the VPS links it from the git checkout.
& tar.exe -czf (Join-Path $out 'wp-content.tar.gz') -C $site `
    --exclude 'wp-content/plugins/akdemy-bridge' --exclude 'wp-content/cache' --exclude 'wp-content/upgrade' --exclude '*.log' `
    wp-content
if ($LASTEXITCODE -ne 0) { throw 'wp-content archive failed' }

Get-ChildItem $out | ForEach-Object { Write-Host ("OK {0}  {1:N1} MB" -f $_.Name, ($_.Length / 1MB)) }
