<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/models/Overtime.php (VERSIÓN COMPLETA Y FINAL)
// ----------------------------------------------------------------------

class Overtime {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    /**
     * Función de ayuda para redondear las horas a la media hora más cercana.
     */
    private function roundToHalf($hours) {
        $whole = floor($hours);
        $fraction = $hours - $whole;
        if ($fraction < 0.25) { return $whole; }
        elseif ($fraction < 0.75) { return $whole + 0.5; }
        else { return $whole + 1.0; }
    }

    /**
     * Verifica si ya existe una entrada idéntica para prevenir duplicados.
     * @return bool True si encuentra un duplicado, false si no.
     */
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

    /**
     * Añade una nueva entrada de horas extras, calculando los porcentajes.
     */
    public function addEntry($data){
        $startDateTime = new DateTime($data['date'] . ' ' . $data['start_time']);
        $endDateTime = new DateTime($data['date'] . ' ' . $data['end_time']);
        if ($endDateTime <= $startDateTime) { $endDateTime->add(new DateInterval('P1D')); }
        
        $hours_50 = 0; $hours_100 = 0;
        
        if ($data['is_holiday']) {
            $diff = $endDateTime->diff($startDateTime);
            $minutes = ($diff->h * 60) + $diff->i;
            $hours_100 = $minutes / 60;
        } else {
            $current = clone $startDateTime;
            while ($current < $endDateTime) {
                $dayOfWeek = (int)$current->format('N');
                $hour = (int)$current->format('H');
                $is_saturday_after_1pm = ($dayOfWeek == 6 && $hour >= 13);
                $is_sunday = ($dayOfWeek == 7);
                $is_night_shift = ($hour >= 22 || $hour < 6);

                if ($is_saturday_after_1pm || $is_sunday || $is_night_shift) { $hours_100 += 1/60; }
                else { $hours_50 += 1/60; }
                $current->add(new DateInterval('PT1M'));
            }
        }
        
        $rounded_hours_50 = $this->roundToHalf($hours_50);
        $rounded_hours_100 = $this->roundToHalf($hours_100);
        
        $this->db->query('INSERT INTO overtime_entries (user_id, entry_date, start_time, end_time, is_holiday, hours_50, hours_100, reason, status) VALUES (:user_id, :entry_date, :start_time, :end_time, :is_holiday, :hours_50, :hours_100, :reason, "pending")');
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':entry_date', $data['date']);
        $this->db->bind(':start_time', $data['start_time']);
        $this->db->bind(':end_time', $data['end_time']);
        $this->db->bind(':is_holiday', $data['is_holiday']);
        $this->db->bind(':hours_50', $rounded_hours_50);
        $this->db->bind(':hours_100', $rounded_hours_100);
        $this->db->bind(':reason', $data['reason']);
        return $this->db->execute();
    }

    /**
     * Actualiza una entrada de horas existente, recalculando los porcentajes.
     */
    public function updateEntry($data){
        $startDateTime = new DateTime($data['date'] . ' ' . $data['start_time']);
        $endDateTime = new DateTime($data['date'] . ' ' . $data['end_time']);
        if ($endDateTime <= $startDateTime) { $endDateTime->add(new DateInterval('P1D')); }

        $hours_50 = 0; $hours_100 = 0;

        if ($data['is_holiday']) {
            $diff = $endDateTime->diff($startDateTime);
            $minutes = ($diff->h * 60) + $diff->i;
            $hours_100 = $minutes / 60;
        } else {
            $current = clone $startDateTime;
            while ($current < $endDateTime) {
                $dayOfWeek = (int)$current->format('N');
                $hour = (int)$current->format('H');
                $is_saturday_after_1pm = ($dayOfWeek == 6 && $hour >= 13);
                $is_sunday = ($dayOfWeek == 7);
                $is_night_shift = ($hour >= 22 || $hour < 6);

                if ($is_saturday_after_1pm || $is_sunday || $is_night_shift) { $hours_100 += 1/60; }
                else { $hours_50 += 1/60; }
                $current->add(new DateInterval('PT1M'));
            }
        }
        
        $rounded_hours_50 = $this->roundToHalf($hours_50);
        $rounded_hours_100 = $this->roundToHalf($hours_100);

        $this->db->query('UPDATE overtime_entries SET entry_date = :entry_date, start_time = :start_time, end_time = :end_time, is_holiday = :is_holiday, hours_50 = :hours_50, hours_100 = :hours_100, reason = :reason WHERE id = :id');
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':entry_date', $data['date']);
        $this->db->bind(':start_time', $data['start_time']);
        $this->db->bind(':end_time', $data['end_time']);
        $this->db->bind(':is_holiday', $data['is_holiday']);
        $this->db->bind(':hours_50', $rounded_hours_50);
        $this->db->bind(':hours_100', $rounded_hours_100);
        $this->db->bind(':reason', $data['reason']);
        return $this->db->execute();
    }
    
    /**
     * Añade una entrada de horas extras con los valores ya calculados (para extras automáticas).
     */
    public function addCalculatedOvertime($data){
        $this->db->query('INSERT INTO overtime_entries (user_id, entry_date, start_time, end_time, is_holiday, hours_50, hours_100, reason, status) VALUES (:user_id, :entry_date, :start_time, :end_time, :is_holiday, :hours_50, :hours_100, :reason, "pending")');
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':entry_date', $data['date']);
        $this->db->bind(':start_time', $data['start_time']);
        $this->db->bind(':end_time', $data['end_time']);
        $this->db->bind(':is_holiday', $data['is_holiday']);
        $this->db->bind(':hours_50', $data['hours_50']);
        $this->db->bind(':hours_100', $data['hours_100']);
        $this->db->bind(':reason', $data['reason']);
        return $this->db->execute();
    }
    
    public function getPendingEntriesByUserId($userId){
        $this->db->query("SELECT * FROM overtime_entries WHERE user_id = :user_id AND status = 'pending' ORDER BY entry_date DESC");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    public function getAllPendingEntries(){
        $this->db->query("SELECT o.*, (o.hours_50 + o.hours_100) as total_hours, u.username, u.full_name FROM overtime_entries o JOIN users u ON o.user_id = u.id WHERE o.status = 'pending' ORDER BY o.entry_date DESC");
        return $this->db->resultSet();
    }
    
    public function getArchivedHistory(){
        $this->db->query("SELECT c.*, u.username as admin_username FROM closures c JOIN users u ON c.closed_by_user_id = u.id ORDER BY c.closure_date DESC");
        return $this->db->resultSet();
    }
    
    public function getEntriesByClosureId($closure_id){
        $this->db->query("SELECT o.*, (o.hours_50 + o.hours_100) as total_hours, u.username, u.full_name FROM overtime_entries o JOIN users u ON o.user_id = u.id WHERE o.closure_id = :closure_id ORDER BY u.full_name, o.entry_date");
        $this->db->bind(':closure_id', $closure_id);
        return $this->db->resultSet();
    }
    
    public function getPendingTotalsByType(){
        $this->db->query("SELECT SUM(hours_50) as total_50, SUM(hours_100) as total_100 FROM overtime_entries WHERE status = 'pending'");
        return $this->db->single();
    }
    
    public function getOvertimeTrend($days = 7){
        $this->db->query("
            SELECT DATE(entry_date) as entry_day, SUM(hours_50 + hours_100) as total_hours 
            FROM overtime_entries 
            WHERE entry_date >= CURDATE() - INTERVAL :days DAY 
            GROUP BY entry_day ORDER BY entry_day ASC
        ");
        $this->db->bind(':days', $days);
        return $this->db->resultSet();
    }
    
 public function getTopEmployeesByHours($limit = 5){
        $this->db->query("
            SELECT u.full_name, u.profile_picture, SUM(o.hours_50 + o.hours_100) as total_hours
            FROM overtime_entries o
            JOIN users u ON o.user_id = u.id
            WHERE o.status = 'pending'
            GROUP BY u.id, u.full_name, u.profile_picture
            ORDER BY total_hours DESC
            LIMIT :limit
        ");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }
    
    public function getHoursByDayOfWeek(){
        $this->db->query("
            SELECT DAYOFWEEK(entry_date) as day_of_week, SUM(hours_50 + hours_100) as total_hours
            FROM overtime_entries WHERE status = 'pending'
            GROUP BY day_of_week ORDER BY day_of_week ASC
        ");
        return $this->db->resultSet();
    }
    
    public function createClosure($adminUserId){
        $pendingEntries = $this->getAllPendingEntries();
        if (empty($pendingEntries)) { return false; }
        $this->db->beginTransaction();
        try {
            $totalHours = 0; $totalHours50 = 0; $totalHours100 = 0; $employeeIds = []; $entryIds = [];
            foreach ($pendingEntries as $entry) {
                $totalHours += $entry->total_hours; $totalHours50 += $entry->hours_50; $totalHours100 += $entry->hours_100;
                $employeeIds[] = $entry->user_id; $entryIds[] = $entry->id;
            }
            $employeeCount = count(array_unique($employeeIds));
            $this->db->query('INSERT INTO closures (total_hours, total_hours_50, total_hours_100, employee_count, closed_by_user_id) VALUES (:total_hours, :total_hours_50, :total_hours_100, :employee_count, :admin_id)');
            $this->db->bind(':total_hours', $totalHours);
            $this->db->bind(':total_hours_50', $totalHours50);
            $this->db->bind(':total_hours_100', $totalHours100);
            $this->db->bind(':employee_count', $employeeCount);
            $this->db->bind(':admin_id', $adminUserId);
            $this->db->execute();
            $closureId = $this->db->lastInsertId();
            if (!$closureId) { throw new Exception("No se pudo obtener el ID del nuevo cierre."); }
            $placeholders = implode(',', array_fill(0, count($entryIds), '?'));
            $this->db->query("UPDATE overtime_entries SET status = 'archived', closure_id = ? WHERE id IN ($placeholders)");
            $params = array_merge([$closureId], $entryIds);
            $this->db->execute($params);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack(); return false;
        }
    }
    
    public function getEntryById($id){
        $this->db->query("SELECT * FROM overtime_entries WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    public function deleteEntry($id){
        $this->db->query('DELETE FROM overtime_entries WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getOvertimeForUserCalendar($userId){
        $this->db->query("SELECT * FROM overtime_entries WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }

    /**
     * NUEVO: Obtiene un resumen mensual de horas extras para un usuario.
     */
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
    
    // ... (resto de los métodos del modelo sin cambios)

}
?>
