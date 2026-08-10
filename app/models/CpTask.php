<?php

class CpTask {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getTaskTypes($mvpOnly = false) {
        $sql = 'SELECT * FROM cp_task_types WHERE is_active = 1';
        if ($mvpOnly) {
            $sql .= ' AND mvp_enabled = 1';
        }
        $sql .= ' ORDER BY sort_order ASC';
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    public function getTaskTypeByFormKey($formKey) {
        $this->db->query('SELECT * FROM cp_task_types WHERE form_key = ? AND is_active = 1 LIMIT 1');
        return $this->db->single([trim((string)$formKey)]);
    }

    public function getTaskTypeById($id) {
        $this->db->query('SELECT * FROM cp_task_types WHERE id = ? LIMIT 1');
        return $this->db->single([(int)$id]);
    }

    public function getTaskTypeByLegacyCode($legacyCode) {
        $this->db->query('SELECT * FROM cp_task_types WHERE legacy_code = ? AND is_active = 1 LIMIT 1');
        return $this->db->single([(int)$legacyCode]);
    }

    public function getRatesForUser($userId) {
        $this->db->query('SELECT * FROM cp_employee_rates WHERE user_id = ? LIMIT 1');
        return $this->db->single([(int)$userId]);
    }

    public static function rateColumnNames() {
        return [
            'armar_s', 'realizar_s', 'cremacion', 'cremacion_adicional', 'localidades', 'covid',
            'cambio_metalica', 'ambu_localidades', 'ambu_vm', 'viajes_activa', 'viajes_pasiva', 'tanato', 'gestion_tramites',
        ];
    }

    public function saveRatesForUser($userId, array $fields) {
        $userId = (int)$userId;
        $existing = $this->getRatesForUser($userId);
        $cols = self::rateColumnNames();
        $vals = [];
        foreach ($cols as $c) {
            $vals[] = isset($fields[$c]) ? (float)$fields[$c] : 0;
        }
        $setList = implode('=?, ', $cols) . '=?';
        if ($existing) {
            $this->db->query('UPDATE cp_employee_rates SET ' . $setList . ' WHERE user_id=?');
            $vals[] = $userId;
            return $this->db->execute($vals);
        }
        $this->db->query('INSERT INTO cp_employee_rates (' . implode(', ', $cols) . ', user_id) VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ',?)');
        $vals[] = $userId;
        return $this->db->execute($vals);
    }

    public function ensureRatesRow($userId) {
        if (!$this->getRatesForUser($userId)) {
            $this->saveRatesForUser($userId, []);
        }
    }

    public function getLocalities($companyId) {
        $this->db->query('SELECT * FROM cp_localities WHERE company_id = ? ORDER BY name ASC');
        $rows = $this->db->resultSet([(int)$companyId]);
        return $this->fixCpCatalogNames($rows);
    }

    public function getPickupPlaces($companyId) {
        $this->db->query('SELECT * FROM cp_pickup_places WHERE company_id = ? ORDER BY name ASC');
        $rows = $this->db->resultSet([(int)$companyId]);
        return $this->fixCpCatalogNames($rows);
    }

    /** @param array<int, object> $rows */
    private function fixCpCatalogNames(array $rows) {
        if (!function_exists('fix_utf8_mojibake')) {
            return $rows;
        }
        foreach ($rows as $row) {
            if (isset($row->name)) {
                $row->name = fix_utf8_mojibake($row->name);
            }
        }
        return $rows;
    }

    public function getLocalityAdicionalLevel($companyId, $name) {
        $name = trim((string)$name);
        if ($name === '') {
            return 0;
        }
        $this->db->query('SELECT has_additional FROM cp_localities WHERE company_id = ? AND name = ? LIMIT 1');
        $row = $this->db->single([(int)$companyId, $name]);
        return $row ? (int)$row->has_additional : 0;
    }

    public function localityHasAdditional($companyId, $name) {
        return $this->getLocalityAdicionalLevel($companyId, $name) === 1;
    }

