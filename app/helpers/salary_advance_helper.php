<?php

function salary_advance_is_ready() {
    return class_exists('SalaryAdvance') && (new SalaryAdvance())->isSchemaReady();
}

function salary_advance_module_enabled() {
    if (!salary_advance_is_ready()) {
        return false;
    }
    if (!function_exists('setting_bool')) {
        return false;
    }
    return setting_bool('salary_advance_enabled', true);
}

function salary_advance_max_annual() {
    return max(1, min(12, setting_int('salary_advance_max_annual', 2)));
}

function salary_advance_max_salary_pct() {
    return max(1, min(100, setting_int('salary_advance_max_salary_pct', 50)));
}

function salary_advance_max_installments_employee() {
    return max(1, min(24, setting_int('salary_advance_max_installments_employee', 2)));
}

function salary_advance_max_installments_hr() {
    return max(1, min(24, setting_int('salary_advance_max_installments_hr', 6)));
}

function salary_advance_one_pending_only() {
    return setting_bool('salary_advance_one_pending_only', true);
}

function salary_advance_require_reference_salary() {
    return false;
}

function salary_advance_installments_ready() {
    return salary_advance_is_ready() && (new SalaryAdvance())->installmentsSchemaReady();
}

function salary_advance_finalizado_ready() {
    return salary_advance_installments_ready() && (new SalaryAdvance())->finalizadoSchemaReady();
}

function salary_advance_month_label($ym) {
    $ym = trim((string)$ym);
    if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
        return $ym;
    }
    $months = [
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
    ];
    [$year, $month] = explode('-', $ym);
    return ($months[$month] ?? $month) . ' ' . $year;
}

function salary_advance_default_months($count, $startYm = null) {
    $count = max(1, (int)$count);
    $start = $startYm && preg_match('/^\d{4}-\d{2}$/', $startYm)
        ? $startYm
        : date('Y-m');
    $out = [];
    $dt = DateTime::createFromFormat('Y-m-d', $start . '-01');
    if (!$dt) {
        $dt = new DateTime('first day of this month');
    }
    for ($i = 0; $i < $count; $i++) {
        $out[] = $dt->format('Y-m');
        $dt->modify('+1 month');
    }
    return $out;
}

/**
 * @return float[]
 */
function salary_advance_split_amounts($total, $count) {
    $total = round((float)$total, 2);
    $count = max(1, (int)$count);
    $base = floor(($total / $count) * 100) / 100;
    $amounts = array_fill(0, $count, $base);
    $assigned = $base * $count;
    $amounts[$count - 1] = round($total - ($assigned - $base), 2);
    return $amounts;
}

function salary_advance_format_installments_for_json(array $rows) {
    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'id' => (int)$row->id,
            'installment_number' => (int)$row->installment_number,
            'due_month' => $row->due_month,
            'due_month_label' => salary_advance_month_label($row->due_month),
            'amount' => (float)$row->amount,
            'amount_fmt' => salary_advance_format_money($row->amount),
            'is_deducted' => !empty($row->is_deducted),
            'deducted_at_fmt' => !empty($row->deducted_at) ? date('d/m/Y H:i', strtotime($row->deducted_at)) : '',
            'notes' => $row->notes ?? '',
        ];
    }
    return $out;
}

function salary_advance_min_amount() {
    return max(0, (float)setting_int('salary_advance_min_amount', 1));
}

function salary_advance_format_money($amount) {
    return '$' . number_format((float)$amount, 2, ',', '.');
}

function salary_advance_status_badge_class($status) {
    switch ($status) {
        case 'Aprobado':
            return 'bg-success';
        case 'Finalizado':
            return 'bg-secondary';
        case 'Rechazado':
            return 'bg-danger';
        default:
            return 'bg-warning text-dark';
    }
}

function salary_advance_statuses() {
    return ['Pendiente', 'Aprobado', 'Rechazado', 'Finalizado'];
}

