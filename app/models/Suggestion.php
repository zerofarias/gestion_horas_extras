<?php
// ----------------------------------------------------------------------
// ARCHIVO 3: app/models/Suggestion.php (NUEVO ARCHIVO)
// Este nuevo modelo manejará la lógica de la base de datos para las sugerencias.
// Debes CREAR este archivo en la ruta app/models/.
// ----------------------------------------------------------------------

class Suggestion {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    public function createSuggestion($data){
        $this->db->query('INSERT INTO suggestions (company_id, suggestion_text) VALUES (:company_id, :suggestion_text)');
        $this->db->bind(':company_id', $data['company_id']);
        $this->db->bind(':suggestion_text', $data['suggestion_text']);
        return $this->db->execute();
    }

    public function getAllSuggestionsByCompany($companyId){
        $this->db->query("SELECT * FROM suggestions WHERE company_id = :company_id ORDER BY created_at DESC");
        $this->db->bind(':company_id', $companyId);
        return $this->db->resultSet();
    }
}
?>