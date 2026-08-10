<?php

class ProdeController {
    private $editionModel;
    private $predictionModel;

    public function __construct() {
        requireEmployeeRole();
        require_employee_portal_feature('prode');
        if (!prode_is_ready()) {
            $_SESSION['flash_error'] = 'El PRODE no está disponible. Ejecutá migration_prode_wc2026.sql.';
            redirect('employee/index');
        }
        $this->editionModel = new ProdeEdition();
        $this->predictionModel = new ProdePrediction();
    }

    public function index() {
        $this->editionModel->lockStartedMatches();
        $edition = $this->editionModel->getActiveEdition();
        if (!$edition) {
            $_SESSION['flash_error'] = 'No hay edición de PRODE activa.';
            redirect('employee/index');
        }

        $userId = (int)$_SESSION['user_id'];
        $entry = $this->predictionModel->getOrCreateEntry((int)$edition->id, $userId);
        $groups = $this->editionModel->getGroups((int)$edition->id);
        $activeGroup = preg_replace('/[^A-L]/', '', strtoupper($_GET['group'] ?? 'A'));
        if ($activeGroup === '') {
            $activeGroup = 'A';
        }
        $groupRow = $this->editionModel->getGroupByCode((int)$edition->id, $activeGroup);
        if (!$groupRow && !empty($groups)) {
            $groupRow = $groups[0];
            $activeGroup = $groupRow->code;
        }

        $matches = $groupRow ? $this->editionModel->getMatchesForGroup((int)$groupRow->id) : [];
        $predMap = $this->predictionModel->getPredictionsMap($userId, (int)$edition->id);
        $totalMatches = $this->editionModel->countMatches((int)$edition->id);
        $filled = $this->predictionModel->countFilledPredictions($userId, (int)$edition->id);

        $groupCodes = array_map(function ($g) {
            return $g->code;
        }, $groups);
        $groupIndex = array_search($activeGroup, $groupCodes, true);
        $groupFilled = 0;
        foreach ($matches as $m) {
            $pred = $predMap[(int)$m->id] ?? null;
            if (prode_prediction_is_saved($pred)) {
                $groupFilled++;
            }
        }

        $this->view('employee/prode/index', [
            'edition' => $edition,
            'entry' => $entry,
            'groups' => $groups,
            'active_group' => $activeGroup,
            'group_index' => $groupIndex !== false ? $groupIndex : 0,
            'group_total' => count($groups),
            'prev_group' => ($groupIndex !== false && $groupIndex > 0) ? $groupCodes[$groupIndex - 1] : null,
            'next_group' => ($groupIndex !== false && $groupIndex < count($groupCodes) - 1) ? $groupCodes[$groupIndex + 1] : null,
            'group_filled' => $groupFilled,
            'group_match_count' => count($matches),
            'matches' => $matches,
            'pred_map' => $predMap,
            'total_matches' => $totalMatches,
            'filled_count' => $filled,
            'csrf_token' => csrf_token(),
        ]);
    }

    public function savePrediction() {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
            exit;
        }
        $token = $_POST['csrf_token'] ?? '';
        if ($token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            echo json_encode(['ok' => false, 'message' => 'Sesión expirada. Recargá la página.']);
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $matchId = (int)($_POST['match_id'] ?? 0);
        $home = $_POST['home_score'] ?? null;
        $away = $_POST['away_score'] ?? null;

        if ($matchId <= 0 || $home === '' || $away === '' || !is_numeric($home) || !is_numeric($away)) {
            echo json_encode(['ok' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $match = $this->editionModel->getMatchById($matchId);
        if (!$match || prode_is_match_locked($match)) {
            echo json_encode(['ok' => false, 'message' => 'Este partido ya no admite cambios']);
            exit;
        }

        $edition = $this->editionModel->getById((int)$match->edition_id);
        if (!$edition || !prode_edition_allows_play($edition)) {
            echo json_encode(['ok' => false, 'message' => 'El PRODE no está abierto para cargar pronósticos']);
            exit;
        }
        if ((int)$edition->id !== (int)(($active = $this->editionModel->getActiveEdition()) ? $active->id : 0)) {
            echo json_encode(['ok' => false, 'message' => 'Edición no válida']);
            exit;
        }

        $existing = $this->predictionModel->getPrediction($userId, $matchId);
        if ($existing && prode_prediction_is_saved($existing)) {
            echo json_encode(['ok' => false, 'message' => 'Este pronóstico ya está guardado y no se puede modificar']);
            exit;
        }

        if (!$this->predictionModel->upsertPrediction($userId, $matchId, $home, $away)) {
            echo json_encode(['ok' => false, 'message' => 'No se pudo guardar']);
            exit;
        }

        $filled = $this->predictionModel->countFilledPredictions($userId, (int)$edition->id);
        echo json_encode([
            'ok' => true,
            'saved_at' => date('H:i:s'),
            'filled_count' => $filled,
            'total_matches' => $this->editionModel->countMatches((int)$edition->id),
        ]);
        exit;
    }

    public function submit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('prode/index');
        }
        csrf_verify();

        $edition = $this->editionModel->getActiveEdition();
        if (!$edition || !prode_edition_allows_play($edition)) {
            $_SESSION['flash_error'] = 'El PRODE no está abierto.';
            redirect('prode/index');
        }

        $userId = (int)$_SESSION['user_id'];
        $total = $this->editionModel->countMatches((int)$edition->id);
        $result = $this->predictionModel->submitAll((int)$edition->id, $userId, $total);
        $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
        redirect('prode/index');
    }

    private function view($view, $data = []) {
        extract($data, EXTR_SKIP);
        require APPROOT . '/views/' . $view . '.php';
    }
}
