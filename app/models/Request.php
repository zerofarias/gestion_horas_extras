<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/models/Request.php (VERSIÓN COMPLETA Y FINAL)
// ----------------------------------------------------------------------

class Request {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    /**
     * Obtiene todos los tipos de solicitud desde la base de datos.
     * @return array Un array de objetos con los tipos de solicitud.
     */
    public function getRequestTypes(){
        $this->db->query("SELECT * FROM request_types ORDER BY name ASC");
        return $this->db->resultSet();
    }

    /**
     * Crea una nueva solicitud en la base de datos.
     * @param array $data Los datos de la solicitud a crear.
     * @return bool True si se creó con éxito, false si no.
     */
    public function createRequest($data){
        $this->db->query('INSERT INTO requests (user_id, request_type_id, start_date, end_date, reason) VALUES (:user_id, :request_type_id, :start_date, :end_date, :reason)');
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':request_type_id', $data['request_type_id']);
        $this->db->bind(':start_date', $data['start_date']);
        $this->db->bind(':end_date', $data['end_date']);
        $this->db->bind(':reason', $data['reason']);
        return $this->db->execute();
    }

    /**
     * Obtiene todas las solicitudes de un usuario específico.
     * @param int $userId El ID del usuario.
     * @return array Un array de objetos con las solicitudes del usuario.
     */
    public function getRequestsByUserId($userId){
        $this->db->query("
            SELECT r.*, rt.name as type_name, rt.color 
            FROM requests r
            JOIN request_types rt ON r.request_type_id = rt.id
            WHERE r.user_id = :user_id 
            ORDER BY r.start_date DESC
        ");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    /**
     * Obtiene todas las solicitudes de todos los usuarios.
     * @return array Un array de objetos con todas las solicitudes.
     */
    public function getAllRequests(){
        $this->db->query("
            SELECT r.*, u.full_name, rt.name as type_name, rt.color
            FROM requests r
            JOIN users u ON r.user_id = u.id
            JOIN request_types rt ON r.request_type_id = rt.id
            ORDER BY r.start_date DESC
        ");
        return $this->db->resultSet();
    }
    
    /**
     * Obtiene una única solicitud por su ID.
     * @param int $id El ID de la solicitud.
     * @return object|false El objeto de la solicitud o false si no se encuentra.
     */
    public function getRequestById($id){
        $this->db->query("SELECT r.*, u.full_name FROM requests r JOIN users u ON r.user_id = u.id WHERE r.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Actualiza una solicitud existente.
     * @param array $data Los nuevos datos de la solicitud.
     * @return bool True si se actualizó con éxito, false si no.
     */
    public function updateRequest($data){
        $this->db->query('UPDATE requests SET request_type_id = :request_type_id, start_date = :start_date, end_date = :end_date, reason = :reason, status = :status WHERE id = :id');
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':request_type_id', $data['request_type_id']);
        $this->db->bind(':start_date', $data['start_date']);
        $this->db->bind(':end_date', $data['end_date']);
        $this->db->bind(':reason', $data['reason']);
        $this->db->bind(':status', $data['status']);
        return $this->db->execute();
    }

    /**
     * Actualiza solo el estado de una solicitud (Aprobado/Rechazado).
     * @param int $id El ID de la solicitud.
     * @param string $status El nuevo estado.
     * @return bool True si se actualizó con éxito, false si no.
     */
    public function updateRequestStatus($id, $status){
        $this->db->query('UPDATE requests SET status = :status WHERE id = :id');
        $this->db->bind(':id', $id);
        $this->db->bind(':status', $status);
        return $this->db->execute();
    }

    /**
     * Elimina una solicitud por su ID.
     * @param int $id El ID de la solicitud.
     * @return bool True si se eliminó con éxito, false si no.
     */
    public function deleteRequest($id){
        $this->db->query('DELETE FROM requests WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getActiveApprovedRequestsForToday(){
        $this->db->query("
            SELECT r.id, u.full_name, u.profile_picture, rt.name as type_name
            FROM requests r
            JOIN users u ON r.user_id = u.id
            JOIN request_types rt ON r.request_type_id = rt.id
            WHERE r.status = 'Aprobado'
            AND CURDATE() BETWEEN r.start_date AND IFNULL(r.end_date, r.start_date)
        ");
        return $this->db->resultSet();
    }


    public function getApprovedRequestsForUserCalendar($userId){
        $this->db->query("
            SELECT r.*, rt.name as type_name, rt.color 
            FROM requests r
            JOIN request_types rt ON r.request_type_id = rt.id
            WHERE r.user_id = :user_id AND r.status = 'Aprobado'
        ");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }


    public function getPendingRequestsWithDetails($companyId) {
        $sql = "SELECT r.id, u.full_name, rt.name as type_name, r.start_date, r.end_date
                FROM requests r
                JOIN users u ON r.user_id = u.id
                JOIN request_types rt ON r.request_type_id = rt.id
                WHERE u.company_id = :company_id AND r.status = 'Pendiente'
                ORDER BY r.start_date ASC";
        $this->db->query($sql);
        $this->db->bind(':company_id', $companyId);
        return $this->db->resultSet();
    }

    public function getMonthlyRequestSummary($companyId, $month) {
        // CORRECCIÓN: Se cambió 'GROUP BY rt.type_name' por 'GROUP BY rt.name'
        $sql = "SELECT rt.name as type_name, COUNT(r.id) as count
                FROM requests r
                JOIN request_types rt ON r.request_type_id = rt.id
                JOIN users u ON r.user_id = u.id
                WHERE u.company_id = :company_id AND r.status = 'Aprobado' AND DATE_FORMAT(r.start_date, '%Y-%m') = :month
                GROUP BY rt.name"; // <-- La corrección está aquí
        $this->db->query($sql);
        $this->db->bind(':company_id', $companyId);
        $this->db->bind(':month', $month);
        return $this->db->resultSet();
    }

    /**
     * Obtiene las solicitudes aprobadas para el planificador.
     */
    public function getApprovedRequestsForPeriod($startDate, $endDate, $companyId) {
        $sql = "SELECT 
                    r.*, 
                    u.full_name,
                    rt.name as type_name,
                    rt.color
                FROM requests r
                JOIN users u ON r.user_id = u.id
                JOIN request_types rt ON r.request_type_id = rt.id
                WHERE u.company_id = :company_id
                AND r.status = 'Aprobado'
                AND r.start_date <= :end_date 
                AND r.end_date >= :start_date";

        $this->db->query($sql);
        $this->db->bind(':company_id', $companyId);
        $this->db->bind(':start_date', $startDate);
        $this->db->bind(':end_date', $endDate);
        
        return $this->db->resultSet();
    }

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
    

}
?>
