<?php

/**
 * Separa un nombre completo en nombres y apellidos (formato habitual: Nombre(s) Apellido(s)).
 * - "Apellido, Nombre" → apellidos antes de la coma, nombres después.
 * - 2 palabras → nombre + apellido.
 * - 3 palabras → dos primeras = nombres, última = apellido.
 * - 4+ palabras → mitad inicial = nombres, mitad final = apellidos.
 */
function split_full_name($fullName) {
    $fullName = trim(preg_replace('/\s+/u', ' ', (string)$fullName));
    if ($fullName === '') {
        return ['first_name' => '', 'last_name' => ''];
    }
    if (strpos($fullName, ',') !== false) {
        $chunks = array_map('trim', explode(',', $fullName, 2));
        return [
            'first_name' => $chunks[1] ?? '',
            'last_name'  => $chunks[0] ?? '',
        ];
    }
    $parts = preg_split('/\s+/u', $fullName);
    $n = count($parts);
    if ($n === 1) {
        return ['first_name' => $parts[0], 'last_name' => ''];
    }
    if ($n === 2) {
        return ['first_name' => $parts[0], 'last_name' => $parts[1]];
    }
    if ($n === 3) {
        return [
            'first_name' => $parts[0] . ' ' . $parts[1],
            'last_name'  => $parts[2],
        ];
    }
    $splitAt = (int) floor($n / 2);
    return [
        'first_name' => implode(' ', array_slice($parts, 0, $splitAt)),
        'last_name'  => implode(' ', array_slice($parts, $splitAt)),
    ];
}

/** Arma full_name para guardar en BD: "Nombres Apellidos". */
function join_full_name($firstName, $lastName) {
    return trim(trim((string)$firstName) . ' ' . trim((string)$lastName));
}

/** Nombres desde POST + full_name derivado. */
function name_from_post(array $post) {
    $first = isset($post['first_name']) ? trim((string)$post['first_name']) : '';
    $last  = isset($post['last_name']) ? trim((string)$post['last_name']) : '';
    return [
        'first_name' => $first,
        'last_name'  => $last,
        'full_name'  => join_full_name($first, $last),
    ];
}

/** Resuelve nombres para mostrar en formulario (POST, array o user). */
function resolve_name_fields($source) {
    if (is_array($source)) {
        if (isset($source['first_name']) || isset($source['last_name'])) {
            return [
                'first_name' => trim((string)($source['first_name'] ?? '')),
                'last_name'  => trim((string)($source['last_name'] ?? '')),
            ];
        }
        if (!empty($source['full_name'])) {
            return split_full_name($source['full_name']);
        }
    }
    if (is_object($source)) {
        if (isset($source->first_name) || isset($source->last_name)) {
            return [
                'first_name' => trim((string)($source->first_name ?? '')),
                'last_name'  => trim((string)($source->last_name ?? '')),
            ];
        }
        if (!empty($source->full_name)) {
            return split_full_name($source->full_name);
        }
    }
    return ['first_name' => '', 'last_name' => ''];
}

/** Normaliza nombre para comparar (sin acentos, minúsculas, sin puntuación). */
function normalize_person_name($name) {
    $name = mb_strtolower(trim((string)$name), 'UTF-8');
    if ($name === '') {
        return '';
    }
    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if ($ascii !== false) {
            $name = $ascii;
        }
    }
    $name = preg_replace('/[^a-z0-9\s]/u', ' ', $name);
    return preg_replace('/\s+/u', ' ', trim($name));
}

/** Tokens ordenados de un nombre (sirve para "Duarte Milagros" vs "Milagros Duarte"). */
function person_name_tokens($name) {
    $norm = normalize_person_name($name);
    if ($norm === '') {
        return [];
    }
    $tokens = preg_split('/\s+/u', $norm);
    sort($tokens);
    return $tokens;
}

/**
 * Similitud 0–1 entre dos nombres (Jaccard sobre palabras).
 */
function person_name_match_score($nameA, $nameB) {
    $ta = person_name_tokens($nameA);
    $tb = person_name_tokens($nameB);
    if (empty($ta) || empty($tb)) {
        return 0.0;
    }
    if ($ta === $tb) {
        return 1.0;
    }
    $inter = count(array_intersect($ta, $tb));
    $union = count(array_unique(array_merge($ta, $tb)));
    return $union > 0 ? $inter / $union : 0.0;
}

/**
 * Sugiere usuarios del sistema que coinciden con un nombre del reloj.
 * @return array<int, array{user: object, score: float}>
 */
function suggest_users_for_clock_name($clockName, array $users, $minScore = 0.55, $limit = 3) {
    $suggestions = [];
    foreach ($users as $user) {
        $full = is_object($user) ? ($user->full_name ?? '') : ($user['full_name'] ?? '');
        $score = person_name_match_score($clockName, $full);
        if ($score >= $minScore) {
            $suggestions[] = ['user' => $user, 'score' => $score];
        }
    }
    usort($suggestions, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    return array_slice($suggestions, 0, $limit);
}
