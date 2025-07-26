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
    
    /**
     * Obtiene todas las marcaciones en bruto para un usuario en un día específico.
     */
    public function getRawClockingsForUserAndDay($userId, $date){
        $this->db->query("
            SELECT event_time FROM clock_events 
            WHERE user_id = :user_id AND DATE(event_time) = :work_date
            ORDER BY event_time ASC
        ");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':work_date', $date);
        return $this->db->resultSet();
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
    
    public function insertClockEvent($userId, $clockId, $eventTime, $batchId){
        $this->db->query("INSERT INTO clock_events (user_id, clock_id, event_time, sync_batch_id) VALUES (:user_id, :clock_id, :event_time, :sync_batch_id)");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':clock_id', $clockId);
        $this->db->bind(':event_time', $eventTime);
        $this->db->bind(':sync_batch_id', $batchId);
        return $this->db->execute();
    }

    public function getRawClockingsReport($filters){
        $query = "SELECT ce.*, u.full_name FROM clock_events ce JOIN users u ON ce.user_id = u.id WHERE 1=1";
        if (!empty($filters['start_date'])) { $query .= " AND DATE(ce.event_time) >= :start_date"; }
        if (!empty($filters['end_date'])) { $query .= " AND DATE(ce.event_time) <= :end_date"; }
        if (!empty($filters['user_id'])) { $query .= " AND ce.user_id = :user_id"; }
        $query .= " ORDER BY ce.event_time DESC";
        $this->db->query($query);
        if (!empty($filters['start_date'])) { $this->db->bind(':start_date', $filters['start_date']); }
        if (!empty($filters['end_date'])) { $this->db->bind(':end_date', $filters['end_date']); }
        if (!empty($filters['user_id'])) { $this->db->bind(':user_id', $filters['user_id']); }
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

    
}
?>
