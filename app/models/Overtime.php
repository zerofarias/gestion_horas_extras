<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/models/Overtime.php (VERSIÓN FINAL COMPLETA)
// ----------------------------------------------------------------------

class Overtime {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    // --- MÉTODOS DE CÁLCULO Y VALIDACIÓN ---

    private function roundToHalf($hours) {
        $whole = floor($hours);
        $fraction = $hours - $whole;
        if ($fraction < 0.25) { return $whole; }
        elseif ($fraction < 0.75) { return $whole + 0.5; }
        else { return $whole + 1.0; }
    }

    private function overlapSeconds($segStart, $segEnd, $windowStart, $windowEnd) {
        $start = max($segStart, $windowStart);
        $end = min($segEnd, $windowEnd);
        if ($end <= $start) {
            return 0;
        }
        return $end - $start;
    }

    private function resolveCompanyIdForEntry(array $data) {
        if (!empty($data['company_id'])) {
            return (int)$data['company_id'];
        }
        $userId = (int)($data['user_id'] ?? 0);
        if ($userId <= 0) {
            return 0;
        }
        $user = (new User())->getUserById($userId);
        return $user ? (int)$user->company_id : 0;
    }

    /**
     * Clasifica segundos de un tramo dentro de un mismo día calendario.
     * 100%: feriado, domingo, sábado desde 12:00, franja nocturna 22:00–06:00.
     * 50%: resto de lunes a viernes y sábados antes del mediodía (solo diurno 06:00–22:00).
     */
    private function classifyDaySeconds($dayDate, $segStart, $segEnd, $isHolidayFlag, $companyId) {
        if ($segEnd <= $segStart) {
            return [0, 0];
        }

        $companyHoliday = $companyId > 0 && (new Holiday())->isHolidayForCompany($companyId, $dayDate);
        if ($isHolidayFlag || $companyHoliday) {
            return [0, $segEnd - $segStart];
        }

        $dow = (int)date('w', strtotime($dayDate));
        if ($dow === 0) {
            return [0, $segEnd - $segStart];
        }

        $dayStart = strtotime($dayDate . ' 00:00:00');
        $sixAm = strtotime($dayDate . ' 06:00:00');
        $noon = strtotime($dayDate . ' 12:00:00');
        $tenPm = strtotime($dayDate . ' 22:00:00');
        $midnight = strtotime($dayDate . ' +1 day');

        $secs50 = 0;
        $secs100 = 0;

        // Nocturno 00:00–06:00 y 22:00–24:00 → siempre 100%
        $secs100 += $this->overlapSeconds($segStart, $segEnd, $dayStart, $sixAm);
        $secs100 += $this->overlapSeconds($segStart, $segEnd, $tenPm, $midnight);

        // Diurno 06:00–22:00
        $diurStart = max($segStart, $sixAm);
        $diurEnd = min($segEnd, $tenPm);
        if ($diurEnd > $diurStart) {
            if ($dow === 6) {
                $secs50 += $this->overlapSeconds($diurStart, $diurEnd, $sixAm, $noon);
                $secs100 += $this->overlapSeconds($diurStart, $diurEnd, $noon, $tenPm);
            } else {
                $secs50 += $diurEnd - $diurStart;
            }
        }

        return [$secs50, $secs100];
    }

    /**
     * Reglas: 100% = feriados, domingos, sábados desde 12:00, nocturno 22:00–06:00.
     * 50% = lunes a viernes diurno (06:00–22:00) y sábados antes del mediodía (06:00–12:00).
     * Soporta turnos que cruzan medianoche.
     */
    private function calculateHoursByRate($entryDate, $startTime, $endTime, $isHoliday = 0, $companyId = 0) {
        $entryDate = trim((string)$entryDate);
        $startTs = strtotime($entryDate . ' ' . $startTime);
        $endTs = strtotime($entryDate . ' ' . $endTime);

        if ($startTs === false || $endTs === false) {
            return ['hours_50' => 0, 'hours_100' => 0];
        }
        if ($endTs <= $startTs) {
            $endTs += 24 * 3600;
        }

        $isHolidayFlag = (int)$isHoliday === 1;
        $companyId = (int)$companyId;
        $secs50 = 0;
        $secs100 = 0;
        $cursor = $startTs;

        while ($cursor < $endTs) {
            $dayDate = date('Y-m-d', $cursor);
            $dayEnd = min($endTs, strtotime($dayDate . ' +1 day'));
            [$day50, $day100] = $this->classifyDaySeconds($dayDate, $cursor, $dayEnd, $isHolidayFlag, $companyId);
            $secs50 += $day50;
            $secs100 += $day100;
            $cursor = $dayEnd;
        }

        return [
            'hours_50' => $this->roundToHalf($secs50 / 3600),
            'hours_100' => $this->roundToHalf($secs100 / 3600),
        ];
    }

