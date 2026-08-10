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

    public function getAllCompanies() {
        $this->db->query('SELECT * FROM companies ORDER BY name ASC');
        return $this->db->resultSet();
    }

    public function getById($id) {
        $this->db->query('SELECT * FROM companies WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getNameById($id) {
        if (!$id) {
            return null;
        }
        $row = $this->getById($id);
        return $row ? $row->name : null;
    }

    public function getIdByName($name) {
        $name = trim((string)$name);
        if ($name === '') {
            return null;
        }
        $this->db->query('SELECT id FROM companies WHERE name = :name LIMIT 1');
        $this->db->bind(':name', $name);
        $row = $this->db->single();
        return $row ? (int)$row->id : null;
    }

    public function getDefaultCompanyId() {
        $name = defined('DEFAULT_COMPANY_NAME') ? DEFAULT_COMPANY_NAME : 'Ecofarma';
        return $this->getIdByName($name);
    }

    public function createCompany($name){
        $this->db->query('INSERT INTO companies (name) VALUES (:name)');
        $this->db->bind(':name', $name);
        return $this->db->execute();
    }

    public function updateCompany($id, $name, $showOvertime = null, $showCpExtras = null) {
        $id = (int)$id;
        $name = trim((string)$name);
        $sets = ['name = :name'];
        if ($showOvertime !== null && $this->hasShowOvertimeColumn()) {
            $sets[] = 'show_overtime = :show_overtime';
        }
        if ($showCpExtras !== null && $this->hasShowCpExtrasColumn()) {
            $sets[] = 'show_cp_extras = :show_cp_extras';
        }
        $this->db->query('UPDATE companies SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $this->db->bind(':name', $name);
        if ($showOvertime !== null && $this->hasShowOvertimeColumn()) {
            $this->db->bind(':show_overtime', $showOvertime ? 1 : 0);
        }
        if ($showCpExtras !== null && $this->hasShowCpExtrasColumn()) {
            $this->db->bind(':show_cp_extras', $showCpExtras ? 1 : 0);
        }
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function hasShowOvertimeColumn() {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        try {
            $this->db->query("SHOW COLUMNS FROM `companies` LIKE 'show_overtime'");
            $ready = (bool)$this->db->single();
        } catch (Throwable $e) {
            $ready = false;
        }
        return $ready;
    }

    public function hasShowCpExtrasColumn() {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        try {
            $this->db->query("SHOW COLUMNS FROM `companies` LIKE 'show_cp_extras'");
            $ready = (bool)$this->db->single();
        } catch (Throwable $e) {
            $ready = false;
        }
        return $ready;
    }
}
?>