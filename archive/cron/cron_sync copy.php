<?php
// ----------------------------------------------------------------------
// ARCHIVO: cron_sync.php (VERSIÓN FINAL MULTI-RELOJ)
// ----------------------------------------------------------------------

// --- PASO 1: Cargar el entorno de la aplicación ---
// Es crucial para tener acceso a los modelos y la configuración.
require_once dirname(__FILE__) . '/app/bootstrap.php';

// --- PASO 2: Configuración del Log ---
$log_file = APPROOT . '/logs/sync.log';
if (!file_exists(dirname($log_file))) {
    mkdir(dirname($log_file), 0755, true);
}

function write_log($message) {
    global $log_file;
    $timestamp = date("Y-m-d H:i:s");
    // Imprime en la consola (para ejecución manual) y escribe en el archivo de log.
    echo "[$timestamp] " . $message . PHP_EOL;
    file_put_contents($log_file, "[$timestamp] " . $message . PHP_EOL, FILE_APPEND);
}

write_log("--- [INICIO] Sincronización Automática de Marcaciones ---");

// --- PASO 3: Instanciar Modelos ---
try {
    $userModel = new User();
    $scheduleModel = new Schedule();
    $db = new Database(); // Para manejar la transacción principal
} catch (Exception $e) {
    write_log("ERROR CRÍTICO: No se pudieron cargar los modelos. " . $e->getMessage());
    exit;
}

// --- PASO 4: Definir Funciones de Sincronización por Marca ---

/**
 * Descarga eventos de un reloj Hikvision.
 */
function syncHikvision($clock, &$allEvents) {
    write_log("Iniciando sincronización para Hikvision: " . $clock['name'] . " (" . $clock['ip'] . ")");
    $apiUrl = 'http://' . $clock['ip'] . '/ISAPI/AccessControl/AcsEvent?format=json';
    $startTime = date('Y-m-d\T00:00:00', strtotime("-2 days")) . '-03:00';
    $endTime = date('Y-m-d\T23:59:59') . '-03:00';
    
    $eventsFound = 0;
    $searchResultPosition = 0;
    $maxResults = 500;
    $totalMatches = 0;
    $searchID = uniqid();

    do {
        $requestBody = json_encode(array('AcsEventCond' => array('searchID' => $searchID, 'searchResultPosition' => $searchResultPosition, 'maxResults' => $maxResults, 'major' => 5, 'minor' => 75, 'startTime' => $startTime, 'endTime' => $endTime)));
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_USERPWD => $clock['user'] . ":" . $clock['pass'], CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $requestBody, CURLOPT_HTTPHEADER => array('Content-Type: application/json')));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode != 200 || !$response) {
            write_log("ERROR: Falló la conexión con " . $clock['name'] . " (Código HTTP: $httpCode).");
            return;
        }

        $responseData = json_decode($response, true);
        if (isset($responseData['AcsEvent']['InfoList'])) {
            $batch = $responseData['AcsEvent']['InfoList'];
            foreach($batch as $event){
                if(isset($event['employeeNoString'])){
                    $allEvents[] = array('clock_id' => $event['employeeNoString'], 'timestamp' => $event['time']);
                    $eventsFound++;
                }
            }
            $numOfMatches = isset($responseData['AcsEvent']['numOfMatches']) ? $responseData['AcsEvent']['numOfMatches'] : 0;
            $totalMatches = isset($responseData['AcsEvent']['totalMatches']) ? $responseData['AcsEvent']['totalMatches'] : 0;
            $searchResultPosition += $numOfMatches;
        } else {
            break;
        }
    } while ($searchResultPosition < $totalMatches && $totalMatches > 0);
    
    write_log("Finalizada sincronización para " . $clock['name'] . ". Eventos encontrados: " . $eventsFound);
}

/**
 * Descarga eventos de un reloj Anviz (ejemplo con base de datos SQLite).
 */
function syncAnviz($clock, &$allEvents) {
    write_log("Iniciando sincronización para Anviz: " . $clock['name']);
    if (!file_exists($clock['db_path'])) {
        write_log("ERROR: No se encontró la base de datos de Anviz en: " . $clock['db_path']);
        return;
    }
    try {
        $anvizDb = new PDO('sqlite:' . $clock['db_path']);
        $anvizDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "SELECT UserID, CheckTime FROM CheckInOut"; // Ajusta esta consulta a tu estructura
        $stmt = $anvizDb->query($query);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($records as $record) {
            $allEvents[] = array('clock_id' => $record['UserID'], 'timestamp' => $record['CheckTime']);
        }
        write_log("Finalizada sincronización para " . $clock['name'] . ". Eventos encontrados: " . count($records));
    } catch (PDOException $e) {
        write_log("ERROR al conectar o consultar la base de datos de Anviz: " . $e->getMessage());
    }
}

// --- PASO 5: Ejecutar Sincronización para Todos los Relojes ---
$allEvents = array();
if (isset($GLOBALS['CLOCKS_CONFIG'])) {
    foreach ($GLOBALS['CLOCKS_CONFIG'] as $clock) {
        switch ($clock['type']) {
            case 'hikvision':
                syncHikvision($clock, $allEvents);
                break;
            case 'anviz':
                syncAnviz($clock, $allEvents);
                break;
            default:
                write_log("ADVERTENCIA: Tipo de reloj desconocido '" . $clock['type'] . "' para " . $clock['name']);
        }
    }
} else {
    write_log("ERROR: La configuración de relojes (CLOCKS_CONFIG) no está definida en config.php.");
}
write_log("Descarga de todos los relojes completa. Total de eventos a procesar: " . count($allEvents));

// --- PASO 6: Procesar y Guardar los Datos ---
if (empty($allEvents)) {
    write_log("No hay eventos nuevos para procesar.");
} else {
    $eventsByUserAndDate = array();
    $unknownClockIds = array();

    foreach ($allEvents as $event) {
        $clockId = $event['clock_id'];
        $user = $userModel->findUserByClockId($clockId);
        if ($user) {
            $dateTime = new DateTime($event['timestamp']);
            $date = $dateTime->format('Y-m-d');
            $eventsByUserAndDate[$user->id][$date][] = $dateTime;
        } else {
            $unknownClockIds[$clockId] = true;
        }
    }

    if(!empty($unknownClockIds)){
        write_log("ADVERTENCIA: Se ignoraron eventos de IDs de reloj no registrados: " . implode(', ', array_keys($unknownClockIds)));
    }

    $db->beginTransaction();
    try {
        $updatedDaysCount = 0;
        foreach ($eventsByUserAndDate as $userId => $dates) {
            foreach ($dates as $date => $times) {
                sort($times);
                $scheduleModel->syncDailyClockings($userId, $date, $times, uniqid('cron_'));
                $updatedDaysCount++;
            }
        }
        $db->commit();
        write_log("Procesamiento finalizado. Se actualizaron {$updatedDaysCount} registros de día/empleado.");
    } catch (Exception $e) {
        $db->rollBack();
        write_log("ERROR CRÍTICO DURANTE EL GUARDADO: Se revirtieron todos los cambios. Error: " . $e->getMessage());
    }
}

write_log("--- [FIN] Sincronización Automática ---");
