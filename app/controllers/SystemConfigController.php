<?php

class SystemConfigController {
    private $settings;
    private $mailSettings;
    private $mailService;
    private $userModel;

    public function __construct() {
        if (!isAdmin()) {
            redirect('login');
        }
        $this->settings = SystemSettingsService::instance();
        $this->userModel = new User();
        if (notifications_is_ready()) {
            $this->mailSettings = new MailSettings();
            $this->mailService = new MailService();
        }
    }

    public function index() {
        if (!system_settings_ready()) {
            $_SESSION['flash_error'] = 'Ejecutá migration_system_settings.sql en MySQL (ver MIGRATIONS.md #32).';
            redirect('admin/dashboard');
        }
        if (!system_config_unlocked()) {
            redirect('systemConfig/unlock');
        }
        $tab = trim($_GET['tab'] ?? 'general');
        $allowed = ['general', 'mail', 'casapav', 'attendance', 'integrations', 'overtime', 'employee', 'salary_advance', 'security'];
        if (!in_array($tab, $allowed, true)) {
            $tab = 'general';
        }
        $admin = $this->userModel->getUserById((int)$_SESSION['user_id']);
        $mailRow = ($this->mailSettings && notifications_is_ready()) ? $this->mailSettings->get() : null;
        $this->view('admin/system_config/index', [
            'tab' => $tab,
            'groups' => $this->settings->getByGroup($tab),
            'all_general' => $this->flattenGroup('general'),
            'all_casapav' => $this->flattenGroup('casapav'),
            'all_attendance' => $this->flattenGroup('attendance'),
            'all_integrations' => $this->flattenGroup('integrations'),
            'mail_settings' => $mailRow,
            'mail_available' => $this->mailService ? $this->mailService->isAvailable() : false,
            'default_test_email' => $admin && !empty($admin->email) ? trim($admin->email) : '',
            'values' => $this->currentValues(),
        ]);
    }

    public function unlock() {
        if (!system_settings_ready()) {
            $_SESSION['flash_error'] = 'Ejecutá migration_system_settings.sql en MySQL (ver MIGRATIONS.md #32).';
            redirect('admin/dashboard');
        }
        if (system_config_unlocked()) {
            redirect('systemConfig');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $pin = trim($_POST['config_pin'] ?? '');
            if ($pin === '') {
                $_SESSION['flash_error'] = 'Ingresá la clave de configuración.';
            } elseif ($this->settings->verifyConfigPin($pin)) {
                system_config_unlock(30);
                $_SESSION['flash_success'] = 'Configuración desbloqueada por 30 minutos.';
                redirect('systemConfig');
            } else {
                $_SESSION['flash_error'] = 'Clave incorrecta.';
            }
        }
        $this->view('admin/system_config/unlock');
    }

