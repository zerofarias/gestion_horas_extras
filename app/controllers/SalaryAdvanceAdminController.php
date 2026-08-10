<?php

class SalaryAdvanceAdminController {
    private $model;

    public function __construct() {
        require_salary_advance_admin();
        $this->model = new SalaryAdvance();
    }

    public function index() {
        $companyId = requireAdminCompany('admin/dashboard');
        $status = trim($_GET['status'] ?? '');
        $search = trim($_GET['search'] ?? '');
        $userId = (int)($_GET['user_id'] ?? 0);
        $openId = (int)($_GET['open'] ?? 0);

        $filters = [];
        if (in_array($status, salary_advance_statuses(), true)) {
            $filters['status'] = $status;
        }
        if ($search !== '') {
            $filters['search'] = $search;
        }
        if ($userId > 0) {
            $filters['user_id'] = $userId;
        }

        $entries = $this->model->getAllByCompany($companyId, $filters);
        $openEntry = null;
        if ($openId > 0) {
            $openEntry = $this->model->getByIdForCompany($openId, $companyId);
        }

        $this->view('admin/salary_advances/index', [
            'entries' => $entries,
            'filters' => [
                'status' => $status,
                'search' => $search,
                'user_id' => $userId,
            ],
            'open_entry' => $openEntry,
            'open_id' => $openId,
            'max_installments_hr' => salary_advance_max_installments_hr(),
            'max_installments_employee' => salary_advance_max_installments_employee(),
            'installments_ready' => salary_advance_installments_ready(),
            'finalizado_ready' => salary_advance_finalizado_ready(),
        ]);
    }

    public function installments($advanceId = 0) {
        $companyId = requireAdminCompany('admin/dashboard');
        $advanceId = (int)$advanceId;
        header('Content-Type: application/json; charset=utf-8');

        if ($advanceId <= 0) {
            echo json_encode(['ok' => false, 'message' => 'ID inválido']);
            exit;
        }

        $advance = $this->model->getByIdForCompany($advanceId, $companyId);
        if (!$advance) {
            echo json_encode(['ok' => false, 'message' => 'No encontrado']);
            exit;
        }

        $installments = salary_advance_format_installments_for_json(
            $this->model->getInstallmentsByAdvanceId($advanceId)
        );

        echo json_encode([
            'ok' => true,
            'advance' => [
                'id' => (int)$advance->id,
                'employee_name' => $advance->employee_name,
                'amount' => (float)$advance->amount,
                'amount_fmt' => salary_advance_format_money($advance->amount),
                'status' => $advance->status,
                'installments_approved' => $advance->installments_approved !== null ? (int)$advance->installments_approved : null,
                'reason' => $advance->reason ?? '',
                'admin_notes' => $advance->admin_notes ?? '',
                'created_at_fmt' => date('d/m/Y H:i', strtotime($advance->created_at)),
            ],
            'installments' => $installments,
            'installments_ready' => salary_advance_installments_ready(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function history($userId = 0) {
        $companyId = requireAdminCompany('admin/dashboard');
        $userId = (int)$userId;
        if ($userId <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'items' => []]);
            exit;
        }

        adminResolveUser($userId);
        $items = $this->model->getByUserId($userId);
        $out = [];
        foreach ($items as $row) {
            $out[] = [
                'id' => (int)$row->id,
                'created_at' => $row->created_at,
                'created_at_fmt' => date('d/m/Y H:i', strtotime($row->created_at)),
                'amount' => salary_advance_format_money($row->amount),
                'installments_requested' => (int)$row->installments_requested,
                'installments_approved' => $row->installments_approved !== null ? (int)$row->installments_approved : null,
                'status' => $row->status,
                'admin_notes' => $row->admin_notes ?? '',
            ];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'items' => $out]);
        exit;
    }

    public function receipt($advanceId = 0, $installmentNumber = 0) {
        $companyId = requireAdminCompany('admin/dashboard');
        $advanceId = (int)$advanceId;
        $installmentNumber = (int)$installmentNumber;

        if (!salary_advance_installments_ready()) {
            $_SESSION['flash_error'] = 'Ejecutá migration_salary_advance_installments.sql (MIGRATIONS.md #39).';
            redirect('salaryAdvanceAdmin/index');
        }

        $row = $this->model->getInstallmentForReceipt($advanceId, $installmentNumber, $companyId);
        if (!$row) {
            $_SESSION['flash_error'] = 'Cuota no encontrada.';
            redirect('salaryAdvanceAdmin/index');
        }

        $totalApproved = (int)($this->model->getByIdForCompany($advanceId, $companyId)->installments_approved ?? $installmentNumber);
        $isFinal = $installmentNumber >= $totalApproved;

        $this->view('admin/salary_advances/receipt', [
            'row' => $row,
            'company_name' => $_SESSION['user_company_name'] ?? app_name(),
            'is_final' => $isFinal,
            'total_installments' => $totalApproved,
            'printed_at' => date('d/m/Y H:i'),
        ]);
    }

    public function process() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('salaryAdvanceAdmin/index');
        }
        csrf_verify();
        requireAdminOnly();

