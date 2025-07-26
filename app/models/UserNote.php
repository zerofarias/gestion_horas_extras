<?php
// ----------------------------------------------------------------------
// ARCHIVO 2: app/models/UserNote.php (NUEVO ARCHIVO)
// Este nuevo modelo manejará la lógica de las notas/incidencias.
// Debes CREAR este archivo en la ruta app/models/.
// ----------------------------------------------------------------------

class UserNote {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    public function getNotesByUserId($userId){
        $this->db->query("
            SELECT un.*, u.full_name as admin_name 
            FROM user_notes un
            JOIN users u ON un.admin_id = u.id
            WHERE un.user_id = :user_id 
            ORDER BY un.created_at DESC
        ");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }

    public function addNote($data){
        $this->db->query('INSERT INTO user_notes (user_id, admin_id, note) VALUES (:user_id, :admin_id, :note)');
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':admin_id', $data['admin_id']);
        $this->db->bind(':note', $data['note']);
        return $this->db->execute();
    }
}
?>