function require_salary_advance_employee() {
    requireEmployeeRole();
    if (!salary_advance_module_enabled()) {
        $_SESSION['flash_error'] = 'El módulo de adelantos de sueldo no está disponible.';
        redirect('employee/index');
    }
    require_employee_portal_feature('salary_advance');
}

function require_salary_advance_admin() {
    requireAdminOnly();
    if (!salary_advance_module_enabled()) {
        $_SESSION['flash_error'] = 'El módulo de adelantos de sueldo no está habilitado.';
        redirect('admin/dashboard');
    }
}

/**
 * @return array{ok:bool,message:string}
 */
function salary_advance_validate_submission($userId, $amount, $installments) {
    $userId = (int)$userId;
    $amount = (float)$amount;
    $installments = (int)$installments;
    $model = new SalaryAdvance();

    if (!salary_advance_module_enabled()) {
        return ['ok' => false, 'message' => 'El módulo de adelantos no está habilitado.'];
    }

    $minAmount = salary_advance_min_amount();
    if ($amount < $minAmount) {
        return ['ok' => false, 'message' => 'El monto mínimo es ' . salary_advance_format_money($minAmount) . '.'];
    }

    $maxInst = salary_advance_max_installments_employee();
    if ($installments < 1 || $installments > $maxInst) {
        return ['ok' => false, 'message' => 'Elegí entre 1 y ' . $maxInst . ' cuotas de devolución.'];
    }

    if (salary_advance_one_pending_only() && $model->hasPendingByUser($userId)) {
        return ['ok' => false, 'message' => 'Ya tenés una solicitud pendiente de revisión.'];
    }

    $year = (int)date('Y');
    $used = $model->countByUserInYear($userId, $year);
    $maxAnnual = salary_advance_max_annual();
    if ($used >= $maxAnnual) {
        return ['ok' => false, 'message' => 'Alcanzaste el límite de ' . $maxAnnual . ' solicitudes en ' . $year . '.'];
    }

    return ['ok' => true, 'message' => ''];
}

function salary_advance_all_settings_meta() {
    return [
        'salary_advance_enabled' => [
            'label' => 'Módulo habilitado',
            'hint' => 'Activa o desactiva adelantos de sueldo en admin y empleados.',
            'type' => 'bool',
        ],
        'salary_advance_max_annual' => [
            'label' => 'Solicitudes anuales máximas',
            'hint' => 'Por empleado y año calendario (pendientes + aprobadas).',
            'type' => 'int',
            'min' => 1,
            'max' => 12,
        ],
        'salary_advance_max_salary_pct' => [
            'label' => '% máximo del sueldo de referencia',
            'hint' => 'Tope del monto solicitado respecto al sueldo declarado por el empleado.',
            'type' => 'int',
            'min' => 1,
            'max' => 100,
        ],
        'salary_advance_max_installments_employee' => [
            'label' => 'Cuotas máximas (empleado)',
            'hint' => 'Opciones al solicitar el adelanto.',
            'type' => 'int',
            'min' => 1,
            'max' => 24,
        ],
        'salary_advance_max_installments_hr' => [
            'label' => 'Cuotas máximas (RRHH al aprobar)',
            'hint' => 'Tope al usar override de cuotas en la aprobación.',
            'type' => 'int',
            'min' => 1,
            'max' => 24,
        ],
        'salary_advance_one_pending_only' => [
            'label' => 'Solo una solicitud pendiente',
            'hint' => 'Bloquea nuevas solicitudes si hay una en estado Pendiente.',
            'type' => 'bool',
        ],
        'salary_advance_require_reference_salary' => [
            'label' => 'Exigir sueldo de referencia',
            'hint' => 'Campo obligatorio y validación del porcentaje máximo.',
            'type' => 'bool',
        ],
        'salary_advance_min_amount' => [
            'label' => 'Monto mínimo (pesos)',
            'hint' => 'Importe mínimo por solicitud.',
            'type' => 'int',
            'min' => 0,
            'max' => 99999999,
        ],
    ];
}
