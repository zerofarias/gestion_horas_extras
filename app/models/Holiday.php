<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/models/Holiday.php (NUEVO ARCHIVO)
// ----------------------------------------------------------------------

class Holiday {
    private $db;

    public function __construct($db = null){
        $this->db = $db instanceof Database ? $db : new Database;
    }

    public function getHolidaysByCompany($companyId) {
        $this->db->query("SELECT * FROM holidays WHERE company_id = :company_id ORDER BY holiday_date DESC");
        $this->db->bind(':company_id', $companyId);
        return $this->db->resultSet();
    }

    public function getHolidaysForPeriod($companyId, $startDate, $endDate) {
        $this->db->query("SELECT * FROM holidays WHERE company_id = :company_id AND holiday_date BETWEEN :start_date AND :end_date");
        $this->db->bind(':company_id', $companyId);
        $this->db->bind(':start_date', $startDate);
        $this->db->bind(':end_date', $endDate);
        return $this->db->resultSet();
    }

    public function createHoliday($data) {
        $this->db->query("INSERT INTO holidays (holiday_date, name, company_id) VALUES (:holiday_date, :name, :company_id)");
        $this->db->bind(':holiday_date', $data['holiday_date']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':company_id', $data['company_id']);
        return $this->db->execute();
    }

    public function deleteHoliday($id) {
        $this->db->query('DELETE FROM holidays WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function deleteHolidayForCompany($id, $companyId) {
        $this->db->query('DELETE FROM holidays WHERE id = ? AND company_id = ?');
        return $this->db->execute([(int)$id, (int)$companyId]);
    }

    public function isHolidayForCompany($companyId, $date) {
        $companyId = (int)$companyId;
        $date = trim((string)$date);
        if ($companyId <= 0 || $date === '') {
            return false;
        }
        $this->db->query('SELECT id FROM holidays WHERE company_id = ? AND holiday_date = ? LIMIT 1');
        $this->db->single([$companyId, $date]);
        return $this->db->rowCount() > 0;
    }
}
?>
