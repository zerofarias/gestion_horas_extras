<?php

class VacationAdminController {
    private $agreementModel;
    private $entitlement;
    private $balanceModel;
    private $userModel;
    private $companyModel;

    public function __construct() {
        if (!hasRole('admin')) {
            redirect('login');
        }
        ensureAdminCompanySession();
        $this->agreementModel = new CollectiveAgreement();
        $this->entitlement = new VacationEntitlementService();
        $this->balanceModel = new VacationBalance();
        $this->userModel = new User();
        $this->companyModel = new Company();
    }

    public function agreements() {
        if (!$this->agreementModel->isReady()) {
            $_SESSION['flash_error'] = 'Ejecutá migration_collective_agreements.sql (ver MIGRATIONS.md #22).';
            redirect('admin/dashboard');
        }
        $agreements = $this->agreementModel->getAll(false);
        foreach ($agreements as $ag) {
            $ag->rules = $this->agreementModel->getRules((int)$ag->id);
        }
        $companyId = requireAdminCompany('admin/dashboard');
        $companies = $this->companyModel->getAllCompanies();
        $defaults = [];
        foreach ($companies as $co) {
            $def = $this->agreementModel->getDefaultForCompany((int)$co->id);
            $defaults[(int)$co->id] = $def;
        }
        $this->view('admin/vacation/agreements', [
            'agreements' => $agreements,
            'companies' => $companies,
            'defaults' => $defaults,
            'company_id' => $companyId,
        ]);
    }

