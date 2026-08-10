<?php

class PeerStarController {
    private $service;

    public function __construct() {
        requireEmployeeRole();
        require_employee_portal_feature('peer_stars');
        $this->service = new PeerStarService();
        if (!$this->service->isReady()) {
            $_SESSION['flash_error'] = 'El módulo de reconocimiento no está instalado. Avisá a RRHH (migration_peer_stars.sql).';
            redirect('employee/index');
        }
    }

    public function index() {
        $userId = (int)$_SESSION['user_id'];
        $user = (new User())->getUserById($userId);
        $companyId = (int)($user->company_id ?? 0);
        $this->view('employee/peer_stars', [
            'balance' => $this->service->getBalance($userId),
            'history' => (new PeerStarLedger())->getReceivedForUser($userId, 40),
            'colleagues' => $this->service->getColleaguesForGiver($userId, $companyId),
            'categories' => peer_star_categories(),
            'max_single' => PeerStarService::MAX_SINGLE_DELTA,
            'max_net' => PeerStarService::MAX_NET_PER_MONTH,
        ]);
    }

    public function give() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('peerStar/index');
        }
        csrf_verify();
        $giverId = (int)$_SESSION['user_id'];
        $receiverId = (int)($_POST['receiver_id'] ?? 0);
        $amount = (int)($_POST['amount'] ?? 0);
        $direction = ($_POST['direction'] ?? 'give') === 'remove' ? -1 : 1;
        $delta = $amount * $direction;
        $category = trim($_POST['reason_category'] ?? 'buena_accion');
        $comment = trim($_POST['comment'] ?? '');

        $giver = (new User())->getUserById($giverId);
        $colleagueIds = array_map(function ($u) {
            return (int)$u->id;
        }, $this->service->getColleaguesForGiver($giverId, (int)($giver->company_id ?? 0)));
        if (!in_array($receiverId, $colleagueIds, true)) {
            $_SESSION['flash_error'] = 'Empleado no válido.';
            redirect('peerStar/index');
        }

        $result = $this->service->transfer($giverId, $receiverId, $delta, $category, $comment);
        $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
        redirect('peerStar/index');
    }

    private function view($view, $data = []) {
        if (file_exists(APPROOT . '/views/' . $view . '.php')) {
            require APPROOT . '/views/' . $view . '.php';
        } else {
            die('Vista no encontrada: ' . $view);
        }
    }
}
