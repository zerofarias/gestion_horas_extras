<?php
// ----------------------------------------------------------------------
// ARCHIVO 2: app/models/Shift.php (NUEVO ARCHIVO)
// Este nuevo modelo manejará toda la lógica de la base de datos
// para los turnos. Debes CREAR este archivo en la ruta app/models/.
// ----------------------------------------------------------------------

class Shift {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    public function getShiftsByCompany($companyId){
        $this->db->query("SELECT * FROM shifts WHERE company_id = :company_id ORDER BY shift_name ASC");
        $this->db->bind(':company_id', $companyId);
        return $this->db->resultSet();
    }

    public function createShift($data){
        $start = new DateTime($data['start_time']);
        $end = new DateTime($data['end_time']);
        $interval = $start->diff($end);
        $totalHours = $interval->h + ($interval->i / 60);

        $this->db->query('INSERT INTO shifts (company_id, shift_name, start_time, end_time, total_hours) VALUES (:company_id, :shift_name, :start_time, :end_time, :total_hours)');
        $this->db->bind(':company_id', $data['company_id']);
        $this->db->bind(':shift_name', $data['shift_name']);
        $this->db->bind(':start_time', $data['start_time']);
        $this->db->bind(':end_time', $data['end_time']);
        $this->db->bind(':total_hours', $totalHours);
        return $this->db->execute();
    }
    
    public function deleteShift($id){
        $this->db->query('DELETE FROM shifts WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
?>