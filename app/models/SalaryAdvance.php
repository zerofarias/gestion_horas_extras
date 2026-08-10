<?php

class SalaryAdvance {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function isSchemaReady() {
        try {
            $this->db->query('SHOW TABLES LIKE \'salary_advances\'');
            return (bool)$this->db->single();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function installmentsSchemaReady() {
        try {
            $this->db->query('SHOW TABLES LIKE \'salary_advance_installments\'');
            return (bool)$this->db->single();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function finalizadoSchemaReady() {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        try {
            $this->db->query('SHOW COLUMNS FROM salary_advances LIKE \'finalized_at\'');
            $ready = (bool)$this->db->single();
        } catch (Throwable $e) {
            $ready = false;
        }
        return $ready;
    }

    private function listSelectSql() {
        return '
            SELECT sa.*,
                   u.full_name AS employee_name,
                   approver.full_name AS approved_by_name
            FROM salary_advances sa
            JOIN users u ON u.id = sa.user_id
            LEFT JOIN users approver ON approver.id = sa.approved_by
        ';
    }

    public function create(array $data) {
        $this->db->query('
            INSERT INTO salary_advances (
                user_id, company_id, amount, reference_salary,
                installments_requested, reason, status
            ) VALUES (
                :user_id, :company_id, :amount, NULL,
                :installments_requested, :reason, \'Pendiente\'
            )
        ');
        $this->db->bind(':user_id', (int)$data['user_id']);
        $this->db->bind(':company_id', (int)$data['company_id']);
        $this->db->bind(':amount', $data['amount']);
        $this->db->bind(':installments_requested', (int)$data['installments_requested']);
        $this->db->bind(':reason', $data['reason'] ?? '');
        if ($this->db->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    public function getById($id) {
        $this->db->query('SELECT * FROM salary_advances WHERE id = :id LIMIT 1');
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    public function getByIdForCompany($id, $companyId) {
        $this->db->query($this->listSelectSql() . '
            WHERE sa.id = :id AND sa.company_id = :company_id
            LIMIT 1
        ');
        $this->db->bind(':id', (int)$id);
        $this->db->bind(':company_id', (int)$companyId);
        return $this->db->single();
    }

    public function getByUserId($userId) {
        $this->db->query($this->listSelectSql() . '
            WHERE sa.user_id = :user_id
            ORDER BY sa.created_at DESC
        ');
        $this->db->bind(':user_id', (int)$userId);
        return $this->db->resultSet();
    }

    public function countByUserInYear($userId, $year) {
        $this->db->query('
            SELECT COUNT(*) AS cnt
            FROM salary_advances
            WHERE user_id = :user_id
              AND status IN (\'Pendiente\', \'Aprobado\', \'Finalizado\')
              AND YEAR(created_at) = :yr
        ');
        $this->db->bind(':user_id', (int)$userId);
        $this->db->bind(':yr', (int)$year);
        $row = $this->db->single();
        return $row ? (int)$row->cnt : 0;
    }

    public function hasPendingByUser($userId) {
        $this->db->query('
            SELECT id FROM salary_advances
            WHERE user_id = :user_id AND status = \'Pendiente\'
            LIMIT 1
        ');
        $this->db->bind(':user_id', (int)$userId);
        return (bool)$this->db->single();
    }

    public function getAllByCompany($companyId, array $filters = []) {
        $sql = $this->listSelectSql() . ' WHERE sa.company_id = :company_id';
        $params = [':company_id' => (int)$companyId];

        if (!empty($filters['status']) && in_array($filters['status'], salary_advance_statuses(), true)) {
            $sql .= ' AND sa.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= ' AND sa.user_id = :user_id';
            $params[':user_id'] = (int)$filters['user_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND u.full_name LIKE :search';
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= ' ORDER BY FIELD(sa.status, \'Pendiente\', \'Aprobado\', \'Finalizado\', \'Rechazado\'), sa.created_at DESC';

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->resultSet();
    }

    public function countPendingByCompany($companyId) {
        $this->db->query('
            SELECT COUNT(*) AS cnt
            FROM salary_advances
            WHERE company_id = :company_id AND status = \'Pendiente\'
        ');
        $this->db->bind(':company_id', (int)$companyId);
        $row = $this->db->single();
        return $row ? (int)$row->cnt : 0;
    }

    public function getPendingByCompany($companyId, $limit = 10) {
        $this->db->query($this->listSelectSql() . '
            WHERE sa.company_id = :company_id AND sa.status = \'Pendiente\'
            ORDER BY sa.created_at ASC
            LIMIT ' . (int)$limit . '
        ');
        $this->db->bind(':company_id', (int)$companyId);
        return $this->db->resultSet();
    }

    public function getInstallmentsByAdvanceId($advanceId) {
        if (!$this->installmentsSchemaReady()) {
            return [];
        }
        $this->db->query('
            SELECT *
            FROM salary_advance_installments
            WHERE salary_advance_id = :advance_id
            ORDER BY installment_number ASC
        ');
        $this->db->bind(':advance_id', (int)$advanceId);
        return $this->db->resultSet();
    }

    public function getInstallmentForReceipt($advanceId, $installmentNumber, $companyId) {
        if (!$this->installmentsSchemaReady()) {
            return null;
        }
        $this->db->query('
            SELECT sai.*,
                   sa.amount AS advance_total,
                   sa.user_id,
                   sa.company_id,
                   sa.created_at AS advance_created_at,
                   u.full_name AS employee_name
            FROM salary_advance_installments sai
            JOIN salary_advances sa ON sa.id = sai.salary_advance_id
            JOIN users u ON u.id = sa.user_id
            WHERE sai.salary_advance_id = :advance_id
              AND sai.installment_number = :inst_num
              AND sa.company_id = :company_id
            LIMIT 1
        ');
        $this->db->bind(':advance_id', (int)$advanceId);
        $this->db->bind(':inst_num', (int)$installmentNumber);
        $this->db->bind(':company_id', (int)$companyId);
        return $this->db->single();
    }

    public function approveWithInstallments($id, $adminId, array $data, array $installments) {
        if (!$this->installmentsSchemaReady()) {
            return $this->approve($id, $adminId, $data);
        }

        try {
            $this->db->beginTransaction();

            $this->db->query('
                UPDATE salary_advances SET
                    status = \'Aprobado\',
                    installments_approved = :installments_approved,
                    hr_installments_override = :hr_override,
                    admin_notes = :admin_notes,
                    approved_by = :approved_by,
                    approved_at = NOW(),
                    rejected_at = NULL
                WHERE id = :id AND status = \'Pendiente\'
            ');
            $this->db->bind(':installments_approved', (int)$data['installments_approved']);
            $this->db->bind(':hr_override', !empty($data['hr_installments_override']) ? 1 : 0);
            $this->db->bind(':admin_notes', $data['admin_notes'] ?? '');
            $this->db->bind(':approved_by', (int)$adminId);
            $this->db->bind(':id', (int)$id);
            $this->db->execute();

            if ($this->db->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }

            $this->deleteInstallmentsByAdvanceId($id);
            foreach ($installments as $row) {
                $this->db->query('
                    INSERT INTO salary_advance_installments (
                        salary_advance_id, installment_number, due_month, amount
                    ) VALUES (
                        :advance_id, :inst_num, :due_month, :amount
                    )
                ');
                $this->db->bind(':advance_id', (int)$id);
                $this->db->bind(':inst_num', (int)$row['installment_number']);
                $this->db->bind(':due_month', $row['due_month']);
                $this->db->bind(':amount', $row['amount']);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function approve($id, $adminId, array $data) {
        $this->db->query('
            UPDATE salary_advances SET
                status = \'Aprobado\',
                installments_approved = :installments_approved,
                hr_installments_override = :hr_override,
                admin_notes = :admin_notes,
                approved_by = :approved_by,
                approved_at = NOW(),
                rejected_at = NULL
            WHERE id = :id AND status = \'Pendiente\'
        ');
        $this->db->bind(':installments_approved', (int)$data['installments_approved']);
        $this->db->bind(':hr_override', !empty($data['hr_installments_override']) ? 1 : 0);
        $this->db->bind(':admin_notes', $data['admin_notes'] ?? '');
        $this->db->bind(':approved_by', (int)$adminId);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    public function saveInstallmentSchedule($advanceId, array $installments) {
        if (!$this->installmentsSchemaReady()) {
            return false;
        }

        try {
            $this->db->beginTransaction();
            foreach ($installments as $row) {
                $this->db->query('
                    UPDATE salary_advance_installments SET
                        due_month = :due_month,
                        amount = :amount,
                        notes = :notes
                    WHERE id = :id AND salary_advance_id = :advance_id
                ');
                $this->db->bind(':due_month', $row['due_month']);
                $this->db->bind(':amount', $row['amount']);
                $this->db->bind(':notes', $row['notes'] ?? '');
                $this->db->bind(':id', (int)$row['id']);
                $this->db->bind(':advance_id', (int)$advanceId);
                $this->db->execute();
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function setInstallmentDeducted($installmentId, $advanceId, $adminId, $deducted) {
        if (!$this->installmentsSchemaReady()) {
            return false;
        }
        if ($deducted) {
            $this->db->query('
                UPDATE salary_advance_installments SET
                    is_deducted = 1,
                    deducted_at = NOW(),
                    deducted_by = :admin_id
                WHERE id = :id AND salary_advance_id = :advance_id
            ');
            $this->db->bind(':admin_id', (int)$adminId);
        } else {
            $this->db->query('
                UPDATE salary_advance_installments SET
                    is_deducted = 0,
                    deducted_at = NULL,
                    deducted_by = NULL
                WHERE id = :id AND salary_advance_id = :advance_id
            ');
        }
        $this->db->bind(':id', (int)$installmentId);
        $this->db->bind(':advance_id', (int)$advanceId);
        if (!$this->db->execute()) {
            return false;
        }
        $this->syncAdvanceCompletionStatus($advanceId);
        return true;
    }

    public function syncAdvanceCompletionStatus($advanceId) {
        if (!$this->installmentsSchemaReady() || !$this->finalizadoSchemaReady()) {
            return;
        }
        $advance = $this->getById($advanceId);
        if (!$advance || !in_array($advance->status, ['Aprobado', 'Finalizado'], true)) {
            return;
        }

        $this->db->query('
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN is_deducted = 1 THEN 1 ELSE 0 END) AS paid
            FROM salary_advance_installments
            WHERE salary_advance_id = :advance_id
        ');
        $this->db->bind(':advance_id', (int)$advanceId);
        $row = $this->db->single();
        $total = $row ? (int)$row->total : 0;
        $paid = $row ? (int)$row->paid : 0;

        if ($total > 0 && $paid === $total && $advance->status === 'Aprobado') {
            $this->db->query('
                UPDATE salary_advances SET
                    status = \'Finalizado\',
                    finalized_at = NOW()
                WHERE id = :id AND status = \'Aprobado\'
            ');
            $this->db->bind(':id', (int)$advanceId);
            $this->db->execute();
        } elseif ($total > 0 && $paid < $total && $advance->status === 'Finalizado') {
            $this->db->query('
                UPDATE salary_advances SET
                    status = \'Aprobado\',
                    finalized_at = NULL
                WHERE id = :id AND status = \'Finalizado\'
            ');
            $this->db->bind(':id', (int)$advanceId);
            $this->db->execute();
        }
    }

    private function deleteInstallmentsByAdvanceId($advanceId) {
        $this->db->query('DELETE FROM salary_advance_installments WHERE salary_advance_id = :advance_id');
        $this->db->bind(':advance_id', (int)$advanceId);
        $this->db->execute();
    }

    public function insertInstallments($advanceId, array $installments) {
        if (!$this->installmentsSchemaReady()) {
            return false;
        }
        $this->deleteInstallmentsByAdvanceId($advanceId);
        foreach ($installments as $row) {
            $this->db->query('
                INSERT INTO salary_advance_installments (
                    salary_advance_id, installment_number, due_month, amount
                ) VALUES (
                    :advance_id, :inst_num, :due_month, :amount
                )
            ');
            $this->db->bind(':advance_id', (int)$advanceId);
            $this->db->bind(':inst_num', (int)$row['installment_number']);
            $this->db->bind(':due_month', $row['due_month']);
            $this->db->bind(':amount', $row['amount']);
            if (!$this->db->execute()) {
                return false;
            }
        }
        return true;
    }

    public function reject($id, $adminId, $notes) {
        $this->db->query('
            UPDATE salary_advances SET
                status = \'Rechazado\',
                admin_notes = :admin_notes,
                approved_by = :approved_by,
                approved_at = NULL,
                rejected_at = NOW()
            WHERE id = :id AND status = \'Pendiente\'
        ');
        $this->db->bind(':admin_notes', $notes);
        $this->db->bind(':approved_by', (int)$adminId);
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }
}