    public function saveCompanyDefault() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('vacationAdmin/agreements');
        }
        csrf_verify();
        requireAdminCompany('vacationAdmin/agreements');
        $companyId = (int)($_POST['company_id'] ?? 0);
        $agreementId = (int)($_POST['agreement_id'] ?? 0);
        $activeCompany = adminCompanyId();
        if ($companyId > 0 && $companyId !== $activeCompany) {
            $_SESSION['flash_error'] = 'Solo podés configurar la empresa activa en sesión.';
            redirect('vacationAdmin/agreements');
        }
        if ($companyId > 0 && $agreementId > 0) {
            $this->agreementModel->setDefaultForCompany($companyId, $agreementId);
            $_SESSION['flash_success'] = 'Convenio por defecto guardado.';
        }
        redirect('vacationAdmin/agreements');
    }

    public function editAgreement($id = 0) {
        $id = (int)$id;
        $agreement = $id > 0 ? $this->agreementModel->getById($id) : null;
        $rules = $id > 0 ? $this->agreementModel->getRules($id) : [];
        $this->view('admin/vacation/edit_agreement', [
            'agreement' => $agreement,
            'rules' => $rules,
            'day_count_modes' => vacation_day_count_modes(),
            'is_new' => $id <= 0,
        ]);
    }

    public function saveAgreement() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('vacationAdmin/agreements');
        }
        csrf_verify();
        $id = (int)($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        if ($code === '' || $name === '') {
            $_SESSION['flash_error'] = 'Código y nombre del convenio son obligatorios.';
            redirect('vacationAdmin/editAgreement/' . ($id > 0 ? $id : ''));
        }
        $ok = $this->agreementModel->saveAgreement([
            'id' => $id > 0 ? $id : null,
            'code' => $code,
            'name' => $name,
            'description' => trim($_POST['description'] ?? ''),
            'period_start_month' => (int)($_POST['period_start_month'] ?? 10),
            'period_start_day' => (int)($_POST['period_start_day'] ?? 1),
            'is_active' => !empty($_POST['is_active']),
        ]);
        if (!$ok) {
            $_SESSION['flash_error'] = 'No se pudo guardar el convenio (¿código duplicado?).';
            redirect('vacationAdmin/agreements');
        }
        if ($id <= 0) {
            $id = (int)$this->agreementModel->lastInsertId();
        }
        $_SESSION['flash_success'] = 'Convenio guardado. Agregá las reglas de antigüedad abajo.';
        redirect('vacationAdmin/editAgreement/' . $id);
    }

    public function saveAgreementRule($agreementId) {
        $agreementId = (int)$agreementId;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $agreementId <= 0) {
            redirect('vacationAdmin/agreements');
        }
        csrf_verify();
        $maxMonths = trim($_POST['max_months'] ?? '');
        $this->agreementModel->insertRule([
            'agreement_id' => $agreementId,
            'min_months' => (int)($_POST['min_months'] ?? 0),
            'max_months' => $maxMonths === '' ? null : (int)$maxMonths,
            'days_entitled' => (int)($_POST['days_entitled'] ?? 0),
            'day_count_mode' => $_POST['day_count_mode'] ?? 'weekdays',
            'allows_split' => true,
            'allows_carryover' => !empty($_POST['allows_carryover']),
            'notes' => trim($_POST['notes'] ?? ''),
        ]);
        $_SESSION['flash_success'] = 'Regla agregada.';
        redirect('vacationAdmin/editAgreement/' . $agreementId);
    }

    public function vacationSetup($userId) {
        $userId = (int)$userId;
        $user = adminResolveUser($userId);
        if (!$this->balanceModel->isReady()) {
            $_SESSION['flash_error'] = 'Ejecutá migration_collective_agreements.sql.';
            redirect('admin/employeeProfile/' . $userId);
        }
        $summary = $this->entitlement->getSummaryForUser($userId);
        $agreements = $this->agreementModel->getAll();
        $bounds = $this->entitlement->getPeriodBoundsForDate($userId);
        $suggestedLabel = $bounds['period_label'] ?? date('Y') . '-' . (date('Y') + 1);
        $this->view('admin/vacation/setup', [
            'user' => $user,
            'summary' => $summary,
            'agreements' => $agreements,
            'movements' => $this->balanceModel->getMovementsByUser($userId, 30),
            'suggested_period' => $suggestedLabel,
        ]);
    }

    public function calculateVacationPreview($userId) {
        $userId = (int)$userId;
        adminResolveUser($userId);
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'message' => 'Método no permitido.']);
            exit;
        }
        csrf_verify();
        $periodLabel = trim($_POST['period_label'] ?? '');
        if ($periodLabel === '' && !empty($_POST['use_first_period'])) {
            $periods = $_POST['periods'] ?? [];
            if (is_array($periods) && isset($periods[0]['period_label'])) {
                $periodLabel = trim($periods[0]['period_label']);
            }
        }
        $result = $this->entitlement->calculatePreview($userId, [
            'hire_date' => trim($_POST['hire_date'] ?? ''),
            'agreement_id' => (int)($_POST['agreement_id'] ?? 0),
            'as_of_date' => trim($_POST['as_of_date'] ?? ''),
            'period_label' => $periodLabel,
        ]);
        echo json_encode($result);
        exit;
    }

    public function saveVacationSetup($userId) {
        $userId = (int)$userId;
        adminResolveUser($userId);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('vacationAdmin/vacationSetup/' . $userId);
        }
        csrf_verify();

        $user = $this->userModel->getUserById($userId);
        $hireDate = trim($_POST['hire_date'] ?? '');
        if ($hireDate === '' && !empty($user->hire_date)) {
            $hireDate = $user->hire_date;
        }
        $probation = trim($_POST['probation_start_date'] ?? '');
        if ($probation === '' && !empty($user->probation_start_date)) {
            $probation = $user->probation_start_date;
        }
        $agreementId = (int)($_POST['agreement_id'] ?? 0);
        $probVal = ($probation !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $probation)) ? $probation : null;
        $hireVal = ($hireDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hireDate)) ? $hireDate : null;
        if ($hireVal || $probVal || $agreementId > 0) {
            $this->userModel->updateVacationProfile(
                $userId,
                $hireVal,
                $agreementId > 0 ? $agreementId : null,
                $probVal
            );
        }

        $adminId = (int)$_SESSION['user_id'];
        $periods = $_POST['periods'] ?? [];
        if (is_array($periods)) {
            foreach ($periods as $row) {
                $label = trim($row['period_label'] ?? '');
                if ($label === '') {
                    continue;
                }
                $entitled = (float)($row['days_entitled'] ?? 0);
                $taken = (float)($row['days_taken'] ?? 0);
                $this->entitlement->importPeriodBalance($userId, $label, $entitled, $taken, $adminId, 'Carga inicial RRHH');
            }
        }

        if (!empty($_POST['liquidate_current'])) {
            $result = $this->entitlement->liquidatePeriod($userId, null, $adminId);
            $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
        } else {
            $_SESSION['flash_success'] = 'Datos de vacaciones guardados.';
        }
        redirect('vacationAdmin/vacationSetup/' . $userId);
    }

    public function liquidateCompanyBatch() {
        if (!$this->balanceModel->isReady()) {
            $_SESSION['flash_error'] = 'Ejecutá migration_collective_agreements.sql.';
            redirect('vacationAdmin/agreements');
        }

        $companyId = requireAdminCompany('admin/dashboard');
        $companyName = $this->companyModel->getNameById($companyId);
        $bounds = null;
        $employees = $this->userModel->getActiveEmployeesForVacationLiquidation($companyId);
        if (!empty($employees)) {
            $first = $employees[0];
            $bounds = $this->entitlement->getPeriodBoundsForDate((int)$first->id);
        }
        $suggestedPeriod = $bounds['period_label'] ?? (date('Y') . '-' . (date('Y') + 1));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $periodLabel = trim($_POST['period_label'] ?? '');
            $result = $this->entitlement->liquidateCompanyBatch(
                $companyId,
                $periodLabel !== '' ? $periodLabel : null,
                (int)$_SESSION['user_id']
            );
            $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
            $_SESSION['vacation_batch_report'] = $result;
            redirect('vacationAdmin/liquidateCompanyBatch?company_id=' . $companyId);
        }

        $report = $_SESSION['vacation_batch_report'] ?? null;
        unset($_SESSION['vacation_batch_report']);

        $this->view('admin/vacation/liquidate_batch', [
            'company_id' => $companyId,
            'company_name' => $companyName,
            'suggested_period' => $suggestedPeriod,
            'preview' => $this->entitlement->getBatchLiquidationPreview($companyId),
            'report' => $report,
        ]);
    }

    public function liquidateUser($userId) {
        $userId = (int)$userId;
        adminResolveUser($userId);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/employeeProfile/' . $userId);
        }
        csrf_verify();
        $periodLabel = trim($_POST['period_label'] ?? '');
        $result = $this->entitlement->liquidatePeriod(
            $userId,
            $periodLabel !== '' ? $periodLabel : null,
            (int)$_SESSION['user_id']
        );
        $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
        redirect('admin/employeeProfile/' . $userId . '#tab-vacation');
    }

    public function reports() {
        $companyId = requireAdminCompany('admin/dashboard');
        $alerts = $this->entitlement->getCompanyVacationAlerts($companyId);
        $employees = $this->userModel->getActiveEmployeesForVacationLiquidation($companyId);
        $balances = [];
        foreach ($employees as $emp) {
            $balances[] = (object)[
                'user' => $emp,
                'pending' => $this->balanceModel->getTotalPending((int)$emp->id),
                'agreement' => $this->entitlement->getEffectiveAgreement($emp),
            ];
        }
        $this->view('admin/vacation/reports', [
            'company_id' => $companyId,
            'alerts' => $alerts,
            'balances' => $balances,
            'october_reminder' => ((int)date('n') === 10),
        ]);
    }

    public function exportVacationBalancesCsv() {
        $companyId = requireAdminCompany('vacationAdmin/reports');
        if (!$this->entitlement->isReady()) {
            $_SESSION['flash_error'] = 'Módulo de vacaciones no disponible.';
            redirect('vacationAdmin/reports');
        }
        $employees = $this->userModel->getActiveEmployeesForVacationLiquidation($companyId);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="vacaciones_saldos_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Empleado', 'Ingreso', 'Convenio', 'Días pendientes'], ';');
        foreach ($employees as $emp) {
            $ag = $this->entitlement->getEffectiveAgreement($emp);
            fputcsv($out, [
                $emp->full_name,
                $emp->hire_date ?? '',
                $ag ? $ag->name : '',
                number_format($this->balanceModel->getTotalPending((int)$emp->id), 2, '.', ''),
            ], ';');
        }
        fclose($out);
        exit();
    }

    private function view($view, $data = []) {
        if (file_exists(APPROOT . '/views/' . $view . '.php')) {
            require APPROOT . '/views/' . $view . '.php';
        } else {
            die('Vista no encontrada: ' . $view);
        }
    }
}
