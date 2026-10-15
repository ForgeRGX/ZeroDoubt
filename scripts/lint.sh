#!/usr/bin/env bash
# Comprueba la sintaxis de todos los .php y las traducciones usando el PHP del contenedor,
# SIN necesidad de reconstruir la imagen ni de tener la base de datos en marcha.
# Úsalo antes de `docker compose up -d --build`.
set -euo pipefail
cd "$(dirname "$0")/.."

docker compose run --rm --no-deps -v "$PWD":/app:ro -w /app web bash -c '
  fail=0
  while IFS= read -r -d "" f; do
    if ! out=$(php -l "$f" 2>&1); then echo "$out"; fail=1; fi
  done < <(find . -name "*.php" -not -path "./.git/*" -print0)
  [ "$fail" -eq 0 ] || { echo "✖ Hay errores de sintaxis."; exit 1; }
  echo "✔ Sintaxis PHP correcta."
  php scripts/check_translations.php
'
