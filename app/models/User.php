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
        // 1. Construir la consulta SQL base con marcadores de nombre (named placeholders)
        $sql = "UPDATE users SET 
                    full_name = :full_name, birth_date = :birth_date, address = :address, phone = :phone, 
                    children_count = :children_count, email = :email, emergency_contact_name = :emergency_contact_name, 
                    emergency_contact_phone = :emergency_contact_phone, start_date = :start_date, company_id = :company_id, 
                    health_insurance = :health_insurance, username = :username, role = :role, clock_id = :clock_id, 
                    weekly_hour_limit = :weekly_hour_limit";
        
        // 2. Crear un array asociativo para los parámetros que se vincularán
        $params = [
            ':full_name' => $data['full_name'],
            ':birth_date' => $data['birth_date'],
            ':address' => $data['address'],
            ':phone' => $data['phone'],
            ':children_count' => $data['children_count'],
            ':email' => $data['email'],
            ':emergency_contact_name' => $data['emergency_contact_name'],
            ':emergency_contact_phone' => $data['emergency_contact_phone'],
            ':start_date' => $data['start_date'],
            ':company_id' => $data['company_id'],
            ':health_insurance' => $data['health_insurance'],
            ':username' => $data['username'],
            ':role' => $data['role'],
            ':clock_id' => $data['clock_id'],
            ':weekly_hour_limit' => $data['weekly_hour_limit']
        ];

        // 3. Añadir campos a la consulta y a los parámetros SOLO si se han proporcionado
        if (!empty($data['profile_picture'])) {
            $sql .= ", profile_picture = :profile_picture";
            $params[':profile_picture'] = $data['profile_picture'];
        }
        if (!empty($data['dni_photo_front'])) {
            $sql .= ", dni_photo_front = :dni_photo_front";
            $params[':dni_photo_front'] = $data['dni_photo_front'];
        }
        if (!empty($data['dni_photo_back'])) {
            $sql .= ", dni_photo_back = :dni_photo_back";
            $params[':dni_photo_back'] = $data['dni_photo_back'];
        }
        if (!empty($data['password'])) {
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            $sql .= ", password = :password";
            $params[':password'] = $hashed_password;
        }

        // 4. Finalizar la consulta SQL y añadir el ID
        $sql .= " WHERE id = :id";
        $params[':id'] = $data['id'];

        // 5. Preparar la consulta
        $this->db->query($sql);

        // 6. Vincular todos los parámetros en un bucle usando el método bind() existente
        foreach($params as $key => $value){
            $this->db->bind($key, $value);
        }

        // 7. Ejecutar y devolver el resultado
        if($this->db->execute()){
            return true;
        } else {
            return false;
        }
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
public function getBirthdayInfo($companyId, $limit = 5) {
        // --- Búsqueda de Cumpleaños de Hoy ---
        $this->db->query("
            SELECT id, full_name, profile_picture, birth_date
            FROM users 
            WHERE company_id = :company_id 
            AND is_active = 1
            AND MONTH(birth_date) = MONTH(CURDATE()) 
            AND DAY(birth_date) = DAY(CURDATE())
        ");
        $this->db->bind(':company_id', $companyId);
        $todaysBirthdays = $this->db->resultSet();

        // --- Búsqueda de Próximos Cumpleaños ---
        // Esta consulta ordena los cumpleaños de forma circular, empezando desde mañana.
        $sqlUpcoming = "
            SELECT id, full_name, profile_picture, birth_date
            FROM users
            WHERE 
                company_id = :company_id 
                AND is_active = 1 
                AND birth_date IS NOT NULL
                -- Excluimos a los que cumplen años hoy de la lista de 'próximos'
                AND NOT (MONTH(birth_date) = MONTH(CURDATE()) AND DAY(birth_date) = DAY(CURDATE()))
            ORDER BY 
                -- Esta lógica ordena los cumpleaños que ya pasaron este año al final de la lista
                CASE 
                    WHEN MONTH(birth_date) < MONTH(CURDATE()) THEN 1
                    WHEN MONTH(birth_date) = MONTH(CURDATE()) AND DAY(birth_date) < DAY(CURDATE()) THEN 1
                    ELSE 0
                END ASC,
                -- Luego ordena por mes y día para encontrar el más cercano
                MONTH(birth_date) ASC, 
                DAY(birth_date) ASC
            LIMIT :limit
        ";
        $this->db->query($sqlUpcoming);
        $this->db->bind(':company_id', $companyId);
        $this->db->bind(':limit', $limit);
        $upcomingBirthdays = $this->db->resultSet();

        return [
            'today' => $todaysBirthdays,
            'upcoming' => $upcomingBirthdays
        ];
    }

}
?>
