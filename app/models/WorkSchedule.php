<?php
// ----------------------------------------------------------------------
// ARCHIVO 2: app/models/WorkSchedule.php (VERSIÓN COMPLETA Y VERIFICADA)
// Asegúrate de que este archivo exista en la ruta app/models/.
// ----------------------------------------------------------------------

class WorkSchedule {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    public function getSchedulesForWeek($year, $week_number){
        $this->db->query("SELECT * FROM work_schedules WHERE year = :year AND week_number = :week_number");
        $this->db->bind(':year', $year);
        $this->db->bind(':week_number', $week_number);
        $results = $this->db->resultSet();

        $schedulesByUser = [];
        foreach($results as $row){
            $schedulesByUser[$row->user_id] = $row;
        }
        return $schedulesByUser;
    }

    public function upsertSchedule($data){
        $this->db->query("
            INSERT INTO work_schedules (user_id, year, week_number, monday, tuesday, wednesday, thursday, friday, saturday, sunday)
            VALUES (:user_id, :year, :week_number, :monday, :tuesday, :wednesday, :thursday, :friday, :saturday, :sunday)
            ON DUPLICATE KEY UPDATE
            monday = VALUES(monday),
            tuesday = VALUES(tuesday),
            wednesday = VALUES(wednesday),
            thursday = VALUES(thursday),
            friday = VALUES(friday),
            saturday = VALUES(saturday),
            sunday = VALUES(sunday)
        ");

        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':year', $data['year']);
        $this->db->bind(':week_number', $data['week_number']);
        $this->db->bind(':monday', $data['monday']);
        $this->db->bind(':tuesday', $data['tuesday']);
        $this->db->bind(':wednesday', $data['wednesday']);
        $this->db->bind(':thursday', $data['thursday']);
        $this->db->bind(':friday', $data['friday']);
        $this->db->bind(':saturday', $data['saturday']);
        $this->db->bind(':sunday', $data['sunday']);
        
        return $this->db->execute();
    }
    
    public function getUserScheduleForCurrentWeek($userId){
        $year = date('Y');
        $week_number = date('W');
        $this->db->query("SELECT * FROM work_schedules WHERE user_id = :user_id AND year = :year AND week_number = :week_number");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':year', $year);
        $this->db->bind(':week_number', $week_number);
        return $this->db->single();
    }
}
?>
