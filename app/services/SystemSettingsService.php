<?php

class SystemSettingsService {
    private static $cache = null;
    /** Evita recursión si loadAll() se reentra durante la carga. */
    private static $loading = false;
    private $model;

    public function __construct() {
        $this->model = new SystemSetting();
    }

    public static function instance() {
        static $inst = null;
        if ($inst === null) {
            $inst = new self();
        }
        return $inst;
    }

    public function isReady() {
        return SystemSetting::isReady();
    }

    public function loadAll() {
        if (self::$cache !== null) {
            return self::$cache;
        }
        if (self::$loading) {
            return [];
        }
        self::$loading = true;
        self::$cache = [];
        try {
            if (!$this->isReady()) {
                return self::$cache;
            }
            foreach ($this->model->getAll() as $row) {
                self::$cache[$row->setting_key] = $this->castValue($row);
            }
            return self::$cache;
        } finally {
            self::$loading = false;
        }
    }

    public function get($key, $default = null) {
        $all = $this->loadAll();
        if (array_key_exists($key, $all)) {
            $val = $all[$key];
            if ($val !== '' && $val !== null) {
                return $val;
            }
        }
        return $this->fallback($key, $default);
    }

    public function getString($key, $default = '') {
        $v = $this->get($key, $default);
        return $v === null ? (string)$default : (string)$v;
    }

    public function getInt($key, $default = 0) {
        $v = $this->get($key, $default);
        return (int)$v;
    }

    public function getBool($key, $default = false) {
        $v = $this->get($key, $default);
        if (is_bool($v)) {
            return $v;
        }
        return in_array(strtolower((string)$v), ['1', 'true', 'yes', 'on'], true);
    }

    public function getByGroup($groupKey) {
        if (!$this->isReady()) {
            return [];
        }
        $out = [];
        foreach ($this->model->getByGroup($groupKey) as $row) {
            $out[$row->setting_key] = $row;
        }
        return $out;
    }

    public function setMany(array $pairs, $userId = null) {
        if (!$this->isReady()) {
            return false;
        }
        $ok = true;
        foreach ($pairs as $key => $value) {
            if (!$this->model->set($key, $value, $userId)) {
                $ok = false;
            }
        }
        self::$cache = null;
        return $ok;
    }

    public function verifyConfigPin($pin) {
        $hash = $this->getString('config_unlock_secret_hash', '');
        if ($hash === '') {
            return false;
        }
        return password_verify((string)$pin, $hash);
    }

    public function setConfigPinHash($hash, $userId = null) {
        return $this->setMany(['config_unlock_secret_hash' => $hash], $userId);
    }

    private function castValue($row) {
        $raw = $row->setting_value ?? '';
        switch ($row->value_type ?? 'string') {
            case 'int':
                return (int)$raw;
            case 'bool':
                return in_array(strtolower((string)$raw), ['1', 'true', 'yes', 'on'], true);
            case 'json':
                $decoded = json_decode((string)$raw, true);
                return is_array($decoded) ? $decoded : [];
            default:
                return (string)$raw;
        }
    }

    private function fallback($key, $default) {
        $map = [
            'sitename' => defined('SITENAME') ? SITENAME : 'Paviotti RRHH',
            'default_company_name' => defined('DEFAULT_COMPANY_NAME') ? DEFAULT_COMPANY_NAME : 'Ecofarma',
            'app_debug' => defined('APP_DEBUG') ? APP_DEBUG : false,
            'cp_deceased_list_limit' => 50,
            'extintos_db_host' => defined('EXTINTOS_DB_HOST') ? EXTINTOS_DB_HOST : '',
            'extintos_db_name' => defined('EXTINTOS_DB_NAME') ? EXTINTOS_DB_NAME : '',
            'extintos_db_user' => defined('EXTINTOS_DB_USER') ? EXTINTOS_DB_USER : '',
            'extintos_db_pass' => defined('EXTINTOS_DB_PASS') ? EXTINTOS_DB_PASS : '',
            'cp_extintos_table_sepulio' => 'extintosH',
            'cp_extintos_table_tanato' => 'extintos',
            'cp_duplicate_check_enabled' => true,
            'attendance_late_tolerance_min' => defined('ATTENDANCE_LATE_TOLERANCE_MIN') ? ATTENDANCE_LATE_TOLERANCE_MIN : 5,
            'attendance_early_leave_tolerance_min' => defined('ATTENDANCE_EARLY_LEAVE_TOLERANCE_MIN') ? ATTENDANCE_EARLY_LEAVE_TOLERANCE_MIN : 5,
            'clock_api_base_url' => defined('CLOCK_API_BASE_URL') ? CLOCK_API_BASE_URL : '',
            'clock_api_email' => defined('CLOCK_API_EMAIL') ? CLOCK_API_EMAIL : '',
            'clock_api_password' => defined('CLOCK_API_PASSWORD') ? CLOCK_API_PASSWORD : '',
            'ecofarma_default_obra_social' => defined('ECOFARMA_DEFAULT_OBRA_SOCIAL') ? ECOFARMA_DEFAULT_OBRA_SOCIAL : '999900',
            'ecofarma_default_comision_pct' => defined('ECOFARMA_DEFAULT_COMISION_PCT') ? ECOFARMA_DEFAULT_COMISION_PCT : 7,
            'overtime_visible_admin' => true,
            'overtime_visible_supervisor' => true,
            'employee_show_overtime' => true,
            'employee_show_overtime_on_home' => true,
            'employee_show_cp_extras' => true,
            'employee_show_cp_extras_on_home' => true,
            'employee_show_vacation_balance' => true,
            'employee_show_pay_stubs' => true,
            'employee_show_training' => true,
            'employee_show_peer_stars' => true,
            'employee_show_surveys' => true,
            'employee_show_suggestions' => true,
            'employee_show_mi_mes' => true,
        ];
        if (array_key_exists($key, $map)) {
            return $map[$key];
        }
        return $default;
    }
}
