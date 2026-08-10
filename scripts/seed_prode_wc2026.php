<?php
/**
 * Carga equipos, grupos y 72 partidos del Mundial 2026.
 * Uso: php scripts/seed_prode_wc2026.php [nombre_bd_opcional]
 */
if (!empty($argv[1])) {
    define('DB_NAME', $argv[1]);
}
define('APPROOT', dirname(__DIR__) . '/app');
require_once APPROOT . '/config/config.php';
require_once APPROOT . '/helpers/prode_helper.php';
require_once APPROOT . '/helpers/prode_fixture_helper.php';
require_once APPROOT . '/models/Database.php';

$groupsData = require APPROOT . '/data/prode_wc2026_groups.php';
$db = new Database();

$db->query("SHOW TABLES LIKE 'prode_editions'");
if (!$db->single()) {
    fwrite(STDERR, "Ejecutá migration_prode_wc2026.sql primero.\n");
    exit(1);
}

$db->query('SELECT id FROM prode_editions WHERE slug = :slug');
$db->bind(':slug', 'wc2026');
$existing = $db->single();
if ($existing) {
    echo "Edición wc2026 ya existe (id={$existing->id}). Omitiendo seed.\n";
    exit(0);
}

$db->query('INSERT INTO prode_editions (slug, title, status, groups_only, starts_on, ends_on)
    VALUES (:slug, :title, :status, 1, :starts, :ends)');
$db->bind(':slug', 'wc2026');
$db->bind(':title', 'Copa del mundo 2026 — Fase de grupos');
$db->bind(':status', 'open');
$db->bind(':starts', '2026-06-11');
$db->bind(':ends', '2026-06-24');
$db->execute();
$editionId = (int)$db->lastInsertId();

$teamIdsBySlot = [];
$groupIds = [];
$sort = 0;
foreach ($groupsData as $code => $teams) {
    $db->query('INSERT INTO prode_groups (edition_id, code, sort_order) VALUES (:eid, :code, :ord)');
    $db->bind(':eid', $editionId);
    $db->bind(':code', $code);
    $db->bind(':ord', $sort++);
    $db->execute();
    $groupId = (int)$db->lastInsertId();
    $groupIds[$code] = $groupId;
    $teamIdsBySlot[$code] = [];

    $slot = 1;
    foreach ($teams as $t) {
        $db->query('INSERT INTO prode_teams (edition_id, name, flag_code) VALUES (:eid, :name, :flag)');
        $db->bind(':eid', $editionId);
        $db->bind(':name', $t['name']);
        $db->bind(':flag', $t['flag']);
        $db->execute();
        $tid = (int)$db->lastInsertId();
        $teamIdsBySlot[$code][$slot] = $tid;

        $db->query('INSERT INTO prode_group_teams (group_id, team_id, slot) VALUES (:gid, :tid, :slot)');
        $db->bind(':gid', $groupId);
        $db->bind(':tid', $tid);
        $db->bind(':slot', $slot);
        $db->execute();
        $slot++;
    }
}

/** Round-robin estándar 4 equipos: 1v2, 3v4, 4v1, 1v3, 2v4, 2v3 */
$pairings = [[1, 2], [3, 4], [4, 1], [1, 3], [2, 4], [2, 3]];

foreach ($groupsData as $code => $teams) {
    $gTeams = $teamIdsBySlot[$code];
    $gid = $groupIds[$code];
    $matchNum = 1;
    foreach ($pairings as $pair) {
        $homeName = $teams[$pair[0] - 1]['name'];
        $awayName = $teams[$pair[1] - 1]['name'];
        $kickoff = prode_official_kickoff_for_match($code, $homeName, $awayName);
        if (!$kickoff) {
            fwrite(STDERR, "Sin horario oficial: grupo $code $homeName vs $awayName\n");
            exit(1);
        }

        $db->query('INSERT INTO prode_matches (edition_id, group_id, match_number, home_team_id, away_team_id, kickoff_at)
            VALUES (:eid, :gid, :mnum, :home, :away, :kick)');
        $db->bind(':eid', $editionId);
        $db->bind(':gid', $gid);
        $db->bind(':mnum', $matchNum++);
        $db->bind(':home', $gTeams[$pair[0]]);
        $db->bind(':away', $gTeams[$pair[1]]);
        $db->bind(':kick', $kickoff);
        $db->execute();
    }
}

echo json_encode([
    'ok' => true,
    'edition_id' => $editionId,
    'groups' => count($groupsData),
    'matches' => count($groupsData) * count($pairings),
], JSON_PRETTY_PRINT) . "\n";
