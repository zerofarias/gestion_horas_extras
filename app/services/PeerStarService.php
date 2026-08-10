<?php

class PeerStarService {
    const MAX_NET_PER_MONTH = 5;
    const MAX_SINGLE_DELTA = 5;

    private $ledger;
    private $scores;
    private $userModel;

    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->ledger = new PeerStarLedger($this->db);
        $this->scores = new PeerStarScore($this->db);
        $this->userModel = new User();
    }

    public function isReady() {
        return $this->ledger->isReady();
    }

    public function getBalance($userId) {
        return $this->scores->getTotal((int)$userId);
    }

    public function transfer($giverId, $receiverId, $delta, $category, $comment = '') {
        if (!$this->isReady()) {
            return ['ok' => false, 'message' => 'Ejecutá migration_peer_stars.sql (ver MIGRATIONS.md #28).'];
        }

        $giverId = (int)$giverId;
        $receiverId = (int)$receiverId;
        $delta = (int)$delta;

        if ($giverId <= 0 || $receiverId <= 0) {
            return ['ok' => false, 'message' => 'Empleados inválidos.'];
        }
        if ($giverId === $receiverId) {
            return ['ok' => false, 'message' => 'No podés asignarte estrellas a vos mismo.'];
        }
        if ($delta === 0 || abs($delta) > self::MAX_SINGLE_DELTA) {
            return ['ok' => false, 'message' => 'La cantidad debe ser entre 1 y 5 (positiva o negativa).'];
        }

        $cats = peer_star_categories();
        if (!isset($cats[$category])) {
            return ['ok' => false, 'message' => 'Categoría inválida.'];
        }

        $giver = $this->userModel->getUserById($giverId);
        $receiver = $this->userModel->getUserById($receiverId);
        if (!$giver || !$receiver || !(int)$giver->is_active || !(int)$receiver->is_active) {
            return ['ok' => false, 'message' => 'Ambos empleados deben estar activos.'];
        }
        if ((int)$giver->company_id !== (int)$receiver->company_id) {
            return ['ok' => false, 'message' => 'Solo podés reconocer compañeros de tu misma empresa.'];
        }
        if ($giver->role !== 'empleado') {
            return ['ok' => false, 'message' => 'Solo empleados pueden dar reconocimiento entre pares.'];
        }
        if ($receiver->role !== 'empleado') {
            return ['ok' => false, 'message' => 'Solo podés reconocer a otros empleados.'];
        }

        $periodYm = date('Y-m');
        $currentNet = $this->ledger->getNetForPairInPeriod($giverId, $receiverId, $periodYm);
        $newNet = $currentNet + $delta;
        if ($newNet > self::MAX_NET_PER_MONTH) {
            return ['ok' => false, 'message' => 'Límite mensual: podés dar como máximo +' . self::MAX_NET_PER_MONTH . ' estrellas a esta persona (ya diste ' . max(0, $currentNet) . ').'];
        }
        if ($newNet < -self::MAX_NET_PER_MONTH) {
            return ['ok' => false, 'message' => 'Límite mensual: podés quitar como máximo ' . self::MAX_NET_PER_MONTH . ' estrellas a esta persona este mes.'];
        }

        $comment = trim(strip_tags((string)$comment));
        if (strlen($comment) > 255) {
            $comment = substr($comment, 0, 255);
        }

        $this->db->beginTransaction();
        try {
            if (!$this->ledger->insert($giverId, $receiverId, $delta, $category, $comment, $periodYm)) {
                throw new RuntimeException('No se pudo registrar el movimiento.');
            }
            if (!$this->scores->addDelta($receiverId, $delta)) {
                throw new RuntimeException('No se pudo actualizar el saldo.');
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'message' => 'No se pudo registrar el movimiento.'];
        }

        $verb = $delta > 0 ? 'diste' : 'restaste';
        return [
            'ok' => true,
            'message' => 'Reconocimiento registrado: ' . $verb . ' ' . abs($delta) . ' estrella(s). Es anónimo para el resto del equipo.',
        ];
    }

    public function getColleaguesForGiver($giverId, $companyId) {
        $all = $this->userModel->getUsersByCompany((int)$companyId);
        $out = [];
        foreach ($all as $u) {
            if ((int)$u->id === (int)$giverId) {
                continue;
            }
            if ($u->role !== 'empleado' || !(int)$u->is_active) {
                continue;
            }
            $out[] = $u;
        }
        return $out;
    }
}
