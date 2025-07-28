<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/models/User.php (VERSIÓN CON LOGIN CORREGIDO)
// ----------------------------------------------------------------------

class User {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    // Encuentra un usuario por su nombre de usuario (para login y registro)
    public function findUserByUsername($username){
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        $row = $this->db->single();

        return ($this->db->rowCount() > 0);
    }

    // Procesa el login del usuario
    public function login($username, $password){
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        $row = $this->db->single();

        if($row){
            // --- CORRECCIÓN APLICADA AQUÍ ---
            // Se cambió '$row->password_hash' por '$row->password' para que coincida con la columna de la base de datos.
            $hashed_password = $row->password; 
            
            if(password_verify($password, $hashed_password)){
                return $row; // Devuelve el objeto de usuario si la contraseña es correcta
            }
        }
        return false; // Devuelve falso si el login falla
    }

    // Crea un nuevo usuario en la base de datos
    public function createUser($data){
        // Asegúrate de que al crear el usuario, el hash se guarde en la columna 'password'.
        $this->db->query('INSERT INTO users (username, full_name, password, role, company_id, profile_picture) VALUES (:username, :full_name, :password, :role, :company_id, :profile_picture)');
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':password', $data['password_hash']); // El hash se guarda en la columna 'password'
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':company_id', $_SESSION['user_company_id']);
        $this->db->bind(':profile_picture', $data['profile_picture']);

        return $this->db->execute();
    }

    // Actualiza la ficha de un empleado
    public function updateUser($data){
        $sql = 'UPDATE users SET full_name = :full_name, role = :role, clock_id = :clock_id, weekly_hour_limit = :weekly_hour_limit';
        
        if(!empty($data['password_hash'])){
            $sql .= ', password = :password'; // Actualizar la columna 'password'
        }
        
        $sql .= ' WHERE id = :id';
        
        $this->db->query($sql);

        $clockId = !empty(trim($data['clock_id'])) ? trim($data['clock_id']) : null;

        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':clock_id', $clockId);
        $this->db->bind(':weekly_hour_limit', $data['weekly_hour_limit']);
        $this->db->bind(':id', $data['id']);

        if(!empty($data['password_hash'])){
            $this->db->bind(':password', $data['password_hash']);
        }

        return $this->db->execute();
    }

    // Obtiene todos los datos de un usuario por su ID
    public function getUserById($id){
        $this->db->query('SELECT * FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        return $row;
    }

    // Obtiene todos los usuarios de una compañía
    public function getUsersByCompany($companyId) {
        $this->db->query("SELECT id, username, full_name, role, is_active, weekly_hour_limit FROM users WHERE company_id = :company_id ORDER BY full_name ASC");
        $this->db->bind(':company_id', $companyId);
        return $this->db->resultSet();
    }

    // Obtiene todos los usuarios
    public function getAllUsers(){
        $this->db->query('SELECT * FROM users ORDER BY full_name ASC');
        return $this->db->resultSet();
    }

    // Cambia el estado de un usuario (activo/inactivo)
    public function toggleUserStatus($id){
        $this->db->query('UPDATE users SET is_active = !is_active WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    /**
     * NUEVO MÉTODO AÑADIDO PARA EL DASHBOARD
     * Cuenta el número de usuarios activos en una compañía.
     */
    public function countActiveUsersByCompany($companyId) {
        $this->db->query("SELECT COUNT(id) as count FROM users WHERE company_id = :company_id AND is_active = 1");
        $this->db->bind(':company_id', $companyId);
        $row = $this->db->single();
        return $row ? $row->count : 0;
    }

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

}
?>
