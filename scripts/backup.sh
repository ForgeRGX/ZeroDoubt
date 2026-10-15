#!/usr/bin/env bash
# Crea backups/cuentas_claras_AAAA-MM-DD.sql. Si ya existe, añade la hora para no sobrescribir.
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p backups

FILE="backups/cuentas_claras_$(date +%F).sql"
[ -e "$FILE" ] && FILE="backups/cuentas_claras_$(date +%F_%H%M%S).sql"

# Las credenciales se leen dentro del contenedor (no hace falta cargar el .env)
if docker compose exec -T db sh -c \
  'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -u root --single-transaction --routines --no-tablespaces "$MYSQL_DATABASE"' \
  > "$FILE"; then
  echo "Backup creado: $FILE ($(du -h "$FILE" | cut -f1))"
else
  rm -f "$FILE"
  echo "Error: no se pudo crear el backup. ¿Está en marcha el contenedor db?" >&2
  exit 1
fi