        $companyId = requireAdminCompany('salaryAdvanceAdmin/index');
        $id = (int)($_POST['id'] ?? 0);
        $action = trim($_POST['action'] ?? '');
        $adminId = (int)$_SESSION['user_id'];

        $entry = $this->model->getByIdForCompany($id, $companyId);
        if (!$entry) {
            $_SESSION['flash_error'] = 'Solicitud no encontrada.';
            redirect('salaryAdvanceAdmin/index');
        }

        if ($action === 'approve') {
            if ($entry->status !== 'Pendiente') {
                $_SESSION['flash_error'] = 'La solicitud ya fue resuelta.';
                redirect('salaryAdvanceAdmin/index?open=' . $id);
            }

            $installments = (int)($_POST['installments_approved'] ?? $entry->installments_requested);
            $hrOverride = !empty($_POST['hr_installments_override']);
            $maxHr = salary_advance_max_installments_hr();
            $maxEmp = salary_advance_max_installments_employee();

            if ($hrOverride) {
                if ($installments < 1 || $installments > $maxHr) {
                    $_SESSION['flash_error'] = 'Las cuotas aprobadas deben estar entre 1 y ' . $maxHr . '.';
                    redirect('salaryAdvanceAdmin/index?open=' . $id);
                }
            } elseif ($installments < 1 || $installments > $maxEmp) {
                $_SESSION['flash_error'] = 'Sin override, las cuotas deben estar entre 1 y ' . $maxEmp . '.';
                redirect('salaryAdvanceAdmin/index?open=' . $id);
            }

            $months = $_POST['installment_month'] ?? [];
            $amounts = $_POST['installment_amount'] ?? [];
            if (!is_array($months) || !is_array($amounts) || count($months) !== $installments || count($amounts) !== $installments) {
                $_SESSION['flash_error'] = 'Completá el mes y monto de cada cuota.';
                redirect('salaryAdvanceAdmin/index?open=' . $id);
            }

            $schedule = [];
            $sum = 0.0;
            for ($i = 0; $i < $installments; $i++) {
                $ym = trim((string)($months[$i] ?? ''));
                $amt = round((float)str_replace(',', '.', (string)($amounts[$i] ?? '0')), 2);
                if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
                    $_SESSION['flash_error'] = 'Mes de descuento inválido en cuota ' . ($i + 1) . '.';
                    redirect('salaryAdvanceAdmin/index?open=' . $id);
                }
                if ($amt <= 0) {
                    $_SESSION['flash_error'] = 'Monto inválido en cuota ' . ($i + 1) . '.';
                    redirect('salaryAdvanceAdmin/index?open=' . $id);
                }
                $schedule[] = [
                    'installment_number' => $i + 1,
                    'due_month' => $ym,
                    'amount' => $amt,
                ];
                $sum += $amt;
            }

            if (abs($sum - (float)$entry->amount) > 0.02) {
                $_SESSION['flash_error'] = 'La suma de las cuotas (' . salary_advance_format_money($sum)
                    . ') debe coincidir con el adelanto (' . salary_advance_format_money($entry->amount) . ').';
                redirect('salaryAdvanceAdmin/index?open=' . $id);
            }