    public function addEntry(array $row) {
        $this->db->query('INSERT INTO cp_task_entries (
            company_id, user_id, task_type_id, activity_date, amount, amount_base,
            is_holiday, holiday_multiplier, status, deceased_code, deceased_name,
            companion_user_id, meta_json
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $meta = isset($row['meta']) ? json_encode($row['meta'], JSON_UNESCAPED_UNICODE) : null;
        $ok = $this->db->execute([
            (int)$row['company_id'],
            (int)$row['user_id'],
            (int)$row['task_type_id'],
            $row['activity_date'],
            $row['amount'],
            $row['amount_base'],
            (int)($row['is_holiday'] ?? 0),
            $row['holiday_multiplier'] ?? 1,
            $row['status'] ?? 'pending',
            $row['deceased_code'] ?? null,
            $row['deceased_name'] ?? null,
            !empty($row['companion_user_id']) ? (int)$row['companion_user_id'] : null,
            $meta,
        ]);
        return $ok ? (int)$this->db->lastInsertId() : false;
    }

    public function getPendingByUser($userId) {
        $this->db->query('SELECT e.*, t.name AS task_name, t.form_key
            FROM cp_task_entries e
            INNER JOIN cp_task_types t ON t.id = e.task_type_id
            WHERE e.user_id = ? AND e.status = ?
            ORDER BY e.activity_date DESC, e.id DESC');
        return $this->db->resultSet([(int)$userId, 'pending']);
    }

    public function getEntryById($id) {
        $this->db->query('SELECT e.*, t.name AS task_name, t.form_key, t.legacy_code
            FROM cp_task_entries e
            INNER JOIN cp_task_types t ON t.id = e.task_type_id
            WHERE e.id = ? LIMIT 1');
        return $this->db->single([(int)$id]);
    }

    public function deletePendingEntry($id, $userId) {
        $this->db->query('DELETE FROM cp_task_entries WHERE id = ? AND user_id = ? AND status = ?');
        return $this->db->execute([(int)$id, (int)$userId, 'pending']);
    }

    public function duplicateExists($userId, $taskTypeId, $deceasedCode) {
        $code = trim((string)$deceasedCode);
        if ($code === '') {
            return false;
        }
        $this->db->query('SELECT id FROM cp_task_entries WHERE user_id = ? AND task_type_id = ? AND deceased_code = ? LIMIT 1');
        return (bool)$this->db->single([(int)$userId, (int)$taskTypeId, $code]);
    }

    public function getPendingForCompany($companyId, $areaId = 0) {
        $sql = 'SELECT e.*, t.name AS task_name, t.legacy_code, t.is_manual_amount, u.full_name AS employee_name, u.area_id
            FROM cp_task_entries e
            INNER JOIN cp_task_types t ON t.id = e.task_type_id
            INNER JOIN users u ON u.id = e.user_id
            WHERE e.company_id = ? AND e.status = ?';
        $params = [(int)$companyId, 'pending'];
        if ($areaId > 0) {
            $sql .= ' AND u.area_id = ?';
            $params[] = (int)$areaId;
        }
        $sql .= ' ORDER BY e.activity_date ASC, u.full_name ASC';
        $this->db->query($sql);
        return $this->db->resultSet($params);
    }

    public function sumPendingByCompany($companyId, $areaId = 0) {
        $sql = 'SELECT COALESCE(SUM(e.amount), 0) AS total
            FROM cp_task_entries e
            INNER JOIN users u ON u.id = e.user_id
            WHERE e.company_id = ? AND e.status = ?';
        $params = [(int)$companyId, 'pending'];
        if ($areaId > 0) {
            $sql .= ' AND u.area_id = ?';
            $params[] = (int)$areaId;
        }
        $this->db->query($sql);
        $row = $this->db->single($params);
        return $row ? (float)$row->total : 0;
    }

    public function getColleagues($companyId, $excludeUserId) {
        $this->db->query('SELECT id, full_name FROM users
            WHERE company_id = ? AND is_active = 1 AND id != ?
            ORDER BY full_name ASC');
        return $this->db->resultSet([(int)$companyId, (int)$excludeUserId]);
    }

    public function getExternalCompanies($companyId) {
        if (!$this->externalTablesReady()) {
            return [];
        }
        $this->db->query('SELECT * FROM cp_external_companies WHERE company_id = ? AND is_active = 1 ORDER BY name ASC');
        return $this->db->resultSet([(int)$companyId]);
    }

    public function externalTablesReady() {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        try {
            $this->db->query("SHOW TABLES LIKE 'cp_external_entries'");
            $ready = (bool)$this->db->single();
        } catch (Throwable $e) {
            $ready = false;
        }
        return $ready;
    }

    public function addExternalEntry(array $row) {
        $this->db->query('INSERT INTO cp_external_entries (
            company_id, user_id, external_company_id, task_label, activity_date,
            amount, amount_base, is_holiday, holiday_multiplier, status, comment
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $ok = $this->db->execute([
            (int)$row['company_id'],
            (int)$row['user_id'],
            (int)$row['external_company_id'],
            $row['task_label'],
            $row['activity_date'],
            $row['amount'],
            $row['amount_base'],
            (int)($row['is_holiday'] ?? 0),
            $row['holiday_multiplier'] ?? 1,
            'pending',
            $row['comment'] ?? null,
        ]);
        return $ok ? (int)$this->db->lastInsertId() : false;
    }

    public function getPendingExternalByUser($userId) {
        if (!$this->externalTablesReady()) {
            return [];
        }
        $this->db->query('SELECT x.*, c.name AS external_company_name
            FROM cp_external_entries x
            INNER JOIN cp_external_companies c ON c.id = x.external_company_id
            WHERE x.user_id = ? AND x.status = ?
            ORDER BY x.activity_date DESC');
        return $this->db->resultSet([(int)$userId, 'pending']);
    }

    public function deletePendingExternalEntry($id, $userId) {
        $this->db->query('DELETE FROM cp_external_entries WHERE id = ? AND user_id = ? AND status = ?');
        return $this->db->execute([(int)$id, (int)$userId, 'pending']);
    }

    public function getPendingExternalForCompany($companyId, $areaId = 0) {
        if (!$this->externalTablesReady()) {
            return [];
        }
        $sql = 'SELECT x.*, c.name AS external_company_name, u.full_name AS employee_name
            FROM cp_external_entries x
            INNER JOIN cp_external_companies c ON c.id = x.external_company_id
            INNER JOIN users u ON u.id = x.user_id
            WHERE x.company_id = ? AND x.status = ?';
        $params = [(int)$companyId, 'pending'];
        if ($areaId > 0) {
            $sql .= ' AND u.area_id = ?';
            $params[] = (int)$areaId;
        }
        $sql .= ' ORDER BY x.activity_date ASC';
        $this->db->query($sql);
        return $this->db->resultSet($params);
    }

    public function sumPendingExternalByCompany($companyId, $areaId = 0) {
        if (!$this->externalTablesReady()) {
            return 0;
        }
        $sql = 'SELECT COALESCE(SUM(x.amount), 0) AS total FROM cp_external_entries x
            INNER JOIN users u ON u.id = x.user_id
            WHERE x.company_id = ? AND x.status = ?';
        $params = [(int)$companyId, 'pending'];
        if ($areaId > 0) {
            $sql .= ' AND u.area_id = ?';
            $params[] = (int)$areaId;
        }
        $this->db->query($sql);
        $row = $this->db->single($params);
        return $row ? (float)$row->total : 0;
    }

    public function getEmployeesWithRates($companyId, $areaId = 0) {
        $rCols = implode(', ', array_map(function ($c) {
            return 'r.' . $c;
        }, self::rateColumnNames()));
        $sql = 'SELECT u.id, u.full_name, u.username, ' . $rCols . '
            FROM users u
            LEFT JOIN cp_employee_rates r ON r.user_id = u.id
            WHERE u.company_id = ? AND u.is_active = 1';
        $params = [(int)$companyId];
        if ($areaId > 0) {
            $sql .= ' AND u.area_id = ?';
            $params[] = (int)$areaId;
        }
        $sql .= ' ORDER BY u.full_name ASC';
        $this->db->query($sql);
        return $this->db->resultSet($params);
    }

    public function closeAllPending($companyId, $closedBy, $areaId = 0) {
        $companyId = (int)$companyId;
        $areaId = (int)$areaId;
        $this->db->beginTransaction();
        try {
            $sql = 'SELECT e.id, e.amount, e.amount_base, t.legacy_code
                FROM cp_task_entries e
                INNER JOIN cp_task_types t ON t.id = e.task_type_id
                INNER JOIN users u ON u.id = e.user_id
                WHERE e.company_id = ? AND e.status = ?';
            $params = [$companyId, 'pending'];
            if ($areaId > 0) {
                $sql .= ' AND u.area_id = ?';
                $params[] = $areaId;
            }
            $sql .= ' FOR UPDATE';
            $this->db->query($sql);
            $entries = $this->db->resultSet($params);

            $externals = [];
            if ($this->externalTablesReady()) {
                $sqlX = 'SELECT x.id, x.amount FROM cp_external_entries x
                    INNER JOIN users u ON u.id = x.user_id
                    WHERE x.company_id = ? AND x.status = ?';
                $paramsX = [$companyId, 'pending'];
                if ($areaId > 0) {
                    $sqlX .= ' AND u.area_id = ?';
                    $paramsX[] = $areaId;
                }
                $sqlX .= ' FOR UPDATE';
                $this->db->query($sqlX);
                $externals = $this->db->resultSet($paramsX);
            }

            $total = 0.0;
            foreach ($entries as $e) {
                $total += (float)$e->amount;
            }
            foreach ($externals as $x) {
                $total += (float)$x->amount;
            }
            if ($total <= 0) {
                throw new RuntimeException('Sin pendientes para cerrar.');
            }

            $lot = $this->nextLotNumber($companyId);
            $amounts = function_exists('cp_compute_closure_amounts')
                ? cp_compute_closure_amounts($total)
                : ['net' => $total, 'rate' => 0.195, 'markup' => round($total * 0.195, 2), 'final' => round($total * 1.195, 2)];
            $ivaRate = $amounts['rate'];
            $ivaAmount = $amounts['markup'];
            $final = $amounts['final'];

            $this->db->query('INSERT INTO cp_task_closures (company_id, lot_number, closed_by, closed_at, total_amount, iva_rate, iva_amount, final_amount)
                VALUES (?,?,?,NOW(),?,?,?,?)');
            if (!$this->db->execute([$companyId, $lot, (int)$closedBy, $total, $ivaRate, $ivaAmount, $final])) {
                throw new RuntimeException('No se pudo crear el cierre.');
            }
            $closureId = (int)$this->db->lastInsertId();

            if (!empty($entries)) {
                $entryIds = array_map(function ($e) {
                    return (int)$e->id;
                }, $entries);
                $ph = implode(',', array_fill(0, count($entryIds), '?'));
                $this->db->query("UPDATE cp_task_entries SET status = ?, closure_id = ? WHERE id IN ($ph) AND status = ?");
                $this->db->execute(array_merge(['closed', $closureId], $entryIds, ['pending']));
            }

            if (!empty($externals)) {
                $extIds = array_map(function ($x) {
                    return (int)$x->id;
                }, $externals);
                $phX = implode(',', array_fill(0, count($extIds), '?'));
                $this->db->query("UPDATE cp_external_entries SET status = ?, closure_id = ? WHERE id IN ($phX) AND status = ?");
                $this->db->execute(array_merge(['closed', $closureId], $extIds, ['pending']));
            }

            $this->db->commit();
            return ['closure_id' => $closureId, 'lot_number' => $lot, 'total' => $total, 'iva' => $ivaAmount, 'final' => $final];
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function addLocality($companyId, $name, $hasAdditional) {
        $this->db->query('INSERT INTO cp_localities (company_id, name, has_additional) VALUES (?,?,?)');
        return $this->db->execute([(int)$companyId, trim($name), (int)$hasAdditional]);
    }

    public function deleteLocality($id, $companyId) {
        $this->db->query('DELETE FROM cp_localities WHERE id = ? AND company_id = ?');
        return $this->db->execute([(int)$id, (int)$companyId]);
    }

    public function addPickupPlace($companyId, $name) {
        $this->db->query('INSERT INTO cp_pickup_places (company_id, name) VALUES (?,?)');
        return $this->db->execute([(int)$companyId, trim($name)]);
    }

    public function deletePickupPlace($id, $companyId) {
        $this->db->query('DELETE FROM cp_pickup_places WHERE id = ? AND company_id = ?');
        return $this->db->execute([(int)$id, (int)$companyId]);
    }

    public function addExternalCompany($companyId, $name) {
        $this->db->query('INSERT INTO cp_external_companies (company_id, name) VALUES (?,?)');
        return $this->db->execute([(int)$companyId, trim($name)]);
    }

    public function deleteExternalCompany($id, $companyId) {
        $this->db->query('UPDATE cp_external_companies SET is_active = 0 WHERE id = ? AND company_id = ?');
        return $this->db->execute([(int)$id, (int)$companyId]);
    }

    private function nextLotNumber($companyId) {
        $this->db->query('SELECT COALESCE(MAX(lot_number), 0) + 1 AS n FROM cp_task_closures WHERE company_id = ?');
        $row = $this->db->single([(int)$companyId]);
        return $row ? (int)$row->n : 1;
    }

    public function getClosures($companyId, $limit = 50) {
        $this->db->query('SELECT c.*, u.full_name AS closed_by_name
            FROM cp_task_closures c
            LEFT JOIN users u ON u.id = c.closed_by
            WHERE c.company_id = ?
            ORDER BY c.closed_at DESC
            LIMIT ' . (int)$limit);
        return $this->db->resultSet([(int)$companyId]);
    }

    public function getClosureById($closureId, $companyId) {
        $this->db->query('SELECT c.*, u.full_name AS closed_by_name
            FROM cp_task_closures c
            LEFT JOIN users u ON u.id = c.closed_by
            WHERE c.id = ? AND c.company_id = ? LIMIT 1');
        return $this->db->single([(int)$closureId, (int)$companyId]);
    }

    public static function manualLegacyCodes() {
        return [3, 6];
    }

    public function getEntryForCompany($entryId, $companyId) {
        $this->db->query('SELECT e.*, t.legacy_code, t.is_manual_amount
            FROM cp_task_entries e
            INNER JOIN cp_task_types t ON t.id = e.task_type_id
            WHERE e.id = ? AND e.company_id = ? LIMIT 1');
        return $this->db->single([(int)$entryId, (int)$companyId]);
    }

    public function updatePendingEntryAmount($entryId, $companyId, $amount, $amountBase = null) {
        $base = $amountBase !== null ? $amountBase : $amount;
        $this->db->query('UPDATE cp_task_entries SET amount = ?, amount_base = ?
            WHERE id = ? AND company_id = ? AND status = ?');
        return $this->db->execute([(float)$amount, (float)$base, (int)$entryId, (int)$companyId, 'pending']);
    }

    public function getExternalEntryForCompany($entryId, $companyId) {
        if (!$this->externalTablesReady()) {
            return null;
        }
        $this->db->query('SELECT * FROM cp_external_entries WHERE id = ? AND company_id = ? LIMIT 1');
        return $this->db->single([(int)$entryId, (int)$companyId]);
    }

    public function updatePendingExternalAmount($entryId, $companyId, $amount) {
        $this->db->query('UPDATE cp_external_entries SET amount = ?, amount_base = ?
            WHERE id = ? AND company_id = ? AND status = ?');
        return $this->db->execute([(float)$amount, (float)$amount, (int)$entryId, (int)$companyId, 'pending']);
    }

    /** Vista previa aumento % sobre pendientes (excluye tareas manuales legacy 3 y 6). */
    public function previewPendingIncrease($companyId, $multiplier) {
        $multiplier = (float)$multiplier;
        if ($multiplier <= 0) {
            $multiplier = 1;
        }
        $manual = implode(',', array_map('intval', self::manualLegacyCodes()));
        $this->db->query("SELECT
            COALESCE(SUM(e.amount), 0) AS total_before,
            COALESCE(SUM(CASE WHEN t.legacy_code IN ($manual) THEN e.amount ELSE e.amount * ? END), 0) AS total_after
            FROM cp_task_entries e
            INNER JOIN cp_task_types t ON t.id = e.task_type_id
            WHERE e.company_id = ? AND e.status = ?");
        $row = $this->db->single([$multiplier, (int)$companyId, 'pending']);
        $extBefore = $this->sumPendingExternalByCompany($companyId, 0);
        $extAfter = round($extBefore * $multiplier, 2);
        return [
            'entries_before' => $row ? (float)$row->total_before : 0,
            'entries_after' => $row ? (float)$row->total_after : 0,
            'external_before' => $extBefore,
            'external_after' => $extAfter,
            'total_before' => ($row ? (float)$row->total_before : 0) + $extBefore,
            'total_after' => ($row ? (float)$row->total_after : 0) + $extAfter,
            'multiplier' => $multiplier,
        ];
    }

    public function applyRateIncreaseToCompany($companyId, $multiplier) {
        $multiplier = (float)$multiplier;
        if ($multiplier <= 0) {
            return false;
        }
        $sets = [];
        foreach (self::rateColumnNames() as $col) {
            $sets[] = $col . ' = ' . $col . ' * ?';
        }
        $params = array_fill(0, count(self::rateColumnNames()), $multiplier);
        $params[] = (int)$companyId;
        $sql = 'UPDATE cp_employee_rates r
            INNER JOIN users u ON u.id = r.user_id
            SET ' . implode(', ', $sets) . '
            WHERE u.company_id = ?';
        $this->db->query($sql);
        return $this->db->execute($params);
    }

    /**
     * Cierre con aumento % en importes pendientes (como legacy aumentobd).
     * Tareas manuales (parcelas, mantenimiento) no se multiplican.
     */
    public function closeAllPendingWithIncrease($companyId, $closedBy, $multiplier, $areaId = 0) {
        $multiplier = (float)$multiplier;
        if ($multiplier <= 0) {
            $multiplier = 1;
        }
        $preview = $this->previewPendingIncrease($companyId, $multiplier);
        if ($preview['total_before'] <= 0) {
            return false;
        }

        $companyId = (int)$companyId;
        $areaId = (int)$areaId;
        $manual = self::manualLegacyCodes();

        $this->db->beginTransaction();
        try {
            $sql = 'SELECT e.id, e.amount, e.amount_base, t.legacy_code
                FROM cp_task_entries e
                INNER JOIN cp_task_types t ON t.id = e.task_type_id
                INNER JOIN users u ON u.id = e.user_id
                WHERE e.company_id = ? AND e.status = ?';
            $params = [$companyId, 'pending'];
            if ($areaId > 0) {
                $sql .= ' AND u.area_id = ?';
                $params[] = $areaId;
            }
            $sql .= ' FOR UPDATE';
            $this->db->query($sql);
            $entries = $this->db->resultSet($params);

            $externals = [];
            if ($this->externalTablesReady()) {
                $sqlX = 'SELECT x.id, x.amount FROM cp_external_entries x
                    INNER JOIN users u ON u.id = x.user_id
                    WHERE x.company_id = ? AND x.status = ?';
                $paramsX = [$companyId, 'pending'];
                if ($areaId > 0) {
                    $sqlX .= ' AND u.area_id = ?';
                    $paramsX[] = $areaId;
                }
                $sqlX .= ' FOR UPDATE';
                $this->db->query($sqlX);
                $externals = $this->db->resultSet($paramsX);
            }

            if (empty($entries) && empty($externals)) {
                throw new RuntimeException('Sin pendientes.');
            }

            $this->applyRateIncreaseToCompany($companyId, $multiplier);

            foreach ($entries as $e) {
                if (!in_array((int)$e->legacy_code, $manual, true)) {
                    $newAmount = round((float)$e->amount * $multiplier, 2);
                    $newBase = round((float)$e->amount_base * $multiplier, 2);
                    $this->updatePendingEntryAmount((int)$e->id, $companyId, $newAmount, $newBase);
                    $e->amount = $newAmount;
                }
            }
            foreach ($externals as $x) {
                $newAmount = round((float)$x->amount * $multiplier, 2);
                $this->updatePendingExternalAmount((int)$x->id, $companyId, $newAmount);
                $x->amount = $newAmount;
            }

            $total = 0.0;
            foreach ($entries as $e) {
                $total += (float)$e->amount;
            }
            foreach ($externals as $x) {
                $total += (float)$x->amount;
            }
            if ($total <= 0) {
                throw new RuntimeException('Total inválido tras aumento.');
            }

            $lot = $this->nextLotNumber($companyId);
            $amounts = function_exists('cp_compute_closure_amounts')
                ? cp_compute_closure_amounts($total)
                : ['net' => $total, 'rate' => 0.195, 'markup' => round($total * 0.195, 2), 'final' => round($total * 1.195, 2)];

            $this->db->query('INSERT INTO cp_task_closures (company_id, lot_number, closed_by, closed_at, total_amount, iva_rate, iva_amount, final_amount)
                VALUES (?,?,?,NOW(),?,?,?,?)');
            if (!$this->db->execute([$companyId, $lot, (int)$closedBy, $total, $amounts['rate'], $amounts['markup'], $amounts['final']])) {
                throw new RuntimeException('No se pudo crear el cierre.');
            }
            $closureId = (int)$this->db->lastInsertId();

            if (!empty($entries)) {
                $entryIds = array_map(function ($e) {
                    return (int)$e->id;
                }, $entries);
                $ph = implode(',', array_fill(0, count($entryIds), '?'));
                $this->db->query("UPDATE cp_task_entries SET status = ?, closure_id = ? WHERE id IN ($ph) AND status = ?");
                $this->db->execute(array_merge(['closed', $closureId], $entryIds, ['pending']));
            }
            if (!empty($externals)) {
                $extIds = array_map(function ($x) {
                    return (int)$x->id;
                }, $externals);
                $phX = implode(',', array_fill(0, count($extIds), '?'));
                $this->db->query("UPDATE cp_external_entries SET status = ?, closure_id = ? WHERE id IN ($phX) AND status = ?");
                $this->db->execute(array_merge(['closed', $closureId], $extIds, ['pending']));
            }

            $this->db->commit();
            return ['closure_id' => $closureId, 'lot_number' => $lot, 'total' => $total, 'iva' => $amounts['markup'], 'final' => $amounts['final']];
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getEntriesByClosure($closureId, $companyId) {
        $this->db->query('SELECT e.*, t.name AS task_name, u.full_name AS employee_name
            FROM cp_task_entries e
            INNER JOIN cp_task_types t ON t.id = e.task_type_id
            INNER JOIN users u ON u.id = e.user_id
            WHERE e.closure_id = ? AND e.company_id = ?
            ORDER BY u.full_name, e.activity_date');
        return $this->db->resultSet([(int)$closureId, (int)$companyId]);
    }

    public function getExternalByClosure($closureId, $companyId) {
        if (!$this->externalTablesReady()) {
            return [];
        }
        $this->db->query('SELECT x.*, c.name AS external_company_name, u.full_name AS employee_name
            FROM cp_external_entries x
            INNER JOIN cp_external_companies c ON c.id = x.external_company_id
            INNER JOIN users u ON u.id = x.user_id
            WHERE x.closure_id = ? AND x.company_id = ?
            ORDER BY u.full_name');
        return $this->db->resultSet([(int)$closureId, (int)$companyId]);
    }

    /** Totales por empleado de un cierre (tareas + externas). */
    public function getClosureTotalsByEmployee($closureId, $companyId) {
        $cid = (int)$companyId;
        $clid = (int)$closureId;
        $byUser = [];

        $this->db->query('SELECT u.id AS user_id, u.full_name, COALESCE(SUM(e.amount), 0) AS net
            FROM cp_task_entries e
            INNER JOIN users u ON u.id = e.user_id
            WHERE e.closure_id = ? AND e.company_id = ?
            GROUP BY u.id, u.full_name');
        foreach ($this->db->resultSet([$clid, $cid]) as $row) {
            $uid = (int)$row->user_id;
            $byUser[$uid] = ['user_id' => $uid, 'full_name' => $row->full_name, 'net' => (float)$row->net];
        }

        if ($this->externalTablesReady()) {
            $this->db->query('SELECT u.id AS user_id, u.full_name, COALESCE(SUM(x.amount), 0) AS net
                FROM cp_external_entries x
                INNER JOIN users u ON u.id = x.user_id
                WHERE x.closure_id = ? AND x.company_id = ?
                GROUP BY u.id, u.full_name');
            foreach ($this->db->resultSet([$clid, $cid]) as $row) {
                $uid = (int)$row->user_id;
                if (!isset($byUser[$uid])) {
                    $byUser[$uid] = ['user_id' => $uid, 'full_name' => $row->full_name, 'net' => 0];
                }
                $byUser[$uid]['net'] += (float)$row->net;
            }
        }

        $rows = [];
        foreach ($byUser as $u) {
            $u['net'] = round($u['net'], 2);
            $calc = function_exists('cp_compute_closure_amounts')
                ? cp_compute_closure_amounts($u['net'])
                : ['markup' => round($u['net'] * 0.195, 2), 'final' => round($u['net'] * 1.195, 2)];
            $rows[] = (object)[
                'user_id' => $u['user_id'],
                'full_name' => $u['full_name'],
                'net' => $u['net'],
                'markup' => $calc['markup'],
                'final' => $calc['final'],
            ];
        }
        usort($rows, function ($a, $b) {
            return strcmp($a->full_name, $b->full_name);
        });
        return $rows;
    }

    public function getEmployeeReport($companyId, $dateFrom, $dateTo) {
        $cid = (int)$companyId;
        if ($this->externalTablesReady()) {
            $this->db->query('SELECT u.id, u.full_name,
                (SELECT COALESCE(SUM(amount), 0) FROM cp_task_entries WHERE user_id = u.id AND company_id = ? AND activity_date BETWEEN ? AND ?)
                + (SELECT COALESCE(SUM(amount), 0) FROM cp_external_entries WHERE user_id = u.id AND company_id = ? AND activity_date BETWEEN ? AND ?) AS total_amount
                FROM users u WHERE u.company_id = ? AND u.is_active = 1
                HAVING total_amount > 0 ORDER BY total_amount DESC');
            return $this->db->resultSet([$cid, $dateFrom, $dateTo, $cid, $dateFrom, $dateTo, $cid]);
        }
        $this->db->query('SELECT u.id, u.full_name,
            (SELECT COALESCE(SUM(amount), 0) FROM cp_task_entries WHERE user_id = u.id AND company_id = ? AND activity_date BETWEEN ? AND ?) AS total_amount
            FROM users u WHERE u.company_id = ? AND u.is_active = 1
            HAVING total_amount > 0 ORDER BY total_amount DESC');
        return $this->db->resultSet([$cid, $dateFrom, $dateTo, $cid]);
    }

    public function getPendingExportRows($companyId, $areaId = 0) {
        $rows = [];
        foreach ($this->getPendingForCompany($companyId, $areaId) as $e) {
            $rows[] = (object)[
                'kind' => 'tarea',
                'activity_date' => $e->activity_date,
                'employee_name' => $e->employee_name,
                'task_name' => $e->task_name,
                'detail' => $e->deceased_name ?: $e->deceased_code,
                'amount' => $e->amount,
            ];
        }
        foreach ($this->getPendingExternalForCompany($companyId, $areaId) as $x) {
            $rows[] = (object)[
                'kind' => 'externa',
                'activity_date' => $x->activity_date,
                'employee_name' => $x->employee_name,
                'task_name' => $x->task_label,
                'detail' => $x->external_company_name,
                'amount' => $x->amount,
            ];
        }
        return $rows;
    }

    /** Entradas CP por fecha para calendario (tareas + externas). */
    public function getCalendarEntriesMap($userId, $companyId, $startDate, $endDate) {
        if (!cp_tasks_is_ready()) {
            return [];
        }
        $uid = (int)$userId;
        $cid = (int)$companyId;
        $map = [];

        $this->db->query('SELECT e.activity_date, e.amount, e.status, e.deceased_name, e.deceased_code,
                t.name AS task_name, t.form_key
            FROM cp_task_entries e
            INNER JOIN cp_task_types t ON t.id = e.task_type_id
            WHERE e.user_id = ? AND e.company_id = ? AND e.activity_date BETWEEN ? AND ?
            ORDER BY e.activity_date ASC');
        foreach ($this->db->resultSet([$uid, $cid, $startDate, $endDate]) as $row) {
            $d = $row->activity_date;
            if (!isset($map[$d])) {
                $map[$d] = [];
            }
            $row->entry_kind = 'task';
            $map[$d][] = $row;
        }

        if ($this->externalTablesReady()) {
            $this->db->query('SELECT x.activity_date, x.amount, x.status, x.task_label AS task_name,
                    c.name AS external_company_name
                FROM cp_external_entries x
                INNER JOIN cp_external_companies c ON c.id = x.external_company_id
                WHERE x.user_id = ? AND x.company_id = ? AND x.activity_date BETWEEN ? AND ?
                ORDER BY x.activity_date ASC');
            foreach ($this->db->resultSet([$uid, $cid, $startDate, $endDate]) as $row) {
                $d = $row->activity_date;
                if (!isset($map[$d])) {
                    $map[$d] = [];
                }
                $row->entry_kind = 'external';
                $row->form_key = 'externas';
                $map[$d][] = $row;
            }
        }

        return $map;
    }
}
