#!/usr/bin/env bash
# Backup nocturno: dump de MySQL + tar de archivos -> bucket GCS.
# Uso: BACKUP_BUCKET=gs://... /opt/sigav/deploy/backup.sh
set -euo pipefail

APP_DIR="/opt/sigav"
cd "$APP_DIR"

# Toma BACKUP_BUCKET del entorno o del .env del proyecto.
if [ -z "${BACKUP_BUCKET:-}" ]; then
  BACKUP_BUCKET="$(grep -E '^BACKUP_BUCKET=' .env | cut -d= -f2-)"
fi
if [ -z "${BACKUP_BUCKET:-}" ]; then
  echo "ERROR: BACKUP_BUCKET no definido" >&2; exit 1
fi

TS="$(date +%Y%m%d-%H%M%S)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

# 1) Dump lógico de la base (latin1, consistente para InnoDB)
docker exec sigav_db sh -c \
  'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --default-character-set=latin1 "$MYSQL_DATABASE"' \
  | gzip > "$TMP/db-$TS.sql.gz"

# 2) Archivos escribibles (los que la app genera en runtime)
tar -czf "$TMP/files-$TS.tar.gz" \
  public/AFIP public/presupuesto public/notas_credito public/branchs \
  public/clientes public/cobros storage 2>/dev/null || true

# 3) Subir al bucket
gcloud storage cp "$TMP/db-$TS.sql.gz"    "$BACKUP_BUCKET/db/"
gcloud storage cp "$TMP/files-$TS.tar.gz" "$BACKUP_BUCKET/files/"

echo "Backup OK: $TS -> $BACKUP_BUCKET"
