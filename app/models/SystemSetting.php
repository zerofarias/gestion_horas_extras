<?php

class SystemSetting {
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
            $db->query('SELECT 1 FROM system_settings LIMIT 1');
            $db->execute();
            $ready = true;
        } catch (Throwable $e) {
            $ready = false;
        }
        return $ready;
    }

    public function getAll() {
        $this->db->query('SELECT * FROM system_settings ORDER BY group_key ASC, setting_key ASC');
        return $this->db->resultSet();
    }

    public function getByKey($key) {
        $this->db->query('SELECT * FROM system_settings WHERE setting_key = ? LIMIT 1');
        return $this->db->single([$key]);
    }

    public function getByGroup($groupKey) {
        $this->db->query('SELECT * FROM system_settings WHERE group_key = ? ORDER BY setting_key ASC');
        return $this->db->resultSet([$groupKey]);
    }

    public function set($key, $value, $userId = null) {
        $existing = $this->getByKey($key);
        if (!$existing) {
            return false;
        }
        $this->db->query('UPDATE system_settings SET setting_value = ?, updated_by_user_id = ? WHERE setting_key = ?');
        return $this->db->execute([
            $value === null ? '' : (string)$value,
            $userId ? (int)$userId : null,
            $key,
        ]);
    }

    public function setIfEmpty($key, $value, $userId = null) {
        $row = $this->getByKey($key);
        if (!$row || trim((string)($row->setting_value ?? '')) !== '') {
            return true;
        }
        return $this->set($key, $value, $userId);
    }
}
