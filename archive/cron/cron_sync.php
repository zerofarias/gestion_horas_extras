<?php
// Cron Job for Automatic Clock Synchronization

// Set a higher time limit for the script to run
set_time_limit(300); // 5 minutes

// Bootstrap the application
require_once dirname(__FILE__) . '/bootstrap.php';

// --- LOGGING SETUP ---
$logPath = APPROOT . '/logs/sync.log';
function write_log($message) {
    global $logPath;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logPath, "[{$timestamp}] - {$message}\n", FILE_APPEND);
}

write_log("--- INICIO TAREA DE SINCRONIZACIÓN AUTOMÁTICA ---");

// --- MAIN LOGIC ---

global $CLOCKS_CONFIG;

if (empty($CLOCKS_CONFIG)) {
    write_log("ERROR: No hay relojes definidos en la configuración (CLOCKS_CONFIG).");
    exit;
}

// Instantiate the SyncModel
$syncModel = new SyncModel();

// Sync for the last 24 hours
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-1 day'));

write_log("Período de sincronización: {$startDate} a {$endDate}");

foreach ($CLOCKS_CONFIG as $clockConfig) {
    $clockName = $clockConfig['name'];
    write_log("Iniciando sincronización para el reloj: '{$clockName}'...");

    $result = $syncModel->runSyncForClock($clockConfig, $startDate, $endDate);

    if ($result['success']) {
        write_log("ÉXITO para '{$clockName}': " . $result['message']);
    } else {
        write_log("FALLO para '{$clockName}': " . $result['message']);
    }
}

write_log("--- FIN TAREA DE SINCRONIZACIÓN AUTOMÁTICA ---");

exit;
?>