<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/models/Holiday.php (NUEVO ARCHIVO)
// ----------------------------------------------------------------------

class Holiday {
    private $db;

    public function __construct(){
        $this->db = new Database;
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
        $this->db->query("DELETE FROM holidays WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
?>
