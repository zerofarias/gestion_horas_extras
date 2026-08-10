<?php

class UserNotification {
    private $db;

    public function __construct(Database $db = null) {
        $this->db = $db ?? new Database();
    }

    public function existsForUser($userId, $type, $referenceId = null, $broadcastId = null) {
        $userId = (int)$userId;
        if ($referenceId !== null && (int)$referenceId > 0) {
            $this->db->query('SELECT id FROM user_notifications
                WHERE user_id = :uid AND type = :type AND reference_id = :ref LIMIT 1');
            $this->db->bind(':uid', $userId);
            $this->db->bind(':type', $type);
            $this->db->bind(':ref', (int)$referenceId);
            return (bool)$this->db->single();
        }
        if ($broadcastId !== null && (int)$broadcastId > 0) {
            $this->db->query('SELECT id FROM user_notifications
                WHERE user_id = :uid AND broadcast_id = :bid LIMIT 1');
            $this->db->bind(':uid', $userId);
            $this->db->bind(':bid', (int)$broadcastId);
            return (bool)$this->db->single();
        }
        return false;
    }

    public function countByBroadcastId($broadcastId) {
        $this->db->query('SELECT COUNT(*) AS c FROM user_notifications WHERE broadcast_id = :bid');
        $this->db->bind(':bid', (int)$broadcastId);
        $row = $this->db->single();
        return $row ? (int)$row->c : 0;
    }

    public function deleteByBroadcastId($broadcastId) {
        $this->db->query('DELETE FROM user_notifications WHERE broadcast_id = :bid');
        $this->db->bind(':bid', (int)$broadcastId);
        return $this->db->execute();
    }

    public function create(array $data) {
        $userId = (int)($data['user_id'] ?? 0);
        $type = $data['type'] ?? 'manual';
        if (!in_array($type, ['manual', 'course', 'pay_stub', 'survey'], true)) {
            $type = 'manual';
        }
        $refId = !empty($data['reference_id']) ? (int)$data['reference_id'] : null;
        $broadcastId = !empty($data['broadcast_id']) ? (int)$data['broadcast_id'] : null;
        if ($broadcastId && $this->existsForUser($userId, $type, $refId, $broadcastId)) {
            return false;
        }
        if ($refId && $this->existsForUser($userId, $type, $refId, null)) {
            return false;
        }
        $this->db->query('INSERT INTO user_notifications (
            user_id, broadcast_id, title, body, link_url, type, reference_id
        ) VALUES (
            :user_id, :broadcast_id, :title, :body, :link_url, :type, :reference_id
        )');
        $this->db->bind(':user_id', (int)$data['user_id']);
        $this->db->bind(':broadcast_id', !empty($data['broadcast_id']) ? (int)$data['broadcast_id'] : null);
        $this->db->bind(':title', trim($data['title'] ?? ''));
        $this->db->bind(':body', $data['body'] ?? '');
        $this->db->bind(':link_url', !empty($data['link_url']) ? trim($data['link_url']) : null);
        $type = $data['type'] ?? 'manual';
        if (!in_array($type, ['manual', 'course', 'pay_stub', 'survey'], true)) {
            $type = 'manual';
        }
        $this->db->bind(':type', $type);
        $this->db->bind(':reference_id', !empty($data['reference_id']) ? (int)$data['reference_id'] : null);
        return $this->db->execute();
    }

    public function countUnread($userId) {
        $this->db->query('SELECT COUNT(*) AS c FROM user_notifications WHERE user_id = :uid AND read_at IS NULL');
        $this->db->bind(':uid', (int)$userId);
        $row = $this->db->single();
        return $row ? (int)$row->c : 0;
    }

    public function getRecentForUser($userId, $limit = 8) {
        $this->db->query('SELECT * FROM user_notifications WHERE user_id = :uid
            ORDER BY created_at DESC LIMIT ' . (int)$limit);
        $this->db->bind(':uid', (int)$userId);
        return $this->db->resultSet();
    }

    public function getAllForUser($userId) {
        $this->db->query('SELECT * FROM user_notifications WHERE user_id = :uid ORDER BY created_at DESC');
        $this->db->bind(':uid', (int)$userId);
        return $this->db->resultSet();
    }

    public function markRead($id, $userId) {
        $this->db->query('UPDATE user_notifications SET read_at = NOW()
            WHERE id = :id AND user_id = :uid AND read_at IS NULL');
        $this->db->bind(':id', (int)$id);
        $this->db->bind(':uid', (int)$userId);
        return $this->db->execute();
    }

    public function markAllRead($userId) {
        $this->db->query('UPDATE user_notifications SET read_at = NOW()
            WHERE user_id = :uid AND read_at IS NULL');
        $this->db->bind(':uid', (int)$userId);
        return $this->db->execute();
    }

    public function markReadByReference($userId, $type, $referenceId) {
        $this->db->query('UPDATE user_notifications SET read_at = NOW()
            WHERE user_id = :uid AND type = :type AND reference_id = :ref AND read_at IS NULL');
        $this->db->bind(':uid', (int)$userId);
        $this->db->bind(':type', $type);
        $this->db->bind(':ref', (int)$referenceId);
        return $this->db->execute();
    }
}
