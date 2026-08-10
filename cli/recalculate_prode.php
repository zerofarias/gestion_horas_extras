<?php
/**
 * Recalcula puntajes del PRODE para todos los partidos finalizados.
 * Uso: php cli/recalculate_prode.php [--edition=wc2026]
 */
if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

define('APPROOT', dirname(__DIR__) . '/app');
require_once APPROOT . '/config/config.php';
require_once APPROOT . '/helpers/prode_helper.php';
require_once APPROOT . '/models/Database.php';
require_once APPROOT . '/models/ProdeEdition.php';
require_once APPROOT . '/models/ProdePrediction.php';
require_once APPROOT . '/services/ProdeScoringService.php';

if (!prode_is_ready()) {
    fwrite(STDERR, "Ejecutá migration_prode_wc2026.sql primero.\n");
    exit(1);
}

$slug = 'wc2026';
foreach ($argv as $arg) {
    if (strpos($arg, '--edition=') === 0) {
        $slug = substr($arg, 10);
    }
}

$editionModel = new ProdeEdition();
$edition = $editionModel->getBySlug($slug);
if (!$edition) {
    fwrite(STDERR, "Edición no encontrada: $slug\n");
    exit(1);
}

$scoring = new ProdeScoringService();
$db = new Database();
$db->query("SELECT id FROM prode_matches WHERE edition_id = :eid AND status = 'finished'");
$db->bind(':eid', (int)$edition->id);
$matches = $db->resultSet();

$done = 0;
foreach ($matches as $m) {
    if ($scoring->recalculateMatch((int)$m->id)) {
        $done++;
    }
}

echo json_encode([
    'ok' => true,
    'edition' => $slug,
    'matches_recalculated' => $done,
], JSON_PRETTY_PRINT) . "\n";
