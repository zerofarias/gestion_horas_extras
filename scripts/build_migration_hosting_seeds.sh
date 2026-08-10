#!/usr/bin/env bash
# Seeds post-esquema para hosting.
# PRODE requiere PHP (seed_prode_wc2026.php); el .sql anterior era solo un puntero.

set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/migration_hosting_seeds.sql"

cat > "$OUT" <<'EOF'
-- migration_hosting_seeds.sql
-- Ejecutar DESPUÉS de migration_hosting_full.sql
--
-- PRODE: este archivo NO inserta datos. En el servidor (SSH o local apuntando al hosting):
--   php scripts/seed_prode_wc2026.php
--   php scripts/download_prode_flags.php
--
-- Opcionales (importar por separado en phpMyAdmin si aplican):
--   seed_course_excel_basico.sql
--   seed_mail_paviotti.sql  (editar contraseña SMTP antes de activar)
--   migration_casapav_labels.sql

SELECT 'Ejecute php scripts/seed_prode_wc2026.php para cargar PRODE.' AS siguiente_paso;
EOF

echo "Escrito: $OUT"
