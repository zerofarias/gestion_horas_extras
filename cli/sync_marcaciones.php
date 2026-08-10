<?php
/**
 * Sincronización automática de marcaciones (cron / Task Scheduler).
 *
 * Ejemplo Windows (diario 6:00):
 *   php C:\xamppcubo\htdocs\gestion_horas_extras\cli\sync_marcaciones.php
 *
 * Opciones:
 *   --days=2          Días hacia atrás desde hoy (default 2)
 *   --company=1       Solo una empresa
 *   --all-companies   Todas las empresas activas
 *   --no-email        No enviar alertas por correo tras sync
 */
if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

define('APPROOT', dirname(__DIR__) . '/app');
require_once APPROOT . '/config/config.php';
require_once APPROOT . '/helpers/session_helper.php';
require_once APPROOT . '/helpers/marcaciones_helper.php';
require_once APPROOT . '/helpers/attendance_helper.php';
require_once APPROOT . '/helpers/auth_helper.php';
require_once APPROOT . '/helpers/notifications_helper.php';
require_once APPROOT . '/helpers/vacation_helper.php';

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

spl_autoload_register(function ($className) {
    $paths = [
        APPROOT . '/controllers/' . $className . '.php',
        APPROOT . '/models/' . $className . '.php',
        APPROOT . '/services/' . $className . '.php',
        APPROOT . '/helpers/' . $className . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

$opts = getopt('', ['days::', 'company::', 'all-companies', 'no-email']);
$days = max(1, (int)($opts['days'] ?? 2));
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
$sendEmail = empty($opts['no-email']);

$companyModel = new Company();
$syncModel = new SyncModel();

$companyIds = [];
if (!empty($opts['company'])) {
    $companyIds[] = (int)$opts['company'];
} elseif (isset($opts['all-companies'])) {
    foreach ($companyModel->getAllCompanies() as $co) {
        $companyIds[] = (int)$co->id;
    }
} else {
    $default = defined('DEFAULT_COMPANY_NAME') ? DEFAULT_COMPANY_NAME : '';
    $all = $companyModel->getAllCompanies();
    foreach ($all as $co) {
        if ($default === '' || stripos($co->name, $default) !== false) {
            $companyIds[] = (int)$co->id;
            break;
        }
    }
    if (empty($companyIds) && !empty($all)) {
        $companyIds[] = (int)$all[0]->id;
    }
}

if (empty($companyIds)) {
    fwrite(STDERR, "No hay empresas para sincronizar.\n");
    exit(1);
}

echo '[' . date('Y-m-d H:i:s') . "] Sync {$startDate} → {$endDate}\n";

$exitCode = 0;
foreach ($companyIds as $companyId) {
    echo "  Empresa #{$companyId}… ";
    $result = $syncModel->syncFromExternalApi($startDate, $endDate, null, $companyId);
    if ($result['success']) {
        echo "OK — {$result['message']}\n";
    } else {
        echo "ERROR — {$result['message']}\n";
        $exitCode = 1;
    }
}

if ($sendEmail && class_exists('AttendanceAlertNotifier')) {
    foreach ($companyIds as $companyId) {
        $notifier = new AttendanceAlertNotifier();
        $sent = $notifier->sendDailyDigestForCompany($companyId);
        if ($sent > 0) {
            echo "  Email alertas empresa #{$companyId}: {$sent} destinatario(s)\n";
        }
    }
}

exit($exitCode);
