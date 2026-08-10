<?php

class ProdePrediction {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getOrCreateEntry($editionId, $userId) {
        $editionId = (int)$editionId;
        $userId = (int)$userId;
        $this->db->query('SELECT * FROM prode_user_entries WHERE edition_id = :eid AND user_id = :uid');
        $this->db->bind(':eid', $editionId);
        $this->db->bind(':uid', $userId);
        $row = $this->db->single();
        if ($row) {
            return $row;
        }
        $this->db->query('INSERT INTO prode_user_entries (edition_id, user_id, status) VALUES (:eid, :uid, :st)');
        $this->db->bind(':eid', $editionId);
        $this->db->bind(':uid', $userId);
        $this->db->bind(':st', 'draft');
        $this->db->execute();
        $this->db->query('SELECT * FROM prode_user_entries WHERE edition_id = :eid AND user_id = :uid');
        $this->db->bind(':eid', $editionId);
        $this->db->bind(':uid', $userId);
        return $this->db->single();
    }

    public function getEntry($editionId, $userId) {
        $this->db->query('SELECT * FROM prode_user_entries WHERE edition_id = :eid AND user_id = :uid');
        $this->db->bind(':eid', (int)$editionId);
        $this->db->bind(':uid', (int)$userId);
        return $this->db->single();
    }

