<?php

class CpTaskAdminController {
    private $cpTask;

    public function __construct() {
        if (!isStaffAdmin()) {
            redirect('login');
        }
        $this->cpTask = new CpTask();
    }

    public function pending() {
        $companyId = require_cp_staff();
        $areaId = isSupervisor() ? supervisorAreaId() : 0;
        $total = $this->cpTask->sumPendingByCompany($companyId, $areaId)
            + $this->cpTask->sumPendingExternalByCompany($companyId, $areaId);
        $this->view('admin/cp_tasks/pending', [
            'entries' => $this->cpTask->getPendingForCompany($companyId, $areaId),
            'external_entries' => $this->cpTask->getPendingExternalForCompany($companyId, $areaId),
            'total' => $total,
            'is_supervisor' => isSupervisor(),
        ]);
    }

    public function rates() {
        requireAdminOnly('cpTaskAdmin/pending');
        $companyId = require_cp_staff();
        $areaId = 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId > 0) {
                requireUserInAdminCompany($userId, 'cpTaskAdmin/rates');
                $fields = [];
                foreach (CpTask::rateColumnNames() as $col) {
                    $fields[$col] = $_POST[$col] ?? 0;
                }
                $this->cpTask->saveRatesForUser($userId, $fields);
                $_SESSION['flash_success'] = 'Tarifas guardadas.';
            }
            redirect('cpTaskAdmin/rates');
        }

        $this->view('admin/cp_tasks/rates', [
            'employees' => $this->cpTask->getEmployeesWithRates($companyId, $areaId),
            'rate_columns' => CpTask::rateColumnNames(),
        ]);
    }

    public function catalogs() {
        requireAdminOnly('cpTaskAdmin/pending');
        $companyId = require_cp_staff();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $action = $_POST['catalog_action'] ?? '';
            if ($action === 'add_locality') {
                $this->cpTask->addLocality($companyId, $_POST['name'] ?? '', (int)($_POST['has_additional'] ?? 0));
            } elseif ($action === 'del_locality') {
                $this->cpTask->deleteLocality((int)($_POST['id'] ?? 0), $companyId);
            } elseif ($action === 'add_pickup') {
                $this->cpTask->addPickupPlace($companyId, $_POST['name'] ?? '');
            } elseif ($action === 'del_pickup') {
                $this->cpTask->deletePickupPlace((int)($_POST['id'] ?? 0), $companyId);
            } elseif ($action === 'add_external_co') {
                $this->cpTask->addExternalCompany($companyId, $_POST['name'] ?? '');
            } elseif ($action === 'del_external_co') {
                $this->cpTask->deleteExternalCompany((int)($_POST['id'] ?? 0), $companyId);
            }
            $_SESSION['flash_success'] = 'Catálogo actualizado.';
            redirect('cpTaskAdmin/catalogs');
        }

        $this->view('admin/cp_tasks/catalogs', [
            'localities' => $this->cpTask->getLocalities($companyId),
            'pickup_places' => $this->cpTask->getPickupPlaces($companyId),
            'external_companies' => $this->cpTask->getExternalCompanies($companyId),
        ]);
    }

    public function closeMonth() {
        requireAdminOnly('cpTaskAdmin/pending');
        $companyId = require_cp_staff();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $action = $_POST['close_action'] ?? 'simple';
            if ($action === 'with_increase') {
                $mult = (float)($_POST['multiplier'] ?? 1);
                if ($mult < 1) {
                    $mult = 1 + ($mult / 100);
                }
                $result = $this->cpTask->closeAllPendingWithIncrease($companyId, (int)$_SESSION['user_id'], $mult, 0);
            } else {
                $result = $this->cpTask->closeAllPending($companyId, (int)$_SESSION['user_id'], 0);
            }
            if ($result) {
                $pctLabel = function_exists('cp_closure_markup_pct_label') ? cp_closure_markup_pct_label() : '19,5%';
                $_SESSION['flash_success'] = 'Cierre lote #' . $result['lot_number']
                    . ' — Neto ' . cp_format_money($result['total'])
                    . ' + ' . $pctLabel . ' ' . cp_format_money($result['iva'])
                    . ' = ' . cp_format_money($result['final']);
            } else {
                $_SESSION['flash_error'] = 'No se pudo realizar el cierre (¿sin pendientes?).';
            }
            redirect('cpTaskAdmin/history');
        }

        $entries = $this->cpTask->getPendingForCompany($companyId, 0);
        $external = $this->cpTask->getPendingExternalForCompany($companyId, 0);
        $this->view('admin/cp_tasks/close', [
            'total' => $this->cpTask->sumPendingByCompany($companyId, 0)
                + $this->cpTask->sumPendingExternalByCompany($companyId, 0),
            'count' => count($entries) + count($external),
            'closures' => $this->cpTask->getClosures($companyId, 10),
        ]);
    }

    public function editAmount() {
        requireAdminOnly('cpTaskAdmin/pending');
        $companyId = require_cp_staff();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('cpTaskAdmin/pending');
        }
        csrf_verify();
        $kind = $_POST['kind'] ?? 'entry';
        $id = (int)($_POST['id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        if ($id <= 0 || $amount <= 0) {
            $_SESSION['flash_error'] = 'Importe inválido.';
            redirect('cpTaskAdmin/pending');
        }
        $ok = false;
        if ($kind === 'external') {
            $row = $this->cpTask->getExternalEntryForCompany($id, $companyId);
            if ($row && $row->status === 'pending') {
                $ok = $this->cpTask->updatePendingExternalAmount($id, $companyId, $amount);
            }
        } else {
            $row = $this->cpTask->getEntryForCompany($id, $companyId);
            if ($row && $row->status === 'pending') {
                $ok = $this->cpTask->updatePendingEntryAmount($id, $companyId, $amount);
            }
        }
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Importe actualizado.' : 'No se pudo actualizar.';
        redirect('cpTaskAdmin/pending');
    }

    public function rateIncrease() {
        requireAdminOnly('cpTaskAdmin/pending');
        $companyId = require_cp_staff();
        $percent = (float)($_POST['percent'] ?? $_GET['percent'] ?? 0);
        $preview = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $pct = (float)($_POST['percent'] ?? 0);
            $percent = $pct;
            $multiplier = $pct > 0 ? (1 + ($pct / 100)) : 1.0;
            $action = $_POST['rate_action'] ?? 'preview';
            if ($action === 'apply_rates') {
                if ($this->cpTask->applyRateIncreaseToCompany($companyId, $multiplier)) {
                    $_SESSION['flash_success'] = 'Tarifas actualizadas (+' . $pct . '%).';
                } else {
                    $_SESSION['flash_error'] = 'No se pudieron actualizar las tarifas.';
                }
                redirect('cpTaskAdmin/rateIncrease');
            }
            if ($action === 'apply_and_close') {
                $result = $this->cpTask->closeAllPendingWithIncrease($companyId, (int)$_SESSION['user_id'], $multiplier, 0);
                if ($result) {
                    $_SESSION['flash_success'] = 'Aumento aplicado y cierre lote #' . $result['lot_number'] . ' — ' . cp_format_money($result['final']) . ' con IVA';
                    redirect('cpTaskAdmin/history');
                }
                $_SESSION['flash_error'] = 'No se pudo completar cierre con aumento.';
                redirect('cpTaskAdmin/rateIncrease');
            }
            if ($action === 'preview' && $pct > 0) {
                $preview = $this->cpTask->previewPendingIncrease($companyId, $multiplier);
            }
        }

        $this->view('admin/cp_tasks/rate_increase', [
            'preview' => $preview,
            'percent' => $percent,
        ]);
    }

    public function reports() {
        requireAdminOnly('cpTaskAdmin/pending');
        $companyId = require_cp_staff();
        $month = isset($_GET['mes']) && preg_match('/^\d{4}-\d{2}$/', $_GET['mes']) ? $_GET['mes'] : date('Y-m');
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));
        $rows = $this->cpTask->getEmployeeReport($companyId, $from, $to);
        foreach ($rows as $r) {
            $calc = cp_compute_closure_amounts((float)$r->total_amount);
            $r->markup = $calc['markup'];
            $r->final = $calc['final'];
        }
        $this->view('admin/cp_tasks/reports', [
            'month' => $month,
            'rows' => $rows,
            'closures' => $this->cpTask->getClosures($companyId, 24),
            'markup_label' => cp_closure_markup_pct_label(),
        ]);
    }

    public function exportReport() {
        requireAdminOnly('cpTaskAdmin/pending');
        $companyId = require_cp_staff();
        $month = isset($_GET['mes']) && preg_match('/^\d{4}-\d{2}$/', $_GET['mes']) ? $_GET['mes'] : date('Y-m');
        $from = $month . '-01';
        $to = date('Y-m-t', strtotime($from));
        $pctLabel = cp_closure_markup_pct_label();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="cp_reporte_' . $month . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Reporte extras CP — ' . $month], ';');
        fputcsv($out, ['Recargo al cierre: ' . $pctLabel], ';');
        fputcsv($out, [], ';');
        fputcsv($out, ['Empleado', 'Importe cargado', 'Recargo (' . $pctLabel . ')', 'Total final'], ';');

        $sumNet = $sumMarkup = $sumFinal = 0;
        foreach ($this->cpTask->getEmployeeReport($companyId, $from, $to) as $r) {
            $calc = cp_compute_closure_amounts((float)$r->total_amount);
            fputcsv($out, [
                $r->full_name,
                number_format($calc['net'], 2, ',', ''),
                number_format($calc['markup'], 2, ',', ''),
                number_format($calc['final'], 2, ',', ''),
            ], ';');
            $sumNet += $calc['net'];
            $sumMarkup += $calc['markup'];
            $sumFinal += $calc['final'];
        }
        fputcsv($out, [], ';');
        fputcsv($out, [
            'TOTAL',
            number_format($sumNet, 2, ',', ''),
            number_format($sumMarkup, 2, ',', ''),
            number_format($sumFinal, 2, ',', ''),
        ], ';');
        fclose($out);
        exit;
    }

    public function history() {
        requireAdminOnly('cpTaskAdmin/pending');
        $companyId = require_cp_staff();
        $this->view('admin/cp_tasks/history', [
            'closures' => $this->cpTask->getClosures($companyId, 100),
        ]);
    }

    public function closureDetail($id = 0) {
        requireAdminOnly('cpTaskAdmin/pending');
        $companyId = require_cp_staff();
        $id = (int)$id;
        $closure = $this->cpTask->getClosureById($id, $companyId);
        if (!$closure) {
            redirect('cpTaskAdmin/history');
        }
        $storedRate = (float)($closure->iva_rate ?? 0);
        $markupLabel = $storedRate > 0
            ? (rtrim(rtrim(number_format($storedRate * 100, 2, ',', '.'), '0'), ',') . '%')
            : cp_closure_markup_pct_label();
        $this->view('admin/cp_tasks/closure_detail', [
            'closure' => $closure,
            'entries' => $this->cpTask->getEntriesByClosure($id, $companyId),
            'external' => $this->cpTask->getExternalByClosure($id, $companyId),
            'employee_totals' => $this->cpTask->getClosureTotalsByEmployee($id, $companyId),
            'markup_label' => $markupLabel,
        ]);
    }

    public function exportPending() {
        requireAdminOnly('cpTaskAdmin/pending');
        $companyId = require_cp_staff();
        $rows = $this->cpTask->getPendingExportRows($companyId, 0);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="cp_pendientes_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Tipo', 'Fecha', 'Empleado', 'Tarea', 'Detalle', 'Importe'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [$r->kind, $r->activity_date, $r->employee_name, $r->task_name, $r->detail, number_format($r->amount, 2, ',', '')], ';');
        }
        fclose($out);
        exit;
    }

    public function exportClosure($id = 0) {
        requireAdminOnly('cpTaskAdmin/pending');
        $companyId = require_cp_staff();
        $id = (int)$id;
        $closure = $this->cpTask->getClosureById($id, $companyId);
        if (!$closure) {
            redirect('cpTaskAdmin/history');
        }
        $pctLabel = cp_closure_markup_pct_label();
        $storedRate = (float)($closure->iva_rate ?? 0);
        $pctDisplay = $storedRate > 0
            ? (rtrim(rtrim(number_format($storedRate * 100, 2, ',', '.'), '0'), ',') . '%')
            : $pctLabel;

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="cp_lote_' . $closure->lot_number . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Cierre lote #' . $closure->lot_number . ' — ' . date('d/m/Y', strtotime($closure->closed_at))], ';');
        fputcsv($out, ['Recargo aplicado: ' . $pctDisplay], ';');
        fputcsv($out, [], ';');
        fputcsv($out, ['Empleado', 'Importe cargado', 'Recargo (' . $pctDisplay . ')', 'Total final'], ';');

        $sumNet = $sumMarkup = $sumFinal = 0;
        foreach ($this->cpTask->getClosureTotalsByEmployee($id, $companyId) as $row) {
            fputcsv($out, [
                $row->full_name,
                number_format($row->net, 2, ',', ''),
                number_format($row->markup, 2, ',', ''),
                number_format($row->final, 2, ',', ''),
            ], ';');
            $sumNet += $row->net;
            $sumMarkup += $row->markup;
            $sumFinal += $row->final;
        }
        fputcsv($out, [], ';');
        fputcsv($out, [
            'TOTAL',
            number_format($sumNet, 2, ',', ''),
            number_format($sumMarkup, 2, ',', ''),
            number_format($sumFinal, 2, ',', ''),
        ], ';');
        fputcsv($out, [], ';');
        fputcsv($out, ['--- Detalle por tarea ---'], ';');
        fputcsv($out, ['Empleado', 'Fecha', 'Tarea', 'Detalle', 'Importe'], ';');
        foreach ($this->cpTask->getEntriesByClosure($id, $companyId) as $e) {
            fputcsv($out, [$e->employee_name, $e->activity_date, $e->task_name, $e->deceased_name ?: $e->deceased_code, number_format($e->amount, 2, ',', '')], ';');
        }
        foreach ($this->cpTask->getExternalByClosure($id, $companyId) as $x) {
            fputcsv($out, [$x->employee_name, $x->activity_date, $x->task_label, $x->external_company_name, number_format($x->amount, 2, ',', '')], ';');
        }
        fclose($out);
        exit;
    }

    private function view($view, $data = []) {
        require_once APPROOT . '/views/' . $view . '.php';
    }
}
