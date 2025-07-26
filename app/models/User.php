<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/models/User.php (VERSIÓN COMPLETA Y FINAL)
// ----------------------------------------------------------------------

class User {
    private $db;

    public function __construct(){
        $this->db = new Database;
    }

    /**
     * Obtiene todos los usuarios de la base de datos.
     */
    public function getAllUsers(){
        $this->db->query("SELECT id, username, full_name, role, profile_picture, is_active FROM users ORDER BY full_name ASC");
        return $this->db->resultSet();
    }

    /**
     * Crea un nuevo usuario en la base de datos.
     */
    public function createUser($data){
        $this->db->query('INSERT INTO users (username, password, full_name, role, profile_picture) VALUES (:username, :password, :full_name, :role, :profile_picture)');
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':password', $data['password_hash']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':profile_picture', $data['profile_picture']);
        return $this->db->execute();
    }

    /**
     * Busca un usuario por su ID.
     */
    public function getUserById($id){
        $this->db->query('SELECT * FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Actualiza los datos de un usuario en la base de datos.
     */
     public function updateUser($data){
        $query = 'UPDATE users SET 
                    full_name = :full_name, birth_date = :birth_date, address = :address,
                    phone = :phone, children_count = :children_count, email = :email,
                    start_date = :start_date, health_insurance = :health_insurance,
                    company_id = :company_id, emergency_contact_name = :emergency_contact_name,
                    emergency_contact_phone = :emergency_contact_phone, username = :username,
                    role = :role, clock_id = :clock_id';

        if (!empty($data['password'])) { $query .= ', password = :password'; }
        if ($data['profile_picture_new_name']) { $query .= ', profile_picture = :profile_picture'; }
        if ($data['dni_photo_front_new_name']) { $query .= ', dni_photo_front = :dni_photo_front'; }
        if ($data['dni_photo_back_new_name']) { $query .= ', dni_photo_back = :dni_photo_back'; }
        $query .= ' WHERE id = :id';

        $this->db->query($query);

        $this->db->bind(':id', $data['id']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':birth_date', $data['birth_date']);
        $this->db->bind(':address', $data['address']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':children_count', $data['children_count']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':start_date', $data['start_date']);
        $this->db->bind(':health_insurance', $data['health_insurance']);
        $this->db->bind(':company_id', $data['company_id']);
        $this->db->bind(':emergency_contact_name', $data['emergency_contact_name']);
        $this->db->bind(':emergency_contact_phone', $data['emergency_contact_phone']);
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':clock_id', $data['clock_id']);

        if (!empty($data['password'])) { $this->db->bind(':password', $data['password_hash']); }
        if ($data['profile_picture_new_name']) { $this->db->bind(':profile_picture', $data['profile_picture_new_name']); }
        if ($data['dni_photo_front_new_name']) { $this->db->bind(':dni_photo_front', $data['dni_photo_front_new_name']); }
        if ($data['dni_photo_back_new_name']) { $this->db->bind(':dni_photo_back', $data['dni_photo_back_new_name']); }

        return $this->db->execute();
    }


    /**
     * Busca un usuario por su nombre de usuario.
     */
    public function findUserByUsername($username){
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        $row = $this->db->single();
        return ($this->db->rowCount() > 0) ? $row : false;
    }

    /**
     * Busca un usuario por su ID del reloj.
     */
    public function findUserByClockId($clockId){
        $this->db->query('SELECT id FROM users WHERE clock_id = :clock_id AND is_active = 1');
        $this->db->bind(':clock_id', $clockId);
        return $this->db->single();
    }

    /**
     * Procesa el login y comprueba si el usuario está activo.
     */
    public function login($username, $password){
        $row = $this->findUserByUsername($username);
        if($row == false){
            return false;
        }
        if($row->is_active == 0){
            return 'inactive';
        }
        $hashed_password = $row->password;
        if(password_verify($password, $hashed_password)){
            return $row;
        } else {
            return false;
        }
    }

    /**
     * Cambia el estado (activo/inactivo) de un usuario.
     */
    public function toggleUserStatus($id){
        $this->db->query('UPDATE users SET is_active = !is_active WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

     public function getTodaysBirthdays(){
        $this->db->query("
            SELECT id, full_name, profile_picture 
            FROM users 
            WHERE MONTH(birth_date) = MONTH(CURDATE()) 
            AND DAY(birth_date) = DAY(CURDATE())
            AND is_active = 1
        ");
        return $this->db->resultSet();
    }
    public function getUserIdsByClockIds($clockIds) {
        if (empty($clockIds)) {
            return [];
        }
    
        // Creamos placeholders nombrados: :id0, :id1, ...
        $placeholders = [];
        foreach ($clockIds as $index => $id) {
            $placeholders[] = ":id$index";
        }
    
        // Unimos los placeholders
        $inClause = implode(',', $placeholders);
    
        // Consulta con placeholders nombrados
        $this->db->query("SELECT id FROM users WHERE clock_id IN ($inClause)");
    
        // Bindeamos cada valor al placeholder correspondiente
        foreach ($clockIds as $index => $id) {
            $this->db->bind(":id$index", $id);
        }
    
        // Ejecutamos y devolvemos los resultados
        $results = $this->db->resultSet(); // sin parámetros porque ya se bindearon antes
    
        $userIds = [];
        foreach ($results as $row) {
            $userIds[] = $row->id;
        }
    
        return $userIds;
    }

    
}
?>