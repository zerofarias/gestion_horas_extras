<?php

class DeceasedLookupService {
    private $pdo;

    public function isConfigured() {
        $cfg = function_exists('extintos_db_config') ? extintos_db_config() : [];
        return !empty($cfg['host']) && !empty($cfg['name']);
    }

    /** Tabla legacy según tipo de formulario (como CasaPavExtrasv11 t1/t2 vs t7). */
    public function tableForFormKey($formKey) {
        $formKey = trim((string)$formKey);
        if ($formKey === 'tanato') {
            return function_exists('setting_string')
                ? setting_string('cp_extintos_table_tanato', 'extintos')
                : 'extintos';
        }
        if (in_array($formKey, ['armar', 'realizar', 'metalica', 'viajes', 'cremacion'], true)) {
            return function_exists('setting_string')
                ? setting_string('cp_extintos_table_sepulio', 'extintosH')
                : 'extintosH';
        }
        return function_exists('setting_string')
            ? setting_string('cp_extintos_table_sepulio', 'extintosH')
            : 'extintosH';
    }

    private function connection() {
        if ($this->pdo !== null) {
            return $this->pdo;
        }
        if (!$this->isConfigured()) {
            return null;
        }
        $cfg = extintos_db_config();
        $dsn = 'mysql:host=' . $cfg['host'] . ';dbname=' . $cfg['name'] . ';charset=utf8mb4';
        $this->pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        return $this->pdo;
    }

    /**
     * Últimos extintos para el select (legacy: ORDER BY cod DESC LIMIT N).
     * @return array<int, object{code:string,name:string}>
     */
    private function allowedExtintosTables() {
        $tables = ['extintos', 'extintosH'];
        if (function_exists('setting_string')) {
            foreach (['cp_extintos_table_sepulio', 'cp_extintos_table_tanato'] as $key) {
                $name = setting_string($key, '');
                if (preg_match('/^[a-zA-Z0-9_]{1,64}$/', $name)) {
                    $tables[] = $name;
                }
            }
        }
        return array_values(array_unique($tables));
    }

    private function resolveExtintosTable($table) {
        $table = trim((string)$table);
        return in_array($table, $this->allowedExtintosTables(), true) ? $table : null;
    }

    public function searchRecent($limit = 50, $tableHint = null) {
        $pdo = $this->connection();
        if (!$pdo) {
            return [];
        }
        $limit = max(1, min(100, (int)$limit));
        $tables = [];
        if ($tableHint) {
            $resolved = $this->resolveExtintosTable($tableHint);
            if ($resolved) {
                $tables[] = $resolved;
            }
        }
        foreach ($this->allowedExtintosTables() as $t) {
            $tables[] = $t;
        }
        $tables = array_values(array_unique($tables));

        foreach ($tables as $table) {
            $rows = $this->fetchFromTable($pdo, $table, $limit);
            if ($rows) {
                return $rows;
            }
        }
        return [];
    }

    private function fetchFromTable(PDO $pdo, $table, $limit) {
        if (!$this->resolveExtintosTable($table)) {
            return [];
        }
        $sql = 'SELECT COD_EXTINTO AS code, apellido AS name
            FROM `' . $table . '`
            ORDER BY COD_EXTINTO DESC
            LIMIT ' . (int)$limit;
        try {
            $stmt = $pdo->query($sql);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : [];
            return $this->fixNames($rows);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function findByCode($code) {
        $code = trim((string)$code);
        if ($code === '') {
            return null;
        }
        $pdo = $this->connection();
        if (!$pdo) {
            return null;
        }
        foreach ($this->allowedExtintosTables() as $table) {
            try {
                $stmt = $pdo->prepare('SELECT COD_EXTINTO AS code, apellido AS name FROM `' . $table . '` WHERE COD_EXTINTO = ? LIMIT 1');
                $stmt->execute([$code]);
                $row = $stmt->fetch(PDO::FETCH_OBJ);
                if ($row) {
                    return $this->fixRowName($row);
                }
            } catch (PDOException $e) {
                continue;
            }
        }
        return null;
    }

    private function fixNames(array $rows) {
        foreach ($rows as $row) {
            $this->fixRowName($row);
        }
        return $rows;
    }

    private function fixRowName($row) {
        if ($row && isset($row->name) && function_exists('fix_utf8_mojibake')) {
            $row->name = fix_utf8_mojibake($row->name);
        }
        return $row;
    }
}
