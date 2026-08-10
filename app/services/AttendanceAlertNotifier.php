<?php

class AttendanceAlertNotifier {
    private $attendanceModel;
    private $userModel;
    private $mail;

    public function __construct() {
        $this->attendanceModel = new AttendanceDaySummary();
        $this->userModel = new User();
        $this->mail = new MailService();
    }

    /**
     * Envía resumen de alertas del día a admins activos de la empresa.
     * @return int Cantidad de correos enviados OK
     */
    public function sendDailyDigestForCompany($companyId, $workDate = null) {
        if (!$this->mail->isAvailable() || !$this->attendanceModel->isSchemaReady()) {
            return 0;
        }
        $companyId = (int)$companyId;
        $workDate = $workDate ?: date('Y-m-d');

        try {
            $plan = new PlanVsActualService();
            if (!$this->attendanceModel->hasRowsForDate($companyId, $workDate)) {
                $plan->recomputeRange($companyId, $workDate, $workDate);
            }
        } catch (Throwable $e) {
            return 0;
        }

        $alerts = $this->attendanceModel->getAlertsForDate($companyId, $workDate, 50);
        if (empty($alerts)) {
            return 0;
        }

        $lines = [];
        foreach ($alerts as $a) {
            $meta = attendanceStatusMeta($a->status ?? '');
            $status = $meta['label'];
            $lines[] = '<li><strong>' . htmlspecialchars($a->full_name) . '</strong> — '
                . htmlspecialchars($status) . ' (' . htmlspecialchars($workDate) . ')</li>';
        }
        $body = '<p>Resumen de asistencia con alertas para <strong>' . htmlspecialchars($workDate) . '</strong>:</p><ul>'
            . implode('', $lines) . '</ul>'
            . '<p><a href="' . htmlspecialchars(URLROOT . '/admin/hrAlerts') . '">Ver centro de alertas RRHH</a></p>';

        $admins = $this->userModel->getActiveAdminsByCompany($companyId);
        $sent = 0;
        foreach ($admins as $admin) {
            $r = $this->mail->sendToUser((int)$admin->id, 'Alertas de asistencia — ' . $workDate, $body);
            if (!empty($r['ok'])) {
                $sent++;
            }
        }
        return $sent;
    }
}
