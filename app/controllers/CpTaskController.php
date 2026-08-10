<?php

class CpTaskController {
    private $cpTask;
    private $pricing;
    private $userModel;
    private $deceased;

    public function __construct() {
        require_cp_employee();
        if (function_exists('cp_empleado_can_view') ? !cp_empleado_can_view() : !employee_portal_can('cp_extras')) {
            $_SESSION['flash_error'] = 'El módulo de extras no está disponible.';
            redirect('employee/index');
        }
        $this->cpTask = new CpTask();
        $this->pricing = new CpTaskPricingService();
        $this->userModel = new User();
        $this->deceased = new DeceasedLookupService();
    }

    public function index() {
        $userId = (int)$_SESSION['user_id'];
        $this->cpTask->ensureRatesRow($userId);
        $pending = $this->cpTask->getPendingByUser($userId);
        $pendingExt = $this->cpTask->getPendingExternalByUser($userId);
        $total = 0;
        foreach ($pending as $p) {
            $total += (float)$p->amount;
        }
        foreach ($pendingExt as $p) {
            $total += (float)$p->amount;
        }
        $types = $this->cpTask->getTaskTypes(false);
        $this->view('employee/cp_tasks/index', [
            'task_groups' => cp_task_types_grouped($types),
            'pending' => $pending,
            'pending_external' => $pendingExt,
            'pending_count' => count($pending) + count($pendingExt),
            'pending_total' => $total,
            'has_rates' => cp_user_has_rates($userId),
            'extintos_ready' => $this->deceased->isConfigured(),
        ]);
    }

    public function myList() {
        redirect('cpTask/index');
    }

