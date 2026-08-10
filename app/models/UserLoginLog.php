<?php

class UserLoginLog {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public static function isReady() {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        try {
            $db = new Database();
            $db->query("SHOW TABLES LIKE 'user_login_logs'");
            $ready = (bool)$db->single();
        } catch (Throwable $e) {
            $ready = false;
        }
        return $ready;
    }

    /** Registra un inicio de sesión exitoso. */
    public function log($userId) {
        if (!self::isReady()) {
            return false;
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null;
        $this->db->query('INSERT INTO user_login_logs (user_id, ip_address, user_agent) VALUES (:uid, :ip, :ua)');
        $this->db->bind(':uid', (int)$userId);
        $this->db->bind(':ip', $ip);
        $this->db->bind(':ua', $ua);
        return $this->db->execute();
    }

    public function countByUser($userId) {
        if (!self::isReady()) {
            return 0;
        }
        $this->db->query('SELECT COUNT(*) AS c FROM user_login_logs WHERE user_id = :uid');
        $this->db->bind(':uid', (int)$userId);
        $row = $this->db->single();
        return $row ? (int)$row->c : 0;
    }
}
