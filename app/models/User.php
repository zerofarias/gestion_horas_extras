<?php

class User {
    private $db;

    public function __construct($db = null){
        $this->db = $db instanceof Database ? $db : new Database;
    }

    public function findUserByUsername($username){
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        $row = $this->db->single();
        return ($this->db->rowCount() > 0);
    }

    public function getUserIdsByClockIds($clockIds) {
        if (empty($clockIds)) {
            return [];
        }
    
        $placeholders = [];
        foreach ($clockIds as $index => $id) {
            $placeholders[] = ":id$index";
        }
    
        $inClause = implode(',', $placeholders);
    
        $this->db->query("SELECT DISTINCT user_id FROM user_clock_mappings WHERE user_clock_id IN ($inClause)");
    
        foreach ($clockIds as $index => $id) {
            $this->db->bind(":id$index", $id);
        }
    
        $results = $this->db->resultSet();
    
        $userIds = [];
        foreach ($results as $row) {
            $userIds[] = $row->user_id;
        }
    
        return $userIds;
    }

    public function login($username, $password){
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        $row = $this->db->single();
        if ($row) {
            if (isset($row->is_active) && !(int)$row->is_active) {
                return 'inactive';
            }
            $hashed_password = $row->password;
            if (password_verify($password, $hashed_password)) {
                return $row;
            }
        }
        return false;
    }

    public function getProfilePictureById($userId) {
        $this->db->query('SELECT profile_picture FROM users WHERE id = :id LIMIT 1');
        $this->db->bind(':id', (int)$userId);
        $row = $this->db->single();
        return $row ? (string)($row->profile_picture ?? '') : '';
    }

    public static function sexOptions() {
        return [
            ''  => '— No especificado',
            'M' => 'Masculino',
            'F' => 'Femenino',
            'X' => 'Otro / X',
        ];
    }

    public static function genderOptions() {
        return [
            ''                  => '— No especificado',
            'mujer'             => 'Mujer',
            'hombre'            => 'Hombre',
            'no_binario'        => 'No binario',
            'otro'              => 'Otro',
            'prefiero_no_decir' => 'Prefiero no decir',
        ];
    }

    public static function sexLabel($code) {
        $opts = self::sexOptions();
        return $opts[$code] ?? ($code ?: '—');
    }

    public static function genderLabel($code) {
        $opts = self::genderOptions();
        return $opts[$code] ?? ($code ?: '—');
    }

    public static function organizationGroupOptions() {
        return [
            'paviotti' => 'Paviotti',
            'moderna' => 'Moderna',
        ];
    }

    public static function normalizeOrganizationGroup($value) {
        $value = strtolower(trim((string)$value));
        return array_key_exists($value, self::organizationGroupOptions()) ? $value : 'paviotti';
    }

