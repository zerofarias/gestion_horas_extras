<?php
/**
 * Aplica horarios oficiales FIFA (convertidos a hora Argentina) a partidos existentes.
 * Uso: php scripts/update_prode_kickoffs.php
 */
define('APPROOT', dirname(__DIR__) . '/app');
require_once APPROOT . '/config/config.php';
require_once APPROOT . '/helpers/prode_helper.php';
require_once APPROOT . '/helpers/prode_fixture_helper.php';
require_once APPROOT . '/models/Database.php';

$db = new Database();
$db->query("SHOW TABLES LIKE 'prode_matches'");
if (!$db->single()) {
    fwrite(STDERR, "Tabla prode_matches no existe.\n");
    exit(1);
}

$db->query('SELECT m.id, g.code AS group_code, ht.name AS home_name, at.name AS away_name, m.kickoff_at
    FROM prode_matches m
    JOIN prode_groups g ON g.id = m.group_id
    JOIN prode_teams ht ON ht.id = m.home_team_id
    JOIN prode_teams at ON at.id = m.away_team_id
    ORDER BY g.code, m.match_number');
$matches = $db->resultSet();

$updated = 0;
$missing = [];

foreach ($matches as $m) {
    $kick = prode_official_kickoff_for_match($m->group_code, $m->home_name, $m->away_name);
    if (!$kick) {
        $missing[] = $m->group_code . ' ' . $m->home_name . ' vs ' . $m->away_name;
        continue;
    }

    $now = prode_now_argentina()->format('Y-m-d H:i:s');
    $locked = ($kick <= $now) ? 1 : 0;

    $db->query('UPDATE prode_matches SET kickoff_at = :kick, predictions_locked = :locked WHERE id = :id');
    $db->bind(':kick', $kick);
    $db->bind(':locked', $locked);
    $db->bind(':id', (int)$m->id);
    $db->execute();
    $updated++;
}

echo json_encode([
    'ok' => true,
    'updated' => $updated,
    'missing' => $missing,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
