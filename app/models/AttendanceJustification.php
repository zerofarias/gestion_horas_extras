<?php

class AttendanceJustification {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    public function isSchemaReady() {
        try {
            $this->db->query("SHOW TABLES LIKE 'attendance_justifications'");
            return (bool)$this->db->single();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Tipos agrupados para el formulario.
     */
    public static function typeLabelsGrouped() {
        return [
            'Ausencia o día completo' => [
                'medical_certificate' => 'Certificado médico (ausencia)',
                'authorized_absence'  => 'Ausencia autorizada (aviso previo)',
                'attended_no_clock'   => 'Asistió pero no marcó',
            ],
            'Salida temprana / permiso' => [
                'early_leave_medical'     => 'Salida temprana — médico / turno médico',
                'early_leave_errand'      => 'Salida temprana — trámite / gestión personal',
                'early_leave_authorized'  => 'Salida temprana — permiso autorizado',
            ],
            'Otro' => [
                'other' => 'Otro motivo',
            ],
        ];
    }

    public static function typeLabels() {
        $flat = [];
        foreach (self::typeLabelsGrouped() as $types) {
            foreach ($types as $k => $label) {
                $flat[$k] = $label;
            }
        }
        return $flat;
    }

    public static function isEarlyLeaveType($type) {
        return in_array($type, [
            'early_leave_medical',
            'early_leave_errand',
            'early_leave_authorized',
        ], true);
    }

    /** Sugiere tipo según alerta de asistencia del día. */
    public static function suggestTypeForStatus($attendanceStatus) {
        $map = [
            'early_leave'  => 'early_leave_authorized',
            'late'         => 'other',
            'no_show'      => 'medical_certificate',
            'missing_out'  => 'early_leave_medical',
            'incomplete'   => 'other',
        ];
        return $map[$attendanceStatus] ?? 'other';
    }

    public function getByUserDate($userId, $workDate) {
        if (!$this->isSchemaReady()) {
            return false;
        }
        $this->db->query('SELECT * FROM attendance_justifications
            WHERE user_id = :user_id AND work_date = :work_date LIMIT 1');
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':work_date', $workDate);
        return $this->db->single();
    }

    public function getForUserPeriod($userId, $startDate, $endDate) {
        if (!$this->isSchemaReady()) {
            return [];
        }
        $this->db->query('SELECT * FROM attendance_justifications
            WHERE user_id = :user_id AND work_date BETWEEN :start_date AND :end_date');
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':start_date', $startDate);
        $this->db->bind(':end_date', $endDate);
        $rows = $this->db->resultSet();
        $map = [];
        foreach ($rows as $r) {
            $map[$r->work_date] = $r;
        }
        return $map;
    }

    public function upsert(array $data) {
        if (!$this->isSchemaReady()) {
            return false;
        }
        $existing = $this->getByUserDate($data['user_id'], $data['work_date']);

        $leaveTime = !empty($data['leave_time']) ? $data['leave_time'] : null;
        if ($leaveTime !== null && strlen($leaveTime) === 5) {
            $leaveTime .= ':00';
        }
        $priorNotice = !empty($data['prior_notice']) ? 1 : 0;

        if ($existing) {
            $sql = 'UPDATE attendance_justifications SET
                justification_type = :justification_type,
                leave_time = :leave_time,
                prior_notice = :prior_notice,
                notes = :notes,
                created_by = :created_by';
            if (!empty($data['file_path'])) {
                $sql .= ', file_path = :file_path';
            }
            $sql .= ' WHERE id = :id';
            $this->db->query($sql);
            $this->db->bind(':justification_type', $data['justification_type']);
            $this->db->bind(':leave_time', $leaveTime);
            $this->db->bind(':prior_notice', $priorNotice);
            $this->db->bind(':notes', $data['notes']);
            $this->db->bind(':created_by', $data['created_by']);
            $this->db->bind(':id', $existing->id);
            if (!empty($data['file_path'])) {
                $this->db->bind(':file_path', $data['file_path']);
            }
            return $this->db->execute();
        }

        $this->db->query('INSERT INTO attendance_justifications (
            company_id, user_id, work_date, justification_type, leave_time, prior_notice,
            notes, file_path, created_by
        ) VALUES (
            :company_id, :user_id, :work_date, :justification_type, :leave_time, :prior_notice,
            :notes, :file_path, :created_by
        )');
        $this->db->bind(':company_id', $data['company_id']);
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':work_date', $data['work_date']);
        $this->db->bind(':justification_type', $data['justification_type']);
        $this->db->bind(':leave_time', $leaveTime);
        $this->db->bind(':prior_notice', $priorNotice);
        $this->db->bind(':notes', $data['notes'] ?? null);
        $this->db->bind(':file_path', $data['file_path'] ?? null);
        $this->db->bind(':created_by', $data['created_by']);
        return $this->db->execute();
    }

    public function deleteByUserDate($userId, $workDate) {
        if (!$this->isSchemaReady()) {
            return false;
        }
        $this->db->query('DELETE FROM attendance_justifications
            WHERE user_id = :user_id AND work_date = :work_date');
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':work_date', $workDate);
        return $this->db->execute();
    }
}
