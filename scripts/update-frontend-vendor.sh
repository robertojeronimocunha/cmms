#!/usr/bin/env bash
# Atualiza dependências JS/CSS do frontend para uso offline.
# Executar com internet: bash scripts/update-frontend-vendor.sh
# Raiz do repositório = diretório pai de scripts/

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEST="$ROOT/frontend/public/assets/vendor"

BOOTSTRAP_VER="5.3.3"
FA_VER="6.5.2"
DT_VER="1.13.8"
JQUERY_VER="3.7.1"
HTMX_VER="1.9.12"
APEX_VER="3.54.1"

mkdir -p "$DEST/bootstrap/$BOOTSTRAP_VER/css" \
         "$DEST/bootstrap/$BOOTSTRAP_VER/js" \
         "$DEST/font-awesome/$FA_VER/css" \
         "$DEST/font-awesome/$FA_VER/webfonts" \
         "$DEST/datatables/$DT_VER/css" \
         "$DEST/datatables/$DT_VER/js" \
         "$DEST/jquery/$JQUERY_VER" \
         "$DEST/htmx/$HTMX_VER" \
         "$DEST/apexcharts/$APEX_VER"

dl() {
  local url="$1" out="$2"
  echo "  -> $out"
  curl -fsSL --connect-timeout 30 --retry 2 -o "$out" "$url"
}

echo "A descarregar para $DEST ..."

dl "https://cdn.jsdelivr.net/npm/bootstrap@${BOOTSTRAP_VER}/dist/css/bootstrap.min.css" \
   "$DEST/bootstrap/$BOOTSTRAP_VER/css/bootstrap.min.css"
dl "https://cdn.jsdelivr.net/npm/bootstrap@${BOOTSTRAP_VER}/dist/js/bootstrap.bundle.min.js" \
   "$DEST/bootstrap/$BOOTSTRAP_VER/js/bootstrap.bundle.min.js"

dl "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/${FA_VER}/css/all.min.css" \
   "$DEST/font-awesome/$FA_VER/css/all.min.css"

for f in fa-brands-400.woff2 fa-regular-400.woff2 fa-solid-900.woff2 fa-v4compatibility.woff2 \
         fa-brands-400.ttf fa-regular-400.ttf fa-solid-900.ttf fa-v4compatibility.ttf; do
  dl "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/${FA_VER}/webfonts/${f}" \
     "$DEST/font-awesome/$FA_VER/webfonts/$f"
done

dl "https://cdn.datatables.net/${DT_VER}/css/dataTables.bootstrap5.min.css" \
   "$DEST/datatables/$DT_VER/css/dataTables.bootstrap5.min.css"
dl "https://cdn.datatables.net/${DT_VER}/js/jquery.dataTables.min.js" \
   "$DEST/datatables/$DT_VER/js/jquery.dataTables.min.js"
dl "https://cdn.datatables.net/${DT_VER}/js/dataTables.bootstrap5.min.js" \
   "$DEST/datatables/$DT_VER/js/dataTables.bootstrap5.min.js"

dl "https://cdn.jsdelivr.net/npm/jquery@${JQUERY_VER}/dist/jquery.min.js" \
   "$DEST/jquery/$JQUERY_VER/jquery.min.js"

dl "https://cdn.jsdelivr.net/npm/htmx.org@${HTMX_VER}/dist/htmx.min.js" \
   "$DEST/htmx/$HTMX_VER/htmx.min.js"

dl "https://cdn.jsdelivr.net/npm/apexcharts@${APEX_VER}/dist/apexcharts.min.js" \
   "$DEST/apexcharts/$APEX_VER/apexcharts.min.js"

echo "Concluído. Reinicie o PHP-FPM/Nginx se necessário (ficheiros estáticos não precisam)."
