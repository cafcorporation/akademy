# Akdemy AI — one-shot local setup: WordPress + LearnPress + Akdemy Bridge + Application Password.
# Usage (from the Akdemy-AI folder):  powershell -File scripts\setup.ps1
$ErrorActionPreference = 'Continue'  # native stderr must not abort (PS 5.1)
Set-Location (Split-Path $PSScriptRoot -Parent)

$cfg = @{}
Get-Content .env | Where-Object { $_ -match '^\s*([A-Z_]+)=(.*)$' } | ForEach-Object { $cfg[$Matches[1]] = $Matches[2] }

function Wp { docker compose run --rm -T cli wp @args }

Write-Host '1/5 Starting containers...'
docker compose up -d
if ($LASTEXITCODE) { throw 'docker compose up failed' }

Write-Host '2/5 Waiting for WordPress files...'
for ($i = 0; $i -lt 60; $i++) {
    docker compose exec -T wordpress test -f /var/www/html/wp-config.php 2>$null
    if (-not $LASTEXITCODE) { break }
    Start-Sleep -Seconds 2
}

Write-Host '3/5 Installing WordPress...'
docker compose run --rm -T cli wp core is-installed 2>$null
if ($LASTEXITCODE) {
    Wp core install --url=$($cfg.WP_URL) --title="$($cfg.WP_TITLE)" `
        --admin_user=$($cfg.WP_ADMIN_USER) --admin_password=$($cfg.WP_ADMIN_PASSWORD) `
        --admin_email=$($cfg.WP_ADMIN_EMAIL) --skip-email
}
Wp rewrite structure '/%postname%/' --hard

Write-Host '4/5 Installing LearnPress + Akdemy Bridge...'
Wp plugin install learnpress --activate
Wp plugin activate akdemy-bridge
Wp plugin delete hello akismet 2>$null

Write-Host '5/5 Application Password for Claude...'
if (-not $cfg.WP_APP_PASSWORD) {
    $app = (Wp user application-password create $cfg.WP_ADMIN_USER akdemy-claude --porcelain | Select-Object -Last 1).Trim()
    (Get-Content .env) -replace '^WP_APP_PASSWORD=.*$', "WP_APP_PASSWORD=$app" | Set-Content .env -Encoding ascii
    $cfg.WP_APP_PASSWORD = $app
}

$auth = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("$($cfg.WP_ADMIN_USER):$($cfg.WP_APP_PASSWORD)"))
$ping = Invoke-RestMethod "$($cfg.WP_URL)/wp-json/akdemy/v1/ping" -Headers @{ Authorization = "Basic $auth" }
Write-Host "`nReady: $($cfg.WP_URL)  (admin: $($cfg.WP_URL)/wp-admin, user '$($cfg.WP_ADMIN_USER)', password in .env)"
$ping | Format-List
