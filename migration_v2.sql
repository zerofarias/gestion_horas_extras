-- ==========================================================================
-- MIGRACIÓN v2: Agregar tablas y columnas faltantes
-- Ejecutar sobre la base de datos paviotti_lanaturaleza
-- Generado: 2026-05-14
-- ==========================================================================

-- Evitar errores si algo ya existe
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------------------------
-- 1. TABLA: companies
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `companies` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Empresa por defecto para usuarios existentes
INSERT INTO `companies` (`id`, `name`)
SELECT 1, 'La Naturaleza'
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `id` = 1);

-- --------------------------------------------------------------------------
-- 2. COLUMNAS FALTANTES EN: users
-- --------------------------------------------------------------------------
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `company_id`              INT(11)        NULL         AFTER `role`,
  ADD COLUMN IF NOT EXISTS `hourly_rate`             DECIMAL(10,2)  NOT NULL DEFAULT 0.00 AFTER `company_id`,
  ADD COLUMN IF NOT EXISTS `weekly_hour_limit`       INT(11)        NOT NULL DEFAULT 45    AFTER `hourly_rate`,
  ADD COLUMN IF NOT EXISTS `vacation_days_available` INT(11)        NOT NULL DEFAULT 14    AFTER `weekly_hour_limit`,
  ADD COLUMN IF NOT EXISTS `birth_date`              DATE           NULL         AFTER `vacation_days_available`;

-- Asignar todos los usuarios existentes a la empresa por defecto
UPDATE `users` SET `company_id` = 1 WHERE `company_id` IS NULL;

-- Agregar FK solo si no existe
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND CONSTRAINT_NAME = 'fk_users_company'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE `users` ADD CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------------------------
-- 3. TABLA: user_clock_mappings
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_clock_mappings` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`       INT(11)      NOT NULL,
  `clock_name`    VARCHAR(100) NOT NULL,
  `user_clock_id` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_clock` (`user_id`, `clock_name`),
  CONSTRAINT `fk_ucm_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 4. TABLA: schedules (fichas de entrada/salida)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schedules` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)       NOT NULL,
  `work_date`   DATE          NOT NULL,
  `entry_time`  TIME          NULL,
  `exit_time`   TIME          NULL,
  `total_hours` DECIMAL(5,2)  NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_work_date` (`user_id`, `work_date`),
  CONSTRAINT `fk_sch_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 5. TABLA: clock_events (marcaciones brutas del reloj)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clock_events` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`         INT(11)      NOT NULL,
  `clock_id`        VARCHAR(100) NULL,
  `event_time`      DATETIME     NOT NULL,
  `sync_batch_id`   VARCHAR(100) NULL,
  `event_serial_no` VARCHAR(255) NULL,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_serial` (`event_serial_no`),
  KEY `idx_user_event_time` (`user_id`, `event_time`),
  CONSTRAINT `fk_ce_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 6. TABLA: shifts (definición de turnos)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shifts` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `company_id` INT(11)      NOT NULL,
  `shift_name` VARCHAR(100) NOT NULL,
  `notes`      TEXT         NULL,
  `color`      VARCHAR(7)   NOT NULL DEFAULT '#3788d8',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_shift_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 7. TABLA: shift_time_ranges (rangos horarios dentro de un turno)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shift_time_ranges` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `shift_id`   INT(11) NOT NULL,
  `start_time` TIME    NOT NULL,
  `end_time`   TIME    NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_str_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 8. TABLA: employee_schedules (planificación semanal/mensual)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `employee_schedules` (
  `id`            INT(11)                           NOT NULL AUTO_INCREMENT,
  `user_id`       INT(11)                           NOT NULL,
  `schedule_date` DATE                              NOT NULL,
  `shift_id`      INT(11)                           NULL,
  `start_time`    TIME                              NULL,
  `end_time`      TIME                              NULL,
  `type`          ENUM('shift','custom','overtime') NOT NULL DEFAULT 'shift',
  `notes`         TEXT                              NULL,
  `created_at`    TIMESTAMP                         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_es_user_date` (`user_id`, `schedule_date`),
  CONSTRAINT `fk_es_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_es_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 9. TABLA: holidays (feriados por empresa)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `holidays` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `company_id`   INT(11)      NOT NULL,
  `holiday_date` DATE         NOT NULL,
  `name`         VARCHAR(255) NOT NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_hol_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 10. TABLA: suggestions (buzón de sugerencias)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `suggestions` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `company_id`      INT(11) NOT NULL,
  `suggestion_text` TEXT    NOT NULL,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_sug_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 11. TABLA: user_notes (notas/incidencias por empleado)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_notes` (
  `id`         INT(11)   NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)   NOT NULL,
  `admin_id`   INT(11)   NOT NULL,
  `note`       TEXT      NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_un_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_un_admin` FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 12. TABLA: schedule_templates (plantillas de horario)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schedule_templates` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `company_id`    INT(11)      NOT NULL,
  `template_name` VARCHAR(255) NOT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_st_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 13. TABLA: schedule_template_entries (entradas de cada plantilla)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `schedule_template_entries` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `template_id` INT(11) NOT NULL,
  `user_id`     INT(11) NOT NULL,
  `day_of_week` TINYINT(1) NOT NULL COMMENT '1=Lunes, 7=Domingo',
  `shift_id`    INT(11) NULL,
  `start_time`  TIME    NULL,
  `end_time`    TIME    NULL,
  `type`        ENUM('shift','custom','overtime') NOT NULL DEFAULT 'shift',
  `notes`       TEXT    NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ste_template` FOREIGN KEY (`template_id`) REFERENCES `schedule_templates`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ste_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)              ON DELETE CASCADE,
  CONSTRAINT `fk_ste_shift`    FOREIGN KEY (`shift_id`)    REFERENCES `shifts`(`id`)             ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 14. TABLA: ShiftSwap (cambios de turno)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shift_swaps` (
  `id`                INT(11)                                    NOT NULL AUTO_INCREMENT,
  `requester_id`      INT(11)                                    NOT NULL,
  `target_id`         INT(11)                                    NOT NULL,
  `requester_date`    DATE                                       NOT NULL,
  `target_date`       DATE                                       NOT NULL,
  `reason`            TEXT                                       NULL,
  `status`            ENUM('Pendiente','Aprobado','Rechazado')  NOT NULL DEFAULT 'Pendiente',
  `created_at`        TIMESTAMP                                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ss_requester` FOREIGN KEY (`requester_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ss_target`    FOREIGN KEY (`target_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reactivar FK checks
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------------------------
-- FIN DE MIGRACIÓN
-- --------------------------------------------------------------------------
SELECT 'Migración completada exitosamente.' AS resultado;
