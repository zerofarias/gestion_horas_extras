<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/models/Suggestion.php (VERSIÓN CORREGIDA Y FINAL)
// ----------------------------------------------------------------------

class Suggestion {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    /**
     * Obtiene la última sugerencia de una compañía para el dashboard.
     * VERSIÓN CORREGIDA: Ya no intenta unirse con la tabla de usuarios.
     */
    public function getLatestSuggestionByCompany($companyId) {
        $this->db->query("SELECT * FROM suggestions 
                        WHERE company_id = :company_id
                        ORDER BY created_at DESC LIMIT 1");
        
        $this->db->bind(':company_id', $companyId);
        return $this->db->single();
    }

    /**
     * Obtiene todas las sugerencias de una compañía.
     * VERSIÓN CORREGIDA: Ya no intenta unirse con la tabla de usuarios.
     */
    public function getAllSuggestionsByCompany($companyId) {
        $this->db->query("SELECT * FROM suggestions 
                        WHERE company_id = :company_id
                        ORDER BY created_at DESC");
        $this->db->bind(':company_id', $companyId);
        return $this->db->resultSet();
    }

    /**
     * Añade una nueva sugerencia.
     * VERSIÓN CORREGIDA: Inserta el company_id en lugar del user_id.
     */
    public function addSuggestion($data) {
        // Asumimos que $data['company_id'] y $data['suggestion_text'] son proporcionados
        $this->db->query("INSERT INTO suggestions (company_id, suggestion_text) VALUES (:company_id, :suggestion_text)");
        $this->db->bind(':company_id', $data['company_id']);
        $this->db->bind(':suggestion_text', $data['suggestion_text']);
        return $this->db->execute();
    }
}
?>