    /** Campos de perfil desde POST (crear / editar usuario). */
    public static function profileFromPost(array $post) {
        $sex = isset($post['sex']) ? trim((string)$post['sex']) : '';
        if (!in_array($sex, ['M', 'F', 'X'], true)) {
            $sex = null;
        }
        $gender = isset($post['gender']) ? trim((string)$post['gender']) : '';
        $allowedGender = array_keys(array_filter(self::genderOptions(), function ($k) {
            return $k !== '';
        }, ARRAY_FILTER_USE_KEY));
        if ($gender !== '' && !in_array($gender, $allowedGender, true)) {
            $gender = null;
        } elseif ($gender === '') {
            $gender = null;
        }
        $birth = isset($post['birth_date']) ? trim((string)$post['birth_date']) : '';
        if ($birth !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth)) {
            $birth = null;
        } elseif ($birth === '') {
            $birth = null;
        }
        return [
            'email'                   => isset($post['email']) ? trim((string)$post['email']) : null,
            'phone_number'            => isset($post['phone_number']) ? trim((string)$post['phone_number']) : null,
            'address'                 => isset($post['address']) ? trim((string)$post['address']) : null,
            'document_number'         => isset($post['document_number']) ? trim((string)$post['document_number']) : null,
            'cuil'                    => isset($post['cuil']) ? trim((string)$post['cuil']) : null,
            'sex'                     => $sex,
            'gender'                  => $gender,
            'birth_date'              => $birth,
            'emergency_contact_name'  => isset($post['emergency_contact_name']) ? trim((string)$post['emergency_contact_name']) : null,
            'emergency_contact_phone' => isset($post['emergency_contact_phone']) ? trim((string)$post['emergency_contact_phone']) : null,
        ];
    }

    public function isProfileExtendedReady() {
        try {
            $this->db->query("SHOW COLUMNS FROM `users` LIKE 'phone_number'");
            return (bool)$this->db->single();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function isAreaReady() {
        try {
            $this->db->query("SHOW COLUMNS FROM `users` LIKE 'area_id'");
            return (bool)$this->db->single();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function isOrganizationGroupReady() {
        try {
            $this->db->query("SHOW COLUMNS FROM `users` LIKE 'employee_group'");
            return (bool)$this->db->single();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function isVacationProfileReady() {
        try {
            $this->db->query("SHOW COLUMNS FROM `users` LIKE 'hire_date'");
            return (bool)$this->db->single();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function isProbationDateReady() {
        try {
            $this->db->query("SHOW COLUMNS FROM `users` LIKE 'probation_start_date'");
            return (bool)$this->db->single();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function updateVacationProfile($userId, $hireDate, $agreementId = null, $probationStartDate = null) {
        if (!$this->isVacationProfileReady()) {
            return false;
        }
        // Parámetros posicionales: evita HY093 con nombres tipo :p_agreement_id / :p_uid en PDO+MySQL.
        $sets = ['hire_date = ?', 'agreement_id = ?'];
        $params = [$hireDate ?: null, $agreementId];
        if ($this->isProbationDateReady()) {
            $sets[] = 'probation_start_date = ?';
            $params[] = $probationStartDate ?: null;
        }
        $params[] = (int)$userId;
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $this->db->query($sql);
        return $this->db->execute($params);
    }

    private function employmentColumnNames() {
        $cols = [];
        if ($this->isProbationDateReady()) {
            $cols[] = 'probation_start_date';
        }
        if ($this->isVacationProfileReady()) {
            $cols[] = 'hire_date';
            $cols[] = 'agreement_id';
        }
        return $cols;
    }

    private function employmentValues(array $data) {
        $vals = [];
        if ($this->isProbationDateReady()) {
            $vals[] = !empty($data['probation_start_date']) ? $data['probation_start_date'] : null;
        }
        if ($this->isVacationProfileReady()) {
            $vals[] = !empty($data['hire_date']) ? $data['hire_date'] : null;
            $vals[] = isset($data['agreement_id']) && (int)$data['agreement_id'] > 0 ? (int)$data['agreement_id'] : null;
        }
        return $vals;
    }

    public function createUser($data) {
        $companyId = isset($data['company_id']) && (int)$data['company_id'] > 0
            ? (int)$data['company_id']
            : 0;
        if ($companyId <= 0) {
            $companyModel = new Company();
            $companyId = (int)($companyModel->getDefaultCompanyId() ?? 0);
        }
        if ($companyId <= 0) {
            $companyId = (int)($_SESSION['user_company_id'] ?? 0);
        }

        $areaId = isset($data['area_id']) && (int)$data['area_id'] > 0 ? (int)$data['area_id'] : null;
        $companyValue = $companyId > 0 ? $companyId : null;

        // Parámetros posicionales: evita HY093 con nombres tipo :company_id / :phone_number en PDO+MySQL.
        $cols = [];
        $vals = [];

        if ($this->isProfileExtendedReady()) {
            $cols = [
                'username', 'full_name', 'email', 'phone_number', 'address', 'document_number', 'cuil',
                'sex', 'gender', 'birth_date', 'emergency_contact_name', 'emergency_contact_phone',
                'password', 'role', 'company_id',
            ];
            $vals = [
                $data['username'],
                $data['full_name'],
                $data['email'] ?? null,
                $data['phone_number'] ?? null,
                $data['address'] ?? null,
                $data['document_number'] ?? null,
                $data['cuil'] ?? null,
                $data['sex'] ?? null,
                $data['gender'] ?? null,
                $data['birth_date'] ?? null,
                $data['emergency_contact_name'] ?? null,
                $data['emergency_contact_phone'] ?? null,
                $data['password_hash'],
                $data['role'],
                $companyValue,
            ];
        } else {
            $cols = ['username', 'full_name', 'password', 'role', 'company_id'];
            $vals = [
                $data['username'],
                $data['full_name'],
                $data['password_hash'],
                $data['role'],
                $companyValue,
            ];
        }

        if ($this->isAreaReady()) {
            $cols[] = 'area_id';
            $vals[] = $areaId;
        }
        if ($this->isOrganizationGroupReady()) {
            $cols[] = 'employee_group';
            $vals[] = self::normalizeOrganizationGroup($data['employee_group'] ?? 'paviotti');
        }

        $cols = array_merge($cols, $this->employmentColumnNames());
        $vals = array_merge($vals, $this->employmentValues($data));

        $cols[] = 'profile_picture';
        $vals[] = $data['profile_picture'];

        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $this->db->query('INSERT INTO users (' . implode(', ', $cols) . ') VALUES (' . $placeholders . ')');
        return $this->db->execute($vals);
    }

    public function updateUser($data) {
        $profileReady = $this->isProfileExtendedReady();
        $areaReady = $this->isAreaReady();
        $companyId = isset($data['company_id']) && (int)$data['company_id'] > 0 ? (int)$data['company_id'] : null;

        // Parámetros posicionales: evita HY093 con nombres en PDO+MySQL (:phone_number, :p_company_id, etc.).
        $sets = [
            'full_name = ?',
            'role = ?',
            'company_id = ?',
            'hourly_rate = ?',
            'weekly_hour_limit = ?',
            'vacation_days_available = ?',
        ];
        $vals = [
            $data['full_name'],
            $data['role'],
            $companyId,
            $data['hourly_rate'],
            $data['weekly_hour_limit'],
            $data['vacation_days_available'] ?? 0,
        ];

        if ($areaReady) {
            $sets[] = 'area_id = ?';
            $vals[] = isset($data['area_id']) && (int)$data['area_id'] > 0 ? (int)$data['area_id'] : null;
        }
        if ($this->isOrganizationGroupReady()) {
            $sets[] = 'employee_group = ?';
            $vals[] = self::normalizeOrganizationGroup($data['employee_group'] ?? 'paviotti');
        }
        if ($profileReady) {
            $sets = array_merge($sets, [
                'email = ?',
                'phone_number = ?',
                'address = ?',
                'document_number = ?',
                'cuil = ?',
                'sex = ?',
                'gender = ?',
                'birth_date = ?',
                'emergency_contact_name = ?',
                'emergency_contact_phone = ?',
            ]);
            $vals = array_merge($vals, [
                $data['email'] ?? null,
                $data['phone_number'] ?? null,
                $data['address'] ?? null,
                $data['document_number'] ?? null,
                $data['cuil'] ?? null,
                $data['sex'] ?? null,
                $data['gender'] ?? null,
                $data['birth_date'] ?? null,
                $data['emergency_contact_name'] ?? null,
                $data['emergency_contact_phone'] ?? null,
            ]);
        }
        foreach ($this->employmentColumnNames() as $col) {
            $sets[] = $col . ' = ?';
        }
        $vals = array_merge($vals, $this->employmentValues($data));
        if ($this->isPlexOperatorReady()) {
            $sets[] = 'plex_operator_name = ?';
            $plex = trim($data['plex_operator_name'] ?? '');
            $vals[] = $plex !== '' ? $plex : null;
        }
        if (!empty($data['password_hash'])) {
            $sets[] = 'password = ?';
            $vals[] = $data['password_hash'];
        }
        if (!empty($data['profile_picture'])) {
            $sets[] = 'profile_picture = ?';
            $vals[] = $data['profile_picture'];
        }

        $vals[] = (int)$data['id'];
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $this->db->query($sql);
        return $this->db->execute($vals);
    }

    /** Actualización de perfil propio (empleado): contraseña y/o foto. */
    public function updateEmployeeProfile($userId, $data) {
        $sets = [];
        $vals = [];
        if (!empty($data['password_hash'])) {
            $sets[] = 'password = ?';
            $vals[] = $data['password_hash'];
        }
        if (!empty($data['profile_picture'])) {
            $sets[] = 'profile_picture = ?';
            $vals[] = $data['profile_picture'];
        }
        if ($sets === []) {
            return true;
        }
        $vals[] = (int)$userId;
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $this->db->query($sql);
        return $this->db->execute($vals);
    }

    public function getUserById($id){
        $this->db->query('SELECT * FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        return $row;
    }

    /** Búsqueda por nombre (todas las empresas) para mapeo de relojes. */
    public function searchUsersByName($query, $limit = 20) {
        $query = trim((string)$query);
        if ($query === '') {
            return [];
        }
        $this->db->query('
            SELECT u.id, u.full_name, u.username, u.company_id, u.is_active,
                   c.name AS company_name
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
            WHERE u.full_name LIKE :q1 OR u.username LIKE :q2
            ORDER BY u.full_name ASC
            LIMIT :lim
        ');
        $like = '%' . $query . '%';
        $this->db->bind(':q1', $like);
        $this->db->bind(':q2', $like);
        $this->db->bind(':lim', (int)$limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getUsersByCompany($companyId) {
        $this->db->query('
            SELECT u.id, u.username, u.full_name, u.role, u.is_active, u.weekly_hour_limit, u.profile_picture, u.company_id,
                   c.name AS company_name
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
            WHERE u.company_id = :company_id
            ORDER BY u.full_name ASC
        ');
        $this->db->bind(':company_id', $companyId);
        return $this->db->resultSet();
    }

    /**
     * Empleados activos (no baja) para liquidación masiva de vacaciones.
     * is_active = 0 → despido, renuncia u otra baja (toggle en usuarios).
     */
    public function getActiveEmployeesForVacationLiquidation($companyId) {
        $sql = '
            SELECT u.id, u.full_name, u.hire_date, u.agreement_id, u.company_id, c.name AS company_name
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
            WHERE u.is_active = 1 AND u.role = :role_emp
        ';
        if ((int)$companyId > 0) {
            $sql .= ' AND u.company_id = :company_id';
        }
        $sql .= ' ORDER BY u.full_name ASC';
        $this->db->query($sql);
        $this->db->bind(':role_emp', 'empleado');
        if ((int)$companyId > 0) {
            $this->db->bind(':company_id', (int)$companyId);
        }
        return $this->db->resultSet();
    }

    /** Empleados activos con empresa y área (notificaciones / avisos). */
    public function getActiveEmployeesForNotifications() {
        $areaJoin = '';
        $areaCol = '';
        $groupCol = $this->isOrganizationGroupReady() ? ', u.employee_group' : ", 'paviotti' AS employee_group";
        try {
            $this->db->query("SHOW COLUMNS FROM `users` LIKE 'area_id'");
            if ($this->db->single()) {
                $areaJoin = ' LEFT JOIN areas a ON a.id = u.area_id';
                $areaCol = ', u.area_id, a.name AS area_name';
            }
        } catch (Throwable $e) {
            // sin area_id
        }
        $this->db->query("
            SELECT u.id, u.full_name, u.company_id{$groupCol}, c.name AS company_name{$areaCol}
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
            {$areaJoin}
            WHERE u.role = 'empleado' AND u.is_active = 1
            ORDER BY c.name ASC, u.full_name ASC
        ");
        return $this->db->resultSet();
    }

    /** Lista para agregar destinatario manual (empleados + admins activos, todas las empresas). */
    public function getUsersForNotificationPicker() {
        $areaJoin = '';
        $areaCol = '';
        $groupCol = $this->isOrganizationGroupReady() ? ', u.employee_group' : ", 'paviotti' AS employee_group";
        try {
            $this->db->query("SHOW COLUMNS FROM `users` LIKE 'area_id'");
            if ($this->db->single()) {
                $areaJoin = ' LEFT JOIN areas a ON a.id = u.area_id';
                $areaCol = ', u.area_id, a.name AS area_name';
            }
        } catch (Throwable $e) {
        }
        $this->db->query("
            SELECT u.id, u.full_name, u.role, u.company_id{$groupCol}, c.name AS company_name{$areaCol}
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
            {$areaJoin}
            WHERE u.is_active = 1 AND u.role IN ('empleado', 'admin')
            ORDER BY u.role DESC, c.name ASC, u.full_name ASC
        ");
        return $this->db->resultSet();
    }

    /** Compañeros activos de la misma empresa (para cambio de turno). */
    public function getColleaguesForShiftSwap($companyId, $excludeUserId) {
        if (!$companyId) {
            return [];
        }
        $this->db->query('
            SELECT u.id, u.full_name, u.profile_picture
            FROM users u
            WHERE u.company_id = :company_id
              AND u.id != :exclude_user_id
              AND u.is_active = 1
            ORDER BY u.full_name ASC
        ');
        $this->db->bind(':company_id', $companyId);
        $this->db->bind(':exclude_user_id', $excludeUserId);
        return $this->db->resultSet();
    }

    public function getAllUsersWithCompany($companyFilterId = null) {
        $sql = '
            SELECT u.*, c.name AS company_name
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
        ';
        if ($companyFilterId) {
            $sql .= ' WHERE u.company_id = :company_id';
        }
        $sql .= ' ORDER BY c.name ASC, u.full_name ASC';
        $this->db->query($sql);
        if ($companyFilterId) {
            $this->db->bind(':company_id', $companyFilterId);
        }
        return $this->db->resultSet();
    }

    public function getAllUsers(){
        $this->db->query('SELECT * FROM users ORDER BY full_name ASC');
        return $this->db->resultSet();
    }

    public function toggleUserStatus($id){
        $this->db->query('UPDATE users SET is_active = !is_active WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
    
    public function countActiveUsersByCompany($companyId) {
        $this->db->query("SELECT COUNT(id) as count FROM users WHERE company_id = :company_id AND is_active = 1");
        $this->db->bind(':company_id', $companyId);
        $row = $this->db->single();
        return $row ? $row->count : 0;
    }

    public function getBirthdayInfo($companyId, $limit = 5) {
        $this->db->query("SELECT id, full_name, profile_picture, birth_date FROM users WHERE company_id = :company_id AND is_active = 1 AND MONTH(birth_date) = MONTH(CURDATE()) AND DAY(birth_date) = DAY(CURDATE())");
        $this->db->bind(':company_id', $companyId);
        $todaysBirthdays = $this->db->resultSet();

        $sqlUpcoming = "SELECT id, full_name, profile_picture, birth_date FROM users WHERE company_id = :company_id AND is_active = 1 AND birth_date IS NOT NULL AND NOT (MONTH(birth_date) = MONTH(CURDATE()) AND DAY(birth_date) = DAY(CURDATE())) ORDER BY CASE WHEN MONTH(birth_date) < MONTH(CURDATE()) THEN 1 WHEN MONTH(birth_date) = MONTH(CURDATE()) AND DAY(birth_date) < DAY(CURDATE()) THEN 1 ELSE 0 END ASC, MONTH(birth_date) ASC, DAY(birth_date) ASC LIMIT :limit";
        $this->db->query($sqlUpcoming);
        $this->db->bind(':company_id', $companyId);
        $this->db->bind(':limit', $limit);
        $upcomingBirthdays = $this->db->resultSet();

        return [
            'today' => $todaysBirthdays,
            'upcoming' => $upcomingBirthdays
        ];
    }

     public function findUserByClockId($clockId){
        $this->db->query('SELECT u.* FROM users u JOIN user_clock_mappings m ON u.id = m.user_id WHERE m.user_clock_id = :clock_id');
        $this->db->bind(':clock_id', $clockId);
        return $this->db->single();
    }

    public function getClockMappingsForUser($userId){
        $this->db->query("SELECT clock_name, user_clock_id FROM user_clock_mappings WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        $results = $this->db->resultSet();
        $mappings = array();
        foreach($results as $row){
            $mappings[$row->clock_name] = $row->user_clock_id;
        }
        return $mappings;
    }

    public function saveClockMappings($userId, $mappings){
        $this->db->query("DELETE FROM user_clock_mappings WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        $this->db->execute();

        $this->db->query("INSERT INTO user_clock_mappings (user_id, clock_name, user_clock_id) VALUES (:user_id, :clock_name, :user_clock_id)");
        foreach($mappings as $clockName => $clockId){
            if(!empty(trim($clockId))){
                $this->db->bind(':user_id', $userId);
                $this->db->bind(':clock_name', $clockName);
                $this->db->bind(':user_clock_id', trim($clockId));
                $this->db->execute();
            }
        }
        return true;
    }

    /**
     * Asigna (o reasigna) un employeeID de reloj a un usuario local.
     * Si ese clock_id ya estaba mapeado a otro usuario, lo desvincula primero.
     */
    public function upsertClockMapping($userId, $clockName, $clockId) {
        // Eliminar cualquier mapeo previo de este clock_id (sea cual sea el usuario)
        $this->db->query("DELETE FROM user_clock_mappings WHERE user_clock_id = :clock_id");
        $this->db->bind(':clock_id', $clockId);
        $this->db->execute();

        // Insertar nuevo mapeo
        $this->db->query("INSERT INTO user_clock_mappings (user_id, clock_name, user_clock_id) VALUES (:user_id, :clock_name, :user_clock_id)");
        $this->db->bind(':user_id',      $userId);
        $this->db->bind(':clock_name',   $clockName);
        $this->db->bind(':user_clock_id', $clockId);
        return $this->db->execute();
    }

    /**
     * Elimina el mapeo de un clock_id (desasociar empleado de reloj).
     */
    public function getUserIdByClockEmployeeId($clockId) {
        $this->db->query('SELECT user_id FROM user_clock_mappings WHERE user_clock_id = :clock_id LIMIT 1');
        $this->db->bind(':clock_id', (string)$clockId);
        $row = $this->db->single();
        return $row ? (int)$row->user_id : 0;
    }

    public function deleteClockMapping($clockId) {
        $this->db->query("DELETE FROM user_clock_mappings WHERE user_clock_id = :clock_id");
        $this->db->bind(':clock_id', $clockId);
        return $this->db->execute();
    }

    public function updateVacationBalance($userId, $newBalance){
        $this->db->query('UPDATE users SET vacation_days_available = :balance WHERE id = :id');
        $this->db->bind(':balance', $newBalance);
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }

    public function isPlexOperatorReady() {
        try {
            $this->db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'plex_operator_name'");
            return (bool)$this->db->single();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function updatePlexOperatorName($userId, $name) {
        if (!$this->isPlexOperatorReady()) {
            return false;
        }
        $name = trim((string)$name);
        $this->db->query('UPDATE users SET plex_operator_name = :name WHERE id = :id');
        $this->db->bind(':name', $name !== '' ? $name : null, $name !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $this->db->bind(':id', (int)$userId);
        return $this->db->execute();
    }

    public function getActiveAdminsByCompany($companyId) {
        $this->db->query("SELECT id, full_name, email FROM users
            WHERE company_id = :company_id AND is_active = 1 AND role = 'admin'");
        $this->db->bind(':company_id', (int)$companyId);
        return $this->db->resultSet();
    }

    public function setInactive($userId) {
        $this->db->query('UPDATE users SET is_active = 0 WHERE id = :id');
        $this->db->bind(':id', (int)$userId);
        return $this->db->execute();
    }
}
?>
