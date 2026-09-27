# WP-CLI against the LocalWP site "AkdemyAI" — no passwords needed (talks to the site's DB directly).
# Usage:  powershell -File scripts\wp.ps1 plugin list
# The Local site must be running (green dot in Local).
$ErrorActionPreference = 'Continue'

$siteId  = 'gxSb-CnoZ'   # AkdemyAI id in %APPDATA%\Local\sites.json
$sitePath = "$env:USERPROFILE\Local Sites\akdemyai\app\public"
$php     = "$env:APPDATA\Local\lightning-services\php-8.2.29+0\bin\win64\php.exe"
$ini     = "$env:APPDATA\Local\run\$siteId\conf\php\php.ini"
$wpcli   = "$env:LOCALAPPDATA\Programs\Local\resources\extraResources\bin\wp-cli\wp-cli.phar"

# PowerShell splits "a,b" into an array; re-join so --fields=a,b reaches WP-CLI intact.
$wpArgs = $args | ForEach-Object { if ($_ -is [array]) { $_ -join ',' } else { $_ } }
# display_startup_errors=0 hides Local's harmless missing php_imagick.dll warning.
& $php -d display_startup_errors=0 -c $ini $wpcli --path="$sitePath" @wpArgs
exit $LASTEXITCODE
