<?php
// En app/models/Overtime.php, asegúrate de tener estos métodos:
// ----------------------------------------------------------------------
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

// ----------------------------------------------------------------------
// En app/models/User.php, añade este método:
// ----------------------------------------------------------------------
public function getUpcomingBirthdays($companyId, $days = 7) {
    $this->db->query("SELECT full_name, profile_picture, birth_date FROM users 
                    WHERE company_id = :company_id AND is_active = 1
                    AND DATE_ADD(birth_date, 
                        INTERVAL YEAR(CURDATE())-YEAR(birth_date)
                                + IF(DAYOFYEAR(CURDATE()) > DAYOFYEAR(birth_date),1,0)
                        YEAR)
                    BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)");
    $this->db->bind(':company_id', $companyId);
    $this->db->bind(':days', $days);
    return $this->db->resultSet();
}

// ----------------------------------------------------------------------
// En app/models/Suggestion.php, asegúrate de tener este método:
// ----------------------------------------------------------------------
public function getLatestSuggestionByCompany($companyId) {
    $this->db->query("SELECT s.*, u.full_name FROM suggestions s
                    JOIN users u ON s.user_id = u.id
                    WHERE u.company_id = :company_id
                    ORDER BY s.created_at DESC LIMIT 1");
    $this->db->bind(':company_id', $companyId);
    return $this->db->single();
}

// ----------------------------------------------------------------------
// En app/models/WorkSchedule.php, añade este método:
// ----------------------------------------------------------------------
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

// ----------------------------------------------------------------------
// En app/models/Request.php, añade este método:
// ----------------------------------------------------------------------
public function countOnLeaveTodayByCompany($companyId) {
    $sql = "SELECT COUNT(DISTINCT r.user_id) as count
            FROM requests r
            JOIN users u ON r.user_id = u.id
            WHERE u.company_id = :company_id 
            AND r.status = 'Aprobado'
            AND CURDATE() BETWEEN r.start_date AND r.end_date";
    $this->db->query($sql);
    $this->db->bind(':company_id', $companyId);
    $row = $this->db->single();
    return $row ? $row->count : 0;
}