            $ok = $this->model->approveWithInstallments($id, $adminId, [
                'installments_approved' => $installments,
                'hr_installments_override' => $hrOverride,
                'admin_notes' => trim($_POST['admin_notes'] ?? ''),
            ], $schedule);

            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Adelanto aprobado y plan de cuotas registrado.'
                : 'No se pudo aprobar la solicitud.';
        } elseif ($action === 'reject') {
            if ($entry->status !== 'Pendiente') {
                $_SESSION['flash_error'] = 'La solicitud ya fue resuelta.';
                redirect('salaryAdvanceAdmin/index?open=' . $id);
            }
            $notes = trim($_POST['admin_notes'] ?? '');
            if ($notes === '') {
                $_SESSION['flash_error'] = 'Indicá el motivo del rechazo en las notas.';
                redirect('salaryAdvanceAdmin/index?open=' . $id);
            }
            $ok = $this->model->reject($id, $adminId, $notes);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Solicitud rechazada.'
                : 'No se pudo rechazar la solicitud.';
        } elseif ($action === 'save_schedule') {
            if (!in_array($entry->status, ['Aprobado'], true)) {
                $_SESSION['flash_error'] = 'Solo se puede editar el plan de adelantos aprobados (no finalizados).';
                redirect('salaryAdvanceAdmin/index?open=' . $id);
            }
            if (!salary_advance_installments_ready()) {
                $_SESSION['flash_error'] = 'Falta migración de cuotas (#39).';
                redirect('salaryAdvanceAdmin/index?open=' . $id);
            }

            $instIds = $_POST['installment_id'] ?? [];
            $months = $_POST['installment_month'] ?? [];
            $amounts = $_POST['installment_amount'] ?? [];
            $notes = $_POST['installment_notes'] ?? [];
            if (!is_array($instIds) || count($instIds) === 0) {
                $_SESSION['flash_error'] = 'No hay cuotas para guardar.';
                redirect('salaryAdvanceAdmin/index?open=' . $id);
            }

            $rows = [];
            $sum = 0.0;
            foreach ($instIds as $idx => $instId) {
                $ym = trim((string)($months[$idx] ?? ''));
                $amt = round((float)str_replace(',', '.', (string)($amounts[$idx] ?? '0')), 2);
                if (!preg_match('/^\d{4}-\d{2}$/', $ym) || $amt <= 0) {
                    $_SESSION['flash_error'] = 'Datos inválidos en cuota ' . ($idx + 1) . '.';
                    redirect('salaryAdvanceAdmin/index?open=' . $id);
                }
                $rows[] = [
                    'id' => (int)$instId,
                    'due_month' => $ym,
                    'amount' => $amt,
                    'notes' => trim((string)($notes[$idx] ?? '')),
                ];
                $sum += $amt;
            }

            if (abs($sum - (float)$entry->amount) > 0.02) {
                $_SESSION['flash_error'] = 'La suma de cuotas debe ser ' . salary_advance_format_money($entry->amount) . '.';
                redirect('salaryAdvanceAdmin/index?open=' . $id);
            }

            $ok = $this->model->saveInstallmentSchedule($id, $rows);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Plan de cuotas actualizado.'
                : 'No se pudo guardar el plan.';
        } elseif ($action === 'generate_schedule') {
            if ($entry->status !== 'Aprobado' || !salary_advance_installments_ready()) {
                $_SESSION['flash_error'] = 'No se puede generar el plan.';
                redirect('salaryAdvanceAdmin/index?open=' . $id);
            }
            $existing = $this->model->getInstallmentsByAdvanceId($id);
            if (!empty($existing)) {
                $_SESSION['flash_error'] = 'Este adelanto ya tiene cuotas registradas.';
                redirect('salaryAdvanceAdmin/index?open=' . $id);
            }
            $count = (int)($entry->installments_approved ?: $entry->installments_requested);
            $months = salary_advance_default_months($count);
            $amounts = salary_advance_split_amounts((float)$entry->amount, $count);
            $schedule = [];
            for ($i = 0; $i < $count; $i++) {
                $schedule[] = [
                    'installment_number' => $i + 1,
                    'due_month' => $months[$i],
                    'amount' => $amounts[$i],
                ];
            }
            $ok = $this->model->insertInstallments($id, $schedule);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Plan de cuotas generado. Podés ajustar meses e importes.'
                : 'No se pudo generar el plan.';
        } elseif ($action === 'toggle_deducted') {
            if (!salary_advance_installments_ready()) {
                $_SESSION['flash_error'] = 'Falta migración de cuotas (#39).';
                redirect('salaryAdvanceAdmin/index?open=' . $id);
            }
            $instId = (int)($_POST['installment_id'] ?? 0);
            $deducted = !empty($_POST['is_deducted']);
            $ok = $this->model->setInstallmentDeducted($instId, $id, $adminId, $deducted);
            if ($ok) {
                $updated = $this->model->getByIdForCompany($id, $companyId);
                if ($deducted && $updated && $updated->status === 'Finalizado') {
                    $_SESSION['flash_success'] = 'Descuento registrado. El adelanto quedó finalizado.';
                } elseif (!$deducted && $updated && $updated->status === 'Aprobado') {
                    $_SESSION['flash_success'] = 'Descuento desmarcado. El adelanto volvió a estado Aprobado.';
                } else {
                    $_SESSION['flash_success'] = $deducted
                        ? 'Descuento marcado como realizado.'
                        : 'Descuento desmarcado.';
                }
            } else {
                $_SESSION['flash_error'] = 'No se pudo actualizar la cuota.';
            }
        } else {
            $_SESSION['flash_error'] = 'Acción no válida.';
        }

        redirect('salaryAdvanceAdmin/index?open=' . $id);
    }

    private function view($view, $data = []) {
        extract($data, EXTR_SKIP);
        require APPROOT . '/views/' . $view . '.php';
    }
}
