<?php

class ProdeScoringService {
    private $editionModel;
    private $predictionModel;

    public function __construct() {
        $this->editionModel = new ProdeEdition();
        $this->predictionModel = new ProdePrediction();
    }

    public static function computePoints($predHome, $predAway, $actualHome, $actualAway) {
        $predHome = (int)$predHome;
        $predAway = (int)$predAway;
        $actualHome = (int)$actualHome;
        $actualAway = (int)$actualAway;

        if ($predHome === $actualHome && $predAway === $actualAway) {
            return 3;
        }
        if (prode_match_result_sign($predHome, $predAway) === prode_match_result_sign($actualHome, $actualAway)) {
            return 1;
        }
        return 0;
    }

    public function recalculateMatch($matchId) {
        $match = $this->editionModel->getMatchById($matchId);
        if (!$match || $match->status !== 'finished') {
            return false;
        }
        if ($match->home_score_actual === null || $match->away_score_actual === null) {
            return false;
        }

        $db = new Database();
        $db->query('SELECT p.* FROM prode_predictions p
            INNER JOIN prode_user_entries e ON e.user_id = p.user_id AND e.edition_id = :eid AND e.status = :submitted
            WHERE p.match_id = :mid
              AND p.home_score_pred IS NOT NULL AND p.away_score_pred IS NOT NULL');
        $db->bind(':eid', (int)$match->edition_id);
        $db->bind(':submitted', 'submitted');
        $db->bind(':mid', (int)$matchId);
        $preds = $db->resultSet();

        foreach ($preds as $p) {
            $pts = self::computePoints(
                $p->home_score_pred,
                $p->away_score_pred,
                $match->home_score_actual,
                $match->away_score_actual
            );
            $db->query('UPDATE prode_predictions SET points_earned = :pts WHERE id = :id');
            $db->bind(':pts', $pts);
            $db->bind(':id', (int)$p->id);
            $db->execute();
        }

        $userIds = $this->predictionModel->getUserIdsWithPredictionForMatch($matchId);
        foreach ($userIds as $uid) {
            $this->predictionModel->updateUserScoreCache((int)$match->edition_id, $uid);
        }

        $db->query('UPDATE prode_predictions p
            LEFT JOIN prode_user_entries e ON e.user_id = p.user_id AND e.edition_id = :eid
            SET p.points_earned = 0
            WHERE p.match_id = :mid AND (e.status IS NULL OR e.status != :submitted1)');
        $db->bind(':eid', (int)$match->edition_id);
        $db->bind(':mid', (int)$matchId);
        $db->bind(':submitted1', 'submitted');
        $db->execute();

        return true;
    }

    public function saveResultAndRecalculate($matchId, $homeScore, $awayScore) {
        if (!$this->editionModel->saveMatchResult($matchId, $homeScore, $awayScore)) {
            return false;
        }
        return $this->recalculateMatch($matchId);
    }
}
