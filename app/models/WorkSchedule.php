<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/models/WorkSchedule.php (CON MÉTODO FALTANTE AÑADIDO)
// ----------------------------------------------------------------------

class WorkSchedule {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    /**
     * Obtiene todas las entradas de horario para un rango de fechas.
     */
    public function getScheduleEntriesForPeriod($companyId, $startDate, $endDate) {
        $sql = "SELECT 
                    es.*, 
                    s.shift_name, s.color 
                FROM employee_schedules es
                LEFT JOIN shifts s ON es.shift_id = s.id
                JOIN users u ON es.user_id = u.id
                WHERE u.company_id = :company_id 
                AND es.schedule_date BETWEEN :start_date AND :end_date
                ORDER BY es.start_time ASC";
        
        $this->db->query($sql);
        $this->db->bind(':company_id', $companyId);
        $this->db->bind(':start_date', $startDate);
        $this->db->bind(':end_date', $endDate);
        
        return $this->db->resultSet();
    }

    /**
     * Borra todas las entradas de un día para un usuario y luego inserta las nuevas.
     */
    public function saveDaySchedule($userId, $date, $entries) {
        $this->db->beginTransaction();
        try {
            $this->db->query('DELETE FROM employee_schedules WHERE user_id = :user_id AND schedule_date = :schedule_date');
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':schedule_date', $date);
            $this->db->execute();

            if (!empty($entries)) {
                $this->db->query('INSERT INTO employee_schedules (user_id, schedule_date, shift_id, start_time, end_time, type, notes) 
                                 VALUES (:user_id, :schedule_date, :shift_id, :start_time, :end_time, :type, :notes)');
                
                foreach ($entries as $entry) {
                    $this->db->bind(':user_id', $userId);
                    $this->db->bind(':schedule_date', $date);
                    $this->db->bind(':shift_id', ($entry['shift_id'] > 0) ? $entry['shift_id'] : NULL);
                    $this->db->bind(':start_time', !empty($entry['start_time']) ? $entry['start_time'] : NULL);
                    $this->db->bind(':end_time', !empty($entry['end_time']) ? $entry['end_time'] : NULL);
                    $this->db->bind(':type', $entry['type']);
                    $this->db->bind(':notes', $entry['notes']);
                    $this->db->execute();
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * MÉTODO CORREGIDO: Ahora el parámetro $companyId es opcional.
     * Si no se proporciona, se busca en la base de datos.
     */
    public function getUserScheduleForCurrentWeek($userId, $companyId = null) {
        // Si no se pasa el companyId, lo buscamos usando el modelo de Usuario.
        // Esto hace que la función sea más robusta ante llamadas de controladores antiguos.
        if (is_null($companyId)) {
            $userModel = new User(); // Asumimos que el autoloader del framework carga el modelo User.
            $user = $userModel->getUserById($userId);
            if ($user && isset($user->company_id)) {
                $companyId = $user->company_id;
            } else {
                // Si no se encuentra el usuario o su compañía, devolvemos un array vacío para evitar errores.
                return array();
            }
        }

        // Calcular el primer y último día de la semana actual (Lunes a Domingo)
        $today = new DateTime();
        $dayOfWeek = $today->format('N'); // 1 (para Lunes) a 7 (para Domingo)
        $startDate = clone $today;
        $startDate->modify('-' . ($dayOfWeek - 1) . ' days');
        $endDate = clone $startDate;
        $endDate->modify('+6 days');

        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');

        // Reutilizamos el método que ya existe y funciona
        $allEntries = $this->getScheduleEntriesForPeriod($companyId, $startDateStr, $endDateStr);

        // Filtramos las entradas solo para el usuario solicitado
        $userEntries = array();
        foreach($allEntries as $entry){
            if($entry->user_id == $userId){
                $userEntries[] = $entry;
            }
        }
        return $userEntries;
    }

        public function getDashboardScheduleSummary($companyId, $startDate, $endDate) {
            $sql = "SELECT schedule_date, type, start_time, end_time 
                    FROM employee_schedules es
                    JOIN users u ON es.user_id = u.id
                    WHERE u.company_id = :company_id 
                    AND es.schedule_date BETWEEN :start_date AND :end_date";
            $this->db->query($sql);
            $this->db->bind(':company_id', $companyId);
            $this->db->bind(':start_date', $startDate);
            $this->db->bind(':end_date', $endDate);
            return $this->db->resultSet();
        }

        public function getWhoIsWorkingNow($companyId) {
            $sql = "SELECT u.full_name, s.shift_name, es.start_time, es.end_time
                    FROM employee_schedules es
                    JOIN users u ON es.user_id = u.id
                    LEFT JOIN shifts s ON es.shift_id = s.id
                    WHERE u.company_id = :company_id
                    AND es.schedule_date = CURDATE()
                    AND CURRENT_TIME() BETWEEN es.start_time AND es.end_time";
            $this->db->query($sql);
            $this->db->bind(':company_id', $companyId);
            return $this->db->resultSet();
        }

        public function countWorkingNowByCompany($companyId) {
    $sql = "SELECT COUNT(DISTINCT es.user_id) as count
            FROM employee_schedules es
            JOIN users u ON es.user_id = u.id
            WHERE u.company_id = :company_id
            AND es.schedule_date = CURDATE()
            AND CURRENT_TIME() BETWEEN es.start_time AND es.end_time";
    $this->db->query($sql);
    $this->db->bind(':company_id', $companyId);
    $row = $this->db->single();
    return $row ? $row->count : 0;
}

}
?>
