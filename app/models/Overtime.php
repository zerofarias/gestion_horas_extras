<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/models/Overtime.php (VERSIÓN FINAL COMPLETA)
// ----------------------------------------------------------------------

class Overtime {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    // --- MÉTODOS PRINCIPALES PARA GESTIONAR ENTRADAS ---

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
        // ... (Tu lógica de cálculo de horas para la actualización va aquí, es idéntica a la de addEntry)
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
     * Elimina una entrada de horas extras.
     */
    public function deleteEntry($id){
        $this->db->query('DELETE FROM overtime_entries WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    /**
     * Realiza el cierre de todas las horas pendientes.
     */
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

    // --- MÉTODOS DE CONSULTA PARA VISTAS ---

    public function getPendingEntriesByUserId($userId){
        $this->db->query("SELECT * FROM overtime_entries WHERE user_id = :user_id AND status = 'pending' ORDER BY entry_date DESC");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    public function getAllPendingEntries(){
        $this->db->query("SELECT o.*, (o.hours_50 + o.hours_100) as total_hours, u.username, u.full_name FROM overtime_entries o JOIN users u ON o.user_id = u.id WHERE o.status = 'pending' ORDER BY o.entry_date DESC");
        return $this->db->resultSet();
    }

    public function getEntryById($id){
        $this->db->query("SELECT * FROM overtime_entries WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // --- MÉTODOS ESPECÍFICOS PARA EL NUEVO DASHBOARD ---

    /**
     * Obtiene los totales de horas pendientes (50% y 100%) para el dashboard.
     */
    public function getPendingTotalsByType() {
        $this->db->query("SELECT 
                            SUM(hours_100) as total_100,
                            SUM(hours_50) as total_50
                        FROM overtime_entries WHERE status = 'pending'");
        return $this->db->single();
    }

    /**
     * Cuenta cuántos empleados distintos tienen horas pendientes.
     */
    public function countEmployeesWithPendingHours() {
        $this->db->query("SELECT COUNT(DISTINCT user_id) as count FROM overtime_entries WHERE status = 'pending'");
        $row = $this->db->single();
        return $row ? $row->count : 0;
    }

    /**
     * Obtiene el top 5 de empleados con más horas pendientes.
     */
    public function getTopEmployeesByPendingHours($limit = 5) {
        $this->db->query("SELECT u.full_name, u.profile_picture, SUM(o.hours_50 + o.hours_100) as total_hours
                        FROM overtime_entries o
                        JOIN users u ON o.user_id = u.id
                        WHERE o.status = 'pending'
                        GROUP BY o.user_id
                        ORDER BY total_hours DESC
                        LIMIT :limit");
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }
    
    /**
     * Obtiene la suma de horas pendientes por cada día de la semana.
     */
    public function getPendingHoursByDayOfWeek() {
        $this->db->query("SELECT DAYOFWEEK(entry_date) as day_of_week, SUM(hours_50 + hours_100) as total_hours 
                        FROM overtime_entries 
                        WHERE status = 'pending'
                        GROUP BY day_of_week");
        return $this->db->resultSet();
    }

    // --- FUNCIONES DE AYUDA ---

    private function roundToHalf($hours) {
        $whole = floor($hours);
        $fraction = $hours - $whole;
        if ($fraction < 0.25) { return $whole; }
        elseif ($fraction < 0.75) { return $whole + 0.5; }
        else { return $whole + 1.0; }
    }
}
?>
