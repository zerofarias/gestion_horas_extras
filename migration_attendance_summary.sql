-- Planificado vs fichado — resumen diario por empleado
-- Ejecutar una vez en la base de datos del proyecto.

CREATE TABLE IF NOT EXISTS `attendance_day_summary` (
  `id`               INT(11)      NOT NULL AUTO_INCREMENT,
  `company_id`       INT(11)      NOT NULL,
  `user_id`          INT(11)      NOT NULL,
  `work_date`        DATE         NOT NULL,
  `planned_minutes`  INT(11)      NULL,
  `planned_start`    TIME         NULL,
  `planned_end`      TIME         NULL,
  `planned_blocks`   TINYINT(3)   NOT NULL DEFAULT 1,
  `actual_minutes`   INT(11)      NULL,
  `actual_entry`     DATETIME     NULL,
  `actual_exit`      DATETIME     NULL,
  `delta_minutes`    INT(11)      NULL,
  `status`           ENUM(
    'ok','late','early_leave','missing_out','no_show',
    'unplanned_clocking','incomplete','on_leave'
  ) NOT NULL DEFAULT 'ok',
  `alert_codes`      JSON         NULL,
  `computed_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_user_date` (`user_id`, `work_date`),
  KEY `idx_attendance_company_date` (`company_id`, `work_date`),
  KEY `idx_attendance_status` (`status`),
  CONSTRAINT `fk_attendance_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
