<?php

function prode_fifa_team_aliases() {
    return [
        'mexico' => 'México',
        'south africa' => 'Sudáfrica',
        'south korea' => 'Corea del Sur',
        'korea republic' => 'Corea del Sur',
        'czechia' => 'Chequia',
        'canada' => 'Canadá',
        'bosnia and herzegovina' => 'Bosnia y Herzegovina',
        'qatar' => 'Catar',
        'switzerland' => 'Suiza',
        'brazil' => 'Brasil',
        'morocco' => 'Marruecos',
        'haiti' => 'Haití',
        'scotland' => 'Escocia',
        'usa' => 'Estados Unidos',
        'united states' => 'Estados Unidos',
        'paraguay' => 'Paraguay',
        'australia' => 'Australia',
        'türkiye' => 'Turquía',
        'turkiye' => 'Turquía',
        'turkey' => 'Turquía',
        'germany' => 'Alemania',
        'curaçao' => 'Curazao',
        'curacao' => 'Curazao',
        'ivory coast' => 'Costa de Marfil',
        'cote divoire' => 'Costa de Marfil',
        'ecuador' => 'Ecuador',
        'netherlands' => 'Países Bajos',
        'japan' => 'Japón',
        'sweden' => 'Polonia',
        'poland' => 'Polonia',
        'tunisia' => 'Túnez',
        'belgium' => 'Bélgica',
        'egypt' => 'Egipto',
        'iran' => 'Irán',
        'new zealand' => 'Nueva Zelanda',
        'spain' => 'España',
        'cape verde' => 'Cabo Verde',
        'saudi arabia' => 'Arabia Saudita',
        'uruguay' => 'Uruguay',
        'france' => 'Francia',
        'senegal' => 'Senegal',
        'norway' => 'Noruega',
        'iraq' => 'Bolivia',
        'bolivia' => 'Bolivia',
        'argentina' => 'Argentina',
        'algeria' => 'Argelia',
        'austria' => 'Austria',
        'jordan' => 'Jordania',
        'portugal' => 'Portugal',
        'uzbekistan' => 'Uzbekistán',
        'colombia' => 'Colombia',
        'congo dr' => 'Rep. Democrática del Congo',
        'democratic republic of the congo' => 'Rep. Democrática del Congo',
        'england' => 'Inglaterra',
        'croatia' => 'Croacia',
        'ghana' => 'Ghana',
        'panama' => 'Panamá',
    ];
}

function prode_normalize_team_name($name) {
    $key = strtolower(trim(preg_replace('/\s+/', ' ', (string)$name)));
    $aliases = prode_fifa_team_aliases();
    return $aliases[$key] ?? trim((string)$name);
}

function prode_est_to_argentina_kickoff($dateYmd, $timeHi) {
    $est = new DateTimeZone('America/New_York');
    $arg = new DateTimeZone(prode_timezone());
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $dateYmd . ' ' . $timeHi, $est);
    if (!$dt) {
        return null;
    }
    return $dt->setTimezone($arg)->format('Y-m-d H:i:s');
}

function prode_fixture_lookup_key($groupCode, $homeName, $awayName) {
    return strtoupper($groupCode) . '|' . prode_normalize_team_name($homeName) . '|' . prode_normalize_team_name($awayName);
}

function prode_build_official_kickoff_index() {
    static $index = null;
    if ($index !== null) {
        return $index;
    }
    $index = [];
    $rows = require APPROOT . '/data/prode_wc2026_fifa_fixture_est.php';
    foreach ($rows as $row) {
        $kick = prode_est_to_argentina_kickoff($row['d'], $row['t']);
        if (!$kick) {
            continue;
        }
        $home = prode_normalize_team_name($row['h']);
        $away = prode_normalize_team_name($row['a']);
        $index[prode_fixture_lookup_key($row['g'], $home, $away)] = $kick;
    }
    return $index;
}

function prode_official_kickoff_for_match($groupCode, $homeName, $awayName) {
    $index = prode_build_official_kickoff_index();
    $key = prode_fixture_lookup_key($groupCode, $homeName, $awayName);
    if (isset($index[$key])) {
        return $index[$key];
    }
    $rev = prode_fixture_lookup_key($groupCode, $awayName, $homeName);
    return $index[$rev] ?? null;
}
