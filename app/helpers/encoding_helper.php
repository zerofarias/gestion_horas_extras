<?php

/**
 * Corrige texto UTF-8 dañado (ej. "Cl├¡nica" → "Clínica") por importación con charset incorrecto.
 */
function fix_utf8_mojibake($text) {
    if ($text === null || $text === '') {
        return (string)$text;
    }
    if (!is_string($text)) {
        $text = (string)$text;
    }
    if (strpos($text, '├') === false && strpos($text, 'Ã') === false) {
        return $text;
    }

    static $from = null;
    static $to = null;
    if ($from === null) {
        $from = [
            '├¡', '├▒', '├®', '├í', '├│', '├║',
            '├ü', '├ë', '├ì', '├ô', '├Ü', '├æ',
        ];
        $to = [
            'í', 'ñ', 'é', 'á', 'ó', 'ú',
            'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ',
        ];
    }

    $fixed = str_replace($from, $to, $text);

    if (strpos($fixed, 'Ã') !== false) {
        $try = @mb_convert_encoding($fixed, 'UTF-8', 'ISO-8859-1');
        if ($try && mb_check_encoding($try, 'UTF-8')) {
            $fixed = $try;
        }
    }

    return $fixed;
}
