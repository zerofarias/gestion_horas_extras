<?php

class ProdeAdminController {
    private $editionModel;
    private $predictionModel;
    private $scoring;

    public function __construct() {
        if (!isStaffAdmin()) {
            redirect('login');
        }
        if (!prode_is_ready()) {
            $_SESSION['flash_error'] = 'Ejecutá migration_prode_wc2026.sql y php scripts/seed_prode_wc2026.php';
            redirect('admin/dashboard');
        }
        $this->editionModel = new ProdeEdition();
        $this->predictionModel = new ProdePrediction();
        $this->scoring = new ProdeScoringService();
        ensureAdminCompanySession();
    }

    public function ranking() {
        $edition = $this->editionModel->getActiveEdition();
        if (!$edition) {
            $_SESSION['flash_error'] = 'No hay edición PRODE.';
            redirect('admin/dashboard');
        }

        $companyFilter = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
        $areaFilter = (int)($_GET['area_id'] ?? 0);
        $companies = (new Company())->getAllCompanies();
        if (isSupervisor()) {
            $companyFilter = adminCompanyId();
        }
        $areas = [];
        if ($companyFilter > 0) {
            $areas = (new Area())->getAvailableForCompany($companyFilter);
        }

        if (isSupervisor()) {
            $supArea = supervisorAreaId();
            if ($supArea > 0 && $areaFilter <= 0) {
                $areaFilter = $supArea;
            }
        }

        $rows = $this->predictionModel->getRanking((int)$edition->id, $companyFilter, $areaFilter);

        $this->view('admin/prode/ranking', [
            'edition' => $edition,
            'rows' => $rows,
            'companies' => $companies,
            'company_id' => $companyFilter,
            'area_id' => $areaFilter,
            'areas' => $areas,
            'total_matches' => $this->editionModel->countMatches((int)$edition->id),
        ]);
    }

    public function exportCsv() {
        $edition = $this->editionModel->getActiveEdition();
        if (!$edition) {
            redirect('prodeAdmin/ranking');
        }
        $companyFilter = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
        $areaFilter = (int)($_GET['area_id'] ?? 0);
        if (isSupervisor()) {
            $companyFilter = adminCompanyId();
            $supArea = supervisorAreaId();
            if ($supArea > 0 && $areaFilter <= 0) {
                $areaFilter = $supArea;
            }
        }
        $rows = $this->predictionModel->getRanking((int)$edition->id, $companyFilter, $areaFilter, 5000);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="prode_ranking_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Puesto', 'Empleado', 'Empresa', 'Área', 'Puntos', 'Exactos', 'Resultado', 'Completados', 'Estado'], ';');
        $rank = 1;
        foreach ($rows as $r) {
            fputcsv($out, [
                $rank++,
                $r->full_name,
                $r->company_name ?? '',
                $r->area_name ?? '',
                (int)$r->total_points,
                (int)$r->exact_hits,
                (int)$r->result_hits,
                (int)$r->predictions_count,
                prode_entry_status_label($r->entry_status ?? 'draft'),
            ], ';');
        }
        fclose($out);
        exit;
    }

    public function matches() {
        $edition = $this->editionModel->getActiveEdition();
        if (!$edition) {
            $_SESSION['flash_error'] = 'No hay edición PRODE.';
            redirect('admin/dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $action = $_POST['action'] ?? '';
            if ($action === 'save_result') {
                $matchId = (int)($_POST['match_id'] ?? 0);
                $home = (int)($_POST['home_score_actual'] ?? -1);
                $away = (int)($_POST['away_score_actual'] ?? -1);
                if ($matchId > 0 && $home >= 0 && $away >= 0) {
                    $this->scoring->saveResultAndRecalculate($matchId, $home, $away);
                    $_SESSION['flash_success'] = 'Resultado guardado y puntajes actualizados.';
                } else {
                    $_SESSION['flash_error'] = 'Resultado inválido.';
                }
            } elseif ($action === 'save_kickoff') {
                $matchId = (int)($_POST['match_id'] ?? 0);
                $raw = trim((string)($_POST['kickoff_at'] ?? ''));
                $dt = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw, new DateTimeZone(prode_timezone()));
                if ($matchId > 0 && $dt) {
                    $this->editionModel->updateKickoff($matchId, $dt->format('Y-m-d H:i:s'));
                    $_SESSION['flash_success'] = 'Horario actualizado (hora Argentina).';
                } else {
                    $_SESSION['flash_error'] = 'Horario inválido.';
                }
            } elseif ($action === 'edition_status') {
                $st = $_POST['edition_status'] ?? '';
                if ($this->editionModel->updateStatus((int)$edition->id, $st)) {
                    $_SESSION['flash_success'] = 'Estado del PRODE actualizado.';
                }
            }
            $g = preg_replace('/[^A-L]/', '', strtoupper($_POST['group'] ?? ''));
            redirect('prodeAdmin/matches' . ($g ? '?group=' . $g : ''));
        }

        $this->editionModel->lockStartedMatches();
        $groupCode = preg_replace('/[^A-L]/', '', strtoupper($_GET['group'] ?? 'A'));
        if ($groupCode === '') {
            $groupCode = 'A';
        }

        $this->view('admin/prode/matches', [
            'edition' => $edition,
            'groups' => $this->editionModel->getGroups((int)$edition->id),
            'active_group' => $groupCode,
            'matches' => $this->editionModel->getAllMatches((int)$edition->id, $groupCode),
        ]);
    }

    private function view($view, $data = []) {
        extract($data, EXTR_SKIP);
        require APPROOT . '/views/' . $view . '.php';
    }
}
