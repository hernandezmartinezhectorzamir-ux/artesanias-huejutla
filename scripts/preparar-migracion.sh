#!/usr/bin/env bash
# Prepara la migración del sitio local a un hosting (p. ej. InfinityFree).
#
# Uso:
#   bash scripts/preparar-migracion.sh https://tusitio.infinityfreeapp.com [ruta-wordpress-local]
#
# Genera en ./migracion/:
#   - bd-<fecha>.sql    Base de datos con las URL ya cambiadas (la base local NO se modifica).
#   - uploads-<fecha>.zip  Imágenes y videos de wp-content/uploads.
#
# Requisitos: WP-CLI (comando `wp` o variable WP_CLI con la ruta al .phar) y zip o PowerShell.
set -euo pipefail

NUEVA_URL="${1:?Indica la URL pública, p. ej. https://tusitio.infinityfreeapp.com}"
NUEVA_URL="${NUEVA_URL%/}"
WP_PATH="${2:-/c/xampp/htdocs/artesanias-wp}"
WP="${WP_CLI:-wp}"
if [[ "$WP" == *.phar ]]; then WP="php $WP"; fi

FECHA=$(date +%Y%m%d-%H%M)
SALIDA="$(pwd)/migracion"
mkdir -p "$SALIDA"

URL_LOCAL=$($WP --path="$WP_PATH" option get home 2>/dev/null | tr -d '\r')
echo "URL local:  $URL_LOCAL"
echo "URL nueva:  $NUEVA_URL"

# 1) Base de datos con URL reemplazadas (también dentro de datos serializados), sin tocar la local.
$WP --path="$WP_PATH" search-replace "$URL_LOCAL" "$NUEVA_URL" --all-tables-with-prefix \
	--precise --skip-columns=guid --export="$SALIDA/bd-$FECHA.sql" --report-changed-only 2>/dev/null
echo "Base de datos: $SALIDA/bd-$FECHA.sql ($(du -h "$SALIDA/bd-$FECHA.sql" | cut -f1))"

# 2) Medios (wp-content/uploads).
ORIGEN="$WP_PATH/wp-content/uploads"
if command -v zip >/dev/null 2>&1; then
	(cd "$ORIGEN/.." && zip -qr "$SALIDA/uploads-$FECHA.zip" uploads)
else
	powershell.exe -NoProfile -Command "Compress-Archive -Path '$(cygpath -w "$ORIGEN" 2>/dev/null || echo "$ORIGEN")' -DestinationPath '$(cygpath -w "$SALIDA/uploads-$FECHA.zip" 2>/dev/null || echo "$SALIDA/uploads-$FECHA.zip")' -Force"
fi
echo "Medios:        $SALIDA/uploads-$FECHA.zip ($(du -h "$SALIDA/uploads-$FECHA.zip" | cut -f1))"

echo
echo "Siguiente: importa el .sql en phpMyAdmin del hosting y sube el contenido de uploads por FTP."
echo "Los archivos de ./migracion/ contienen datos del sitio: no los subas al repositorio (están en .gitignore)."
