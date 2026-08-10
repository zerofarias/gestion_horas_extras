<?php
/**
 * Cliente HTTP para API Relojes v0.2.0 (JWT).
 * Documentación: http://gpaviotti.com.ar:6333/docs#/
 */
class ClockApiClient {
    private $jwtToken = null;

    public function getToken($forceRefresh = false) {
        if (!$forceRefresh && $this->jwtToken) {
            return $this->jwtToken;
        }

        $loginUrl = function_exists('clock_api_login_url') ? clock_api_login_url() : (defined('CLOCK_API_LOGIN_URL') ? CLOCK_API_LOGIN_URL : '');
        $email    = function_exists('setting_string') ? setting_string('clock_api_email', defined('CLOCK_API_EMAIL') ? CLOCK_API_EMAIL : '') : (defined('CLOCK_API_EMAIL') ? CLOCK_API_EMAIL : '');
        $password = function_exists('setting_string') ? setting_string('clock_api_password', defined('CLOCK_API_PASSWORD') ? CLOCK_API_PASSWORD : '') : (defined('CLOCK_API_PASSWORD') ? CLOCK_API_PASSWORD : '');

        if ($loginUrl === '' || $email === '' || $password === '') {
            return null;
        }

        $ch = curl_init($loginUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['email' => $email, 'password' => $password]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);
        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200) {
            return null;
        }

        $decoded = json_decode($response, true);
        if (empty($decoded['accesoOk']) || empty($decoded['token'])) {
            return null;
        }

