<?php

class VacationLedgerService {
    private $balanceModel;
    private $entitlement;
    private $workSchedule;
    private $userModel;

    public function __construct() {
        $this->balanceModel = new VacationBalance();
        $this->entitlement = new VacationEntitlementService();
        $this->workSchedule = new WorkSchedule();
        $this->userModel = new User();
    }

    /**
     * Aplica consumo de días (FIFO períodos abiertos).
     * @param array $dates Lista de fechas Y-m-d (cada día = 1 unidad salvo que se pase cantidad por rango)
     * @return array{ok:bool,message:string}
     */
    public function applyTake($userId, array $dates, $source, $adminId, $requestId = null, $notes = '', $skipScheduleSave = false) {
        if (!$this->balanceModel->isReady()) {
            return ['ok' => false, 'message' => 'Módulo de vacaciones no instalado.'];
        }
        $dates = array_values(array_unique(array_filter($dates)));
        if (empty($dates)) {
            return ['ok' => true, 'message' => 'Sin días a descontar.'];
        }

        $daysNeeded = (float)count($dates);
        $db = new Database();
        $ownTx = !$db->inTransaction();
        if ($ownTx) {
            $db->beginTransaction();
        }
        try {
            $totalPending = $this->balanceModel->getTotalPending($userId);
            if ($daysNeeded > $totalPending + 0.001) {
                if ($ownTx) {
                    $db->rollBack();
                }
                return [
                    'ok' => false,
                    'message' => 'Saldo insuficiente. Pendientes: ' . vacation_format_days($totalPending)
                        . ', solicitado: ' . vacation_format_days($daysNeeded) . '.',
                ];
            }

            $periods = $this->balanceModel->getPeriodsByUser($userId, true);
            usort($periods, function ($a, $b) {
                return strcmp($a->period_start, $b->period_start);
            });

            $remaining = $daysNeeded;
            foreach ($periods as $period) {
                if ($remaining <= 0) {
                    break;
                }
                $avail = (float)$period->days_pending;
                if ($avail <= 0) {
                    continue;
                }
                $take = min($avail, $remaining);
                $newTaken = (float)$period->days_taken + $take;
                $this->balanceModel->updatePeriodBalances((int)$period->id, (float)$period->days_entitled, $newTaken);
                $this->balanceModel->addMovement([
                    'period_id' => (int)$period->id,
                    'user_id' => $userId,
                    'movement_type' => 'take',
                    'source' => $source,
                    'days' => $take,
                    'request_id' => $requestId,
                    'schedule_dates' => $dates,
                    'notes' => $notes,
                    'created_by' => $adminId,
                ]);
                $remaining -= $take;
            }

            if (!$skipScheduleSave) {
                foreach ($dates as $date) {
                    $this->workSchedule->saveDaySchedule($userId, $date, [vacation_schedule_entry(
                        $requestId ? 'Vacaciones (sol. #' . $requestId . ')' : 'Vacaciones'
                    )]);
                }
            }

            $this->balanceModel->syncUserVacationCache($userId);
            if ($ownTx) {
                $db->commit();
            }
        } catch (Throwable $e) {
            if ($ownTx) {
                $db->rollBack();
            }
            return ['ok' => false, 'message' => 'No se pudo aplicar el descuento de vacaciones.'];
        }
        return ['ok' => true, 'message' => 'Se descontaron ' . vacation_format_days($daysNeeded) . ' día(s) de vacaciones.'];
    }

    /**
     * Revierte días (p. ej. quitar vacaciones del planificador).
     */
    public function applyReversal($userId, array $dates, $source, $adminId, $notes = '', $skipScheduleSave = false) {
        if (empty($dates)) {
            return ['ok' => true, 'message' => 'Sin cambios.'];
        }
        $days = (float)count(array_unique($dates));
        $db = new Database();
        $ownTx = !$db->inTransaction();
        if ($ownTx) {
            $db->beginTransaction();
        }
        try {
            $periods = $this->balanceModel->getPeriodsByUser($userId, true);
            usort($periods, function ($a, $b) {
                return strcmp($b->period_start, $a->period_start);
            });

            $remaining = $days;
            foreach ($periods as $period) {
                if ($remaining <= 0) {
                    break;
                }
                $taken = (float)$period->days_taken;
                if ($taken <= 0) {
                    continue;
                }
                $rev = min($taken, $remaining);
                $newTaken = $taken - $rev;
                $this->balanceModel->updatePeriodBalances((int)$period->id, (float)$period->days_entitled, $newTaken);
                $this->balanceModel->addMovement([
                    'period_id' => (int)$period->id,
                    'user_id' => $userId,
                    'movement_type' => 'reversal',
                    'source' => $source,
                    'days' => $rev,
                    'schedule_dates' => $dates,
                    'notes' => $notes ?: 'Reversión vacaciones',
                    'created_by' => $adminId,
                ]);
                $remaining -= $rev;
            }

            if (!$skipScheduleSave) {
                foreach ($dates as $date) {
                    $this->workSchedule->saveDaySchedule($userId, $date, []);
                }
            }

            $this->balanceModel->syncUserVacationCache($userId);
            if ($ownTx) {
                $db->commit();
            }
        } catch (Throwable $e) {
            if ($ownTx) {
                $db->rollBack();
            }
            return ['ok' => false, 'message' => 'No se pudo revertir el descuento de vacaciones.'];
        }
        return ['ok' => true, 'message' => 'Se revirtieron ' . vacation_format_days($days) . ' día(s).'];
    }

    /**
     * Procesa cambio de un día en planificador: delta de chips vacation.
     */
    public function processPlannerDayChange($userId, $date, array $oldEntries, array $newEntries, $adminId) {
        $oldV = vacation_count_vacation_days_in_entries($oldEntries);
        $newV = 0;
        foreach ($newEntries as $e) {
            if (($e['type'] ?? '') === 'vacation') {
                $newV++;
            }
        }
        $delta = $newV - $oldV;
        if ($delta > 0) {
            $dates = array_fill(0, $delta, $date);
            return $this->applyTake($userId, $dates, 'planner', $adminId, null, 'Planificador ' . $date, true);
        }
        if ($delta < 0) {
            $dates = array_fill(0, -$delta, $date);
            return $this->applyReversal($userId, $dates, 'planner', $adminId, 'Quitar vacaciones planificador', true);
        }
        return ['ok' => true, 'message' => ''];
    }

    /**
     * Aprobación de solicitud de vacaciones por rango.
     */
    public function applyTakeFromRequest($request, $adminId) {
        $userId = (int)$request->user_id;
        $start = $request->start_date;
        $end = $request->end_date ?: $request->start_date;
        $rule = $this->entitlement->getApplicableRule($userId, $start);
        $mode = $rule ? $rule->day_count_mode : 'weekdays';

        $dates = [];
        $d = new DateTime($start);
        $endDt = new DateTime($end);
        while ($d <= $endDt) {
            $ds = $d->format('Y-m-d');
            if ($mode === 'calendar' || (int)$d->format('N') < 6) {
                $dates[] = $ds;
            }
            $d->modify('+1 day');
        }

        return $this->applyTake($userId, $dates, 'request', $adminId, (int)$request->id, 'Solicitud #' . (int)$request->id);
    }
}
