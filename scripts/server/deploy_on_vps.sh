#!/usr/bin/env bash
# Runs ON THE VPS (called over SSH by deploy_akdemy.sh). Brings the git checkout up to date and
# republishes the courses in place (same IDs, enrolments and certificates are kept).
#
# Layout on the VPS:
#   /var/www/akdemy-src   git checkout of this repo (owner: deploy, group: www-data)
#   /var/www/akdemy       WordPress; wp-content/plugins/akdemy-bridge -> /var/www/akdemy-src/wordpress/plugins/akdemy-bridge
set -euo pipefail

SRC="${AKDEMY_SRC:-/var/www/akdemy-src}"
WP_PATH="${AKDEMY_WP:-/var/www/akdemy}"
BRANCH="${AKDEMY_BRANCH:-main}"
wp() { sudo -u www-data /usr/local/bin/wp --path="$WP_PATH" "$@"; }

cd "$SRC"
echo "→ git: updating $SRC to origin/$BRANCH"
git fetch --quiet origin "$BRANCH"
git checkout --quiet "$BRANCH"
git merge --ff-only --quiet "origin/$BRANCH"
echo "  at $(git log -1 --format='%h %s')"

echo "→ node: installing build dependencies"
npm ci --omit=dev --no-audit --no-fund --silent

echo "→ build: course packages"
node tools/build-course.mjs

echo "→ WordPress: publishing courses in place"
for pkg in build/*.json; do
  wp akdemy update-course "$SRC/$pkg"
done

echo "→ WordPress: caches, permalinks, translations"
wp cache flush
wp rewrite flush
wp language core update --quiet || true
wp language plugin update --all --quiet || true

echo "✔ Akdemy deployed: $(wp option get home)"