        $this->jwtToken = $decoded['token'];
        return $this->jwtToken;
    }

    /**
     * @return array{ok:bool,status:int,data:?array,error:?string}
     */
    public function get($path, array $query = []) {
        $base = function_exists('clock_api_base_url') ? clock_api_base_url() : (defined('CLOCK_API_BASE_URL') ? rtrim(CLOCK_API_BASE_URL, '/') : '');
        if ($base === '') {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'CLOCK_API_BASE_URL no configurada.'];
        }

        $url = $base . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $token = $this->getToken();
        if (!$token) {
            return ['ok' => false, 'status' => 401, 'data' => null, 'error' => 'No se pudo autenticar en la API Relojes.'];
        }

        $doRequest = function ($bearer) use ($url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $bearer,
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $body      = curl_exec($ch);
            $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            return [$body, $httpCode, $curlError];
        };

        list($body, $httpCode, $curlError) = $doRequest($token);

        if ($httpCode === 401 || $httpCode === 403) {
            $token = $this->getToken(true);
            if ($token) {
                list($body, $httpCode, $curlError) = $doRequest($token);
            }
        }

        if ($curlError) {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'Error de conexión: ' . $curlError];
        }

        $decoded = $body ? json_decode($body, true) : null;
        if ($httpCode !== 200) {
            $detail = is_array($decoded) && isset($decoded['detail'])
                ? (is_string($decoded['detail']) ? $decoded['detail'] : json_encode($decoded['detail']))
                : '';
            return [
                'ok'     => false,
                'status' => $httpCode,
                'data'   => $decoded,
                'error'  => 'La API respondió HTTP ' . $httpCode . ($detail ? ': ' . $detail : ''),
            ];
        }

        if ($body && json_last_error() !== JSON_ERROR_NONE) {
            return ['ok' => false, 'status' => $httpCode, 'data' => null, 'error' => 'JSON inválido en la respuesta.'];
        }

        return ['ok' => true, 'status' => $httpCode, 'data' => $decoded, 'error' => null];
    }

    /**
     * Nombre visible de una marcación (API 0.2.0: nombreApellido enriquecido).
     */
    public static function marcacionPersonName(array $m) {
        $name = trim($m['personName'] ?? '');
        if ($name === '') {
            $name = trim($m['nombreApellido'] ?? '');
        }
        return $name;
    }

    /**
     * @return array{error?:string,marcaciones?:array,total?:int,periodo?:array,descarga_info?:mixed}
     */
    public function getMarcaciones($startDate, $endDate, $employeeID = null, $deviceName = null) {
        $res = $this->get('/api/v1/marcaciones', array_filter([
            'start_time' => $startDate . 'T00:00:00',
            'end_time'   => $endDate   . 'T23:59:59',
            'employeeID' => $employeeID,
            'deviceName' => $deviceName,
        ]));

        if (!$res['ok']) {
            return ['error' => $res['error']];
        }

        $data = $res['data'];
        if (!is_array($data) || !isset($data['marcaciones']) || !is_array($data['marcaciones'])) {
            return ['error' => 'Respuesta inválida: falta clave marcaciones.'];
        }

        return [
            'marcaciones'    => $data['marcaciones'],
            'total'          => isset($data['total']) ? (int)$data['total'] : count($data['marcaciones']),
            'periodo'        => $data['periodo'] ?? null,
            'filtros'        => $data['filtros'] ?? null,
            'descarga_info'  => $data['descarga_info'] ?? null,
        ];
    }

    /**
     * @return array<string,string> codigo_reloj => nombreApellido
     */
    public function buildLegajoCodigoMap() {
        $res = $this->get('/api/v1/legajos');
        if (!$res['ok'] || !is_array($res['data'])) {
            return [];
        }

        $map = [];
        foreach ($res['data']['legajos'] ?? [] as $legajo) {
            $nombre = trim($legajo['nombreApellido'] ?? '');
            if ($nombre === '') {
                continue;
            }
            foreach ($legajo['detalles'] ?? [] as $det) {
                $codigo = trim((string)($det['codigo_reloj'] ?? ''));
                if ($codigo !== '') {
                    $map[$codigo] = $nombre;
                }
            }
        }
        return $map;
    }

    /**
     * @return string|null nombreApellido o null si no existe
     */
    public function getLegajoNombreByCodigo($codigo) {
        $codigo = trim((string)$codigo);
        if ($codigo === '') {
            return null;
        }

        $res = $this->get('/api/v1/legajos/por-codigo/' . rawurlencode($codigo));
        if (!$res['ok'] || !is_array($res['data'])) {
            return null;
        }

        $name = trim($res['data']['nombreApellido'] ?? '');
        return $name !== '' ? $name : null;
    }

    /**
     * Farmacias / sucursales Ecofarma (Plex).
     * @return array{ok:bool,sucursales:array,total:int,error:?string}
     */
    public function getEcofarmaSucursales($buscar = null) {
        $query = [];
        if ($buscar !== null && trim((string)$buscar) !== '') {
            $query['buscar'] = trim((string)$buscar);
        }
        $res = $this->get('/api/v1/ecofarma/sucursales', $query);
        if (!$res['ok']) {
            return ['ok' => false, 'sucursales' => [], 'total' => 0, 'error' => $res['error']];
        }
        $data = $res['data'] ?? [];
        $list = $data['sucursales'] ?? [];
        if (!is_array($list)) {
            $list = [];
        }
        return [
            'ok' => true,
            'sucursales' => $list,
            'total' => isset($data['total']) ? (int)$data['total'] : count($list),
            'error' => null,
        ];
    }

    /**
     * Facturación ACOS por sucursal y rango de fechas.
     * @return array{ok:bool,items:array,meta:array,error:?string}
     */
    public function getEcofarmaFacturacionAcos($fechaDesde, $fechaHasta, $sucursalId, $idObraSocial = null) {
        $fechaDesde = trim((string)$fechaDesde);
        $fechaHasta = trim((string)$fechaHasta);
        $sucursalId = (int)$sucursalId;
        if ($sucursalId <= 0) {
            return ['ok' => false, 'items' => [], 'meta' => [], 'error' => 'Seleccioná una farmacia.'];
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            return ['ok' => false, 'items' => [], 'meta' => [], 'error' => 'Fechas inválidas (use YYYY-MM-DD).'];
        }
        if ($fechaHasta < $fechaDesde) {
            return ['ok' => false, 'items' => [], 'meta' => [], 'error' => 'La fecha hasta no puede ser anterior a la fecha desde.'];
        }

        $obra = $idObraSocial !== null && trim((string)$idObraSocial) !== ''
            ? trim((string)$idObraSocial)
            : (function_exists('setting_string') ? setting_string('ecofarma_default_obra_social', '999900') : (defined('ECOFARMA_DEFAULT_OBRA_SOCIAL') ? ECOFARMA_DEFAULT_OBRA_SOCIAL : '999900'));

        $res = $this->get('/api/v1/ecofarma/facturacion-acos', [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'sucursal_id' => $sucursalId,
            'id_obra_social' => $obra,
        ]);

        if (!$res['ok']) {
            return ['ok' => false, 'items' => [], 'meta' => [], 'error' => $res['error']];
        }

        $data = $res['data'] ?? [];
        $items = $data['items'] ?? [];
        if (!is_array($items)) {
            $items = [];
        }

        return [
            'ok' => true,
            'items' => $items,
            'meta' => [
                'total' => isset($data['total']) ? (int)$data['total'] : count($items),
                'fecha_desde' => $data['fecha_desde'] ?? $fechaDesde,
                'fecha_hasta' => $data['fecha_hasta'] ?? $fechaHasta,
                'sucursal_id' => $data['sucursal_id'] ?? $sucursalId,
                'sucursal_filtro' => $data['sucursal_filtro'] ?? '',
                'id_obra_social' => $data['id_obra_social'] ?? $obra,
            ],
            'error' => null,
        ];
    }

    /**
     * Resumen por operador + comisión (vista principal de comisiones).
     * @return array{ok:bool,operadores:array,totales:array,meta:array,error:?string}
     */
    public function getEcofarmaResumenOperadores($fechaDesde, $fechaHasta, $sucursalId, $idObraSocial = null, $porcentajeComision = null) {
        $fechaDesde = trim((string)$fechaDesde);
        $fechaHasta = trim((string)$fechaHasta);
        $sucursalId = (int)$sucursalId;
        if ($sucursalId <= 0) {
            return ['ok' => false, 'operadores' => [], 'totales' => [], 'meta' => [], 'error' => 'Seleccioná una farmacia.'];
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            return ['ok' => false, 'operadores' => [], 'totales' => [], 'meta' => [], 'error' => 'Fechas inválidas (use YYYY-MM-DD).'];
        }
        if ($fechaHasta < $fechaDesde) {
            return ['ok' => false, 'operadores' => [], 'totales' => [], 'meta' => [], 'error' => 'La fecha hasta no puede ser anterior a la fecha desde.'];
        }

        $obra = $idObraSocial !== null && trim((string)$idObraSocial) !== ''
            ? trim((string)$idObraSocial)
            : (function_exists('setting_string') ? setting_string('ecofarma_default_obra_social', '999900') : (defined('ECOFARMA_DEFAULT_OBRA_SOCIAL') ? ECOFARMA_DEFAULT_OBRA_SOCIAL : '999900'));

        $pct = $porcentajeComision !== null && $porcentajeComision !== ''
            ? (float)$porcentajeComision
            : (float)(function_exists('setting_int') ? setting_int('ecofarma_default_comision_pct', 7) : (defined('ECOFARMA_DEFAULT_COMISION_PCT') ? ECOFARMA_DEFAULT_COMISION_PCT : 7));

        $res = $this->get('/api/v1/ecofarma/facturacion-acos/resumen-operadores', [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'sucursal_id' => $sucursalId,
            'id_obra_social' => $obra,
            'porcentaje_comision' => $pct,
        ]);

        if (!$res['ok']) {
            return ['ok' => false, 'operadores' => [], 'totales' => [], 'meta' => [], 'error' => $res['error']];
        }

        $data = $res['data'] ?? [];
        $operadores = $data['operadores'] ?? [];
        if (!is_array($operadores)) {
            $operadores = [];
        }

        return [
            'ok' => true,
            'operadores' => $operadores,
            'totales' => is_array($data['totales'] ?? null) ? $data['totales'] : [],
            'meta' => [
                'total_operadores' => isset($data['total_operadores']) ? (int)$data['total_operadores'] : count($operadores),
                'fecha_desde' => $data['fecha_desde'] ?? $fechaDesde,
                'fecha_hasta' => $data['fecha_hasta'] ?? $fechaHasta,
                'sucursal_id' => $data['sucursal_id'] ?? $sucursalId,
                'sucursal_filtro' => $data['sucursal_filtro'] ?? '',
                'id_obra_social' => $data['id_obra_social'] ?? $obra,
                'porcentaje_comision' => $data['porcentaje_comision'] ?? $pct,
            ],
            'error' => null,
        ];
    }
}
