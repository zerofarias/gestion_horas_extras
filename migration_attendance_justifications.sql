  -- Justificación de inasistencias / día sin marca (certificado, asistió sin fichar, etc.)
  CREATE TABLE IF NOT EXISTS `attendance_justifications` (
    `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
    `company_id`          INT(11)      NOT NULL,
    `user_id`             INT(11)      NOT NULL,
    `work_date`           DATE         NOT NULL,
    `justification_type`  ENUM(
      'medical_certificate',
      'attended_no_clock',
      'authorized_absence',
      'early_leave_medical',
      'early_leave_errand',
      'early_leave_authorized',
      'other'
    ) NOT NULL,
    `leave_time`          TIME         NULL COMMENT 'Hora de salida si aplica permiso anticipado',
    `prior_notice`        TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=hubo aviso previo',
    `notes`               TEXT         NULL,
    `file_path`           VARCHAR(500) NULL,
    `created_by`          INT(11)      NOT NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_just_user_date` (`user_id`, `work_date`),
    KEY `idx_just_company_date` (`company_id`, `work_date`),
    CONSTRAINT `fk_just_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_just_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_just_admin` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
