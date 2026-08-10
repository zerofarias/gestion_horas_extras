<?php

function peer_stars_is_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $db = new Database();
        $db->query("SHOW TABLES LIKE 'peer_star_ledger'");
        $ready = (bool)$db->single();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

function peer_star_categories() {
    return [
        'objetivo' => 'Cumplió un objetivo',
        'buena_accion' => 'Buena acción',
        'extraordinario' => 'Acto extraordinario',
        'negativo' => 'Conducta a mejorar',
        'otro' => 'Otro',
    ];
}

function peer_star_category_label($key) {
    $cats = peer_star_categories();
    return $cats[$key] ?? $key;
}

function peer_star_anonymous_giver_label() {
    return 'Un compañero';
}
