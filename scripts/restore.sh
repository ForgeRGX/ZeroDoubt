#!/usr/bin/env bash
# Uso: scripts/restore.sh backups/zero_doubt_2026-10-07.sql
# Sustituye las tablas actuales por las del backup.
set -euo pipefail
cd "$(dirname "$0")/.."

[ $# -eq 1 ] && [ -f "$1" ] || { echo "Uso: $0 fichero.sql" >&2; exit 1; }
read -r -p "Esto sobrescribirá los datos actuales con $1. ¿Continuar? [s/N] " ans
[[ "$ans" =~ ^[sS]$ ]] || { echo "Cancelado."; exit 0; }

docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -u root "$MYSQL_DATABASE"' < "$1"
echo "Backup restaurado."