    public function lock() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('systemConfig');
        }
        csrf_verify();
        system_config_lock();
        $_SESSION['flash_success'] = 'Configuración bloqueada.';
        redirect('admin/dashboard');
    }

    public function save() {
        require_system_config_unlock();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('systemConfig');
        }
        csrf_verify();
        $group = trim($_POST['group'] ?? '');
        $userId = (int)$_SESSION['user_id'];
        $tab = $group;

        switch ($group) {
            case 'general':
                $this->saveGeneral($_POST, $userId);
                break;
            case 'mail':
                $this->saveMail($_POST);
                $tab = 'mail';
                break;
            case 'casapav':
                $this->saveCasapav($_POST, $userId);
                break;
            case 'attendance':
                $this->saveAttendance($_POST, $userId);
                break;
            case 'integrations':
                $this->saveIntegrations($_POST, $userId);
                break;
            case 'overtime':
                $this->saveOvertime($_POST, $userId);
                break;
            case 'employee':
                $this->saveEmployee($_POST, $userId);
                break;
            case 'salary_advance':
                $this->saveSalaryAdvance($_POST, $userId);
                break;
            case 'security':
                $this->saveSecurity($_POST, $userId);
                break;
            default:
                $_SESSION['flash_error'] = 'Grupo no válido.';
                redirect('systemConfig');
        }

        $_SESSION['flash_success'] = 'Configuración guardada.';
        redirect('systemConfig?tab=' . urlencode($tab));
    }

    public function mailTest() {
        require_system_config_unlock();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('systemConfig?tab=mail');
        }
        csrf_verify();
        if (!$this->mailService) {
            $_SESSION['flash_error'] = 'El módulo de correo no está disponible.';
            redirect('systemConfig?tab=mail');
        }
        $to = trim($_POST['test_email'] ?? '');
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $admin = $this->userModel->getUserById((int)$_SESSION['user_id']);
            $to = $admin && !empty($admin->email) ? trim($admin->email) : '';
        }
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Indicá un email de prueba válido.';
            redirect('systemConfig?tab=mail');
        }
        $result = $this->mailService->send(
            $to,
            'Prueba SMTP — ' . app_name(),
            '<p>Correo de prueba desde <strong>' . htmlspecialchars(app_name()) . '</strong>.</p>'
        );
        if ($result['ok']) {
            $_SESSION['flash_success'] = 'Correo de prueba enviado a ' . $to . '.';
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        redirect('systemConfig?tab=mail');
    }

    private function saveGeneral(array $post, $userId) {
        $sitename = trim($post['sitename'] ?? '');
        $company = trim($post['default_company_name'] ?? '');
        $debug = !empty($post['app_debug']) ? '1' : '0';
        if ($sitename === '') {
            $_SESSION['flash_error'] = 'El nombre del sitio no puede estar vacío.';
            redirect('systemConfig?tab=general');
        }
        $this->settings->setMany([
            'sitename' => $sitename,
            'default_company_name' => $company !== '' ? $company : 'Ecofarma',
            'app_debug' => $debug,
        ], $userId);
    }

    private function saveMail(array $post) {
        if (!$this->mailSettings) {
            $_SESSION['flash_error'] = 'Ejecutá migration_notifications_paystubs.sql (#20).';
            redirect('systemConfig?tab=mail');
        }
        if (!$this->mailSettings->save($post)) {
            $_SESSION['flash_error'] = 'No se pudo guardar la configuración de correo.';
            redirect('systemConfig?tab=mail');
        }
    }

    private function saveCasapav(array $post, $userId) {
        $limit = max(10, min(100, (int)($post['cp_deceased_list_limit'] ?? 50)));
        $pairs = [
            'cp_deceased_list_limit' => (string)$limit,
            'extintos_db_host' => trim($post['extintos_db_host'] ?? ''),
            'extintos_db_name' => trim($post['extintos_db_name'] ?? ''),
            'extintos_db_user' => trim($post['extintos_db_user'] ?? ''),
            'cp_extintos_table_sepulio' => trim($post['cp_extintos_table_sepulio'] ?? 'extintosH'),
            'cp_extintos_table_tanato' => trim($post['cp_extintos_table_tanato'] ?? 'extintos'),
            'cp_duplicate_check_enabled' => !empty($post['cp_duplicate_check_enabled']) ? '1' : '0',
            'cp_closure_markup_pct' => (string)max(0, min(100, (float)str_replace(',', '.', trim($post['cp_closure_markup_pct'] ?? '19.5')))),
            'cp_extras_visible_admin' => !empty($post['cp_extras_visible_admin']) ? '1' : '0',
            'cp_extras_visible_supervisor' => !empty($post['cp_extras_visible_supervisor']) ? '1' : '0',
        ];
        $pass = trim($post['extintos_db_pass'] ?? '');
        if ($pass !== '') {
            $pairs['extintos_db_pass'] = $pass;
        }
        $this->settings->setMany($pairs, $userId);
    }

    private function saveAttendance(array $post, $userId) {
        $late = max(0, min(120, (int)($post['attendance_late_tolerance_min'] ?? 5)));
        $early = max(0, min(120, (int)($post['attendance_early_leave_tolerance_min'] ?? 5)));
        $this->settings->setMany([
            'attendance_late_tolerance_min' => (string)$late,
            'attendance_early_leave_tolerance_min' => (string)$early,
        ], $userId);
    }

    private function saveIntegrations(array $post, $userId) {
        $base = rtrim(trim($post['clock_api_base_url'] ?? ''), '/');
        $pairs = [
            'clock_api_base_url' => $base,
            'clock_api_email' => trim($post['clock_api_email'] ?? ''),
            'ecofarma_default_obra_social' => trim($post['ecofarma_default_obra_social'] ?? '999900'),
            'ecofarma_default_comision_pct' => (string)max(0, min(100, (int)($post['ecofarma_default_comision_pct'] ?? 7))),
        ];
        $pass = trim($post['clock_api_password'] ?? '');
        if ($pass !== '') {
            $pairs['clock_api_password'] = $pass;
        }
        $this->settings->setMany($pairs, $userId);
    }

    private function saveOvertime(array $post, $userId) {
        $this->settings->setMany([
            'overtime_visible_admin' => !empty($post['overtime_visible_admin']) ? '1' : '0',
            'overtime_visible_supervisor' => !empty($post['overtime_visible_supervisor']) ? '1' : '0',
        ], $userId);
    }

    private function saveEmployee(array $post, $userId) {
        $pairs = [];
        foreach (array_keys(employee_portal_all_settings()) as $key) {
            $pairs[$key] = !empty($post[$key]) ? '1' : '0';
        }
        $this->settings->setMany($pairs, $userId);
    }

    private function saveSalaryAdvance(array $post, $userId) {
        $maxAnnual = max(1, min(12, (int)($post['salary_advance_max_annual'] ?? 2)));
        $maxEmpInst = max(1, min(24, (int)($post['salary_advance_max_installments_employee'] ?? 2)));
        $maxHrInst = max(1, min(24, (int)($post['salary_advance_max_installments_hr'] ?? 6)));
        if ($maxHrInst < $maxEmpInst) {
            $maxHrInst = $maxEmpInst;
        }
        $minAmount = max(0, min(99999999, (int)($post['salary_advance_min_amount'] ?? 1)));

        $this->settings->setMany([
            'salary_advance_enabled' => !empty($post['salary_advance_enabled']) ? '1' : '0',
            'salary_advance_max_annual' => (string)$maxAnnual,
            'salary_advance_max_installments_employee' => (string)$maxEmpInst,
            'salary_advance_max_installments_hr' => (string)$maxHrInst,
            'salary_advance_one_pending_only' => !empty($post['salary_advance_one_pending_only']) ? '1' : '0',
            'salary_advance_min_amount' => (string)$minAmount,
        ], $userId);
    }

    private function saveSecurity(array $post, $userId) {
        $current = trim($post['current_pin'] ?? '');
        $new = trim($post['new_pin'] ?? '');
        $confirm = trim($post['new_pin_confirm'] ?? '');
        if ($new === '' && $current === '') {
            return;
        }
        if ($current === '' || !$this->settings->verifyConfigPin($current)) {
            $_SESSION['flash_error'] = 'La clave actual no es correcta.';
            redirect('systemConfig?tab=security');
        }
        if (strlen($new) < 3) {
            $_SESSION['flash_error'] = 'La nueva clave debe tener al menos 3 caracteres.';
            redirect('systemConfig?tab=security');
        }
        if ($new !== $confirm) {
            $_SESSION['flash_error'] = 'La confirmación no coincide.';
            redirect('systemConfig?tab=security');
        }
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $this->settings->setConfigPinHash($hash, $userId);
    }

    private function flattenGroup($group) {
        $rows = $this->settings->getByGroup($group);
        $out = [];
        foreach ($rows as $key => $row) {
            $out[$key] = $this->settings->get($key);
        }
        return $out;
    }

    private function currentValues() {
        return [
            'sitename' => setting_string('sitename'),
            'default_company_name' => setting_string('default_company_name'),
            'app_debug' => setting_bool('app_debug'),
            'cp_deceased_list_limit' => setting_int('cp_deceased_list_limit', 50),
            'extintos_db_host' => setting_string('extintos_db_host'),
            'extintos_db_name' => setting_string('extintos_db_name'),
            'extintos_db_user' => setting_string('extintos_db_user'),
            'cp_extintos_table_sepulio' => setting_string('cp_extintos_table_sepulio', 'extintosH'),
            'cp_extintos_table_tanato' => setting_string('cp_extintos_table_tanato', 'extintos'),
            'cp_duplicate_check_enabled' => setting_bool('cp_duplicate_check_enabled', true),
            'cp_closure_markup_pct' => setting_string('cp_closure_markup_pct', '19.5'),
            'cp_extras_visible_admin' => setting_bool('cp_extras_visible_admin', true),
            'cp_extras_visible_supervisor' => setting_bool('cp_extras_visible_supervisor', true),
            'attendance_late_tolerance_min' => setting_int('attendance_late_tolerance_min', 5),
            'attendance_early_leave_tolerance_min' => setting_int('attendance_early_leave_tolerance_min', 5),
            'clock_api_base_url' => setting_string('clock_api_base_url'),
            'clock_api_email' => setting_string('clock_api_email'),
            'ecofarma_default_obra_social' => setting_string('ecofarma_default_obra_social', '999900'),
            'ecofarma_default_comision_pct' => setting_int('ecofarma_default_comision_pct', 7),
            'salary_advance_enabled' => setting_bool('salary_advance_enabled', true),
            'salary_advance_max_annual' => salary_advance_max_annual(),
            'salary_advance_max_salary_pct' => salary_advance_max_salary_pct(),
            'salary_advance_max_installments_employee' => salary_advance_max_installments_employee(),
            'salary_advance_max_installments_hr' => salary_advance_max_installments_hr(),
            'salary_advance_one_pending_only' => salary_advance_one_pending_only(),
            'salary_advance_require_reference_salary' => salary_advance_require_reference_salary(),
            'salary_advance_min_amount' => (int)salary_advance_min_amount(),
        ];
    }

    private function view($view, $data = []) {
        extract($data, EXTR_SKIP);
        require APPROOT . '/views/' . $view . '.php';
    }
}
