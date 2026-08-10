<?php

class PeerStarAdminController {
    public function __construct() {
        if (!hasRole('admin')) {
            redirect('login');
        }
        if (!peer_stars_is_ready()) {
            $_SESSION['flash_error'] = 'Ejecutá migration_peer_stars.sql (ver MIGRATIONS.md #28).';
            redirect('admin/dashboard');
        }
        ensureAdminCompanySession();
    }

    public function ranking() {
        $companyId = requireAdminCompany('admin/dashboard');
        $areaId = (int)($_GET['area_id'] ?? 0);
        $areas = (new Area())->getAvailableForCompany($companyId);
        $rows = (new PeerStarLedger())->getRanking($companyId, $areaId);
        $this->view('admin/training/peer_stars_ranking', [
            'rows' => $rows,
            'areas' => $areas,
            'company_id' => $companyId,
            'area_id' => $areaId,
        ]);
    }

    public function exportCsv() {
        $companyId = requireAdminCompany('peerStarAdmin/ranking');
        $areaId = (int)($_GET['area_id'] ?? 0);
        $rows = (new PeerStarLedger())->getRanking($companyId, $areaId, 5000);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="ranking_reconocimiento_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Empleado', 'Área', 'Puntos reconocimiento'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [$r->full_name, $r->area_name ?? '', (int)$r->total_score], ';');
        }
        fclose($out);
        exit();
    }

    private function view($view, $data = []) {
        if (file_exists(APPROOT . '/views/' . $view . '.php')) {
            extract($data, EXTR_SKIP);
            require APPROOT . '/views/' . $view . '.php';
        } else {
            die('Vista no encontrada: ' . $view);
        }
    }
}