    /**
     * Recalcula hs 50%/100% de entradas pendientes según fecha y horario.
     */
    public function recalculatePendingRates($companyId = null) {
        $entries = $this->getAllPendingEntries($companyId);
        $userModel = new User();
        $updated = 0;

        foreach ($entries as $entry) {
            $user = $userModel->getUserById((int)$entry->user_id);
            $cid = $user ? (int)$user->company_id : 0;
            $hours = $this->calculateHoursByRate(
                $entry->entry_date,
                $entry->start_time,
                $entry->end_time,
                $entry->is_holiday,
                $cid
            );
            $cur50 = round((float)$entry->hours_50, 2);
            $cur100 = round((float)$entry->hours_100, 2);
            if ($cur50 === round($hours['hours_50'], 2) && $cur100 === round($hours['hours_100'], 2)) {
                continue;
            }
            $this->db->query('UPDATE overtime_entries SET hours_50 = :hours_50, hours_100 = :hours_100 WHERE id = :id AND status = :pending');
            $this->db->bind(':id', (int)$entry->id);
            $this->db->bind(':pending', 'pending');
            $this->db->bind(':hours_50', $hours['hours_50']);
            $this->db->bind(':hours_100', $hours['hours_100']);
            if ($this->db->execute()) {
                $updated++;
            }
        }

        return $updated;
    }

    public function checkForDuplicateEntry($data){
        $this->db->query("
            SELECT id FROM overtime_entries 
            WHERE user_id = :user_id 
            AND entry_date = :entry_date 
            AND start_time = :start_time 
            AND end_time = :end_time
        ");
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':entry_date', $data['date']);
        $this->db->bind(':start_time', $data['start_time']);
        $this->db->bind(':end_time', $data['end_time']);
        
        $this->db->single();
        
        return $this->db->rowCount() > 0;
    }

    // --- MÉTODOS PRINCIPALES PARA GESTIONAR ENTRADAS ---

    public function addEntry($data){
        $companyId = $this->resolveCompanyIdForEntry($data);
        $hours = $this->calculateHoursByRate($data['date'], $data['start_time'], $data['end_time'], $data['is_holiday'], $companyId);

        $this->db->query('INSERT INTO overtime_entries (user_id, entry_date, start_time, end_time, is_holiday, reason, hours_50, hours_100, status) VALUES (:user_id, :entry_date, :start_time, :end_time, :is_holiday, :reason, :hours_50, :hours_100, :status)');
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':entry_date', $data['date']);
        $this->db->bind(':start_time', $data['start_time']);
        $this->db->bind(':end_time', $data['end_time']);
        $this->db->bind(':is_holiday', (int)$data['is_holiday']);
        $this->db->bind(':reason', $data['reason']);
        $this->db->bind(':hours_50', $hours['hours_50']);
        $this->db->bind(':hours_100', $hours['hours_100']);
        $this->db->bind(':status', isset($data['status']) ? $data['status'] : 'pending');
        if ($this->db->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    public function updateEntry($data){
        $entry = $this->getEntryById($data['id'] ?? 0);
        if (!$entry) {
            return false;
        }
        $calcData = $data;
        $calcData['user_id'] = $entry->user_id;
        $companyId = $this->resolveCompanyIdForEntry($calcData);
        $hours = $this->calculateHoursByRate($data['date'], $data['start_time'], $data['end_time'], $data['is_holiday'], $companyId);

        $this->db->query('UPDATE overtime_entries SET entry_date = :entry_date, start_time = :start_time, end_time = :end_time, is_holiday = :is_holiday, reason = :reason, hours_50 = :hours_50, hours_100 = :hours_100 WHERE id = :id AND status = :pending');
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':pending', 'pending');
        $this->db->bind(':entry_date', $data['date']);
        $this->db->bind(':start_time', $data['start_time']);
        $this->db->bind(':end_time', $data['end_time']);
        $this->db->bind(':is_holiday', (int)$data['is_holiday']);
        $this->db->bind(':reason', $data['reason']);
        $this->db->bind(':hours_50', $hours['hours_50']);
        $this->db->bind(':hours_100', $hours['hours_100']);
        return $this->db->execute();
    }

