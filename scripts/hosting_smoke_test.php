<?php
/**
 * Smoke test post-migración hosting (solo lectura).
 * Uso: php scripts/hosting_smoke_test.php [nombre_bd]
 */
define('APPROOT', dirname(__DIR__) . '/app');
require_once APPROOT . '/config/config.php';

$dbName = $argv[1] ?? DB_NAME;

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . $dbName . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    fwrite(STDERR, 'No se pudo conectar a la BD: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$checks = [];
$failed = 0;

function check($label, $ok, $detail = '') {
    global $checks, $failed;
    $checks[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
    if (!$ok) {
        $failed++;
    }
}

$requiredTables = [
    'companies', 'users', 'overtime_entries', 'closures', 'requests',
    'employee_schedules', 'shift_swaps', 'attendance_day_summary',
    'system_settings', 'pay_stubs', 'surveys', 'prode_editions', 'cp_task_types',
];

foreach ($requiredTables as $table) {
    $st = $pdo->prepare('SHOW TABLES LIKE ?');
    $st->execute([$table]);
    check("Tabla $table", (bool)$st->fetch(), $table);
}

$row = $pdo->query('SELECT COUNT(*) AS n FROM overtime_entries')->fetch(PDO::FETCH_OBJ);
check('Horas extras históricas (>0)', $row && (int)$row->n > 0, 'count=' . ($row->n ?? 0));

$row = $pdo->query('SELECT COUNT(*) AS n FROM closures')->fetch(PDO::FETCH_OBJ);
check('Cierres históricos (>0)', $row && (int)$row->n > 0, 'count=' . ($row->n ?? 0));

$row = $pdo->query('SELECT COUNT(*) AS n FROM users')->fetch(PDO::FETCH_OBJ);
check('Usuarios (>0)', $row && (int)$row->n > 0, 'count=' . ($row->n ?? 0));

check('users.company_id', (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'company_id'")->fetch());
check('shift_swaps.proposer_schedule_id', (bool)$pdo->query("SHOW COLUMNS FROM shift_swaps LIKE 'proposer_schedule_id'")->fetch());

$roleCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_OBJ);
check('users.role incluye supervisor', $roleCol && strpos($roleCol->Type, 'supervisor') !== false, $roleCol->Type ?? '');

$row = $pdo->query('SELECT COUNT(*) AS n FROM prode_matches')->fetch(PDO::FETCH_OBJ);
check('PRODE partidos semilla (>0)', $row && (int)$row->n > 0, 'count=' . ($row->n ?? 0));

$row = $pdo->query('SELECT COUNT(*) AS n FROM companies')->fetch(PDO::FETCH_OBJ);
check('Empresas del grupo (>=5)', $row && (int)$row->n >= 5, 'count=' . ($row->n ?? 0));

echo PHP_EOL . "=== Smoke test hosting (BD: {$dbName}) ===" . PHP_EOL;
foreach ($checks as $c) {
    $icon = $c['ok'] ? 'OK' : 'FAIL';
    $detail = $c['detail'] !== '' ? " ({$c['detail']})" : '';
    echo "[$icon] {$c['label']}{$detail}" . PHP_EOL;
}

echo PHP_EOL . ($failed === 0 ? 'Todos los checks pasaron.' : "$failed check(s) fallaron.") . PHP_EOL;
exit($failed === 0 ? 0 : 1);
