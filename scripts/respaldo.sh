#!/usr/bin/env bash
# Respaldo diario de Artesanías Huejutla (programar con cron en el servidor):
#   0 3 * * * /ruta/scripts/respaldo.sh /var/www/artesanias >> ~/respaldos/respaldo.log 2>&1
# Conserva 14 respaldos de base de datos y 7 de archivos subidos.
set -euo pipefail
WP_PATH="${1:?Ruta de WordPress}"
DESTINO="${2:-$HOME/respaldos}"
FECHA=$(date +%Y%m%d-%H%M%S)
mkdir -p "$DESTINO"
cd "$WP_PATH"

wp db export "$DESTINO/bd-$FECHA.sql" --quiet
gzip "$DESTINO/bd-$FECHA.sql"
tar -czf "$DESTINO/uploads-$FECHA.tar.gz" wp-content/uploads

ls -1t "$DESTINO"/bd-*.sql.gz      | tail -n +15 | xargs -r rm --
ls -1t "$DESTINO"/uploads-*.tar.gz | tail -n +8  | xargs -r rm --
echo "$(date -Is) Respaldo completo: bd-$FECHA.sql.gz, uploads-$FECHA.tar.gz"
# Recomendado: copiar $DESTINO a almacenamiento externo (rclone a S3/Drive) fuera del servidor.
