<?php

class MailSettings {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function get() {
        if (!notifications_is_ready()) {
            return null;
        }
        $this->db->query('SELECT * FROM mail_settings WHERE id = 1');
        return $this->db->single();
    }

    public function save(array $data) {
        $existing = $this->get();
        $password = isset($data['smtp_password']) ? trim($data['smtp_password']) : '';
        if ($existing && $password === '') {
            $password = $existing->smtp_password;
        }
        $this->db->query('UPDATE mail_settings SET
            smtp_host = :host,
            smtp_port = :port,
            smtp_encryption = :enc,
            smtp_user = :user,
            smtp_password = :pass,
            from_email = :from_email,
            from_name = :from_name,
            is_enabled = :enabled
            WHERE id = 1');
        $this->db->bind(':host', trim($data['smtp_host'] ?? ''));
        $this->db->bind(':port', (int)($data['smtp_port'] ?? 587));
        $this->db->bind(':enc', in_array($data['smtp_encryption'] ?? '', ['none', 'tls', 'ssl'], true) ? $data['smtp_encryption'] : 'tls');
        $this->db->bind(':user', trim($data['smtp_user'] ?? ''));
        $this->db->bind(':pass', $password);
        $this->db->bind(':from_email', trim($data['from_email'] ?? ''));
        $this->db->bind(':from_name', trim($data['from_name'] ?? ''));
        $this->db->bind(':enabled', !empty($data['is_enabled']) ? 1 : 0);
        return $this->db->execute();
    }
}
