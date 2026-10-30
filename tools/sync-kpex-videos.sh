#!/usr/bin/env bash
# Copies the Kpex navigation videos used by the Kpex course lessons into Akdemy's own WordPress space
# (wp-content/uploads/kpex-videos/), so the course plays them from Akdemy, independent of the Kpex server.
#   ./tools/sync-kpex-videos.sh            (from Git Bash, in the Akdemy-AI folder)
# Source: the Kpex project on this PC (KPEX_VIDEOS, default ../Kpex-AI/Kpex2G/public/videos/modules).
# List of videos: courses/kpex-user-course/video/kpex-navigation-videos.json (MP4 + poster + EN/ES subtitles).
# Re-run whenever a video is re-recorded or added. Needs .deploy.env (AKDEMY_SSH), as deploy_akdemy.sh.
set -euo pipefail
cd "$(dirname "$0")/.."
[ -f .deploy.env ] || { echo "✖ Falta .deploy.env"; exit 1; }
# shellcheck disable=SC1091
source .deploy.env
: "${AKDEMY_SSH:?Define AKDEMY_SSH en .deploy.env}"
SRC="${KPEX_VIDEOS:-../Kpex-AI/Kpex2G/public/videos/modules}"
DEST="${AKDEMY_WP:-/var/www/akdemy}/wp-content/uploads/kpex-videos"
LIST=courses/kpex-user-course/video/kpex-navigation-videos.json

files=$(node -e "const d=require('./$LIST');for(const v of Object.values(d))for(const s of ['.mp4','.jpg','_EN.vtt','_ES.vtt'])console.log(v.file+s)")
missing=0
for f in $files; do [ -f "$SRC/$f" ] || { echo "✖ falta $SRC/$f"; missing=1; }; done
[ $missing -eq 0 ] || exit 1

echo "→ 1/2 Carpeta en el servidor: $DEST"
# The folder is created once with sudo (password needed, so it runs in an interactive terminal):
#   ssh -t akdemy-vps "sudo mkdir -p /var/www/akdemy/wp-content/uploads/kpex-videos && sudo chown deploy:www-data /var/www/akdemy/wp-content/uploads/kpex-videos && sudo chmod 2775 /var/www/akdemy/wp-content/uploads/kpex-videos"
if ! ssh "$AKDEMY_SSH" "test -d '$DEST' && test -w '$DEST'"; then
  echo "✖ La carpeta no existe o no tiene permiso de escritura. Créala una vez (pide tu contraseña de sudo):"
  echo "   ssh -t $AKDEMY_SSH \"sudo mkdir -p $DEST && sudo chown deploy:www-data $DEST && sudo chmod 2775 $DEST\""
  exit 1
fi

echo "→ 2/2 Copiando $(echo "$files" | wc -l | tr -d ' ') archivos ($(cd "$SRC" && du -ch $files | tail -1 | cut -f1))"
( cd "$SRC" && tar -cf - $files ) | ssh "$AKDEMY_SSH" "tar -xf - -C '$DEST' && chmod 664 '$DEST'/* && ls '$DEST' | wc -l"
echo "✔ Vídeos en Akdemy: https://akdemy.caf-corporation.com/wp-content/uploads/kpex-videos/"
