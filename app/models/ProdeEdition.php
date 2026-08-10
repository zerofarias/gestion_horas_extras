<?php

class ProdeEdition {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public static function isReady() {
        return prode_is_ready();
    }

    public function getActiveEdition() {
        $this->db->query("SELECT * FROM prode_editions WHERE status IN ('open','upcoming') ORDER BY FIELD(status,'open','upcoming') LIMIT 1");
        $row = $this->db->single();
        if ($row) {
            return $row;
        }
        $this->db->query("SELECT * FROM prode_editions ORDER BY id DESC LIMIT 1");
        return $this->db->single();
    }

    public function getById($id) {
        $this->db->query('SELECT * FROM prode_editions WHERE id = :id');
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    public function getBySlug($slug) {
        $this->db->query('SELECT * FROM prode_editions WHERE slug = :slug');
        $this->db->bind(':slug', $slug);
        return $this->db->single();
    }

    public function updateStatus($editionId, $status) {
        if (!in_array($status, ['upcoming', 'open', 'closed'], true)) {
            return false;
        }
        $this->db->query('UPDATE prode_editions SET status = :st WHERE id = :id');
        $this->db->bind(':st', $status);
        $this->db->bind(':id', (int)$editionId);
        return $this->db->execute();
    }

    public function getGroups($editionId) {
        $this->db->query('SELECT * FROM prode_groups WHERE edition_id = :eid ORDER BY sort_order ASC, code ASC');
        $this->db->bind(':eid', (int)$editionId);
        return $this->db->resultSet();
    }

    public function getGroupByCode($editionId, $code) {
        $this->db->query('SELECT * FROM prode_groups WHERE edition_id = :eid AND code = :code LIMIT 1');
        $this->db->bind(':eid', (int)$editionId);
        $this->db->bind(':code', $code);
        return $this->db->single();
    }

    public function getMatchesForGroup($groupId) {
        $this->db->query('SELECT m.*,
            ht.name AS home_name, ht.flag_code AS home_flag,
            at.name AS away_name, at.flag_code AS away_flag
            FROM prode_matches m
            JOIN prode_teams ht ON ht.id = m.home_team_id
            JOIN prode_teams at ON at.id = m.away_team_id
            WHERE m.group_id = :gid
            ORDER BY m.match_number ASC');
        $this->db->bind(':gid', (int)$groupId);
        return $this->db->resultSet();
    }

    public function getAllMatches($editionId, $groupCode = null) {
        $sql = 'SELECT m.*, g.code AS group_code,
            ht.name AS home_name, ht.flag_code AS home_flag,
            at.name AS away_name, at.flag_code AS away_flag
            FROM prode_matches m
            JOIN prode_groups g ON g.id = m.group_id
            JOIN prode_teams ht ON ht.id = m.home_team_id
            JOIN prode_teams at ON at.id = m.away_team_id
            WHERE m.edition_id = :eid';
        if ($groupCode !== null && $groupCode !== '') {
            $sql .= ' AND g.code = :gcode';
        }
        $sql .= ' ORDER BY g.sort_order ASC, m.match_number ASC';
        $this->db->query($sql);
        $this->db->bind(':eid', (int)$editionId);
        if ($groupCode !== null && $groupCode !== '') {
            $this->db->bind(':gcode', $groupCode);
        }
        return $this->db->resultSet();
    }

    public function getMatchById($matchId) {
        $this->db->query('SELECT m.*, g.code AS group_code, g.edition_id
            FROM prode_matches m
            JOIN prode_groups g ON g.id = m.group_id
            WHERE m.id = :id');
        $this->db->bind(':id', (int)$matchId);
        return $this->db->single();
    }

    public function countMatches($editionId) {
        $this->db->query('SELECT COUNT(*) AS c FROM prode_matches WHERE edition_id = :eid');
        $this->db->bind(':eid', (int)$editionId);
        $row = $this->db->single();
        return $row ? (int)$row->c : 0;
    }

    public function saveMatchResult($matchId, $homeScore, $awayScore) {
        $homeScore = max(0, min(20, (int)$homeScore));
        $awayScore = max(0, min(20, (int)$awayScore));
        $this->db->query('UPDATE prode_matches SET
            home_score_actual = :hs, away_score_actual = :as,
            status = :st, predictions_locked = 1
            WHERE id = :id');
        $this->db->bind(':hs', $homeScore);
        $this->db->bind(':as', $awayScore);
        $this->db->bind(':st', 'finished');
        $this->db->bind(':id', (int)$matchId);
        return $this->db->execute();
    }

    public function updateKickoff($matchId, $kickoffAt) {
        $now = prode_now_argentina()->format('Y-m-d H:i:s');
        $locked = ($kickoffAt <= $now) ? 1 : 0;
        $this->db->query('UPDATE prode_matches SET kickoff_at = :k, predictions_locked = :locked WHERE id = :id');
        $this->db->bind(':k', $kickoffAt);
        $this->db->bind(':locked', $locked);
        $this->db->bind(':id', (int)$matchId);
        return $this->db->execute();
    }

    public function lockStartedMatches() {
        $now = prode_now_argentina()->format('Y-m-d H:i:s');
        $this->db->query('UPDATE prode_matches SET predictions_locked = 1
            WHERE predictions_locked = 0 AND kickoff_at IS NOT NULL AND kickoff_at <= :now');
        $this->db->bind(':now', $now);
        return $this->db->execute();
    }
}
