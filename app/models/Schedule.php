<?php
// ----------------------------------------------------------------------
// ARCHIVO 2: app/models/Schedule.php (VERSIÓN CORREGIDA Y COMPLETA)
// Se añade el método `doesClockEventExist` que faltaba.
// ----------------------------------------------------------------------

class Schedule {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    /**
     * Verifica si un evento de marcación específico ya existe en la BD.
     * @return bool True si ya existe, false si no.
     */
    public function doesClockEventExist($userId, $eventTime){
        $this->db->query("SELECT id FROM clock_events WHERE user_id = :user_id AND event_time = :event_time");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':event_time', $eventTime);
        $this->db->single();
        return $this->db->rowCount() > 0;
    }

    public function doesEventExistBySerial($serialNo){
        $this->db->query("SELECT id FROM clock_events WHERE event_serial_no = :serial_no");
        $this->db->bind(':serial_no', $serialNo);
        $this->db->single();
        return $this->db->rowCount() > 0;
    }
    
    /**
     * Obtiene todas las marcaciones en bruto para un usuario en un día específico.
     */
    public function getRawClockingsForUserAndDay($userId, $date){
        $this->db->query("
            SELECT event_time, direction, direction_label FROM clock_events 
            WHERE user_id = :user_id AND DATE(event_time) = :work_date
            ORDER BY event_time ASC
        ");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':work_date', $date);
        return $this->db->resultSet();
    }

    /**
     * Normaliza direction de la API/reloj a 'in' (entrada) o 'out' (salida).
     */
    public function normalizeClockDirection($direction, $directionLabel = null) {
        $d = strtoupper(trim((string)$direction));
        $l = mb_strtolower(trim((string)$directionLabel));

        if (in_array($d, ['P10', 'E', 'IN', '1'], true)) {
            return 'in';
        }
        if (in_array($d, ['P20', 'S', 'OUT', '0'], true)) {
            return 'out';
        }
        if (strpos($l, 'entrada') !== false || strpos($l, 'ingreso') !== false) {
            return 'in';
        }
        if (strpos($l, 'salida') !== false || strpos($l, 'egreso') !== false) {
            return 'out';
        }
        return null;
    }

    private function intervalToMinutes(DateInterval $interval) {
        return ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i + ($interval->s / 60);
    }

    /**
     * Calcula jornada del día.
     * - Si hay la misma cantidad de entradas y salidas: suma pares en orden (turno con descanso).
     * - Si hay marcas duplicadas (ej. 2 entradas + 1 salida): 1ª entrada y última salida.
     *
     * @param array $clockings Filas con event_time, direction, direction_label
     */
    public function computeDaySummaryFromClockings(array $clockings) {
        $events = [];
        foreach ($clockings as $row) {
            if (empty($row->event_time)) {
                continue;
            }
            $events[] = [
                'time' => new DateTime($row->event_time),
                'dir'  => $this->normalizeClockDirection($row->direction ?? null, $row->direction_label ?? null),
            ];
        }

        $empty = [
            'entry_time' => null, 'exit_time' => null, 'total_hours' => null,
            'calc_method' => null, 'entries_count' => 0, 'exits_count' => 0,
        ];

        if (empty($events)) {
            return $empty;
        }

        usort($events, function ($a, $b) {
            return $a['time'] <=> $b['time'];
        });

        $hasDirection = false;
        foreach ($events as $e) {
            if ($e['dir'] !== null) {
                $hasDirection = true;
                break;
            }
        }

        if (!$hasDirection) {
            $chrono = $this->computeDaySummaryChronological($events);
            $chrono['calc_method'] = 'chronological';
            $chrono['entries_count'] = count($events);
            $chrono['exits_count'] = 0;
            return $chrono;
        }

        $entries = [];
        $exits   = [];
        foreach ($events as $e) {
            if ($e['dir'] === 'in') {
                $entries[] = $e['time'];
            } elseif ($e['dir'] === 'out') {
                $exits[] = $e['time'];
            }
        }

        $firstEntry = !empty($entries) ? $entries[0] : null;
        $lastExit   = !empty($exits) ? $exits[count($exits) - 1] : null;
        $calcMethod = null;
        $totalMinutes = 0;

        if (count($entries) > 0 && count($entries) === count($exits)) {
            $calcMethod = 'paired';
            for ($i = 0; $i < count($entries); $i++) {
                if ($exits[$i] > $entries[$i]) {
                    $totalMinutes += $this->intervalToMinutes($entries[$i]->diff($exits[$i]));
                }
            }
        } elseif (count($entries) > 0 && count($exits) > 0) {
            $calcMethod = 'first_last';
            if ($lastExit > $firstEntry) {
                $totalMinutes = $this->intervalToMinutes($firstEntry->diff($lastExit));
            }
        }

        // Anviz u otros relojes: todas las marcas con el mismo direction (ej. solo P20)
        // → 1ª marca del día = entrada, última = salida (mismo criterio que desbalanceo).
        if ($totalMinutes <= 0 && count($events) >= 2) {
            $firstMark = $events[0]['time'];
            $lastMark  = $events[count($events) - 1]['time'];
            if ($lastMark > $firstMark) {
                $totalMinutes = $this->intervalToMinutes($firstMark->diff($lastMark));
                $firstEntry   = $firstMark;
                $lastExit     = $lastMark;
                $calcMethod   = 'first_last';
            }
        }

        $totalHours = $totalMinutes > 0 ? round($totalMinutes / 60, 2) : null;

        return [
            'entry_time'    => $firstEntry ? $firstEntry->format('H:i:s') : null,
            'exit_time'     => $lastExit ? $lastExit->format('H:i:s') : null,
            'total_hours'   => $totalHours,
            'calc_method'   => $calcMethod,
            'entries_count' => count($entries),
            'exits_count'    => count($exits),
        ];
    }

    /** Respaldo cuando no hay direction: empareja marcas consecutivas por horario. */
    private function computeDaySummaryChronological(array $events) {
        $times = array_map(function ($e) { return $e['time']; }, $events);
        $firstEntry = null;
        $lastExit   = null;
        $totalHours = null;

        if (count($times) >= 1) {
            $firstEntry = min($times);
        }
        if (count($times) >= 2) {
            $lastExit = max($times);
            if (count($times) % 2 === 0) {
                $totalMinutes = 0;
                for ($i = 0; $i < count($times); $i += 2) {
                    $totalMinutes += $this->intervalToMinutes($times[$i]->diff($times[$i + 1]));
                }
                $totalHours = round($totalMinutes / 60, 2);
            }
        }

        return [
            'entry_time'  => $firstEntry ? $firstEntry->format('H:i:s') : null,
            'exit_time'   => $lastExit ? $lastExit->format('H:i:s') : null,
            'total_hours' => $totalHours,
        ];
    }

    public function recalcDayScheduleForUser($userId, $date) {
        $clockings = $this->getRawClockingsForUserAndDay($userId, $date);
        $summary   = $this->computeDaySummaryFromClockings($clockings);
        return $this->upsertScheduleFromClock(
            $userId,
            $date,
            $summary['entry_time'],
            $summary['exit_time'],
            $summary['total_hours']
        );
    }

    /**
     * Recalcula schedules para todos los user/día con marcaciones en el rango.
     */
    public function recalcSchedulesForDateRange($startDate, $endDate, $companyId = null) {
        $query = "SELECT DISTINCT ce.user_id, DATE(ce.event_time) AS work_date
                  FROM clock_events ce
                  INNER JOIN users u ON ce.user_id = u.id
                  WHERE DATE(ce.event_time) BETWEEN :start_date AND :end_date";
        if ($companyId !== null) {
            $query .= " AND u.company_id = :company_id";
        }
        $query .= " ORDER BY work_date ASC, user_id ASC";

        $this->db->query($query);
        $this->db->bind(':start_date', $startDate);
        $this->db->bind(':end_date', $endDate);
        if ($companyId !== null) {
            $this->db->bind(':company_id', $companyId);
        }

        $rows = $this->db->resultSet();
        $count = 0;
        foreach ($rows as $row) {
            $this->recalcDayScheduleForUser($row->user_id, $row->work_date);
            $count++;
        }
        return $count;
    }

    public function getTodaysEntry($userId){
        $this->db->query("SELECT * FROM schedules WHERE user_id = :user_id AND work_date = CURDATE()");
        $this->db->bind(':user_id', $userId);
        return $this->db->single();
    }
    
    public function clockIn($userId){
        $this->db->query("INSERT INTO schedules (user_id, work_date, entry_time) VALUES (:user_id, CURDATE(), CURTIME())");
        $this->db->bind(':user_id', $userId);
        return $this->db->execute();
    }

    public function clockOut($scheduleId){
        $this->db->query("SELECT entry_time FROM schedules WHERE id = :id");
        $this->db->bind(':id', $scheduleId);
        $row = $this->db->single();
        $entryTime = new DateTime($row->entry_time);
        $exitTime = new DateTime();
        $interval = $entryTime->diff($exitTime);
        $totalHours = $interval->h + ($interval->i / 60);
        $this->db->query("UPDATE schedules SET exit_time = CURTIME(), total_hours = :total_hours WHERE id = :id");
        $this->db->bind(':id', $scheduleId);
        $this->db->bind(':total_hours', $totalHours);
        return $this->db->execute();
    }
    
    public function getWeeklyHours($userId){
        $this->db->query("SELECT SUM(total_hours) as weekly_total FROM schedules WHERE user_id = :user_id AND YEARWEEK(work_date, 1) = YEARWEEK(CURDATE(), 1)");
        $this->db->bind(':user_id', $userId);
        $result = $this->db->single();
        return $result ? (float)$result->weekly_total : 0;
    }
    
    public function getWeekEntries($userId){
        $this->db->query("SELECT * FROM schedules WHERE user_id = :user_id AND YEARWEEK(work_date, 1) = YEARWEEK(CURDATE(), 1) ORDER BY work_date ASC");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }

    public function upsertScheduleFromClock($userId, $date, $entryTime, $exitTime, $totalHours){
        $this->db->query("INSERT INTO schedules (user_id, work_date, entry_time, exit_time, total_hours) VALUES (:user_id, :work_date, :entry_time, :exit_time, :total_hours) ON DUPLICATE KEY UPDATE entry_time = VALUES(entry_time), exit_time = VALUES(exit_time), total_hours = VALUES(total_hours)");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':work_date', $date);
        $this->db->bind(':entry_time', $entryTime);
        $this->db->bind(':exit_time', $exitTime);
        $this->db->bind(':total_hours', $totalHours);
        return $this->db->execute();
    }
    
    public function insertClockEvent($userId, $clockId, $eventTime, $batchId, $serialNo, $deviceName = null, $direction = null, $directionLabel = null){
        $this->db->query("INSERT INTO clock_events (user_id, clock_id, device_name, direction, direction_label, event_time, sync_batch_id, event_serial_no) VALUES (:user_id, :clock_id, :device_name, :direction, :direction_label, :event_time, :sync_batch_id, :serial_no)");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':clock_id', $clockId);
        $this->db->bind(':device_name', $deviceName);
        $this->db->bind(':direction', $direction);
        $this->db->bind(':direction_label', $directionLabel);
        $this->db->bind(':event_time', $eventTime);
        $this->db->bind(':sync_batch_id', $batchId);
        $this->db->bind(':serial_no', $serialNo);
        return $this->db->execute();
    }

    /**
     * Todas las marcaciones del caché local con filtros opcionales.
     * Orden DESC por fecha. Usado por admin/marcaciones.
     */
    public function getAllMarcaciones($filters = [], $companyId = null) {
        $query = "SELECT ce.id, ce.user_id, u.full_name, ce.device_name, ce.direction, ce.direction_label,
                         ce.event_time, ce.clock_id, ce.event_serial_no
                  FROM clock_events ce
                  JOIN users u ON ce.user_id = u.id
                  WHERE 1=1";
        if ($companyId !== null)           $query .= " AND u.company_id = :company_id";
        if (!empty($filters['start_date']))  $query .= " AND DATE(ce.event_time) >= :start_date";
        if (!empty($filters['end_date']))    $query .= " AND DATE(ce.event_time) <= :end_date";
        if (!empty($filters['user_id']))     $query .= " AND ce.user_id = :user_id";
        if (!empty($filters['device_name'])) $query .= " AND ce.device_name = :device_name";
        $query .= " ORDER BY ce.event_time DESC";

        $this->db->query($query);
        if ($companyId !== null)           $this->db->bind(':company_id', $companyId);
        if (!empty($filters['start_date']))  $this->db->bind(':start_date',  $filters['start_date']);
        if (!empty($filters['end_date']))    $this->db->bind(':end_date',    $filters['end_date']);
        if (!empty($filters['user_id']))     $this->db->bind(':user_id',     $filters['user_id'], PDO::PARAM_INT);
        if (!empty($filters['device_name'])) $this->db->bind(':device_name', $filters['device_name']);
        return $this->db->resultSet();
    }

    /**
     * Lista de relojes distintos que tienen marcaciones en la BD local.
     */
    public function getDistinctDevices($companyId = null) {
        $sql = "SELECT DISTINCT ce.device_name
                FROM clock_events ce
                JOIN users u ON ce.user_id = u.id
                WHERE ce.device_name IS NOT NULL";
        if ($companyId !== null) {
            $sql .= " AND u.company_id = :company_id";
        }
        $sql .= " ORDER BY ce.device_name";
        $this->db->query($sql);
        if ($companyId !== null) {
            $this->db->bind(':company_id', $companyId);
        }
        return $this->db->resultSet();
    }

    /**
     * Últimas marcaciones de un empleado para su ficha.
     */
    public function getMarcacionesByUser($userId, $limit = 100, $filters = []) {
        $query = "SELECT ce.id, ce.event_time, ce.device_name, ce.direction, ce.direction_label, ce.clock_id
                  FROM clock_events ce
                  WHERE ce.user_id = :user_id";
        if (!empty($filters['start_date'])) $query .= " AND DATE(ce.event_time) >= :start_date";
        if (!empty($filters['end_date']))   $query .= " AND DATE(ce.event_time) <= :end_date";
        $query .= " ORDER BY ce.event_time DESC LIMIT :limit";

        $this->db->query($query);
        $this->db->bind(':user_id', $userId, PDO::PARAM_INT);
        $this->db->bind(':limit',   $limit,  PDO::PARAM_INT);
        if (!empty($filters['start_date'])) $this->db->bind(':start_date', $filters['start_date']);
        if (!empty($filters['end_date']))   $this->db->bind(':end_date',   $filters['end_date']);
        return $this->db->resultSet();
    }

    public function clearSchedulesForRange($userIds, $startDate, $endDate){
        if (empty($userIds)) { return true; }
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $this->db->query("DELETE FROM schedules WHERE user_id IN ($placeholders) AND work_date BETWEEN ? AND ?");
        $this->db->execute(array_merge($userIds, [$startDate, $endDate]));
        $this->db->query("DELETE FROM clock_events WHERE user_id IN ($placeholders) AND DATE(event_time) BETWEEN ? AND ?");
        $this->db->execute(array_merge($userIds, [$startDate, $endDate]));
        return true;
    }
    
    public function getLatestClockingsByUserId($userId, $limit = 50){
        $this->db->query("SELECT ce.* FROM clock_events ce WHERE ce.user_id = :user_id ORDER BY ce.event_time DESC LIMIT :limit");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }


    public function syncDailyClockings($userId, $date, $clockings, $syncBatchId) {
        $this->db->beginTransaction();
        try {
            // 1. Borrar todas las marcaciones existentes para ese día y usuario
            $this->db->query('DELETE FROM clock_events WHERE user_id = :user_id AND DATE(event_time) = :event_date');
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':event_date', $date);
            $this->db->execute();

            // Obtener el clock_id del usuario una sola vez
            $userModel = new User();
            $user = $userModel->getUserById($userId);
            $userClockId = $user ? $user->clock_id : null;

            // 2. Insertar las nuevas marcaciones
            $this->db->query('INSERT INTO clock_events (user_id, clock_id, event_time, source_clock_name, sync_batch_id) VALUES (:user_id, :clock_id, :event_time, :source_clock_name, :sync_batch_id)');
            $times = array();
            foreach ($clockings as $clocking) {
                $timeObject = is_array($clocking) ? $clocking['time'] : $clocking;
                $sourceName = is_array($clocking) ? $clocking['source'] : 'Sincronizado';
                $times[] = $timeObject;

                $this->db->bind(':user_id', $userId);
                $this->db->bind(':clock_id', $userClockId);
                $this->db->bind(':event_time', $timeObject->format('Y-m-d H:i:s'));
                $this->db->bind(':source_clock_name', $sourceName);
                $this->db->bind(':sync_batch_id', $syncBatchId);
                $this->db->execute();
            }

            // 3. Recalcular y guardar el resumen del día en la tabla `schedules`
            $firstEntry = null; $lastExit = null; $totalHours = null;
            if (count($times) >= 2) {
                $firstEntry = min($times);
                $lastExit = max($times);
                if (count($times) % 2 == 0) {
                    $totalMinutes = 0;
                    for ($i = 0; $i < count($times); $i += 2) {
                        $interval = $times[$i]->diff($times[$i + 1]);
                        $totalMinutes += ($interval->h * 60) + $interval->i;
                    }
                    $totalHours = $totalMinutes / 60;
                }
            } elseif (count($times) == 1) {
                $firstEntry = $times[0];
            }

            $this->db->query('DELETE FROM schedules WHERE user_id = :user_id AND work_date = :work_date');
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':work_date', $date);
            $this->db->execute();
            
            $this->db->query('INSERT INTO schedules (user_id, work_date, entry_time, exit_time, total_hours) VALUES (:user_id, :work_date, :entry_time, :exit_time, :total_hours)');
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':work_date', $date);
            $this->db->bind(':entry_time', $firstEntry ? $firstEntry->format('H:i:s') : null);
            $this->db->bind(':exit_time', $lastExit ? $lastExit->format('H:i:s') : null);
            $this->db->bind(':total_hours', $totalHours);
            $this->db->execute();

            // Si todo fue exitoso, confirmamos los cambios
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            // Si algo falla, revertimos todos los cambios
            $this->db->rollBack();
            // Re-lanzamos la excepción para que el script cron sepa que algo falló
            throw $e;
        }
    }
    
}
?>
