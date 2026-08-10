<?php

class CpTaskPricingService {
    private $cpTask;
    private $holiday;

    public function __construct() {
        $this->cpTask = new CpTask();
        $this->holiday = new Holiday();
    }

    public function calculate($formKey, $userId, $companyId, array $input) {
        $type = $this->cpTask->getTaskTypeByFormKey($formKey);
        if (!$type) {
            return ['ok' => false, 'message' => 'Tipo de tarea no válido.'];
        }

        $activityDate = trim($input['activity_date'] ?? '');
        if ($activityDate === '') {
            return ['ok' => false, 'message' => 'Indicá la fecha de la tarea.'];
        }
        if (function_exists('cp_validate_activity_date')) {
            $dateErr = cp_validate_activity_date($activityDate);
            if ($dateErr) {
                return ['ok' => false, 'message' => $dateErr];
            }
        }

        if ((int)$type->is_manual_amount === 1) {
            return $this->calculateManual($type, $companyId, $input, $activityDate);
        }

        $rates = $this->cpTask->getRatesForUser($userId);
        if (!$rates) {
            return ['ok' => false, 'message' => 'No tenés tarifas cargadas. Pedí a RRHH que configure tus importes.'];
        }

        $formKey = $type->form_key;
        $base = 0.0;

        switch ($formKey) {
            case 'armar':
            case 'realizar':
                $base = $this->priceSepelio($rates, $formKey, $companyId, $input);
                break;
            case 'cremacion':
                $base = $this->priceCremacion($rates, $input);
                break;
            case 'ambulancia':
                $base = $this->priceAmbulancia($rates, $companyId, $input);
                break;
            case 'metalica':
                $base = (float)$rates->cambio_metalica;
                break;
            case 'viajes':
                $base = $this->priceViajes($rates, $input);
                break;
            case 'tanato':
                $base = (float)$rates->tanato;
                break;
            case 'gestion':
                $base = (float)$rates->gestion_tramites;
                break;
            default:
                return ['ok' => false, 'message' => 'Cálculo no disponible para esta tarea.'];
        }

        if ($base <= 0) {
            return ['ok' => false, 'message' => 'El importe calculado es cero. Revisá las tarifas en RRHH.'];
        }

        return $this->finalizeAmount($type, $companyId, $activityDate, $base, $input);
    }

    private function calculateManual($type, $companyId, array $input, $activityDate) {
        $manual = isset($input['manual_amount']) ? (float)$input['manual_amount'] : 0;
        if ($manual <= 0) {
            return ['ok' => false, 'message' => 'Ingresá un importe mayor a cero.'];
        }
        $isHoliday = $this->holiday->isHolidayForCompany($companyId, $activityDate);
        $multiplier = 1.0;
        $amount = $manual;
        if ($isHoliday && (int)$type->holiday_multiplier_eligible === 1) {
            return [
                'ok' => true,
                'amount' => $amount,
                'amount_base' => round($manual, 2),
                'is_holiday' => 1,
                'holiday_multiplier' => 1,
                'task_type_id' => (int)$type->id,
                'meta' => array_merge($input, ['holiday_note' => 'Feriado: verificá si corresponde duplicar el importe manual.']),
                'holiday_warning' => true,
            ];
        }
        return $this->finalizeAmount($type, $companyId, $activityDate, $manual, $input, true);
    }

    private function finalizeAmount($type, $companyId, $activityDate, $base, array $input, $skipHolidayMultiply = false) {
        $isHoliday = $this->holiday->isHolidayForCompany($companyId, $activityDate);
        $multiplier = 1.0;
        if (!$skipHolidayMultiply && $isHoliday && (int)$type->holiday_multiplier_eligible === 1) {
            $multiplier = 2.0;
        }
        return [
            'ok' => true,
            'amount' => round($base * $multiplier, 2),
            'amount_base' => round($base, 2),
            'is_holiday' => $isHoliday ? 1 : 0,
            'holiday_multiplier' => $multiplier,
            'task_type_id' => (int)$type->id,
            'meta' => $input,
        ];
    }

    private function priceSepelio($rates, $formKey, $companyId, array $input) {
        $base = $formKey === 'armar' ? (float)$rates->armar_s : (float)$rates->realizar_s;
        $localityName = trim($input['locality_name'] ?? $input['installed_place'] ?? '');
        if ($localityName !== '' && $this->cpTask->localityHasAdditional($companyId, $localityName)) {
            $base += (float)$rates->localidades;
        }
        if (($input['covid'] ?? '') === 'Si' || ($input['covid'] ?? '') === '1') {
            $base += (float)$rates->covid;
        }
        return $base;
    }

    private function priceCremacion($rates, array $input) {
        $count = max(1, (int)($input['coffin_count'] ?? 1));
        $base = (float)$rates->cremacion;
        $extra = (float)$rates->cremacion_adicional;
        if ($count > 1) {
            $base += $extra * ($count - 1);
        }
        return $base;
    }

    private function priceAmbulancia($rates, $companyId, array $input) {
        $place = trim($input['locality_name'] ?? $input['realizado'] ?? '');
        $level = $this->cpTask->getLocalityAdicionalLevel($companyId, $place);
        return $level === 2 ? (float)$rates->ambu_vm : (float)$rates->ambu_localidades;
    }

    private function priceViajes($rates, array $input) {
        $km = max(0, (float)($input['km'] ?? 0));
        if ($km <= 0) {
            return 0;
        }
        $guardia = (int)($input['guardia_type'] ?? $input['guardia'] ?? 1);
        $rate = $guardia === 2 ? (float)$rates->viajes_pasiva : (float)$rates->viajes_activa;
        return $rate * $km;
    }
}
