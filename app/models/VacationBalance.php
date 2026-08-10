<?php

class VacationBalance {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function isReady() {
        return vacation_module_ready();
    }

    public function getPeriodsByUser($userId, $openOnly = false) {
        $sql = 'SELECT vbp.*, ca.name AS agreement_name, ca.code AS agreement_code
                FROM vacation_balance_periods vbp
                JOIN collective_agreements ca ON ca.id = vbp.agreement_id
                WHERE vbp.user_id = :uid';
        if ($openOnly) {
            $sql .= " AND vbp.status = 'open'";
        }
        $sql .= ' ORDER BY vbp.period_start DESC';
        $this->db->query($sql);
        $this->db->bind(':uid', (int)$userId);
        return $this->db->resultSet();
    }

    public function getPeriodByUserLabel($userId, $periodLabel) {
        $this->db->query('SELECT * FROM vacation_balance_periods WHERE user_id = :uid AND period_label = :lbl LIMIT 1');
        $this->db->bind(':uid', (int)$userId);
        $this->db->bind(':lbl', $periodLabel);
        return $this->db->single();
    }

    public function getPeriodById($id) {
        $this->db->query('SELECT * FROM vacation_balance_periods WHERE id = :id');
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    public function getTotalPending($userId) {
        $this->db->query("
            SELECT COALESCE(SUM(days_pending), 0) AS total
            FROM vacation_balance_periods
            WHERE user_id = :uid AND status = 'open'
        ");
        $this->db->bind(':uid', (int)$userId);
        $row = $this->db->single();
        return $row ? (float)$row->total : 0.0;
    }

    public function createPeriod(array $data) {
        $this->db->query('
            INSERT INTO vacation_balance_periods
                (user_id, period_label, period_start, period_end, agreement_id, agreement_rule_id,
                 days_entitled, days_taken, days_pending, status, liquidated_at, liquidated_by)
            VALUES
                (:uid, :lbl, :ps, :pe, :aid, :rid, :ent, :taken, :pend, :st, :liq_at, :liq_by)
        ');
        $pending = (float)$data['days_entitled'] - (float)($data['days_taken'] ?? 0);
        $this->db->bind(':uid', (int)$data['user_id']);
        $this->db->bind(':lbl', $data['period_label']);
        $this->db->bind(':ps', $data['period_start']);
        $this->db->bind(':pe', $data['period_end']);
        $this->db->bind(':aid', (int)$data['agreement_id']);
        $this->db->bind(':rid', $data['agreement_rule_id'] ?? null);
        $this->db->bind(':ent', (float)$data['days_entitled']);
        $this->db->bind(':taken', (float)($data['days_taken'] ?? 0));
        $this->db->bind(':pend', $pending);
        $this->db->bind(':st', $data['status'] ?? 'open');
        $this->db->bind(':liq_at', $data['liquidated_at'] ?? null);
        $this->db->bind(':liq_by', $data['liquidated_by'] ?? null);
        if ($this->db->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return 0;
    }

    public function updatePeriodBalances($periodId, $daysEntitled, $daysTaken) {
        $pending = max(0, (float)$daysEntitled - (float)$daysTaken);
        $this->db->query('
            UPDATE vacation_balance_periods
            SET days_entitled = :ent, days_taken = :taken, days_pending = :pend
            WHERE id = :id
        ');
        $this->db->bind(':ent', (float)$daysEntitled);
        $this->db->bind(':taken', (float)$daysTaken);
        $this->db->bind(':pend', $pending);
        $this->db->bind(':id', (int)$periodId);
        return $this->db->execute();
    }

    public function addMovement(array $data) {
        $datesJson = null;
        if (!empty($data['schedule_dates'])) {
            $datesJson = is_string($data['schedule_dates'])
                ? $data['schedule_dates']
                : json_encode($data['schedule_dates']);
        }
        $this->db->query('
            INSERT INTO vacation_balance_movements
                (period_id, user_id, movement_type, source, days, request_id, schedule_dates, notes, created_by)
            VALUES
                (:pid, :uid, :mtype, :src, :days, :req, :dates, :notes, :by)
        ');
        $this->db->bind(':pid', (int)$data['period_id']);
        $this->db->bind(':uid', (int)$data['user_id']);
        $this->db->bind(':mtype', $data['movement_type']);
        $this->db->bind(':src', $data['source']);
        $this->db->bind(':days', (float)$data['days']);
        $this->db->bind(':req', $data['request_id'] ?? null);
        $this->db->bind(':dates', $datesJson);
        $this->db->bind(':notes', $data['notes'] ?? null);
        $this->db->bind(':by', (int)$data['created_by']);
        return $this->db->execute();
    }

    public function getMovementsByUser($userId, $limit = 50) {
        $this->db->query('
            SELECT m.*, p.period_label
            FROM vacation_balance_movements m
            JOIN vacation_balance_periods p ON p.id = m.period_id
            WHERE m.user_id = :uid
            ORDER BY m.created_at DESC
            LIMIT ' . (int)$limit
        );
        $this->db->bind(':uid', (int)$userId);
        return $this->db->resultSet();
    }

    public function syncUserVacationCache($userId) {
        $total = $this->getTotalPending($userId);
        $userModel = new User();
        return $userModel->updateVacationBalance($userId, round($total, 2));
    }
}
