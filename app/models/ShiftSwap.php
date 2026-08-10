<?php

class ShiftSwap {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    public function isSchemaReady() {
        try {
            $this->db->query("SHOW COLUMNS FROM `shift_swaps` LIKE 'proposer_schedule_id'");
            return (bool)$this->db->single();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function createSwapRequest(array $data) {
        $accepterScheduleId = !empty($data['accepter_schedule_id']) ? (int)$data['accepter_schedule_id'] : null;

        $this->db->query('INSERT INTO shift_swaps (
            proposer_user_id, accepter_user_id, proposer_schedule_id, accepter_schedule_id, notes, status
        ) VALUES (
            :proposer_user_id, :accepter_user_id, :proposer_schedule_id, :accepter_schedule_id, :notes, \'Pendiente\'
        )');
        $this->db->bind(':proposer_user_id', $data['proposer_user_id']);
        $this->db->bind(':accepter_user_id', $data['accepter_user_id']);
        $this->db->bind(':proposer_schedule_id', $data['proposer_schedule_id']);
        $this->db->bind(':accepter_schedule_id', $accepterScheduleId);
        $this->db->bind(':notes', $data['notes'] ?? '');
        return $this->db->execute();
    }

    public function getById($id) {
        $this->db->query('SELECT * FROM shift_swaps WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getByIdForCompany($id, $companyId) {
        $this->db->query($this->listSelectSql() . '
            WHERE ss.id = :id AND pu.company_id = :company_id
            LIMIT 1
        ');
        $this->db->bind(':id', $id);
        $this->db->bind(':company_id', $companyId);
        return $this->db->single();
    }

    private function listSelectSql() {
        return "
            SELECT ss.*,
                   pu.full_name AS proposer_name,
                   au.full_name AS accepter_name,
                   ps.schedule_date AS proposer_date, ps.start_time AS proposer_start, ps.end_time AS proposer_end,
                   aps.schedule_date AS accepter_date, aps.start_time AS accepter_start, aps.end_time AS accepter_end,
                   psn.shift_name AS proposer_shift_name,
                   asn.shift_name AS accepter_shift_name
            FROM shift_swaps ss
            JOIN users pu ON pu.id = ss.proposer_user_id
            JOIN users au ON au.id = ss.accepter_user_id
            JOIN employee_schedules ps ON ps.id = ss.proposer_schedule_id
            LEFT JOIN employee_schedules aps ON aps.id = ss.accepter_schedule_id
            LEFT JOIN shifts psn ON psn.id = ps.shift_id
            LEFT JOIN shifts asn ON asn.id = aps.shift_id
        ";
    }

    public function getSwapsByUserId($userId) {
        $this->db->query($this->listSelectSql() . "
            WHERE ss.proposer_user_id = :swap_uid1 OR ss.accepter_user_id = :swap_uid2
            ORDER BY ss.created_at DESC
        ");
        $this->db->bind(':swap_uid1', $userId);
        $this->db->bind(':swap_uid2', $userId);
        return $this->db->resultSet();
    }

    /** Cambios de turno del usuario que caen en un rango de fechas (por fecha del turno del proponente o aceptante). */
    public function getForUserInPeriod($userId, $startDate, $endDate) {
        if (!$this->isSchemaReady()) {
            return [];
        }
        $this->db->query($this->listSelectSql() . "
            WHERE (ss.proposer_user_id = :uid1 OR ss.accepter_user_id = :uid2)
              AND (
                  (ps.schedule_date BETWEEN :start1 AND :end1)
                  OR (aps.schedule_date IS NOT NULL AND aps.schedule_date BETWEEN :start2 AND :end2)
              )
            ORDER BY ps.schedule_date ASC, ss.created_at ASC
        ");
        $this->db->bind(':uid1', (int)$userId);
        $this->db->bind(':uid2', (int)$userId);
        $this->db->bind(':start1', $startDate);
        $this->db->bind(':end1', $endDate);
        $this->db->bind(':start2', $startDate);
        $this->db->bind(':end2', $endDate);
        return $this->db->resultSet();
    }

    public function countPendingByCompany($companyId) {
        $this->db->query("
            SELECT COUNT(ss.id) AS cnt
            FROM shift_swaps ss
            JOIN users pu ON pu.id = ss.proposer_user_id
            WHERE pu.company_id = :company_id AND ss.status = 'Pendiente'
        ");
        $this->db->bind(':company_id', $companyId);
        $row = $this->db->single();
        return $row ? (int)$row->cnt : 0;
    }

    public function getPendingByCompany($companyId) {
        $this->db->query($this->listSelectSql() . "
            WHERE pu.company_id = :company_id AND ss.status = 'Pendiente'
            ORDER BY ss.created_at ASC
        ");
        $this->db->bind(':company_id', $companyId);
        return $this->db->resultSet();
    }

    public function updateStatus($id, $status, $reviewedBy = null, $accepterScheduleId = null) {
        $sql = 'UPDATE shift_swaps SET status = :status, reviewed_by = :reviewed_by';
        if ($accepterScheduleId !== null) {
            $sql .= ', accepter_schedule_id = :accepter_schedule_id';
        }
        $sql .= ' WHERE id = :id';
        $this->db->query($sql);
        $this->db->bind(':status', $status);
        $this->db->bind(':reviewed_by', $reviewedBy);
        $this->db->bind(':id', $id);
        if ($accepterScheduleId !== null) {
            $this->db->bind(':accepter_schedule_id', $accepterScheduleId);
        }
        return $this->db->execute();
    }

    /**
     * Busca el turno del compañero el mismo día que el turno ofrecido (distinto horario).
     */
    public function resolveAccepterScheduleId($proposerScheduleId, $accepterUserId) {
        $workSchedule = new WorkSchedule();

        $proposer = $workSchedule->getScheduleEntryById($proposerScheduleId);
        if (!$proposer || (int)$proposer->user_id === (int)$accepterUserId) {
            return null;
        }

        $candidates = $workSchedule->getSchedulesForUserOnDate($accepterUserId, $proposer->schedule_date);
        if (empty($candidates)) {
            return null;
        }

        $filtered = [];
        foreach ($candidates as $block) {
            if ($block->start_time === $proposer->start_time && $block->end_time === $proposer->end_time) {
                continue;
            }
            $filtered[] = $block;
        }

        if (empty($filtered)) {
            return null;
        }

        return (int)$filtered[0]->id;
    }

    public function approveSwap($swapId, $adminId) {
        $swap = $this->getById($swapId);
        if (!$swap || $swap->status !== 'Pendiente') {
            return ['ok' => false, 'message' => 'El cambio de turno no está pendiente.'];
        }

        $accepterScheduleId = !empty($swap->accepter_schedule_id)
            ? (int)$swap->accepter_schedule_id
            : $this->resolveAccepterScheduleId($swap->proposer_schedule_id, $swap->accepter_user_id);

        if (!$accepterScheduleId) {
            return [
                'ok' => false,
                'message' => 'No se encontró un turno del compañero el mismo día para intercambiar. Verificá la planificación.',
            ];
        }

        $workSchedule = new WorkSchedule();
        $pRow = $workSchedule->getScheduleEntryById($swap->proposer_schedule_id);
        $aRow = $workSchedule->getScheduleEntryById($accepterScheduleId);

        if (!$pRow || !$aRow) {
            return ['ok' => false, 'message' => 'Uno de los turnos planificados ya no existe.'];
        }

        if ((int)$pRow->user_id !== (int)$swap->proposer_user_id
            || (int)$aRow->user_id !== (int)$swap->accepter_user_id) {
            return ['ok' => false, 'message' => 'Los turnos cambiaron desde que se solicitó el intercambio.'];
        }

        $this->db->beginTransaction();
        try {
            $this->db->query('UPDATE employee_schedules SET user_id = :new_user WHERE id = :id');
            $this->db->bind(':new_user', $swap->accepter_user_id);
            $this->db->bind(':id', $swap->proposer_schedule_id);
            $this->db->execute();

            $this->db->bind(':new_user', $swap->proposer_user_id);
            $this->db->bind(':id', $accepterScheduleId);
            $this->db->execute();

            $this->updateStatus($swapId, 'Aprobado', $adminId, $accepterScheduleId);
            $this->db->commit();
            return ['ok' => true, 'message' => 'Cambio de turno aprobado. La planificación fue actualizada.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'Error al aplicar el cambio en la planificación.'];
        }
    }

    public function rejectSwap($swapId, $adminId) {
        if ($this->updateStatus($swapId, 'Rechazado', $adminId)) {
            return ['ok' => true, 'message' => 'Cambio de turno rechazado.'];
        }
        return ['ok' => false, 'message' => 'No se pudo rechazar el cambio.'];
    }

    public function hasPendingForProposerAndColleague($proposerScheduleId, $accepterUserId) {
        $this->db->query("
            SELECT id FROM shift_swaps
            WHERE status = 'Pendiente'
              AND proposer_schedule_id = :proposer_schedule_id
              AND accepter_user_id = :accepter_user_id
            LIMIT 1
        ");
        $this->db->bind(':proposer_schedule_id', $proposerScheduleId);
        $this->db->bind(':accepter_user_id', $accepterUserId);
        return (bool)$this->db->single();
    }
}
