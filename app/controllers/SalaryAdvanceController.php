<?php

class SalaryAdvanceController {
    private $model;
    private $userModel;

    public function __construct() {
        require_salary_advance_employee();
        $this->model = new SalaryAdvance();
        $this->userModel = new User();
    }

    public function index() {
        $userId = (int)$_SESSION['user_id'];
        $user = $this->userModel->getUserById($userId);
        if (!$user) {
            redirect('login');
        }

        $year = (int)date('Y');
        $usedAnnual = $this->model->countByUserInYear($userId, $year);
        $hasPending = $this->model->hasPendingByUser($userId);
        $history = $this->model->getByUserId($userId);

        $canSubmit = !$hasPending && $usedAnnual < salary_advance_max_annual();

        $this->view('employee/salary_advance/index', [
            'user' => $user,
            'history' => $history,
            'used_annual' => $usedAnnual,
            'max_annual' => salary_advance_max_annual(),
            'max_installments' => salary_advance_max_installments_employee(),
            'min_amount' => salary_advance_min_amount(),
            'has_pending' => $hasPending,
            'can_submit' => $canSubmit,
            'year' => $year,
        ]);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('salaryAdvance/index');
        }
        csrf_verify();

        $userId = (int)$_SESSION['user_id'];
        $user = $this->userModel->getUserById($userId);
        if (!$user || empty($user->company_id)) {
            $_SESSION['flash_error'] = 'Tu usuario no tiene empresa asignada.';
            redirect('salaryAdvance/index');
        }

        $amount = (float)str_replace(',', '.', trim($_POST['amount'] ?? '0'));
        $installments = (int)($_POST['installments_requested'] ?? 1);
        $reason = trim($_POST['reason'] ?? '');

        $validation = salary_advance_validate_submission($userId, $amount, $installments);
        if (!$validation['ok']) {
            $_SESSION['flash_error'] = $validation['message'];
            redirect('salaryAdvance/index');
        }

        $id = $this->model->create([
            'user_id' => $userId,
            'company_id' => (int)$user->company_id,
            'amount' => $amount,
            'installments_requested' => $installments,
            'reason' => $reason,
        ]);

        if ($id) {
            $_SESSION['flash_success'] = 'Solicitud de adelanto enviada. RRHH la revisará a la brevedad.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo guardar la solicitud.';
        }
        redirect('salaryAdvance/index');
    }

    private function view($view, $data = []) {
        extract($data, EXTR_SKIP);
        require APPROOT . '/views/' . $view . '.php';
    }
}
