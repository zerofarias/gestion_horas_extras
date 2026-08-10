-- Validación post-migración hosting (phpMyAdmin → SQL)
-- Ejecutar tras migration_hosting_full.sql (+ seeds si aplica)

SELECT '=== RRHH core ===' AS section;
SHOW COLUMNS FROM users LIKE 'company_id';
SHOW COLUMNS FROM users LIKE 'email';
SHOW COLUMNS FROM users LIKE 'probation_start_date';
SHOW COLUMNS FROM requests LIKE 'certificate_path';
SHOW COLUMNS FROM shift_swaps LIKE 'proposer_schedule_id';

SELECT '=== Tablas clave ===' AS section;
SHOW TABLES LIKE 'companies';
SHOW TABLES LIKE 'attendance_%';
SHOW TABLES LIKE 'courses';
SHOW TABLES LIKE 'areas';
SHOW TABLES LIKE 'user_login_logs';
SHOW TABLES LIKE 'user_notifications';
SHOW TABLES LIKE 'pay_stubs';
SHOW TABLES LIKE 'mail_settings';
SHOW TABLES LIKE 'employee_incidents';
SHOW TABLES LIKE 'vacation_balance_periods';
SHOW TABLES LIKE 'system_settings';
SHOW TABLES LIKE 'cp_%';
SHOW TABLES LIKE 'prode_%';
SHOW TABLES LIKE 'surveys';

SELECT '=== Índices UNIQUE recientes ===' AS section;
SHOW INDEX FROM survey_responses WHERE Key_name = 'uq_survey_user_response';
SHOW INDEX FROM cp_task_closures WHERE Key_name = 'uq_cp_closure_company_lot';

SELECT '=== Datos preservados (conteos) ===' AS section;
SELECT COUNT(*) AS users_count FROM users;
SELECT COUNT(*) AS overtime_entries_count FROM overtime_entries;
SELECT COUNT(*) AS closures_count FROM closures;
SELECT COUNT(*) AS requests_count FROM requests;

SELECT '=== Empresas ===' AS section;
SELECT id, name, extras_mode FROM companies ORDER BY name;

SELECT '=== employee_schedules.type ===' AS section;
SHOW COLUMNS FROM employee_schedules LIKE 'type';

SELECT '=== user_notifications.type (debe incluir survey) ===' AS section;
SHOW COLUMNS FROM user_notifications LIKE 'type';

SELECT '=== users.role (debe incluir supervisor) ===' AS section;
SHOW COLUMNS FROM users LIKE 'role';
