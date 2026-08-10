<?php

class PeerStarLedger {
    private $db;

    public function __construct(Database $db = null) {
        $this->db = $db ?? new Database();
    }

    public function isReady() {
        return peer_stars_is_ready();
    }

    public function getNetForPairInPeriod($giverId, $receiverId, $periodYm) {
        $this->db->query('SELECT COALESCE(SUM(delta), 0) AS net
            FROM peer_star_ledger
            WHERE giver_user_id = :g AND receiver_user_id = :r AND period_ym = :p');
        $this->db->bind(':g', (int)$giverId);
        $this->db->bind(':r', (int)$receiverId);
        $this->db->bind(':p', $periodYm);
        $row = $this->db->single();
        return $row ? (int)$row->net : 0;
    }

    public function insert($giverId, $receiverId, $delta, $category, $comment, $periodYm) {
        $this->db->query('INSERT INTO peer_star_ledger
            (giver_user_id, receiver_user_id, delta, reason_category, comment, period_ym)
            VALUES (:g, :r, :d, :c, :note, :p)');
        $this->db->bind(':g', (int)$giverId);
        $this->db->bind(':r', (int)$receiverId);
        $this->db->bind(':d', (int)$delta);
        $this->db->bind(':c', $category);
        $this->db->bind(':note', $comment !== '' ? $comment : null);
        $this->db->bind(':p', $periodYm);
        return $this->db->execute();
    }

    /** Historial recibido (sin identidad del emisor ni comentario — evita re-identificación). */
    public function getReceivedForUser($userId, $limit = 30) {
        $this->db->query('SELECT delta, reason_category, period_ym, created_at
            FROM peer_star_ledger
            WHERE receiver_user_id = :uid
            ORDER BY created_at DESC
            LIMIT ' . (int)$limit);
        $this->db->bind(':uid', (int)$userId);
        return $this->db->resultSet();
    }

    public function getRanking($companyId, $areaId = 0, $limit = 200) {
        $sql = 'SELECT u.id, u.full_name, u.profile_picture, u.area_id, a.name AS area_name,
                       COALESCE(ps.total_score, 0) AS total_score
                FROM users u
                LEFT JOIN peer_star_scores ps ON ps.user_id = u.id
                LEFT JOIN areas a ON a.id = u.area_id
                WHERE u.company_id = :cid AND u.is_active = 1 AND u.role = \'empleado\'';
        if ($areaId > 0) {
            $sql .= ' AND u.area_id = :aid';
        }
        $sql .= ' ORDER BY total_score DESC, u.full_name ASC LIMIT ' . (int)$limit;
        $this->db->query($sql);
        $this->db->bind(':cid', (int)$companyId);
        if ($areaId > 0) {
            $this->db->bind(':aid', (int)$areaId);
        }
        return $this->db->resultSet();
    }
}
