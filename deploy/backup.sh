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

# 1b) Mercado Artesanal: segunda base en el MISMO MySQL (ver deploy/mercado/).
#     Solo si la base existe; no rompe el backup de Acantilado si no está.
if docker exec sigav_db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SHOW DATABASES LIKE '"'"'mercado'"'"'"' 2>/dev/null | grep -q mercado; then
  docker exec sigav_db sh -c \
    'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --default-character-set=latin1 mercado' \
    | gzip > "$TMP/db-mercado-$TS.sql.gz"
fi

# 2) Archivos escribibles (los que la app genera en runtime)
tar -czf "$TMP/files-$TS.tar.gz" \
  public/AFIP public/presupuesto public/notas_credito public/branchs \
  public/clientes public/cobros storage 2>/dev/null || true

# 2b) Archivos de la instancia de Mercado (checkout propio en /opt/mercado)
if [ -d /opt/mercado ]; then
  tar -C /opt/mercado -czf "$TMP/files-mercado-$TS.tar.gz" \
    public/presupuesto public/notas_credito public/branchs public/clientes \
    public/cobros public/facturas public/productos public/assets/perfil \
    public/assets/sucursales storage 2>/dev/null || true
fi

# 3) Subir al bucket
gcloud storage cp "$TMP/db-$TS.sql.gz"    "$BACKUP_BUCKET/db/"
gcloud storage cp "$TMP/files-$TS.tar.gz" "$BACKUP_BUCKET/files/"
[ -f "$TMP/db-mercado-$TS.sql.gz" ]    && gcloud storage cp "$TMP/db-mercado-$TS.sql.gz"    "$BACKUP_BUCKET/db/"
[ -f "$TMP/files-mercado-$TS.tar.gz" ] && gcloud storage cp "$TMP/files-mercado-$TS.tar.gz" "$BACKUP_BUCKET/files/"

echo "Backup OK: $TS -> $BACKUP_BUCKET"
