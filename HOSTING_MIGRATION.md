# Migración BD hosting — `paviotti_lanaturaleza`

Guía para pasar del esquema RRHH primitivo (solo `users`, `overtime_entries`, `closures`, `requests`) al esquema completo de la app, **sin borrar tablas legacy** del cementerio.

## 1. Backup (obligatorio)

En cPanel → phpMyAdmin → base `paviotti_lanaturaleza`:

1. Pestaña **Exportar** → método **Rápido** o **Personalizado** → formato **SQL** → **Continuar**.
2. Guardar el archivo con fecha, ej. `paviotti_lanaturaleza_backup_2026-06-09.sql`.
3. Verificar que el archivo pesa varios MB y contiene `INSERT INTO overtime_entries`.

No continuar sin backup.

## 2. Migración del esquema (una importación)

1. Subir al hosting (FTP o repo) estos archivos del proyecto:
   - [`migration_hosting_full.sql`](migration_hosting_full.sql)
   - [`migration_hosting_seeds.sql`](migration_hosting_seeds.sql) (después del full)
2. phpMyAdmin → seleccionar `paviotti_lanaturaleza` → **Importar**.
3. Importar **`migration_hosting_full.sql`** (puede tardar 1–3 min).
4. Si termina con `migration_hosting_full completada.`, ejecutar seeds PRODE:

```bash
php scripts/seed_prode_wc2026.php
php scripts/download_prode_flags.php
```

(Si no hay SSH en el hosting, correr esos comandos en local con `config.php` apuntando a la BD del hosting, o pedir acceso SSH/cron.)

5. Validar con [`scripts/validate_hosting_migration.sql`](scripts/validate_hosting_migration.sql) y [`scripts/hosting_smoke_test.php`](scripts/hosting_smoke_test.php).

### Regenerar scripts (local)

```bash
bash scripts/build_migration_hosting_full.sh
bash scripts/build_migration_hosting_seeds.sh
```

### Migraciones omitidas a propósito

| Archivo | Motivo |
|---------|--------|
| `migration_attendance_justifications_v2.sql` | Ya incluido en #6 |
| `migration_users_default_ecofarma.sql` | Movería todos los usuarios a Ecofarma |
| `migration_pay_stub_admin_note.sql` | Ya en #20 |
| `migration_shift_swaps.sql` | Esquema obsoleto |
| `migration_notifications_fix_fk.sql` | Solo si #20 falló a medias |
| `migration_notifications_dedup_index.sql` | Omitir: #20 ya incluye `uq_user_notif_type_ref` |
| `migration_areas_scope_unique.sql` | Opcional; puede chocar con #18 |

## 3. Validación SQL

Importar o pegar [`scripts/validate_hosting_migration.sql`](scripts/validate_hosting_migration.sql).

Comprobar:

- `overtime_entries_count` ≈ 350+ (datos históricos intactos)
- `closures_count` = 16
- `users_count` = 27 (o el total actual en producción)
- Existen tablas `prode_%`, `system_settings`, `cp_%`
- `shift_swaps.proposer_schedule_id` existe

## 4. Seeds opcionales (phpMyAdmin)

| Archivo | Cuándo |
|---------|--------|
| [`seed_course_excel_basico.sql`](seed_course_excel_basico.sql) | Curso demo capacitación |
| [`seed_mail_paviotti.sql`](seed_mail_paviotti.sql) | SMTP (editar contraseña antes de activar) |
| [`migration_casapav_labels.sql`](migration_casapav_labels.sql) | Nombres legibles tareas CP |

| Recurso | Acción |
|---------|--------|
| PRODE equipos/partidos | `php scripts/seed_prode_wc2026.php` (ver §2) |
| PRODE banderas | `php scripts/download_prode_flags.php` |

## 5. Despliegue de código

1. Subir código PHP actual (git pull / FTP).
2. En hosting: `composer install --no-dev` (PHPMailer).
3. Configurar [`app/config/config.php`](app/config/config.php) con credenciales del hosting (no las de XAMPP).
   - **URLROOT** se detecta solo del dominio (`https://paviotti.com.ar/gestion_horas_extras`). Si el redirect va a localhost, subí este `config.php` actualizado o creá `config.local.php` con:
     ```php
     define('URLROOT', 'https://paviotti.com.ar/gestion_horas_extras');
     ```
4. Permisos escritura: `public/uploads/` y subcarpetas (`avatars`, `pay_stubs`, `justifications`, …).
5. Panel **Configuración** → cambiar PIN inicial (`lautaro`).

### Ajuste La Naturaleza

Tras migración, usuarios quedan en `company_id = 1` (La Naturaleza). Opcional:

```sql
UPDATE system_settings
SET setting_value = 'La Naturaleza'
WHERE setting_key = 'default_company_name';
```

## 6. Smoke test

- [ ] Login admin y empleado
- [ ] Listado horas extras / cierres históricos
- [ ] Solicitudes (vacaciones, etc.)
- [ ] Admin: usuarios, asistencia, configuración (sin error SQL)
- [ ] PRODE visible si edición en estado `open`

## 7. Si algo falla

1. Restaurar backup completo desde phpMyAdmin → Importar.
2. Anotar el **STEP N** del error en `migration_hosting_full.sql`.
3. Corregir y re-ejecutar solo desde ese paso (o regenerar script y repetir en copia de prueba).
