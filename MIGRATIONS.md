# Migraciones SQL — orden de ejecución

> Arquitectura, roles, relaciones empleado↔marcaciones↔vacaciones: ver **`GUIA_MAESTRA.md`**.

No hay runner automático. Ejecutar en **phpMyAdmin** o cliente MySQL, en orden, sobre la base `gestion_horas_extras` (o la que uses).

## Orden recomendado

| # | Archivo | Notas |
|---|---------|--------|
| 1 | `migration_v2.sql` | Schema base (usuarios, turnos, companies iniciales). **No** usar su `shift_swaps` antiguo si vas a aplicar el fix. |
| 2 | `migration_companies_grupo.sql` | Empresas del grupo (Ecofarma, Servicios Sociales, etc.) |
| 3 | `migration_marcaciones_cache.sql` | Cache de marcaciones API |
| 3b | `migration_clock_events_device.sql` | **Tras #1 o #3:** `device_name`, `direction`, `direction_label` en `clock_events` (ficha empleado / sync) |
| 4 | `migration_users_profile_extended.sql` | Email, teléfono, dirección, DNI, CUIL, sexo, género, contacto emergencia |
| 5 | `migration_attendance_summary.sql` | Resumen asistencia |
| 6 | `migration_attendance_justifications.sql` | Justificaciones v1 |
| 7 | `migration_attendance_justifications_v2.sql` | Solo si v1 no incluyó todos los campos; puede fallar si ya está aplicado |
| 8 | `migration_requests_admin_review.sql` | Campos revisión admin en `requests` |
| 9 | `migration_shift_swaps_fix.sql` | **Obligatorio** para cambio de turno (`proposer_schedule_id`, etc.) |
| 10 | `migration_shift_swaps_accepter_null.sql` | `accepter_schedule_id` nullable hasta aprobar |
| 11 | `migration_users_default_ecofarma.sql` | **Opcional / peligroso** en multi-empresa: asigna todos los usuarios a Ecofarma |
| 12 | `migration_learning.sql` | Áreas, cursos, lecciones, quiz, estrellas, premios, tareas |
| 13 | `seed_course_excel_basico.sql` | **Opcional:** curso semilla Excel + premio 100 estrellas (tras #12) |
| 14 | `migration_learning_enrich.sql` | Materiales, anotaciones instructor, preguntas/sugerencias, notas empleado |
| 15 | `migration_learning_quiz_bonus.sql` | Quiz aleatorio por empleado + bonus primer completador |
| 16 | `migration_user_login_logs.sql` | Registro de cada login exitoso (solo consulta SQL, sin pantalla) |
| 17 | `migration_learning_reviews.sql` | Reseñas de curso (me gusta / no me gusta + comentario opcional) |
| 18 | `migration_areas_global.sql` | Áreas: `company_id` NULL = todas las empresas; unifica duplicados por nombre |
| 19 | `migration_areas_scope_unique.sql` | **Opcional:** mismo nombre de área permitido en empresas distintas (`UNIQUE name+company_id`) |
| 20 | `migration_notifications_paystubs.sql` | Avisos modales, notificaciones campana, recibos de sueldo, SMTP (`mail_settings`). Requiere `composer install` para PHPMailer. |
| 20b | `migration_notifications_dedup_index.sql` | **Opcional:** índice único `(user_id, type, reference_id)` para evitar duplicados de curso/recibo a nivel BD. |
| 20c | `migration_pay_stub_admin_note.sql` | **Tras #20:** columna `admin_note` en `pay_stubs` (nota del administrador al cargar el recibo). |
| 21 | `migration_employee_incidents.sql` | Incidencias disciplinarias por empleado (solo admin): llamados, sanciones, suspensiones, adjuntos. |
| 22 | `migration_collective_agreements.sql` | Convenios CEC, períodos Oct–Sep, saldo multi-período, `hire_date`. |
| 23 | `migration_schedule_vacation_types.sql` | **Tras #22:** tipos `vacation` y `leave` en `employee_schedules`. |
| 24 | `migration_users_probation_date.sql` | **Tras #22:** columna `probation_start_date`. En phpMyAdmin preferir **Importar** el archivo (no copiar/pegar comentarios `--`, anulan el ALTER). |
| 25 | `migration_role_supervisor.sql` | Rol `supervisor` (jefe de área) en `users.role` |
| 26 | `migration_areas_agreement.sql` | Convenio por área (`areas.agreement_id`) |
| 27 | `migration_users_plex_operator.sql` | Vínculo operador Ecofarma (`users.plex_operator_name`) |
| 28 | `migration_peer_stars.sql` | Reconocimiento entre pares (saldo aparte de cursos) |
| 29 | `migration_surveys.sql` | Encuestas + extensión `user_notifications.type` con `survey` (tras #20) |
| 30 | `migration_casapav_tasks.sql` | Extras por tarea Casa Paviotti (`cp_*`, `companies.extras_mode`) |
| 31 | `migration_casapav_phase_b.sql` | **Tras #30:** 11 tareas activas, tarifas extendidas, externas, catálogos semilla |
| — | `migration_casapav_labels.sql` | **Opcional:** nombres legibles en `cp_task_types` (alineado con UI empleado) |
| — | `migration_companies_logo.sql` | **Opcional:** `companies.logo_path` + logos en `public/img/companies/` |
| — | `scripts/import_cp_legacy.php` | **Opcional (CLI):** import histórico desde BD legacy `datos`/`login`. Ver `GUIA_MAESTRA.md` §8.6 |
| — | `seed_mail_paviotti.sql` | **Tras #20:** carga SMTP Paviotti (usuario, contraseña, activar envío). Ejecutar en phpMyAdmin. |
| 32 | `migration_system_settings.sql` | Panel **Configuración** (admin + PIN). Variables: correo (vía `mail_settings`), Casa Paviotti (extintos, límite select), asistencia, API relojes, Ecofarma. **PIN inicial:** `lautaro` — cambiar en Seguridad. |
| 32b | `migration_system_settings_employee_portal.sql` | **Tras #32:** switches de visibilidad del portal empleado (horas extras, vacaciones, recibos, etc.). |
| 33 | `migration_overtime_visibility.sql` | **Tras #32:** `companies.show_overtime`, `areas.show_overtime` (hereda empresa), roles admin/supervisor en Configuración → Horas extras. |
| 34 | `migration_cp_extras_visibility.sql` | **Tras #33:** `companies.show_cp_extras`, `areas.show_cp_extras`, recargo de cierre `cp_closure_markup_pct` (default 19,5%), visibilidad admin/supervisor CP en Configuración → Casa Paviotti. |
| 35 | `migration_prode_wc2026.sql` | PRODE Mundial 2026 (fase grupos): pronósticos empleados + ranking admin. Tras ejecutar: `php scripts/seed_prode_wc2026.php` y `php scripts/download_prode_flags.php`. |
| 36 | `migration_survey_responses_unique.sql` | **Tras #21:** UNIQUE `(survey_id, user_id)` en encuestas identificadas; evita doble envío concurrente. |
| 37 | `migration_cp_closure_lot_unique.sql` | **Tras #30:** UNIQUE `(company_id, lot_number)` en cierres CP; evita lotes duplicados concurrentes. |
| 38 | `migration_salary_advances.sql` | Adelantos de sueldo: tabla `salary_advances` (solicitud, cuotas, aprobación admin). |
| 38b | `migration_salary_advance_settings.sql` | **Tras #38 y #32:** claves en `system_settings` (límites anuales, cuotas, % sueldo) + `employee_show_salary_advance`. |
| 39 | `migration_salary_advance_installments.sql` | **Tras #38:** cuotas de devolución (`salary_advance_installments`), mes de descuento y registro de cobro. |
| 40 | `migration_salary_advance_finalizado.sql` | **Tras #39:** estado `Finalizado` cuando todas las cuotas fueron descontadas (`finalized_at`). |

## Hosting (BD primitiva → esquema completo)

Si la base de producción solo tiene `users`, `overtime_entries`, `closures`, `requests` (como el dump histórico de `paviotti_lanaturaleza`), usar el paquete consolidado:

1. **Backup** obligatorio en phpMyAdmin.
2. Importar [`migration_hosting_full.sql`](migration_hosting_full.sql) (generado con `bash scripts/build_migration_hosting_full.sh`).
3. PRODE: `php scripts/seed_prode_wc2026.php` y `php scripts/download_prode_flags.php`.
4. Validar: [`scripts/validate_hosting_migration.sql`](scripts/validate_hosting_migration.sql) y `php scripts/hosting_smoke_test.php`.

Guía detallada: [`HOSTING_MIGRATION.md`](HOSTING_MIGRATION.md).

## No aplicar (obsoleto / conflicto)

- `migration_shift_swaps.sql` — esquema viejo; usar `migration_shift_swaps_fix.sql` en su lugar.

## Validación post-migración

```sql
-- Cambio de turno (columnas esperadas por ShiftSwap.php)
SHOW COLUMNS FROM shift_swaps LIKE 'proposer_schedule_id';

-- Asistencia
SHOW TABLES LIKE 'attendance_%';

-- Empresas
SELECT id, name FROM companies ORDER BY name;

-- Capacitación
SHOW TABLES LIKE 'courses';
SHOW TABLES LIKE 'areas';
SHOW TABLES LIKE 'user_login_logs';
SHOW TABLES LIKE 'user_notifications';
SHOW TABLES LIKE 'pay_stubs';
SHOW TABLES LIKE 'mail_settings';
SHOW TABLES LIKE 'employee_incidents';
SHOW TABLES LIKE 'vacation_balance_periods';
SHOW COLUMNS FROM employee_schedules LIKE 'type';

-- Casa Paviotti extras por tarea
SHOW TABLES LIKE 'cp_%';
SELECT name, extras_mode FROM companies WHERE name = 'Casa Paviotti';

-- Configuración del sistema
SHOW TABLES LIKE 'system_settings';
SELECT setting_key, group_key FROM system_settings ORDER BY group_key, setting_key;
```

### Consultas útiles (login)

```sql
-- Cuántas veces inició sesión cada usuario
SELECT u.username, u.full_name, COUNT(*) AS logins
FROM user_login_logs l
JOIN users u ON u.id = l.user_id
GROUP BY u.id
ORDER BY logins DESC;

-- Detalle de accesos recientes
SELECT u.username, l.logged_at, l.ip_address
FROM user_login_logs l
JOIN users u ON u.id = l.user_id
ORDER BY l.logged_at DESC
LIMIT 100;

-- Primeros en completar un curso
SELECT u.full_name, e.completion_rank, e.stars_awarded, e.bonus_stars, e.completed_at
FROM course_enrollments e
JOIN users u ON u.id = e.user_id
WHERE e.course_id = 1 AND e.status = 'completed'
ORDER BY e.completion_rank ASC;
```

Si `proposer_schedule_id` no existe, ejecutar `migration_shift_swaps_fix.sql` y luego `migration_shift_swaps_accepter_null.sql`.

## Inconsistencias conocidas

- `migration_v2.sql` inserta empresa "La Naturaleza"; el código usa `DEFAULT_COMPANY_NAME = 'Ecofarma'` en `app/config/config.php`.
- Tablas `requests`, `request_types`, `overtime_entries`, `closures` se asumen ya creadas antes de v2 o en despliegues previos.
