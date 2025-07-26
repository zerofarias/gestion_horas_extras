<?php
// ----------------------------------------------------------------------
// ARCHIVO 2: app/models/Company.php (NUEVO ARCHIVO)
// Este nuevo modelo manejará toda la lógica de la base de datos
// para las empresas. Debes CREAR este archivo.
// ----------------------------------------------------------------------

class Company {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    public function getAllCompanies(){
        $this->db->query("SELECT * FROM companies ORDER BY name ASC");
        return $this->db->resultSet();
    }

    public function createCompany($name){
        $this->db->query('INSERT INTO companies (name) VALUES (:name)');
        $this->db->bind(':name', $name);
        return $this->db->execute();
    }
}
?>