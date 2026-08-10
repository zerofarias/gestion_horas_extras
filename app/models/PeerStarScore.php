<?php

class PeerStarScore {
    private $db;

    public function __construct(Database $db = null) {
        $this->db = $db ?? new Database();
    }

    public function getTotal($userId) {
        $this->db->query('SELECT total_score FROM peer_star_scores WHERE user_id = :uid');
        $this->db->bind(':uid', (int)$userId);
        $row = $this->db->single();
        return $row ? (int)$row->total_score : 0;
    }

    public function ensure($userId) {
        $this->db->query('INSERT IGNORE INTO peer_star_scores (user_id, total_score) VALUES (:uid, 0)');
        $this->db->bind(':uid', (int)$userId);
        $this->db->execute();
    }

    public function addDelta($userId, $delta) {
        $this->ensure($userId);
        $this->db->query('UPDATE peer_star_scores SET total_score = total_score + :d WHERE user_id = :uid');
        $this->db->bind(':d', (int)$delta);
        $this->db->bind(':uid', (int)$userId);
        return $this->db->execute();
    }
}
