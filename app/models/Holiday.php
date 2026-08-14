<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/models/Holiday.php (NUEVO ARCHIVO)
// ----------------------------------------------------------------------

class Holiday {
    private $db;

    public function __construct($db = null){
        $this->db = $db instanceof Database ? $db : new Database;
    }

    public function getHolidaysByCompany($companyId) {
        $this->db->query("SELECT * FROM holidays WHERE company_id = :company_id ORDER BY holiday_date DESC");
        $this->db->bind(':company_id', $companyId);
        return $this->db->resultSet();
    }

    public function getHolidaysForPeriod($companyId, $startDate, $endDate) {
        $this->db->query("SELECT * FROM holidays WHERE company_id = :company_id AND holiday_date BETWEEN :start_date AND :end_date");
        $this->db->bind(':company_id', $companyId);
        $this->db->bind(':start_date', $startDate);
        $this->db->bind(':end_date', $endDate);
        $holidays = $this->db->resultSet();
        $byDate = [];
        foreach ($holidays as $holiday) {
            $byDate[$holiday->holiday_date] = $holiday;
        }
        foreach ($this->getScopedHolidaysForPeriod((int)$companyId, $startDate, $endDate) as $holiday) {
            if (!isset($byDate[$holiday->holiday_date])) {
                $byDate[$holiday->holiday_date] = $holiday;
            }
        }
        ksort($byDate);
        return array_values($byDate);
    }

    public function createHoliday($data) {
        $this->db->query("INSERT INTO holidays (holiday_date, name, company_id) VALUES (:holiday_date, :name, :company_id)");
        $this->db->bind(':holiday_date', $data['holiday_date']);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':company_id', $data['company_id']);
        return $this->db->execute();
    }

    public function deleteHoliday($id) {
        $this->db->query('DELETE FROM holidays WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function deleteHolidayForCompany($id, $companyId) {
        $this->db->query('DELETE FROM holidays WHERE id = ? AND company_id = ?');
        return $this->db->execute([(int)$id, (int)$companyId]);
    }

    public function isHolidayForCompany($companyId, $date) {
        $companyId = (int)$companyId;
        $date = trim((string)$date);
        if ($companyId <= 0 || $date === '') {
            return false;
        }
        return !empty($this->getHolidaysForPeriod($companyId, $date, $date));
    }

    private function scopedRulesReady() {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        try {
            $this->db->query("SHOW TABLES LIKE 'holiday_rules'");
            $ready = (bool)$this->db->single();
        } catch (Throwable $e) {
            $ready = false;
        }
        return $ready;
    }

    private function normalizePlace($value) {
        $value = trim((string)$value);
        if (function_exists('iconv')) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($ascii !== false) {
                $value = $ascii;
            }
        }
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function getScopedHolidaysForPeriod($companyId, $startDate, $endDate) {
        if ($companyId <= 0 || !$this->scopedRulesReady()) {
            return [];
        }
        $location = (new Company())->getLocation($companyId);
        if (!$location) {
            return [];
        }
        $this->db->query("SELECT id, name, month_day, scope_type, province, locality
            FROM holiday_rules WHERE is_active = 1");
        $rules = $this->db->resultSet();
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        if ($end < $start) {
            return [];
        }
        $companyProvince = $this->normalizePlace($location->province ?? '');
        $companyLocality = $this->normalizePlace($location->locality ?? '');
        $result = [];
        for ($year = (int)$start->format('Y'); $year <= (int)$end->format('Y'); $year++) {
            foreach ($rules as $rule) {
                if (!$this->ruleAppliesToLocation($rule, $companyProvince, $companyLocality)) {
                    continue;
                }
                $date = $year . '-' . $rule->month_day;
                $holidayDate = DateTime::createFromFormat('!Y-m-d', $date);
                if (!$holidayDate || $holidayDate->format('Y-m-d') !== $date || $holidayDate < $start || $holidayDate > $end) {
                    continue;
                }
                $result[] = (object)[
                    'id' => 'rule-' . $rule->id . '-' . $year,
                    'holiday_date' => $date,
                    'name' => $rule->name,
                    'company_id' => $companyId,
                    'source' => 'scoped_rule',
                ];
            }
        }
        return $result;
    }

    private function ruleAppliesToLocation($rule, $companyProvince, $companyLocality) {
        if ($rule->scope_type === 'national') {
            return true;
        }
        $ruleProvince = $this->normalizePlace($rule->province ?? '');
        if ($rule->scope_type === 'province') {
            return $ruleProvince !== '' && $ruleProvince === $companyProvince;
        }
        return $rule->scope_type === 'locality'
            && $ruleProvince !== ''
            && $ruleProvince === $companyProvince
            && $this->normalizePlace($rule->locality ?? '') === $companyLocality;
    }
}
?>
