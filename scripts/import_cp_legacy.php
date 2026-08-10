<?php
/**
 * Importación histórica desde BD legacy CasaPav (tabla datos, login).
 *
 * Uso:
 *   php scripts/import_cp_legacy.php --legacy-db=paviotti_casapav [--dry-run] [--limit=5000] [--only-closed]
 *
 * Requiere migraciones #30 y #31 en la BD RRHH.
 * estado legacy: 0 = pendiente, 1 = cerrado (con lote).
 */
if (php_sapi_name() !== 'cli') {
    die("Solo CLI\n");
}

$appRoot = dirname(__DIR__) . '/app';
require_once $appRoot . '/bootstrap.php';
spl_autoload_register(function ($className) use ($appRoot) {
    foreach (['models', 'controllers', 'services'] as $dir) {
        $path = $appRoot . '/' . $dir . '/' . $className . '.php';
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
}, true, true);

$opts = getopt('', ['legacy-db:', 'dry-run', 'limit:', 'only-closed', 'include-pending']);
$legacyDb = $opts['legacy-db'] ?? '';
$dryRun = isset($opts['dry-run']);
$limit = isset($opts['limit']) ? max(1, (int)$opts['limit']) : 50000;
$onlyClosed = !isset($opts['include-pending']);

if ($legacyDb === '') {
    fwrite(STDERR, "Indicá --legacy-db=nombre_base_legacy\n");
    exit(1);
}

$cp = new CpTask();
$db = new Database();
$companyModel = new Company();
$companyId = (int)($companyModel->getIdByName('Casa Paviotti') ?? 0);
if ($companyId <= 0) {
    fwrite(STDERR, "No se encontró empresa Casa Paviotti.\n");
    exit(1);
}

$legacy = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . $legacyDb . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "RRHH empresa CP id=$companyId | Legacy=$legacyDb | " . ($dryRun ? 'DRY-RUN' : 'LIVE') . "\n";

$typeByLegacy = [];
foreach ($cp->getTaskTypes(false) as $t) {
    $typeByLegacy[(int)$t->legacy_code] = (int)$t->id;
}

$userMap = [];
$normalizeName = function ($s) {
    $s = mb_strtolower(trim((string)$s));
    $s = str_replace([',', '.', '-'], ' ', $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
};
$nameKey = function ($s) use ($normalizeName) {
    $parts = array_filter(explode(' ', $normalizeName($s)));
    sort($parts, SORT_STRING);
    return implode(' ', $parts);
};

$logins = $legacy->query('SELECT id, fullnombre FROM login')->fetchAll(PDO::FETCH_OBJ);
$db->query('SELECT id, full_name FROM users WHERE company_id = ?');
$users = $db->resultSet([$companyId]);
$byExact = [];
$bySorted = [];
foreach ($users as $u) {
    $byExact[$normalizeName($u->full_name)] = (int)$u->id;
    $bySorted[$nameKey($u->full_name)] = (int)$u->id;
}
$unmapped = [];
foreach ($logins as $lg) {
    $raw = trim($lg->fullnombre);
    $uid = $byExact[$normalizeName($raw)] ?? $bySorted[$nameKey($raw)] ?? 0;
    if ($uid > 0) {
        $userMap[(int)$lg->id] = $uid;
    } else {
        $unmapped[] = $raw;
    }
}
echo 'Mapeos login→users: ' . count($userMap) . ' / ' . count($logins) . "\n";
if ($unmapped) {
    echo "Sin mapear (" . count($unmapped) . "): " . implode(', ', array_slice($unmapped, 0, 8));
    if (count($unmapped) > 8) {
        echo '...';
    }
    echo "\n";
}

$where = $onlyClosed ? 'estado = 1 AND lote > 0' : '1=1';
$sql = "SELECT * FROM datos WHERE $where ORDER BY lote ASC, id_datos ASC LIMIT " . (int)$limit;
$rows = $legacy->query($sql)->fetchAll(PDO::FETCH_OBJ);
echo 'Filas a procesar: ' . count($rows) . "\n";

$closureCache = [];
$stats = ['entries' => 0, 'external' => 0, 'closures' => 0, 'skipped' => 0, 'errors' => 0];

if (!$dryRun) {
    $db->beginTransaction();
}

try {
    foreach ($rows as $d) {
        $legacyUserId = (int)$d->id_usuario;
        $userId = $userMap[$legacyUserId] ?? 0;
        if ($userId <= 0) {
            $stats['skipped']++;
            continue;
        }

        $legacyTarea = (int)$d->id_tarea;
        $amount = (float)$d->importe;
        if ($amount <= 0) {
            $stats['skipped']++;
            continue;
        }

        $activityDate = $d->fecha_tarea;
        if (!$activityDate || $activityDate === '0000-00-00') {
            $activityDate = $d->fecha_carga;
        }
        $isHoliday = (int)($d->feriado ?? 0) === 1 ? 1 : 0;
        $mult = $isHoliday ? 2.0 : 1.0;
        $status = ((int)$d->estado === 1 && (int)$d->lote > 0) ? 'closed' : 'pending';
        $closureId = null;

        if ($status === 'closed') {
            $lotKey = (int)$d->lote;
            if (!isset($closureCache[$lotKey])) {
                if ($dryRun) {
                    $closureCache[$lotKey] = -$lotKey;
                } else {
                    $db->query('SELECT id FROM cp_task_closures WHERE company_id = ? AND lot_number = ? LIMIT 1');
                    $existing = $db->single([$companyId, $lotKey]);
                    if ($existing) {
                        $closureCache[$lotKey] = (int)$existing->id;
                    } else {
                        $fechaLote = $d->fecha_lote && $d->fecha_lote !== '0000-00-00' ? $d->fecha_lote : date('Y-m-d');
                        $db->query('INSERT INTO cp_task_closures (company_id, lot_number, closed_by, closed_at, total_amount, iva_rate, iva_amount, final_amount, notes)
                            VALUES (?,?,?, ?, 0, 0.19, 0, 0, ?)');
                        $db->execute([
                            $companyId,
                            $lotKey,
                            null,
                            $fechaLote . ' 12:00:00',
                            'Import legacy lote ' . $lotKey,
                        ]);
                        $closureCache[$lotKey] = (int)$db->lastInsertId();
                        $stats['closures']++;
                    }
                }
            }
            $closureId = $closureCache[$lotKey] ?? null;
        }

        if ($legacyTarea === 11) {
            if (!$cp->externalTablesReady()) {
                $stats['skipped']++;
                continue;
            }
            if ($dryRun) {
                $stats['external']++;
                continue;
            }
            $extCoId = 0;
            $db->query('SELECT id FROM cp_external_companies WHERE company_id = ? ORDER BY id ASC LIMIT 1');
            $co = $db->single([$companyId]);
            if ($co) {
                $extCoId = (int)$co->id;
            }
            if ($extCoId <= 0) {
                $stats['errors']++;
                continue;
            }
            $db->query('INSERT INTO cp_external_entries (
                company_id, user_id, external_company_id, task_label, activity_date,
                amount, amount_base, is_holiday, holiday_multiplier, status, closure_id, comment
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $ok = $db->execute([
                $companyId, $userId, $extCoId,
                trim((string)($d->tarea ?: 'Externa')),
                $activityDate,
                $amount, $amount,
                $isHoliday, $mult,
                $status, $closureId,
                'legacy:' . (int)$d->id_datos,
            ]);
            if ($ok) {
                $stats['external']++;
            } else {
                $stats['errors']++;
            }
            continue;
        }

        $taskTypeId = $typeByLegacy[$legacyTarea] ?? 0;
        if ($taskTypeId <= 0) {
            $stats['skipped']++;
            continue;
        }

        $deceasedCode = trim((string)($d->cod_extinto ?? ''));
        if ($deceasedCode === '' || $deceasedCode === '0') {
            $deceasedCode = 'LEGACY-' . (int)$d->id_datos;
        } else {
            $deceasedCode = (string)$deceasedCode;
        }
        $deceasedName = trim((string)($d->extinto ?? ''));
        if ($deceasedName === '') {
            $deceasedName = trim((string)($d->paciente ?? ''));
        }

        if ($dryRun) {
            $stats['entries']++;
            continue;
        }

        $db->query('SELECT id FROM cp_task_entries WHERE company_id = ? AND user_id = ? AND task_type_id = ? AND deceased_code = ? LIMIT 1');
        if ($db->single([$companyId, $userId, $taskTypeId, $deceasedCode])) {
            $stats['skipped']++;
            continue;
        }

        $meta = [
            'legacy_id_datos' => (int)$d->id_datos,
            'legacy_lote' => (int)$d->lote,
            'lugar' => $d->lugar ?? null,
            'comentario' => $d->comentario ?? null,
        ];
        $id = $cp->addEntry([
            'company_id' => $companyId,
            'user_id' => $userId,
            'task_type_id' => $taskTypeId,
            'activity_date' => $activityDate,
            'amount' => $amount,
            'amount_base' => $amount,
            'is_holiday' => $isHoliday,
            'holiday_multiplier' => $mult,
            'status' => $status,
            'deceased_code' => $deceasedCode,
            'deceased_name' => $deceasedName ?: null,
            'meta' => $meta,
        ]);
        if (!$id) {
            $stats['errors']++;
            continue;
        }
        if ($closureId) {
            $db->query('UPDATE cp_task_entries SET closure_id = ? WHERE id = ?');
            $db->execute([$closureId, $id]);
        }
        $stats['entries']++;
    }

    if (!$dryRun) {
        foreach ($closureCache as $lot => $cid) {
            if ($cid <= 0) {
                continue;
            }
            $db->query('SELECT COALESCE(SUM(amount),0) AS t FROM cp_task_entries WHERE closure_id = ?');
            $t1 = (float)($db->single([$cid])->t ?? 0);
            $t2 = 0;
            if ($cp->externalTablesReady()) {
                $db->query('SELECT COALESCE(SUM(amount),0) AS t FROM cp_external_entries WHERE closure_id = ?');
                $t2 = (float)($db->single([$cid])->t ?? 0);
            }
            $total = $t1 + $t2;
            $iva = round($total * 0.19, 2);
            $final = round($total + $iva, 2);
            $db->query('UPDATE cp_task_closures SET total_amount = ?, iva_amount = ?, final_amount = ? WHERE id = ?');
            $db->execute([$total, $iva, $final, $cid]);
        }
        $db->commit();
    }
} catch (Throwable $e) {
    if (!$dryRun) {
        $db->rollBack();
    }
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

echo json_encode($stats, JSON_PRETTY_PRINT) . "\n";
echo "Listo.\n";
