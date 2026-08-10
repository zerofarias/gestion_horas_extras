<?php
/**
 * Descarga banderas SVG para el PRODE (circle-flags).
 * Uso: php scripts/download_prode_flags.php
 */
$groups = require dirname(__DIR__) . '/app/data/prode_wc2026_groups.php';
$codes = ['default'];
foreach ($groups as $teams) {
    foreach ($teams as $t) {
        $codes[] = strtolower($t['flag']);
    }
}
$codes = array_unique($codes);
$dir = dirname(__DIR__) . '/public/img/flags';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$map = ['eng' => 'gb-eng', 'sco' => 'gb-sct'];
$ok = 0;
foreach ($codes as $code) {
    if ($code === 'default') {
        continue;
    }
    $remote = $map[$code] ?? $code;
    $url = 'https://hatscripts.github.io/circle-flags/flags/' . $remote . '.svg';
    $dest = $dir . '/' . $code . '.svg';
    $data = @file_get_contents($url);
    if ($data !== false && strlen($data) > 50) {
        file_put_contents($dest, $data);
        $ok++;
        echo "OK $code\n";
    } else {
        echo "FAIL $code ($url)\n";
    }
}
echo "Descargadas: $ok\n";