    public function lookupDeceased($code = '') {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->deceased->isConfigured()) {
            echo json_encode(['ok' => false]);
            return;
        }
        $row = $this->deceased->findByCode($code);
        echo json_encode(['ok' => (bool)$row, 'name' => $row ? $row->name : null]);
    }

    /** AJAX: ¿ya existe esta tarea + extinto para el empleado? */
    public function checkDeceasedDuplicate() {
        header('Content-Type: application/json; charset=utf-8');
        if (function_exists('setting_bool') && !setting_bool('cp_duplicate_check_enabled', true)) {
            echo json_encode(['duplicate' => false]);
            return;
        }
        $formKey = trim($_GET['form_key'] ?? '');
        $code = trim($_GET['deceased_code'] ?? '');
        $userId = (int)$_SESSION['user_id'];
        $type = $this->cpTask->getTaskTypeByFormKey($formKey);
        if (!$type || $code === '') {
            echo json_encode(['duplicate' => false]);
            return;
        }
        $dup = $this->cpTask->duplicateExists($userId, (int)$type->id, $code);
        $name = '';
        if ($dup && $this->deceased->isConfigured()) {
            $row = $this->deceased->findByCode($code);
            $name = $row ? $row->name : '';
        }
        echo json_encode([
            'duplicate' => $dup,
            'message' => $dup ? cp_task_duplicate_message($formKey, $name) : '',
        ]);
    }

    public function create($formKey = '') {
        $formKey = trim((string)$formKey);
        $type = $this->cpTask->getTaskTypeByFormKey($formKey);
        if (!$type) {
            $_SESSION['flash_error'] = 'Tarea no disponible.';
            redirect('cpTask/index');
        }
        $user = $this->userModel->getUserById((int)$_SESSION['user_id']);
        $companyId = (int)($user->company_id ?? 0);
        $view = 'employee/cp_tasks/form_' . $formKey;
        if (!file_exists(APPROOT . '/views/' . $view . '.php')) {
            $_SESSION['flash_error'] = 'Formulario no encontrado.';
            redirect('cpTask/index');
        }
        $type->display_name = cp_task_display_name($type->form_key, $type->name);
        $usesDeceased = cp_task_form_uses_deceased_select($formKey);
        $this->view($view, [
            'task_type' => $type,
            'colleagues' => $this->cpTask->getColleagues($companyId, (int)$_SESSION['user_id']),
            'localities' => $this->cpTask->getLocalities($companyId),
            'pickup_places' => $this->cpTask->getPickupPlaces($companyId),
            'external_companies' => $this->cpTask->getExternalCompanies($companyId),
            'deceased_list' => ($usesDeceased && $this->deceased->isConfigured())
                ? $this->deceased->searchRecent(
                    function_exists('setting_int') ? setting_int('cp_deceased_list_limit', 50) : 50,
                    $this->deceased->tableForFormKey($formKey)
                )
                : [],
            'cp_duplicate_check' => function_exists('setting_bool') ? setting_bool('cp_duplicate_check_enabled', true) : true,
            'extintos_ready' => $usesDeceased && $this->deceased->isConfigured(),
        ]);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('cpTask/index');
        }
        csrf_verify();
        $formKey = trim($_POST['form_key'] ?? '');
        if ($formKey === 'externas') {
            $this->storeExternal();
            return;
        }

        $user = $this->userModel->getUserById((int)$_SESSION['user_id']);
        $userId = (int)$_SESSION['user_id'];
        $companyId = (int)($user->company_id ?? 0);

        $dateErr = cp_validate_activity_date($_POST['activity_date'] ?? '');
        if ($dateErr) {
            $_SESSION['flash_error'] = $dateErr;
            redirect('cpTask/create/' . $formKey);
        }

        $result = $this->pricing->calculate($formKey, $userId, $companyId, $_POST);
        if (!$result['ok']) {
            $_SESSION['flash_error'] = $result['message'];
            redirect('cpTask/create/' . $formKey);
        }

        $deceasedCode = trim($_POST['deceased_code'] ?? '');
        $deceasedName = trim($_POST['deceased_name'] ?? '');

        if (cp_task_form_uses_deceased_select($formKey)) {
            if ($deceasedCode === '') {
                $_SESSION['flash_error'] = 'Seleccioná el extinto de la lista.';
                redirect('cpTask/create/' . $formKey);
            }
            if ($deceasedName === '' && $this->deceased->isConfigured()) {
                $found = $this->deceased->findByCode($deceasedCode);
                if ($found) {
                    $deceasedName = trim((string)$found->name);
                }
            }
            $dupEnabled = function_exists('setting_bool') ? setting_bool('cp_duplicate_check_enabled', true) : true;
            if ($dupEnabled && $this->cpTask->duplicateExists($userId, $result['task_type_id'], $deceasedCode)) {
                $_SESSION['flash_error'] = cp_task_duplicate_message($formKey, $deceasedName);
                redirect('cpTask/create/' . $formKey);
            }
        } elseif ($deceasedCode !== '' && (function_exists('setting_bool') ? setting_bool('cp_duplicate_check_enabled', true) : true) && $this->cpTask->duplicateExists($userId, $result['task_type_id'], $deceasedCode)) {
            $_SESSION['flash_error'] = cp_task_duplicate_message($formKey, $deceasedName);
            redirect('cpTask/create/' . $formKey);
        }

        $companionId = (int)($_POST['companion_user_id'] ?? 0);
        $entryId = $this->cpTask->addEntry([
            'company_id' => $companyId,
            'user_id' => $userId,
            'task_type_id' => $result['task_type_id'],
            'activity_date' => trim($_POST['activity_date'] ?? ''),
            'amount' => $result['amount'],
            'amount_base' => $result['amount_base'],
            'is_holiday' => $result['is_holiday'],
            'holiday_multiplier' => $result['holiday_multiplier'],
            'deceased_code' => $deceasedCode !== '' ? $deceasedCode : null,
            'deceased_name' => $deceasedName !== '' ? $deceasedName : null,
            'companion_user_id' => $companionId > 0 ? $companionId : null,
            'meta' => $result['meta'],
        ]);

        if ($entryId) {
            $msg = 'Tarea registrada: ' . cp_format_money($result['amount']);
            if (!empty($result['holiday_warning'])) {
                $msg .= ' (feriado: importe manual sin duplicar automático)';
            } elseif ($result['is_holiday']) {
                $msg .= ' (incluye feriado ×2)';
            }
            $_SESSION['flash_success'] = $msg;
        } else {
            $_SESSION['flash_error'] = 'No se pudo guardar la tarea.';
        }
        redirect('cpTask/index');
    }

    private function storeExternal() {
        $user = $this->userModel->getUserById((int)$_SESSION['user_id']);
        $userId = (int)$_SESSION['user_id'];
        $companyId = (int)($user->company_id ?? 0);
        $extCoId = (int)($_POST['external_company_id'] ?? 0);
        $amount = (float)($_POST['manual_amount'] ?? 0);
        $activityDate = trim($_POST['activity_date'] ?? '');
        $taskLabel = trim($_POST['task_label'] ?? '');
        $dateErr = cp_validate_activity_date($activityDate);
        if ($dateErr) {
            $_SESSION['flash_error'] = $dateErr;
            redirect('cpTask/create/externas');
        }
        if ($extCoId <= 0 || $amount <= 0 || $activityDate === '' || $taskLabel === '') {
            $_SESSION['flash_error'] = 'Completá empresa, tarea, fecha e importe.';
            redirect('cpTask/create/externas');
        }
        $holiday = new Holiday();
        $isHoliday = $holiday->isHolidayForCompany($companyId, $activityDate) ? 1 : 0;
        $id = $this->cpTask->addExternalEntry([
            'company_id' => $companyId,
            'user_id' => $userId,
            'external_company_id' => $extCoId,
            'task_label' => $taskLabel,
            'activity_date' => $activityDate,
            'amount' => $amount,
            'amount_base' => $amount,
            'is_holiday' => $isHoliday,
            'holiday_multiplier' => 1,
            'comment' => trim($_POST['comment'] ?? ''),
        ]);
        $_SESSION[$id ? 'flash_success' : 'flash_error'] = $id
            ? 'Tarea externa registrada: ' . cp_format_money($amount)
            : 'No se pudo guardar.';
        redirect('cpTask/index');
    }

    public function delete($id = 0) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('cpTask/index');
        }
        csrf_verify();
        $id = (int)$id;
        $userId = (int)$_SESSION['user_id'];
        if ($this->cpTask->deletePendingEntry($id, $userId)) {
            $_SESSION['flash_success'] = 'Registro eliminado.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo eliminar (solo pendientes propios).';
        }
        redirect('cpTask/index');
    }

    public function deleteExternal($id = 0) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('cpTask/index');
        }
        csrf_verify();
        $id = (int)$id;
        if ($this->cpTask->deletePendingExternalEntry($id, (int)$_SESSION['user_id'])) {
            $_SESSION['flash_success'] = 'Registro externo eliminado.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo eliminar.';
        }
        redirect('cpTask/index');
    }

    private function view($view, $data = []) {
        require_once APPROOT . '/views/' . $view . '.php';
    }
}
