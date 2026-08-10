<?php

function prode_is_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $db = new Database();
        $db->query("SHOW TABLES LIKE 'prode_editions'");
        $ready = (bool)$db->single();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

function prode_flag_url($flagCode) {
    $code = strtolower(preg_replace('/[^a-z0-9_-]/i', '', (string)$flagCode));
    if ($code === '') {
        $code = 'default';
    }
    $rel = 'img/flags/' . $code . '.svg';
    $abs = dirname(APPROOT) . '/public/' . $rel;
    if (!is_file($abs)) {
        $rel = 'img/flags/default.svg';
    }
    return URLROOT . '/' . $rel;
}

function prode_match_result_sign($home, $away) {
    $home = (int)$home;
    $away = (int)$away;
    if ($home > $away) {
        return 1;
    }
    if ($home < $away) {
        return -1;
    }
    return 0;
}

function prode_timezone() {
    return 'America/Argentina/Cordoba';
}

function prode_now_argentina() {
    return new DateTimeImmutable('now', new DateTimeZone(prode_timezone()));
}

function prode_parse_kickoff($kickoffAt) {
    if ($kickoffAt === null || $kickoffAt === '') {
        return null;
    }
    try {
        return new DateTimeImmutable((string)$kickoffAt, new DateTimeZone(prode_timezone()));
    } catch (Throwable $e) {
        return null;
    }
}

/** Horario de inicio en hora Argentina (kickoff_at se guarda siempre en esa zona). */
function prode_format_kickoff($kickoffAt, $withDate = true) {
    $dt = prode_parse_kickoff($kickoffAt);
    if (!$dt) {
        return '—';
    }
    return $dt->format($withDate ? 'd/m/Y H:i' : 'd/m H:i') . ' hs (ARG)';
}

function prode_kickoff_input_value($kickoffAt) {
    $dt = prode_parse_kickoff($kickoffAt);
    return $dt ? $dt->format('Y-m-d\TH:i') : '';
}

function prode_prediction_is_saved($prediction) {
    if (!$prediction) {
        return false;
    }
    return $prediction->home_score_pred !== null && $prediction->away_score_pred !== null;
}

function prode_is_match_locked($match) {
    if (!$match) {
        return true;
    }
    if (!empty($match->predictions_locked)) {
        return true;
    }
    $kick = prode_parse_kickoff($match->kickoff_at ?? null);
    if ($kick && $kick <= prode_now_argentina()) {
        return true;
    }
    return false;
}

function prode_edition_allows_play($edition) {
    return $edition && ($edition->status ?? '') === 'open';
}

function prode_portal_is_open() {
    if (!prode_is_ready() || !employee_portal_can('prode')) {
        return false;
    }
    $edition = (new ProdeEdition())->getActiveEdition();
    return prode_edition_allows_play($edition);
}

function prode_edition_status_label($status) {
    $map = [
        'upcoming' => 'Próximamente',
        'open' => 'Abierto',
        'closed' => 'Cerrado',
    ];
    return $map[$status] ?? $status;
}

function prode_entry_status_label($status) {
    return $status === 'submitted' ? 'Confirmado' : 'Borrador';
}
