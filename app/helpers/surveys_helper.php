<?php

function surveys_is_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $db = new Database();
        $db->query("SHOW TABLES LIKE 'surveys'");
        $ready = (bool)$db->single();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

function survey_question_types() {
    return [
        'short_text' => 'Texto corto',
        'long_text' => 'Párrafo',
        'single_choice' => 'Opción única',
        'multiple_choice' => 'Opción múltiple',
        'scale' => 'Escala 1–5',
        'date' => 'Fecha',
    ];
}

function survey_status_label($status) {
    $map = ['draft' => 'Borrador', 'published' => 'Publicada', 'closed' => 'Cerrada'];
    return $map[$status] ?? $status;
}
