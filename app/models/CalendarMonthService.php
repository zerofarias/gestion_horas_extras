<?php

/**
 * Arma el calendario mensual unificado: plan + asistencia + solicitudes + feriados + justificaciones.
 */
class CalendarMonthService {
    private $workScheduleModel;
    private $attendanceSummaryModel;
    private $requestModel;
    private $holidayModel;
    private $justificationModel;
    private $planVsActualService;
    private $shiftSwapModel;
    private $incidentModel;
    private $cpTaskModel;

    public function __construct() {
        $this->workScheduleModel = new WorkSchedule();
        $this->attendanceSummaryModel = new AttendanceDaySummary();
        $this->requestModel = new Request();
        $this->holidayModel = new Holiday();
        $this->justificationModel = new AttendanceJustification();
        $this->planVsActualService = new PlanVsActualService();
        $this->shiftSwapModel = new ShiftSwap();
        $this->incidentModel = new EmployeeIncident();
        if (function_exists('cp_tasks_is_ready') && cp_tasks_is_ready()) {
            $this->cpTaskModel = new CpTask();
        }
    }

    public function buildMonth($companyId, $userId, $yearMonth) {
        $parts = explode('-', $yearMonth);
        $year  = (int)($parts[0] ?? date('Y'));
        $month = (int)($parts[1] ?? date('m'));
        if ($month < 1 || $month > 12) {
            $month = (int)date('m');
        }

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));
        $today     = date('Y-m-d');

        try {
            $d = new DateTime($startDate);
            $end = new DateTime($endDate);
            while ($d <= $end) {
                $this->planVsActualService->computeAndSave($userId, $d->format('Y-m-d'), $companyId);
                $d->modify('+1 day');
            }
        } catch (Exception $e) {
            // Tabla de asistencia ausente: calendario igual muestra plan y solicitudes
        }

        $schedulesByDate = $this->workScheduleModel->getUserScheduleForMonth($userId, $companyId, $startDate);
        $attendanceMap   = $this->getAttendanceMap($userId, $startDate, $endDate);
        $justificationMap = $this->getJustificationMap($userId, $startDate, $endDate);
        $holidayMap      = $this->buildHolidayMap($companyId, $startDate, $endDate);
        $requestMap      = $this->buildRequestMap($userId, $companyId, $startDate, $endDate);
        $swapMap         = $this->buildSwapMap($userId, $startDate, $endDate);
        $incidentMap     = $this->buildIncidentMap($userId, $startDate, $endDate);
        $cpTaskMap       = $this->buildCpTaskMap($companyId, $userId, $startDate, $endDate);

        $daysInMonth = (int)date('t', strtotime($startDate));
        $firstDow    = (int)date('N', strtotime($startDate));
        $padStart    = $firstDow - 1;

        $weeks = [];
        $daysList = [];
        $week  = array_fill(0, 7, null);
        $cell  = 0;

        for ($i = 0; $i < $padStart; $i++) {
            $week[$cell++] = ['in_month' => false, 'date' => null];
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $planned = $schedulesByDate[$date] ?? [];

            $rawDay = [
                'in_month'          => true,
                'date'              => $date,
                'day'               => $day,
                'is_today'          => ($date === $today),
                'holiday'           => $holidayMap[$date] ?? null,
                'planned'           => $planned,
                'has_planned'       => !empty($planned),
                'attendance'        => $attendanceMap[$date] ?? null,
                'justification'     => $justificationMap[$date] ?? null,
                'requests'          => $requestMap[$date] ?? [],
                'swaps'             => $swapMap[$date] ?? [],
                'incidents'         => $incidentMap[$date] ?? [],
                'cp_tasks'          => $cpTaskMap[$date] ?? [],
                '_scope_company_id' => (int)$companyId,
                '_scope_user_id'    => (int)$userId,
            ];

            $enriched = EmployeeDayContext::enrich($rawDay);
            $daysList[] = $enriched;

            $week[$cell++] = $enriched;

            if ($cell === 7) {
                $weeks[] = $week;
                $week = array_fill(0, 7, null);
                $cell = 0;
            }
        }

        if ($cell > 0) {
            while ($cell < 7) {
                $week[$cell++] = ['in_month' => false, 'date' => null];
            }
            $weeks[] = $week;
        }

        $monthNames = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        return [
            'year'        => $year,
            'month'       => $month,
            'month_label' => ($monthNames[$month] ?? '') . ' ' . $year,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'weeks'       => $weeks,
            'days_list'   => $daysList,
            'prev_month'  => date('Y-m', strtotime($startDate . ' -1 month')),
            'next_month'  => date('Y-m', strtotime($startDate . ' +1 month')),
        ];
    }

    private function getAttendanceMap($userId, $startDate, $endDate) {
        $db = new Database;
        $db->query('SELECT * FROM attendance_day_summary
            WHERE user_id = :user_id AND work_date BETWEEN :start_date AND :end_date');
        $db->bind(':user_id', $userId);
        $db->bind(':start_date', $startDate);
        $db->bind(':end_date', $endDate);
        $rows = $db->resultSet();
        $map = [];
        foreach ($rows as $r) {
            $map[$r->work_date] = $r;
        }
        return $map;
    }

    private function getJustificationMap($userId, $startDate, $endDate) {
        try {
            return $this->justificationModel->getForUserPeriod($userId, $startDate, $endDate);
        } catch (Exception $e) {
            return [];
        }
    }

    private function buildHolidayMap($companyId, $startDate, $endDate) {
        $map = [];
        foreach ($this->holidayModel->getHolidaysForPeriod($companyId, $startDate, $endDate) as $h) {
            $map[$h->holiday_date] = $h->name;
        }
        return $map;
    }

    private function buildRequestMap($userId, $companyId, $startDate, $endDate) {
        $map = [];
        $rows = $this->requestModel->getApprovedRequestsForPeriod($startDate, $endDate, $companyId);
        foreach ($rows as $r) {
            if ((int)$r->user_id !== (int)$userId) {
                continue;
            }
            $d = new DateTime($r->start_date);
            $end = new DateTime($r->end_date);
            while ($d <= $end) {
                $key = $d->format('Y-m-d');
                if ($key >= $startDate && $key <= $endDate) {
                    if (!isset($map[$key])) {
                        $map[$key] = [];
                    }
                    $map[$key][] = $r;
                }
                $d->modify('+1 day');
            }
        }
        return $map;
    }

    private function buildSwapMap($userId, $startDate, $endDate) {
        $map = [];
        $uid = (int)$userId;
        foreach ($this->shiftSwapModel->getForUserInPeriod($userId, $startDate, $endDate) as $swap) {
            $dates = [];
            if ((int)$swap->proposer_user_id === $uid && !empty($swap->proposer_date)) {
                $dates[] = $swap->proposer_date;
            }
            if ((int)$swap->accepter_user_id === $uid && !empty($swap->accepter_date)) {
                $dates[] = $swap->accepter_date;
            }
            foreach (array_unique($dates) as $date) {
                if ($date >= $startDate && $date <= $endDate) {
                    if (!isset($map[$date])) {
                        $map[$date] = [];
                    }
                    $map[$date][] = $swap;
                }
            }
        }
        return $map;
    }

    private function buildIncidentMap($userId, $startDate, $endDate) {
        $map = [];
        foreach ($this->incidentModel->getByUserInPeriod($userId, $startDate, $endDate) as $inc) {
            $key = $inc->incident_date;
            if (!isset($map[$key])) {
                $map[$key] = [];
            }
            $map[$key][] = $inc;
        }
        return $map;
    }

    private function buildCpTaskMap($companyId, $userId, $startDate, $endDate) {
        if (!$this->cpTaskModel || !function_exists('company_uses_casapav_tasks')
            || !company_uses_casapav_tasks($companyId)) {
            return [];
        }
        if (function_exists('company_cp_extras_enabled') && !company_cp_extras_enabled($companyId)) {
            return [];
        }
        if (function_exists('cp_scope_enabled_for_user')) {
            $user = (new User())->getUserById((int)$userId);
            if ($user && !cp_scope_enabled_for_user($user)) {
                return [];
            }
        }
        return $this->cpTaskModel->getCalendarEntriesMap($userId, $companyId, $startDate, $endDate);
    }
}