    public function getPredictionsMap($userId, $editionId) {
        $this->db->query('SELECT p.*
            FROM prode_predictions p
            JOIN prode_matches m ON m.id = p.match_id
            WHERE p.user_id = :uid AND m.edition_id = :eid');
        $this->db->bind(':uid', (int)$userId);
        $this->db->bind(':eid', (int)$editionId);
        $map = [];
        foreach ($this->db->resultSet() as $row) {
            $map[(int)$row->match_id] = $row;
        }
        return $map;
    }

    public function countFilledPredictions($userId, $editionId) {
        $this->db->query('SELECT COUNT(*) AS c FROM prode_predictions p
            JOIN prode_matches m ON m.id = p.match_id
            WHERE p.user_id = :uid AND m.edition_id = :eid
              AND p.home_score_pred IS NOT NULL AND p.away_score_pred IS NOT NULL');
        $this->db->bind(':uid', (int)$userId);
        $this->db->bind(':eid', (int)$editionId);
        $row = $this->db->single();
        return $row ? (int)$row->c : 0;
    }

    public function getPrediction($userId, $matchId) {
        $this->db->query('SELECT * FROM prode_predictions WHERE user_id = :uid AND match_id = :mid LIMIT 1');
        $this->db->bind(':uid', (int)$userId);
        $this->db->bind(':mid', (int)$matchId);
        return $this->db->single();
    }

    public function upsertPrediction($userId, $matchId, $homeScore, $awayScore) {
        $userId = (int)$userId;
        $matchId = (int)$matchId;
        $homeScore = max(0, min(20, (int)$homeScore));
        $awayScore = max(0, min(20, (int)$awayScore));

        $existing = $this->getPrediction($userId, $matchId);
        if ($existing && prode_prediction_is_saved($existing)) {
            return false;
        }

        if ($existing) {
            $this->db->query('UPDATE prode_predictions SET
                home_score_pred = :hs, away_score_pred = :as, is_submitted = 0, updated_at = NOW()
                WHERE id = :id');
            $this->db->bind(':id', (int)$existing->id);
        } else {
            $this->db->query('INSERT INTO prode_predictions (user_id, match_id, home_score_pred, away_score_pred, is_submitted)
                VALUES (:uid, :mid, :hs, :as, 0)');
            $this->db->bind(':uid', $userId);
            $this->db->bind(':mid', $matchId);
        }
        $this->db->bind(':hs', $homeScore);
        $this->db->bind(':as', $awayScore);
        if (!$this->db->execute()) {
            return false;
        }

        $match = (new ProdeEdition())->getMatchById($matchId);
        if ($match) {
            $this->refreshEntryCounts((int)$match->edition_id, $userId);
        }
        return true;
    }

    public function refreshEntryCounts($editionId, $userId) {
        $filled = $this->countFilledPredictions($userId, $editionId);
        $this->getOrCreateEntry($editionId, $userId);
        $this->db->query('UPDATE prode_user_entries SET predictions_count = :cnt WHERE edition_id = :eid AND user_id = :uid');
        $this->db->bind(':cnt', $filled);
        $this->db->bind(':eid', (int)$editionId);
        $this->db->bind(':uid', (int)$userId);
        return $this->db->execute();
    }

    public function submitAll($editionId, $userId, $totalMatches) {
        $editionId = (int)$editionId;
        $userId = (int)$userId;
        $filled = $this->countFilledPredictions($userId, $editionId);
        if ($filled < $totalMatches) {
            return ['ok' => false, 'message' => 'Completá los ' . $totalMatches . ' partidos antes de confirmar (tenés ' . $filled . ').'];
        }

        $this->db->query('UPDATE prode_predictions p
            JOIN prode_matches m ON m.id = p.match_id
            SET p.is_submitted = 1
            WHERE p.user_id = :uid AND m.edition_id = :eid');
        $this->db->bind(':uid', $userId);
        $this->db->bind(':eid', $editionId);
        $this->db->execute();

        $this->getOrCreateEntry($editionId, $userId);
        $this->db->query('UPDATE prode_user_entries SET status = :st, submitted_at = NOW(), predictions_count = :cnt
            WHERE edition_id = :eid AND user_id = :uid');
        $this->db->bind(':st', 'submitted');
        $this->db->bind(':cnt', $filled);
        $this->db->bind(':eid', $editionId);
        $this->db->bind(':uid', $userId);
        if (!$this->db->execute()) {
            return ['ok' => false, 'message' => 'No se pudo confirmar.'];
        }
        return ['ok' => true, 'message' => '¡Pronósticos confirmados! Buena suerte.'];
    }

    public function getRanking($editionId, $companyId = 0, $areaId = 0, $limit = 500) {
        $sql = 'SELECT u.id, u.full_name, u.profile_picture,
            c.name AS company_name, a.name AS area_name,
            COALESCE(e.total_points, 0) AS total_points,
            COALESCE(e.exact_hits, 0) AS exact_hits,
            COALESCE(e.result_hits, 0) AS result_hits,
            COALESCE(e.predictions_count, 0) AS predictions_count,
            e.status AS entry_status, e.submitted_at
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
            LEFT JOIN areas a ON a.id = u.area_id
            LEFT JOIN prode_user_entries e ON e.user_id = u.id AND e.edition_id = :eid
            WHERE u.role = :role AND u.is_active = 1';
        if ($companyId > 0) {
            $sql .= ' AND u.company_id = :cid';
        }
        if ($areaId > 0) {
            $sql .= ' AND u.area_id = :aid';
        }
        $sql .= ' ORDER BY total_points DESC, exact_hits DESC, result_hits DESC, u.full_name ASC LIMIT ' . (int)$limit;

        $this->db->query($sql);
        $this->db->bind(':eid', (int)$editionId);
        $this->db->bind(':role', 'empleado');
        if ($companyId > 0) {
            $this->db->bind(':cid', (int)$companyId);
        }
        if ($areaId > 0) {
            $this->db->bind(':aid', (int)$areaId);
        }
        return $this->db->resultSet();
    }

    public function updateUserScoreCache($editionId, $userId) {
        $editionId = (int)$editionId;
        $userId = (int)$userId;
        $this->db->query('SELECT
            COALESCE(SUM(p.points_earned), 0) AS pts,
            COALESCE(SUM(CASE WHEN p.points_earned = 3 THEN 1 ELSE 0 END), 0) AS exact_h,
            COALESCE(SUM(CASE WHEN p.points_earned = 1 THEN 1 ELSE 0 END), 0) AS result_h
            FROM prode_predictions p
            JOIN prode_matches m ON m.id = p.match_id
            INNER JOIN prode_user_entries e ON e.user_id = p.user_id AND e.edition_id = m.edition_id AND e.status = :submitted
            WHERE p.user_id = :uid AND m.edition_id = :eid AND m.status = :finished');
        $this->db->bind(':uid', $userId);
        $this->db->bind(':eid', $editionId);
        $this->db->bind(':submitted', 'submitted');
        $this->db->bind(':finished', 'finished');
        $agg = $this->db->single();
        $pts = $agg ? (int)$agg->pts : 0;
        $exact = $agg ? (int)$agg->exact_h : 0;
        $result = $agg ? (int)$agg->result_h : 0;

        $this->getOrCreateEntry($editionId, $userId);
        $this->db->query('UPDATE prode_user_entries SET total_points = :pts, exact_hits = :ex, result_hits = :res
            WHERE edition_id = :eid AND user_id = :uid');
        $this->db->bind(':pts', $pts);
        $this->db->bind(':ex', $exact);
        $this->db->bind(':res', $result);
        $this->db->bind(':eid', $editionId);
        $this->db->bind(':uid', $userId);
        return $this->db->execute();
    }

    public function getUserIdsWithPredictionForMatch($matchId) {
        $this->db->query('SELECT DISTINCT user_id FROM prode_predictions WHERE match_id = :mid');
        $this->db->bind(':mid', (int)$matchId);
        return array_map(function ($r) {
            return (int)$r->user_id;
        }, $this->db->resultSet());
    }
}