    public function addCalculatedOvertime($data){
        $this->db->query('INSERT INTO overtime_entries (user_id, entry_date, start_time, end_time, is_holiday, reason, hours_50, hours_100, status) VALUES (:user_id, :entry_date, :start_time, :end_time, :is_holiday, :reason, :hours_50, :hours_100, :status)');
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':entry_date', $data['date']);
        $this->db->bind(':start_time', $data['start_time']);
        $this->db->bind(':end_time', $data['end_time']);
        $this->db->bind(':is_holiday', (int)$data['is_holiday']);
        $this->db->bind(':reason', $data['reason']);
        $this->db->bind(':hours_50', $data['hours_50']);
        $this->db->bind(':hours_100', $data['hours_100']);
        $this->db->bind(':status', isset($data['status']) ? $data['status'] : 'pending');
        return $this->db->execute();
    }

    public function getEntryById($id){
        $this->db->query('SELECT * FROM overtime_entries WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    public function deleteEntry($id){
        $this->db->query('DELETE FROM overtime_entries WHERE id = :id AND status = :pending');
        $this->db->bind(':id', $id);
        $this->db->bind(':pending', 'pending');
        return $this->db->execute() && $this->db->rowCount() > 0;
    }

    private function closureBelongsToCompanySql($closureAlias = 'c') {
        return "EXISTS (
            SELECT 1 FROM overtime_entries o
            INNER JOIN users emp ON emp.id = o.user_id
            WHERE o.closure_id = {$closureAlias}.id AND emp.company_id = :company_id
        )";
    }

    public function createClosure($adminUserId, $companyId = null){
        if ($companyId === null || (int)$companyId <= 0) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $sql = "SELECT o.*, (o.hours_50 + o.hours_100) AS total_hours, u.username, u.full_name
                FROM overtime_entries o
                JOIN users u ON o.user_id = u.id
                WHERE o.status = 'pending' AND u.company_id = :company_id";
            $params = [':company_id' => (int)$companyId];
            $sql .= ' FOR UPDATE';
            $this->db->query($sql);
            if (!empty($params)) {
                foreach ($params as $k => $v) {
                    $this->db->bind($k, $v);
                }
            }
            $pendingEntries = $this->db->resultSet();
            if (empty($pendingEntries)) {
                $this->db->rollBack();
                return false;
            }

            $totalHours = 0;
            $totalHours50 = 0;
            $totalHours100 = 0;
            $employeeIds = [];
            $entryIds = [];
            foreach ($pendingEntries as $entry) {
                $totalHours += $entry->total_hours;
                $totalHours50 += $entry->hours_50;
                $totalHours100 += $entry->hours_100;
                $employeeIds[] = $entry->user_id;
                $entryIds[] = (int)$entry->id;
            }
            $employeeCount = count(array_unique($employeeIds));

            $this->db->query('INSERT INTO closures (total_hours, total_hours_50, total_hours_100, employee_count, closed_by_user_id) VALUES (:total_hours, :total_hours_50, :total_hours_100, :employee_count, :admin_id)');
            $this->db->bind(':total_hours', $totalHours);
            $this->db->bind(':total_hours_50', $totalHours50);
            $this->db->bind(':total_hours_100', $totalHours100);
            $this->db->bind(':employee_count', $employeeCount);
            $this->db->bind(':admin_id', $adminUserId);
            $this->db->execute();
            $closureId = (int)$this->db->lastInsertId();
            if ($closureId <= 0) {
                throw new Exception('No se pudo obtener el ID del nuevo cierre.');
            }

            $placeholders = implode(',', array_fill(0, count($entryIds), '?'));
            $this->db->query("UPDATE overtime_entries SET status = 'archived', closure_id = ? WHERE id IN ($placeholders) AND status = 'pending'");
            $updateParams = array_merge([$closureId], $entryIds);
            $this->db->execute($updateParams);
            if ($this->db->rowCount() < 1) {
                throw new Exception('No se pudieron archivar las entradas pendientes.');
            }

            $this->db->commit();
            return $closureId;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // --- MÉTODOS DE CONSULTA PARA VISTAS ---

    public function getPendingEntriesByUserId($userId){
        $this->db->query("SELECT * FROM overtime_entries WHERE user_id = :user_id AND status = 'pending' ORDER BY entry_date DESC");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    public function getAllPendingEntries($companyId = null){
        $sql = "SELECT o.*, (o.hours_50 + o.hours_100) as total_hours, u.username, u.full_name
                FROM overtime_entries o
                JOIN users u ON o.user_id = u.id
                WHERE o.status = 'pending'";
        if ($companyId !== null) {
            $sql .= " AND u.company_id = :company_id";
        }
        $sql .= " ORDER BY o.entry_date DESC";
        $this->db->query($sql);
        if ($companyId !== null) {
            $this->db->bind(':company_id', $companyId);
        }
        return $this->db->resultSet();
    }
    
    public function getArchivedHistory($companyId = null){
        $sql = "SELECT DISTINCT c.*, u.username AS admin_username
                FROM closures c
                JOIN users u ON c.closed_by_user_id = u.id";
        if ($companyId !== null && (int)$companyId > 0) {
            $sql .= ' WHERE ' . $this->closureBelongsToCompanySql('c');
        }
        $sql .= ' ORDER BY c.closure_date DESC';
        $this->db->query($sql);
        if ($companyId !== null && (int)$companyId > 0) {
            $this->db->bind(':company_id', (int)$companyId);
        }
        return $this->db->resultSet();
    }
    
    public function getEntriesByClosureId($closure_id, $companyId = null){
        $sql = "SELECT o.*, (o.hours_50 + o.hours_100) as total_hours, u.username, u.full_name
                FROM overtime_entries o
                JOIN users u ON o.user_id = u.id
                WHERE o.closure_id = :closure_id";
        if ($companyId !== null) {
            $sql .= " AND u.company_id = :company_id";
        }
        $sql .= " ORDER BY u.full_name, o.entry_date";
        $this->db->query($sql);
        $this->db->bind(':closure_id', $closure_id);
        if ($companyId !== null) {
            $this->db->bind(':company_id', $companyId);
        }
        return $this->db->resultSet();
    }

    /**
     * NUEVO MÉTODO AÑADIDO: Obtiene los datos de un cierre específico por su ID.
     */
    public function getClosureById($id, $companyId = null){
        $sql = "SELECT c.*, u.username AS admin_username
                FROM closures c
                JOIN users u ON c.closed_by_user_id = u.id
                WHERE c.id = :id";
        if ($companyId !== null && (int)$companyId > 0) {
            $sql .= ' AND ' . $this->closureBelongsToCompanySql('c');
        }
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        if ($companyId !== null && (int)$companyId > 0) {
            $this->db->bind(':company_id', (int)$companyId);
        }
        return $this->db->single();
    }

    // --- MÉTODOS ESPECÍFICOS PARA EL NUEVO DASHBOARD ---

    public function getPendingTotalsByType($companyId = null) {
        $sql = "SELECT 
                    SUM(o.hours_100) as total_100,
                    SUM(o.hours_50) as total_50
                FROM overtime_entries o
                JOIN users u ON o.user_id = u.id
                WHERE o.status = 'pending'";
        if ($companyId !== null) {
            $sql .= " AND u.company_id = :company_id";
        }
        $this->db->query($sql);
        if ($companyId !== null) {
            $this->db->bind(':company_id', $companyId);
        }
        return $this->db->single();
    }

    public function countEmployeesWithPendingHours($companyId = null) {
        $sql = "SELECT COUNT(DISTINCT o.user_id) as count
                FROM overtime_entries o
                JOIN users u ON o.user_id = u.id
                WHERE o.status = 'pending'";
        if ($companyId !== null) {
            $sql .= " AND u.company_id = :company_id";
        }
        $this->db->query($sql);
        if ($companyId !== null) {
            $this->db->bind(':company_id', $companyId);
        }
        $row = $this->db->single();
        return $row ? $row->count : 0;
    }

    public function getTopEmployeesByPendingHours($limit = 5, $companyId = null) {
        $sql = "SELECT u.id as user_id, u.full_name, u.profile_picture, SUM(o.hours_50 + o.hours_100) as total_hours
                        FROM overtime_entries o
                        JOIN users u ON o.user_id = u.id
                        WHERE o.status = 'pending'";
        if ($companyId !== null) {
            $sql .= " AND u.company_id = :company_id";
        }
        $sql .= " GROUP BY o.user_id, u.id, u.full_name, u.profile_picture
                        ORDER BY total_hours DESC
                        LIMIT :limit";
        $this->db->query($sql);
        if ($companyId !== null) {
            $this->db->bind(':company_id', $companyId);
        }
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }
    
    public function getPendingHoursByDayOfWeek($companyId = null) {
        $sql = "SELECT DAYOFWEEK(o.entry_date) as day_of_week, SUM(o.hours_50 + o.hours_100) as total_hours 
                        FROM overtime_entries o
                        JOIN users u ON o.user_id = u.id
                        WHERE o.status = 'pending'";
        if ($companyId !== null) {
            $sql .= " AND u.company_id = :company_id";
        }
        $sql .= " GROUP BY day_of_week";
        $this->db->query($sql);
        if ($companyId !== null) {
            $this->db->bind(':company_id', $companyId);
        }
        return $this->db->resultSet();
    }

    public function getOvertimeForUserCalendar($userId){
        $this->db->query("SELECT * FROM overtime_entries WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }

    public function getMonthlyOvertimeSummaryForUser($userId, $months = 6){
        $this->db->query("
            SELECT 
                DATE_FORMAT(entry_date, '%Y-%m') as month,
                SUM(hours_50) as total_50,
                SUM(hours_100) as total_100
            FROM overtime_entries
            WHERE user_id = :user_id AND entry_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY month
            ORDER BY month ASC
        ");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':months', $months);
        return $this->db->resultSet();
    }

    public function getHistoricalOvertime($companyId, $months = 6){
        $this->db->query("
            SELECT 
                DATE_FORMAT(c.closure_date, '%Y-%m') AS month,
                SUM(c.total_hours_50) AS total_50,
                SUM(c.total_hours_100) AS total_100
            FROM closures c
            WHERE c.closure_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
              AND " . $this->closureBelongsToCompanySql('c') . "
            GROUP BY month
            ORDER BY month ASC
        ");
        $this->db->bind(':company_id', (int)$companyId);
        $this->db->bind(':months', $months);
        return $this->db->resultSet();
    }

    public function getPendingOvertimeWithRate($companyId) {
        $this->db->query("SELECT o.hours_50, o.hours_100, u.hourly_rate 
                        FROM overtime_entries o
                        JOIN users u ON o.user_id = u.id
                        WHERE o.status = 'pending' AND u.company_id = :company_id");
        $this->db->bind(':company_id', $companyId);
        return $this->db->resultSet();
    }

    public function getEntriesByUserId($userId){
        $this->db->query("SELECT * FROM overtime_entries WHERE user_id = :user_id ORDER BY entry_date DESC");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }

    public function getEntriesByUserIdForMonth($userId, $year, $month) {
        $this->db->query("SELECT * FROM overtime_entries
            WHERE user_id = :user_id
            AND YEAR(entry_date) = :p_year
            AND MONTH(entry_date) = :p_month
            ORDER BY entry_date DESC, start_time DESC");
        $this->db->bind(':user_id', (int)$userId);
        $this->db->bind(':p_year', (int)$year);
        $this->db->bind(':p_month', (int)$month);
        return $this->db->resultSet();
    }

    public function summarizeMonthEntries(array $entries) {
        $totals = [
            'count' => count($entries),
            'hours_50' => 0.0,
            'hours_100' => 0.0,
            'pending_count' => 0,
            'archived_count' => 0,
        ];
        foreach ($entries as $e) {
            $totals['hours_50'] += (float)$e->hours_50;
            $totals['hours_100'] += (float)$e->hours_100;
            if ($e->status === 'pending') {
                $totals['pending_count']++;
            } elseif ($e->status === 'archived') {
                $totals['archived_count']++;
            }
        }
        $totals['hours_total'] = round($totals['hours_50'] + $totals['hours_100'], 1);
        $totals['hours_50'] = round($totals['hours_50'], 1);
        $totals['hours_100'] = round($totals['hours_100'], 1);
        return $totals;
    }
}
?>