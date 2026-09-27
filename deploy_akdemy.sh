#!/usr/bin/env bash
# One command: validate → commit → push to git → deploy on the Akdemy VPS.
#   ./deploy_akdemy.sh "Mensaje del cambio"      (from Git Bash, in this folder)
# Needs .deploy.env next to this script (never committed):
#   AKDEMY_SSH=deploy@<IP-del-VPS>        SSH login (key-based, no passwords)
#   AKDEMY_BRANCH=main                    optional
set -euo pipefail
cd "$(dirname "$0")"

[ -f .deploy.env ] || { echo "✖ Falta .deploy.env (ver cabecera de este script)"; exit 1; }
# shellcheck disable=SC1091
source .deploy.env
: "${AKDEMY_SSH:?Define AKDEMY_SSH en .deploy.env}"
BRANCH="${AKDEMY_BRANCH:-main}"
MSG="${1:-Deploy Akdemy $(date '+%Y-%m-%d %H:%M')}"

current=$(git rev-parse --abbrev-ref HEAD)
[ "$current" = "$BRANCH" ] || { echo "✖ Estás en '$current'; el despliegue sale de '$BRANCH'"; exit 1; }

echo "→ 1/4 Validando cursos (build local)"
node tools/build-course.mjs >/dev/null

echo "→ 2/4 Commit"
git add -A
if git diff --cached --quiet; then
  echo "  sin cambios nuevos"
else
  git commit -q -m "$MSG"
  echo "  $(git log -1 --format='%h %s')"
fi

echo "→ 3/4 Push a origin/$BRANCH"
git push -q origin "$BRANCH"

echo "→ 4/4 Desplegando en el VPS ($AKDEMY_SSH)"
ssh "$AKDEMY_SSH" "bash /var/www/akdemy-src/scripts/server/deploy_on_vps.sh"

echo "✔ Listo"
