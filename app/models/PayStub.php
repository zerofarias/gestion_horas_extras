<?php

class PayStub {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getById($id) {
        $this->db->query('SELECT ps.*, u.full_name, u.company_id AS user_company_id
            FROM pay_stubs ps
            JOIN users u ON u.id = ps.user_id
            WHERE ps.id = :id');
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    public function getByUserId($userId) {
        $this->db->query('SELECT * FROM pay_stubs WHERE user_id = :uid ORDER BY period ASC');
        $this->db->bind(':uid', (int)$userId);
        return $this->db->resultSet();
    }

    public function getForAdminList($companyId = null, $search = '') {
        $sql = 'SELECT ps.*, u.full_name, c.name AS company_name
            FROM pay_stubs ps
            JOIN users u ON u.id = ps.user_id
            JOIN companies c ON c.id = ps.company_id
            WHERE 1=1';
        if ($companyId) {
            $sql .= ' AND ps.company_id = :cid';
        }
        if ($search !== '') {
            $sql .= ' AND u.full_name LIKE :q';
        }
        $sql .= ' ORDER BY ps.period DESC, u.full_name ASC LIMIT 200';
        $this->db->query($sql);
        if ($companyId) {
            $this->db->bind(':cid', (int)$companyId);
        }
        if ($search !== '') {
            $this->db->bind(':q', '%' . $search . '%');
        }
        return $this->db->resultSet();
    }

    public function create(array $data) {
        $this->db->query('INSERT INTO pay_stubs (
            user_id, company_id, period, file_path, file_type, admin_note, uploaded_by, status
        ) VALUES (
            :user_id, :company_id, :period, :file_path, :file_type, :admin_note, :uploaded_by, \'pending_signature\'
        )');
        $this->db->bind(':user_id', (int)$data['user_id']);
        $this->db->bind(':company_id', (int)$data['company_id']);
        $this->db->bind(':period', $data['period']);
        $this->db->bind(':file_path', $data['file_path']);
        $this->db->bind(':file_type', $data['file_type'] === 'image' ? 'image' : 'pdf');
        $note = trim($data['admin_note'] ?? '');
        $this->db->bind(':admin_note', $note !== '' ? $note : null);
        $this->db->bind(':uploaded_by', (int)$data['uploaded_by']);
        if ($this->db->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    public function periodExists($userId, $period, $excludeId = 0) {
        $sql = 'SELECT id FROM pay_stubs WHERE user_id = :uid AND period = :period';
        if ($excludeId > 0) {
            $sql .= ' AND id != :ex';
        }
        $this->db->query($sql);
        $this->db->bind(':uid', (int)$userId);
        $this->db->bind(':period', $period);
        if ($excludeId > 0) {
            $this->db->bind(':ex', (int)$excludeId);
        }
        return (bool)$this->db->single();
    }

    public function getOldestPendingId($userId) {
        $this->db->query("SELECT id FROM pay_stubs
            WHERE user_id = :uid AND status = 'pending_signature'
            ORDER BY period ASC LIMIT 1");
        $this->db->bind(':uid', (int)$userId);
        $row = $this->db->single();
        return $row ? (int)$row->id : 0;
    }

    public function canUserAccess($userId, $payStubId) {
        $stub = $this->getById($payStubId);
        if (!$stub || (int)$stub->user_id !== (int)$userId) {
            return false;
        }
        if ($stub->status === 'signed') {
            return true;
        }
        $oldest = $this->getOldestPendingId($userId);
        return $oldest === (int)$payStubId;
    }

    public function sign($id, $userId, $signaturePath, $signerIp) {
        $this->db->query("UPDATE pay_stubs SET
            status = 'signed', signed_at = NOW(), signature_path = :sig, signer_ip = :ip
            WHERE id = :id AND user_id = :uid AND status = 'pending_signature'");
        $this->db->bind(':sig', $signaturePath);
        $this->db->bind(':ip', $signerIp);
        $this->db->bind(':id', (int)$id);
        $this->db->bind(':uid', (int)$userId);
        return $this->db->execute();
    }
}
