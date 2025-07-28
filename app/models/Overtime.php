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
        // ... (Tu lógica original para añadir entradas)
    }

    public function updateEntry($data){
        // ... (Tu lógica original para actualizar entradas)
    }
    
    public function deleteEntry($id){
        $this->db->query('DELETE FROM overtime_entries WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
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
    
    /**
     * MÉTODO RESTAURADO: Obtiene el historial de todos los cierres.
     */
    public function getArchivedHistory(){
        $this->db->query("SELECT c.*, u.username as admin_username FROM closures c JOIN users u ON c.closed_by_user_id = u.id ORDER BY c.closure_date DESC");
        return $this->db->resultSet();
    }
    
    /**
     * MÉTODO RESTAURADO: Obtiene todas las entradas de horas que pertenecen a un cierre específico.
     */
    public function getEntriesByClosureId($closure_id){
        $this->db->query("SELECT o.*, (o.hours_50 + o.hours_100) as total_hours, u.username, u.full_name FROM overtime_entries o JOIN users u ON o.user_id = u.id WHERE o.closure_id = :closure_id ORDER BY u.full_name, o.entry_date");
        $this->db->bind(':closure_id', $closure_id);
        return $this->db->resultSet();
    }

    // --- MÉTODOS ESPECÍFICOS PARA EL NUEVO DASHBOARD ---

    public function getPendingTotalsByType() {
        $this->db->query("SELECT 
                            SUM(hours_100) as total_100,
                            SUM(hours_50) as total_50
                        FROM overtime_entries WHERE status = 'pending'");
        return $this->db->single();
    }

    public function countEmployeesWithPendingHours() {
        $this->db->query("SELECT COUNT(DISTINCT user_id) as count FROM overtime_entries WHERE status = 'pending'");
        $row = $this->db->single();
        return $row ? $row->count : 0;
    }

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
    
    public function getPendingHoursByDayOfWeek() {
        $this->db->query("SELECT DAYOFWEEK(entry_date) as day_of_week, SUM(hours_50 + hours_100) as total_hours 
                        FROM overtime_entries 
                        WHERE status = 'pending'
                        GROUP BY day_of_week");
        return $this->db->resultSet();
    }

    public function getClosureSummaryData($companyId) {
        $this->db->query("
            SELECT 
                u.full_name,
                SUM(IF(o.type = 50, o.hours, 0)) as total_50,
                SUM(IF(o.type = 100, o.hours, 0)) as total_100,
                SUM(o.is_holiday) as total_feriados_count,
                SUM(o.hours) as total_hours
            FROM overtime_entries o
            JOIN users u ON o.user_id = u.id
            WHERE o.status = 'pending' AND u.company_id = :company_id
            GROUP BY o.user_id, u.full_name
            HAVING SUM(o.hours) > 0
            ORDER BY u.full_name
        ");
        $this->db->bind(':company_id', $companyId);
        return $this->db->resultSet();
    }

}
?>
