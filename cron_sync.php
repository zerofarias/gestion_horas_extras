<?php
// ----------------------------------------------------------------------
// ARCHIVO 1: cron_sync.php (VERSIÓN ACTUALIZADA)
// Este es el script para la sincronización automática.
// Reemplaza el archivo en la RAÍZ de tu proyecto.
// ----------------------------------------------------------------------

// --- PASO 1: Cargar el entorno de la aplicación ---
require_once 'app/bootstrap.php';

// --- PASO 2: Definir la ruta del archivo de log ---
$log_file = APPROOT . '/logs/sync.log';

function write_log($message) {
    global $log_file;
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($log_file, "[$timestamp] " . $message . PHP_EOL, FILE_APPEND);
}

write_log("--- [INICIO] Sincronización Automática ---");

// --- INICIO DE CAMBIO: Período de sincronización configurable ---
// Para cambiar el rango de días, simplemente modifica este número.
$days_to_sync = 30;
// --- FIN DE CAMBIO ---

// --- PASO 3: Instanciar los modelos necesarios ---
$userModel = new User();
$scheduleModel = new Schedule();

// --- PASO 4: Conectarse al reloj y descargar eventos ---
$apiUrl = 'http://' . HIKVISION_IP . '/ISAPI/AccessControl/AcsEvent?format=json';
$username = HIKVISION_USER;
$password = HIKVISION_PASS;

$startTime = date('Y-m-d\TH:i:s', strtotime("-$days_to_sync days")) . '-03:00';
$endTime = date('Y-m-d\TH:i:s') . '-03:00';
write_log("Buscando eventos entre: $startTime y $endTime");

$allEvents = [];
$searchResultPosition = 0;
$maxResults = 500;
$totalMatches = 0;
$searchID = uniqid();

do {
    $requestBody = json_encode([
        'AcsEventCond' => [
            'searchID' => $searchID, 'searchResultPosition' => $searchResultPosition,
            'maxResults' => $maxResults, 'major' => 5, 'minor' => 75,
            'startTime' => $startTime, 'endTime' => $endTime
        ]
    ]);
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_USERPWD => "$username:$password", CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $requestBody, CURLOPT_HTTPHEADER => array('Content-Type: application/json')));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200 || !$response) {
        write_log("ERROR: No se pudo conectar con el reloj (Código HTTP: $httpCode).");
        exit;
    }

    $responseData = json_decode($response, true);
    if (isset($responseData['AcsEvent']['InfoList'])) {
        $eventsInBatch = $responseData['AcsEvent']['InfoList'];
        $allEvents = array_merge($allEvents, $eventsInBatch);
        $numOfMatches = isset($responseData['AcsEvent']['numOfMatches']) ? $responseData['AcsEvent']['numOfMatches'] : 0;
        $totalMatches = isset($responseData['AcsEvent']['totalMatches']) ? $responseData['AcsEvent']['totalMatches'] : 0;
        $searchResultPosition += $numOfMatches;
    } else {
        break;
    }
} while ($searchResultPosition < $totalMatches && $totalMatches > 0);

write_log("Respuesta del reloj recibida. Se encontraron " . count($allEvents) . " eventos en total.");

// --- PASO 5: Procesar y Guardar los Datos (Lógica Actualizada) ---
if (!empty($allEvents)) {
    $newEventsCount = 0;
    $duplicateEventsCount = 0;
    $syncBatchId = uniqid('cron_');
    $affectedDaysByUser = [];

    // 1. Guardar cada evento individual si no existe
    foreach ($allEvents as $event) {
        if (isset($event['employeeNoString'])) {
            $clockId = $event['employeeNoString'];
            $user = $userModel->findUserByClockId($clockId);
            if ($user) {
                $dateTime = new DateTime($event['time']);
                $eventTimestamp = $dateTime->format('Y-m-d H:i:s');
                $eventDate = $dateTime->format('Y-m-d');

                // --- INICIO DE CAMBIO: Se comprueba si el evento ya existe ---
                if (!$scheduleModel->doesClockEventExist($user->id, $eventTimestamp)) {
                    $scheduleModel->insertClockEvent($user->id, $clockId, $eventTimestamp, $syncBatchId);
                    $newEventsCount++;
                    // Marcamos este día para este usuario como afectado (necesita recálculo)
                    $affectedDaysByUser[$user->id][$eventDate] = true;
                } else {
                    $duplicateEventsCount++;
                }
                // --- FIN DE CAMBIO ---
            }
        }
    }
    write_log("Procesamiento de eventos individuales finalizado. Nuevos: $newEventsCount. Duplicados ignorados: $duplicateEventsCount.");

    // 2. Recalcular y guardar el resumen diario para las fechas afectadas (si hubo eventos nuevos)
    if ($newEventsCount > 0) {
        write_log("Recalculando resúmenes diarios para los días afectados...");
        $recalculatedCount = 0;
        foreach($affectedDaysByUser as $userId => $dates){
            foreach(array_keys($dates) as $date){
                // 1. Obtener todas las marcaciones (ya actualizadas) para este usuario y día
                $allDayEvents = $scheduleModel->getRawClockingsForUserAndDay($userId, $date);
                
                if(!empty($allDayEvents)){
                    $times = [];
                    foreach($allDayEvents as $event){
                        $times[] = new DateTime($event->event_time);
                    }
                    sort($times);

                    $firstEntry = min($times);
                    $lastExit = null;
                    $totalHours = null;

                    if (count($times) >= 2 && count($times) % 2 == 0) {
                        $totalMinutes = 0;
                        for ($i = 0; $i < count($times); $i += 2) {
                            $interval = $times[$i]->diff($times[$i + 1]);
                            $totalMinutes += ($interval->h * 60) + $interval->i;
                        }
                        $totalHours = $totalMinutes / 60;
                        $lastExit = max($times);
                    }
                    
                    // 2. Actualizar el resumen del día en la tabla `schedules`
                    $scheduleModel->upsertScheduleFromClock($userId, $date, $firstEntry->format('H:i:s'), $lastExit ? $lastExit->format('H:i:s') : null, $totalHours);
                    $recalculatedCount++;
                }
            }
        }
        write_log("Se recalcularon {$recalculatedCount} resúmenes diarios.");
    }
    
} else {
    write_log("No se encontraron nuevos eventos para procesar.");
}

write_log("--- [FIN] Sincronización Automática ---");
echo "Sincronización automática completada.";
?>