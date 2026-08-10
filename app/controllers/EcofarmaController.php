<?php

class EcofarmaController {
    private $api;

    public function __construct() {
        if (!hasRole('admin')) {
            redirect('login');
        }
        $this->api = new ClockApiClient();
    }

    public function index() {
        $sucRes = $this->api->getEcofarmaSucursales();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $filters = $this->readFiltersFromRequest();
            redirect('ecofarma/index?' . http_build_query($this->filtersToQuery($filters)));
        }

        $filters = $this->readFiltersFromRequest();
        $resumen = null;
        $detalle = null;

        if ($this->hasValidQuery($filters)) {
            $resumen = $this->fetchResumen($filters);
            if (($filters['vista'] ?? 'resumen') === 'detalle' && ($resumen['ok'] ?? false)) {
                $detalle = $this->fetchFacturacion($filters);
            }
        }

        $this->view('admin/ecofarma_comisiones', [
            'sucursales' => $sucRes['ok'] ? $sucRes['sucursales'] : [],
            'sucursales_error' => $sucRes['ok'] ? null : ($sucRes['error'] ?? 'No se pudieron cargar las farmacias.'),
            'filters' => $filters,
            'resumen' => $resumen,
            'detalle' => $detalle,
            'default_obra_social' => function_exists('setting_string') ? setting_string('ecofarma_default_obra_social', '999900') : (defined('ECOFARMA_DEFAULT_OBRA_SOCIAL') ? ECOFARMA_DEFAULT_OBRA_SOCIAL : '999900'),
            'default_comision_pct' => function_exists('setting_int') ? setting_int('ecofarma_default_comision_pct', 7) : (defined('ECOFARMA_DEFAULT_COMISION_PCT') ? ECOFARMA_DEFAULT_COMISION_PCT : 7),
        ]);
    }

    /** Export CSV — resumen por operador (principal). */
    public function exportResumen() {
        $filters = $this->readFiltersFromRequest();
        if (!$this->hasValidQuery($filters)) {
            $_SESSION['flash_error'] = 'Completá farmacia y rango de fechas antes de exportar.';
            redirect('ecofarma/index');
        }

        $result = $this->fetchResumen($filters);
        if (!$result['ok']) {
            $_SESSION['flash_error'] = $result['error'];
            redirect('ecofarma/index?' . http_build_query($this->filtersToQuery($filters)));
        }

        $label = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $result['meta']['sucursal_filtro'] ?? 'sucursal');
        $filename = 'ecofarma_comisiones_' . $label . '_' . $filters['fecha_desde'] . '_' . $filters['fecha_hasta'] . '.csv';

        $this->sendCsvHeaders($filename);
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Operador', 'Cant. líneas', 'Total ventas', 'Subtotal ventas', 'Total descuento',
            '% comisión', 'Comisión',
        ], ';');

        foreach ($result['operadores'] as $row) {
            fputcsv($out, [
                $row['operador'] ?? '',
                (int)($row['cantidad_lineas'] ?? 0),
                (float)($row['total_ventas'] ?? 0),
                (float)($row['subtotal_ventas'] ?? 0),
                (float)($row['total_descuento'] ?? 0),
                (float)($row['porcentaje_comision'] ?? $filters['porcentaje_comision']),
                (float)($row['comision'] ?? 0),
            ], ';');
        }

        $t = $result['totales'];
        fputcsv($out, [], ';');
        fputcsv($out, [
            'TOTAL GENERAL',
            (int)($t['cantidad_lineas'] ?? 0),
            (float)($t['total_ventas'] ?? 0),
            '',
            '',
            (float)($t['porcentaje_comision'] ?? $filters['porcentaje_comision']),
            (float)($t['comision'] ?? 0),
        ], ';');

        fclose($out);
        exit;
    }

    /** Export CSV — detalle línea por línea. */
    public function exportExcel() {
        $filters = $this->readFiltersFromRequest();
        if (!$this->hasValidQuery($filters)) {
            $_SESSION['flash_error'] = 'Completá farmacia y rango de fechas antes de exportar.';
            redirect('ecofarma/index');
        }

        $result = $this->fetchFacturacion($filters);
        if (!$result['ok']) {
            $_SESSION['flash_error'] = $result['error'];
            redirect('ecofarma/index?' . http_build_query($this->filtersToQuery(array_merge($filters, ['vista' => 'detalle']))));
        }

        $label = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $result['meta']['sucursal_filtro'] ?? 'sucursal');
        $filename = 'ecofarma_detalle_' . $label . '_' . $filters['fecha_desde'] . '_' . $filters['fecha_hasta'] . '.csv';

        $this->sendCsvHeaders($filename);
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Emision', 'Tipo', 'Comprobante', 'Sucursal', 'Operador', 'Producto',
            'Cantidad', 'Precio unitario', 'Precio real', 'Subtotal', 'IVA',
            'Desc. importe', 'Desc. %', 'Total', 'Importe ACOS', 'ID Global',
        ], ';');

        foreach ($result['items'] as $row) {
            fputcsv($out, [
                $row['Emision'] ?? '',
                $row['Tipo'] ?? '',
                $row['comprobante'] ?? '',
                $row['Sucursal'] ?? '',
                $row['Operador'] ?? '',
                $row['Producto'] ?? '',
                isset($row['CantDecimal']) ? (float)$row['CantDecimal'] : '',
                isset($row['PrecioUnitario']) ? (float)$row['PrecioUnitario'] : '',
                isset($row['PrecioReal']) ? (float)$row['PrecioReal'] : '',
                isset($row['Subtotal']) ? (float)$row['Subtotal'] : '',
                isset($row['IVA']) ? (float)$row['IVA'] : '',
                isset($row['DesImporte']) ? (float)$row['DesImporte'] : '',
                isset($row['DesPorcentaje']) ? (float)$row['DesPorcentaje'] : '',
                isset($row['Total']) ? (float)$row['Total'] : '',
                isset($row['ImporteACOS']) ? (float)$row['ImporteACOS'] : '',
                $row['IDGlobal_Efectivo'] ?? '',
            ], ';');
        }

        fclose($out);
        exit;
    }

    private function sendCsvHeaders($filename) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    private function readFiltersFromRequest() {
        $src = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
        $vista = trim($src['vista'] ?? 'resumen');
        if (!in_array($vista, ['resumen', 'detalle'], true)) {
            $vista = 'resumen';
        }
        $pct = trim($src['porcentaje_comision'] ?? '');
        if ($pct === '') {
            $pct = (string)(function_exists('setting_int') ? setting_int('ecofarma_default_comision_pct', 7) : (defined('ECOFARMA_DEFAULT_COMISION_PCT') ? ECOFARMA_DEFAULT_COMISION_PCT : 7));
        }

        return [
            'fecha_desde' => trim($src['fecha_desde'] ?? ''),
            'fecha_hasta' => trim($src['fecha_hasta'] ?? ''),
            'sucursal_id' => (int)($src['sucursal_id'] ?? 0),
            'id_obra_social' => trim($src['id_obra_social'] ?? (function_exists('setting_string') ? setting_string('ecofarma_default_obra_social', '999900') : (defined('ECOFARMA_DEFAULT_OBRA_SOCIAL') ? ECOFARMA_DEFAULT_OBRA_SOCIAL : '999900'))),
            'porcentaje_comision' => $pct,
            'vista' => $vista,
        ];
    }

    private function filtersToQuery(array $filters) {
        return [
            'fecha_desde' => $filters['fecha_desde'],
            'fecha_hasta' => $filters['fecha_hasta'],
            'sucursal_id' => $filters['sucursal_id'],
            'id_obra_social' => $filters['id_obra_social'],
            'porcentaje_comision' => $filters['porcentaje_comision'],
            'vista' => $filters['vista'] ?? 'resumen',
        ];
    }

    private function hasValidQuery(array $filters) {
        return $filters['sucursal_id'] > 0
            && $filters['fecha_desde'] !== ''
            && $filters['fecha_hasta'] !== '';
    }

    private function fetchResumen(array $filters) {
        return $this->api->getEcofarmaResumenOperadores(
            $filters['fecha_desde'],
            $filters['fecha_hasta'],
            $filters['sucursal_id'],
            $filters['id_obra_social'],
            $filters['porcentaje_comision']
        );
    }

    private function fetchFacturacion(array $filters) {
        return $this->api->getEcofarmaFacturacionAcos(
            $filters['fecha_desde'],
            $filters['fecha_hasta'],
            $filters['sucursal_id'],
            $filters['id_obra_social']
        );
    }

    private function view($view, $data = []) {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        extract($data);
        require_once APPROOT . '/views/' . $view . '.php';
    }
}
