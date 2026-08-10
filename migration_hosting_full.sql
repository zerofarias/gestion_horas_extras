-- migration_hosting_full.sql
-- Generado: 2026-06-10T17:56:21Z
-- Base objetivo: paviotti_lanaturaleza (estado primitivo RRHH + tablas legacy)
-- BACKUP OBLIGATORIO antes de importar en producción.

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ==========================================================================
-- STEP 1 — migration_v2.sql
-- ==========================================================================
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


-- ==========================================================================
-- STEP 2 — migration_companies_grupo.sql
-- ==========================================================================
-- Empresas del grupo para asignar empleados y filtrar cambios de turno
-- Ejecutar una vez en MySQL.

INSERT INTO `companies` (`name`)
SELECT 'Servicios Sociales' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'Servicios Sociales');

INSERT INTO `companies` (`name`)
SELECT 'Casa Paviotti' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'Casa Paviotti');

INSERT INTO `companies` (`name`)
SELECT 'A.M.S.S.I' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'A.M.S.S.I');

INSERT INTO `companies` (`name`)
SELECT 'Ecofarma' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'Ecofarma');


-- ==========================================================================
-- STEP 3 — migration_marcaciones_cache.sql
-- ==========================================================================
-- Caché de todas las marcaciones importadas desde la API Relojes (mapeadas o no).
-- Ejecutar una vez en la base de datos del proyecto.

CREATE TABLE IF NOT EXISTS `marcaciones_cache` (
  `id`               INT(11)       NOT NULL AUTO_INCREMENT,
  `api_event_id`     INT(11)       NULL,
  `employee_id`      VARCHAR(100)  NOT NULL,
  `person_name`      VARCHAR(200)  NULL,
  `user_id`          INT(11)       NULL,
  `event_time`       DATETIME      NOT NULL,
  `device_name`      VARCHAR(100)  NULL,
  `direction`        VARCHAR(50)   NULL,
  `direction_label`  VARCHAR(100)  NULL,
  `event_serial_no`  VARCHAR(255)  NOT NULL,
  `sync_batch_id`    VARCHAR(100)  NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mc_serial` (`event_serial_no`),
  KEY `idx_mc_event_time` (`event_time`),
  KEY `idx_mc_employee` (`employee_id`),
  KEY `idx_mc_user` (`user_id`),
  CONSTRAINT `fk_mc_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================================================
-- STEP 3b — migration_clock_events_device.sql
-- ==========================================================================
-- Columnas de dispositivo/dirección en marcaciones (tras migration_v2.sql).
-- Requeridas por Schedule.php, SyncModel y ficha de empleado.

ALTER TABLE `clock_events`
  ADD COLUMN IF NOT EXISTS `device_name` VARCHAR(100) NULL AFTER `clock_id`,
  ADD COLUMN IF NOT EXISTS `direction` VARCHAR(50) NULL AFTER `device_name`,
  ADD COLUMN IF NOT EXISTS `direction_label` VARCHAR(100) NULL AFTER `direction`;


-- ==========================================================================
-- STEP 4 — migration_users_profile_extended.sql
-- ==========================================================================
-- Datos personales del empleado (ficha / editar usuario)
-- Ejecutar en phpMyAdmin sobre la base del proyecto.

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `email`                    VARCHAR(120)   NULL AFTER `full_name`,
  ADD COLUMN IF NOT EXISTS `phone_number`             VARCHAR(40)    NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `address`                  VARCHAR(255)   NULL AFTER `phone_number`,
  ADD COLUMN IF NOT EXISTS `document_number`          VARCHAR(20)    NULL COMMENT 'DNI / documento' AFTER `address`,
  ADD COLUMN IF NOT EXISTS `cuil`                       VARCHAR(15)    NULL AFTER `document_number`,
  ADD COLUMN IF NOT EXISTS `sex`                        ENUM('M','F','X') NULL COMMENT 'Sexo registrado (legal/admin)' AFTER `cuil`,
  ADD COLUMN IF NOT EXISTS `gender`                     VARCHAR(40)    NULL COMMENT 'Identidad de género' AFTER `sex`,
  ADD COLUMN IF NOT EXISTS `emergency_contact_name`   VARCHAR(120)   NULL AFTER `gender`,
  ADD COLUMN IF NOT EXISTS `emergency_contact_phone`  VARCHAR(40)    NULL AFTER `emergency_contact_name`;


-- ==========================================================================
-- STEP 5 — migration_attendance_summary.sql
-- ==========================================================================
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


-- ==========================================================================
-- STEP 6 — migration_attendance_justifications.sql
-- ==========================================================================
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


-- ==========================================================================
-- STEP 8 — migration_requests_admin_review.sql
-- ==========================================================================
-- Certificado admin, descarte de bandeja y notas internas en solicitudes
-- Ejecutar una vez en MySQL.

ALTER TABLE `requests`
  ADD COLUMN `certificate_path` VARCHAR(500) NULL COMMENT 'Certificado adjunto (admin)' AFTER `reason`,
  ADD COLUMN `admin_dismissed_at` DATETIME NULL COMMENT 'Quitada de prioridad sin aprobar/rechazar' AFTER `status`,
  ADD COLUMN `admin_notes` TEXT NULL COMMENT 'Notas internas del administrador' AFTER `admin_dismissed_at`;


-- ==========================================================================
-- STEP 9 — migration_shift_swaps_fix.sql
-- ==========================================================================
-- Usar SOLO si migration_shift_swaps.sql no alcanzó (tabla vieja con requester_id/target_id).
-- Borra la tabla anterior y crea el esquema correcto para cambios de turno.

DROP TABLE IF EXISTS `shift_swaps`;

CREATE TABLE `shift_swaps` (
  `id`                    INT(11) NOT NULL AUTO_INCREMENT,
  `proposer_user_id`      INT(11) NOT NULL,
  `accepter_user_id`      INT(11) NOT NULL,
  `proposer_schedule_id`  INT(11) NOT NULL,
  `accepter_schedule_id`  INT(11) NOT NULL,
  `notes`                 TEXT NULL,
  `status`                ENUM('Pendiente','Aprobado','Rechazado') NOT NULL DEFAULT 'Pendiente',
  `reviewed_by`           INT(11) NULL,
  `created_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ss_status` (`status`),
  KEY `idx_ss_proposer` (`proposer_user_id`),
  CONSTRAINT `fk_ss_proposer` FOREIGN KEY (`proposer_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ss_accepter` FOREIGN KEY (`accepter_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ss_ps` FOREIGN KEY (`proposer_schedule_id`) REFERENCES `employee_schedules`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ss_as` FOREIGN KEY (`accepter_schedule_id`) REFERENCES `employee_schedules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================================================
-- STEP 10 — migration_shift_swaps_accepter_null.sql
-- ==========================================================================
-- Permite solicitar cambio solo con el compañero (sin elegir su turno).
-- El turno del compañero se resuelve al aprobar (mismo día que tu turno).

ALTER TABLE `shift_swaps`
  MODIFY COLUMN `accepter_schedule_id` INT(11) NULL;


-- ==========================================================================
-- STEP 12 — migration_learning.sql
-- ==========================================================================
-- Capacitación gamificada + tareas (áreas, cursos, quiz, estrellas, premios)
-- Ejecutar en phpMyAdmin después de migration_v2 y companies.

-- Áreas / departamentos por empresa
CREATE TABLE IF NOT EXISTS `areas` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `company_id` INT(11)      NOT NULL,
  `name`       VARCHAR(120) NOT NULL,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_areas_company` (`company_id`),
  CONSTRAINT `fk_areas_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `area_id` INT(11) NULL AFTER `company_id`;

-- FK area (puede fallar si ya existe; ignorar en ese caso)
-- ALTER TABLE `users` ADD CONSTRAINT `fk_users_area` FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`) ON DELETE SET NULL;

-- Cursos
CREATE TABLE IF NOT EXISTS `courses` (
  `id`                INT(11)       NOT NULL AUTO_INCREMENT,
  `company_id`        INT(11)       NOT NULL,
  `area_id`           INT(11)       NULL,
  `title`             VARCHAR(200)  NOT NULL,
  `slug`              VARCHAR(200)  NOT NULL,
  `description`       TEXT          NULL,
  `thumbnail_url`     VARCHAR(500)  NULL,
  `stars_on_complete` INT(11)       NOT NULL DEFAULT 5,
  `passing_score`     TINYINT(3)    NOT NULL DEFAULT 70,
  `estimated_minutes` INT(11)       NOT NULL DEFAULT 60,
  `require_quiz`      TINYINT(1)    NOT NULL DEFAULT 1,
  `max_quiz_attempts` TINYINT(3)    NOT NULL DEFAULT 3,
  `is_published`      TINYINT(1)    NOT NULL DEFAULT 0,
  `sort_order`        INT(11)       NOT NULL DEFAULT 0,
  `created_by`        INT(11)       NULL,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP     NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_courses_slug_company` (`company_id`, `slug`),
  KEY `idx_courses_company` (`company_id`),
  CONSTRAINT `fk_courses_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_courses_area` FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_courses_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_lessons` (
  `id`               INT(11)      NOT NULL AUTO_INCREMENT,
  `course_id`        INT(11)      NOT NULL,
  `position`         INT(11)      NOT NULL DEFAULT 1,
  `title`            VARCHAR(200) NOT NULL,
  `content_type`     ENUM('video','text','file') NOT NULL DEFAULT 'text',
  `content_url`      TEXT         NULL,
  `content_body`     MEDIUMTEXT   NULL,
  `duration_minutes` INT(11)      NOT NULL DEFAULT 5,
  `is_required`      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_lessons_course` (`course_id`, `position`),
  CONSTRAINT `fk_lessons_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_quiz_questions` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `course_id`     INT(11)      NOT NULL,
  `position`      INT(11)      NOT NULL DEFAULT 1,
  `question_text` TEXT         NOT NULL,
  `explanation`   TEXT         NULL,
  PRIMARY KEY (`id`),
  KEY `idx_quiz_q_course` (`course_id`, `position`),
  CONSTRAINT `fk_quiz_q_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_quiz_options` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `question_id` INT(11)      NOT NULL,
  `option_text` VARCHAR(500) NOT NULL,
  `is_correct`  TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_quiz_opt_q` (`question_id`),
  CONSTRAINT `fk_quiz_opt_q` FOREIGN KEY (`question_id`) REFERENCES `course_quiz_questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_assignments` (
  `id`          INT(11)     NOT NULL AUTO_INCREMENT,
  `course_id`   INT(11)     NOT NULL,
  `target_type` ENUM('company','area','user') NOT NULL,
  `target_id`   INT(11)     NOT NULL,
  `due_date`    DATE        NULL,
  `created_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ca_course` (`course_id`),
  KEY `idx_ca_target` (`target_type`, `target_id`),
  CONSTRAINT `fk_ca_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_enrollments` (
  `id`                     INT(11)     NOT NULL AUTO_INCREMENT,
  `course_id`              INT(11)     NOT NULL,
  `user_id`                INT(11)     NOT NULL,
  `status`                 ENUM('not_started','in_progress','completed','failed_quiz') NOT NULL DEFAULT 'not_started',
  `current_lesson_position` INT(11)    NOT NULL DEFAULT 1,
  `progress_percent`       TINYINT(3)  NOT NULL DEFAULT 0,
  `quiz_score`             TINYINT(3)  NULL,
  `quiz_attempts`          TINYINT(3)  NOT NULL DEFAULT 0,
  `quiz_passed_at`         DATETIME    NULL,
  `completed_at`           DATETIME    NULL,
  `stars_awarded`          INT(11)     NOT NULL DEFAULT 0,
  `started_at`             DATETIME    NULL,
  `updated_at`             TIMESTAMP   NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollment` (`course_id`, `user_id`),
  KEY `idx_enroll_user` (`user_id`),
  CONSTRAINT `fk_enroll_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enroll_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lesson_completions` (
  `id`            INT(11)   NOT NULL AUTO_INCREMENT,
  `enrollment_id` INT(11)   NOT NULL,
  `lesson_id`     INT(11)   NOT NULL,
  `completed_at`  DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lesson_complete` (`enrollment_id`, `lesson_id`),
  CONSTRAINT `fk_lc_enroll` FOREIGN KEY (`enrollment_id`) REFERENCES `course_enrollments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lc_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_star_wallets` (
  `user_id`     INT(11)   NOT NULL,
  `total_stars` INT(11)   NOT NULL DEFAULT 0,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `star_transactions` (
  `id`          INT(11)     NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)     NOT NULL,
  `delta`       INT(11)     NOT NULL,
  `source_type` ENUM('course','task','reward','manual') NOT NULL,
  `source_id`   INT(11)     NULL,
  `note`        VARCHAR(255) NULL,
  `created_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_st_user` (`user_id`),
  CONSTRAINT `fk_st_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rewards` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `company_id`     INT(11)      NOT NULL,
  `title`          VARCHAR(200) NOT NULL,
  `description`    TEXT         NULL,
  `stars_required` INT(11)      NOT NULL DEFAULT 100,
  `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rewards_company` (`company_id`),
  CONSTRAINT `fk_rewards_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reward_redemptions` (
  `id`           INT(11)     NOT NULL AUTO_INCREMENT,
  `user_id`      INT(11)     NOT NULL,
  `reward_id`    INT(11)     NOT NULL,
  `stars_spent`  INT(11)     NOT NULL,
  `status`       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `redeemed_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at`  DATETIME    NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rr_user` (`user_id`),
  CONSTRAINT `fk_rr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rr_reward` FOREIGN KEY (`reward_id`) REFERENCES `rewards`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tareas
CREATE TABLE IF NOT EXISTS `tasks` (
  `id`                INT(11)      NOT NULL AUTO_INCREMENT,
  `company_id`        INT(11)      NOT NULL,
  `title`             VARCHAR(200) NOT NULL,
  `description`       TEXT         NULL,
  `due_date`          DATE         NULL,
  `stars_on_complete` INT(11)      NOT NULL DEFAULT 0,
  `is_active`         TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`        INT(11)      NULL,
  `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_company` (`company_id`),
  CONSTRAINT `fk_tasks_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasks_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `task_assignments` (
  `id`          INT(11)     NOT NULL AUTO_INCREMENT,
  `task_id`     INT(11)     NOT NULL,
  `target_type` ENUM('company','area','user') NOT NULL,
  `target_id`   INT(11)     NOT NULL,
  `created_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ta_task` (`task_id`),
  CONSTRAINT `fk_ta_task` FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `task_completions` (
  `id`           INT(11)   NOT NULL AUTO_INCREMENT,
  `task_id`      INT(11)   NOT NULL,
  `user_id`      INT(11)   NOT NULL,
  `note`         TEXT      NULL,
  `completed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_task_user` (`task_id`, `user_id`),
  CONSTRAINT `fk_tc_task` FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tc_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================================================
-- STEP 14 — migration_learning_enrich.sql
-- ==========================================================================
-- Enriquecimiento capacitación: materiales, anotaciones, comunidad, notas empleado
-- Ejecutar después de migration_learning.sql

ALTER TABLE `course_lessons`
  ADD COLUMN IF NOT EXISTS `objectives` TEXT NULL AFTER `content_body`,
  ADD COLUMN IF NOT EXISTS `instructor_notes` MEDIUMTEXT NULL AFTER `objectives`,
  ADD COLUMN IF NOT EXISTS `key_points` TEXT NULL AFTER `instructor_notes`;

CREATE TABLE IF NOT EXISTS `course_resources` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `course_id`     INT(11)      NOT NULL,
  `lesson_id`     INT(11)      NULL,
  `title`         VARCHAR(200) NOT NULL,
  `description`   VARCHAR(500) NULL,
  `resource_type` ENUM('link','video','pdf','document','spreadsheet','archive','image','other') NOT NULL DEFAULT 'document',
  `external_url`  TEXT         NULL,
  `file_path`     VARCHAR(500) NULL,
  `file_name`     VARCHAR(255) NULL,
  `file_size`     INT(11)      NULL,
  `sort_order`    INT(11)      NOT NULL DEFAULT 0,
  `is_visible`    TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`    INT(11)      NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cr_course` (`course_id`, `sort_order`),
  KEY `idx_cr_lesson` (`lesson_id`),
  CONSTRAINT `fk_cr_res_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cr_res_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cr_res_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_discussions` (
  `id`          INT(11)     NOT NULL AUTO_INCREMENT,
  `course_id`   INT(11)     NOT NULL,
  `lesson_id`   INT(11)     NULL,
  `user_id`     INT(11)     NOT NULL,
  `post_type`   ENUM('question','suggestion','comment') NOT NULL DEFAULT 'question',
  `body`        TEXT        NOT NULL,
  `admin_reply` TEXT        NULL,
  `replied_by`  INT(11)     NULL,
  `replied_at`  DATETIME    NULL,
  `is_resolved` TINYINT(1)  NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cd_course` (`course_id`, `created_at`),
  KEY `idx_cd_lesson` (`lesson_id`),
  CONSTRAINT `fk_cd_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cd_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cd_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cd_replier` FOREIGN KEY (`replied_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_lesson_notes` (
  `id`         INT(11)     NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)     NOT NULL,
  `lesson_id`  INT(11)     NOT NULL,
  `body`       MEDIUMTEXT  NOT NULL,
  `updated_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_lesson_note` (`user_id`, `lesson_id`),
  CONSTRAINT `fk_uln_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uln_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================================================
-- STEP 15 — migration_learning_quiz_bonus.sql
-- ==========================================================================
-- Quiz aleatorio por empleado + bonus primer completador
-- Ejecutar después de migration_learning.sql

ALTER TABLE `courses`
  ADD COLUMN IF NOT EXISTS `first_finisher_bonus` INT(11) NOT NULL DEFAULT 2
    COMMENT 'Estrellas extra para el primero en aprobar el curso' AFTER `stars_on_complete`;

ALTER TABLE `course_enrollments`
  ADD COLUMN IF NOT EXISTS `quiz_order_seed` INT(11) UNSIGNED NULL
    COMMENT 'Semilla para orden aleatorio de preguntas/opciones' AFTER `quiz_attempts`,
  ADD COLUMN IF NOT EXISTS `completion_rank` INT(11) NULL
    COMMENT '1 = primer empleado en completar el curso' AFTER `completed_at`,
  ADD COLUMN IF NOT EXISTS `bonus_stars` INT(11) NOT NULL DEFAULT 0
    COMMENT 'Estrellas extra (ej. primer puesto)' AFTER `stars_awarded`;


-- ==========================================================================
-- STEP 16 — migration_user_login_logs.sql
-- ==========================================================================
-- Registro de cada inicio de sesión exitoso (consulta interna por BD, sin pantalla en la app)
CREATE TABLE IF NOT EXISTS `user_login_logs` (
  `id`         BIGINT(20)   NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      NOT NULL,
  `ip_address` VARCHAR(45)  NULL,
  `user_agent` VARCHAR(500) NULL,
  `logged_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_user_date` (`user_id`, `logged_at`),
  KEY `idx_login_logged_at` (`logged_at`),
  CONSTRAINT `fk_login_log_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================================================
-- STEP 17 — migration_learning_reviews.sql
-- ==========================================================================
-- Reseñas de curso (like / dislike + comentario opcional)
-- Ejecutar después de migration_learning.sql

CREATE TABLE IF NOT EXISTS course_reviews (
    id INT(11) NOT NULL AUTO_INCREMENT,
    course_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    vote ENUM('like', 'dislike') NOT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_course_user (course_id, user_id),
    KEY idx_course_vote (course_id, vote),
    CONSTRAINT fk_crev_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_crev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================================================
-- STEP 18 — migration_areas_global.sql
-- ==========================================================================
-- #18 Áreas globales: compartidas entre todas las empresas (company_id NULL)
-- Ejecutar en phpMyAdmin sobre gestion_horas_extras (tras migration_learning.sql).

-- Quitar FK estricta por empresa
ALTER TABLE `areas` DROP FOREIGN KEY `fk_areas_company`;

ALTER TABLE `areas`
  MODIFY `company_id` INT(11) NULL COMMENT 'NULL = todas las empresas del grupo';

ALTER TABLE `areas`
  ADD CONSTRAINT `fk_areas_company`
  FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL;

-- Unificar duplicados por nombre (ej. Administración en Ecofarma y en otra empresa)
UPDATE `users` u
INNER JOIN `areas` a ON a.id = u.area_id
INNER JOIN (
  SELECT `name`, MIN(`id`) AS keep_id FROM `areas` GROUP BY `name`
) m ON m.`name` = a.`name` AND a.`id` <> m.keep_id
SET u.area_id = m.keep_id;

UPDATE `courses` c
INNER JOIN `areas` a ON a.id = c.area_id
INNER JOIN (
  SELECT `name`, MIN(`id`) AS keep_id FROM `areas` GROUP BY `name`
) m ON m.`name` = a.`name` AND a.`id` <> m.keep_id
SET c.area_id = m.keep_id;

UPDATE `course_assignments` ca
INNER JOIN `areas` a ON ca.target_type = 'area' AND ca.target_id = a.id
INNER JOIN (
  SELECT `name`, MIN(`id`) AS keep_id FROM `areas` GROUP BY `name`
) m ON m.`name` = a.`name` AND a.`id` <> m.keep_id
SET ca.target_id = m.keep_id;

UPDATE `task_assignments` ta
INNER JOIN `areas` a ON ta.target_type = 'area' AND ta.target_id = a.id
INNER JOIN (
  SELECT `name`, MIN(`id`) AS keep_id FROM `areas` GROUP BY `name`
) m ON m.`name` = a.`name` AND a.`id` <> m.keep_id
SET ta.target_id = m.keep_id;

DELETE a FROM `areas` a
INNER JOIN (
  SELECT `name`, MIN(`id`) AS keep_id FROM `areas` GROUP BY `name`
) m ON m.`name` = a.`name` AND a.`id` <> m.keep_id;

-- Todas las áreas pasan a ser del grupo
UPDATE `areas` SET `company_id` = NULL;

-- Evitar duplicar nombres al crear nuevas áreas
ALTER TABLE `areas` ADD UNIQUE KEY `uq_areas_name` (`name`);


-- ==========================================================================
-- STEP 20 — migration_notifications_paystubs.sql
-- ==========================================================================
-- #20 Notificaciones, avisos modales y recibos de sueldo
-- Ejecutar tras migration_learning.sql (y #18 areas global si aplica).

CREATE TABLE IF NOT EXISTS `mail_settings` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `smtp_host`     VARCHAR(255) NULL,
  `smtp_port`     INT(11)      NOT NULL DEFAULT 587,
  `smtp_encryption` ENUM('none','tls','ssl') NOT NULL DEFAULT 'tls',
  `smtp_user`     VARCHAR(255) NULL,
  `smtp_password` VARCHAR(500) NULL,
  `from_email`    VARCHAR(255) NULL,
  `from_name`     VARCHAR(120) NULL,
  `is_enabled`    TINYINT(1)   NOT NULL DEFAULT 0,
  `updated_at`    TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `mail_settings` (
  `id`, `smtp_host`, `smtp_port`, `smtp_encryption`, `smtp_user`,
  `from_email`, `from_name`, `is_enabled`
) VALUES (
  1, 'mail.paviotti.com.ar', 587, 'tls', 'notificaciones@paviotti.com.ar',
  'notificaciones@paviotti.com.ar', 'Paviotti Notificaciones', 0
) ON DUPLICATE KEY UPDATE `id` = `id`;
-- Contraseña SMTP: ejecutar seed_mail_paviotti.sql y activar is_enabled = 1

CREATE TABLE IF NOT EXISTS `announcements` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(200) NOT NULL,
  `body`         TEXT         NOT NULL,
  `image_path`   VARCHAR(500) NULL,
  `link_url`     VARCHAR(500) NULL,
  `link_label`   VARCHAR(120) NULL,
  `starts_at`    DATETIME     NOT NULL,
  `ends_at`      DATETIME     NOT NULL,
  `display_mode` ENUM('once','sessions_3','always') NOT NULL DEFAULT 'once',
  `target_all`   TINYINT(1)   NOT NULL DEFAULT 0,
  `send_email`   TINYINT(1)   NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`   INT(11)      NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ann_dates` (`starts_at`, `ends_at`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `announcement_targets` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` INT(11) NOT NULL,
  `target_type`     ENUM('company','area','user') NOT NULL,
  `target_id`       INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_at_ann` (`announcement_id`),
  CONSTRAINT `fk_at_ann` FOREIGN KEY (`announcement_id`) REFERENCES `announcements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `announcement_user_state` (
  `announcement_id` INT(11) NOT NULL,
  `user_id`         INT(11) NOT NULL,
  `times_shown`     INT(11) NOT NULL DEFAULT 0,
  `dismissed_at`    DATETIME NULL,
  `last_shown_at`   DATETIME NULL,
  PRIMARY KEY (`announcement_id`, `user_id`),
  CONSTRAINT `fk_aus_ann` FOREIGN KEY (`announcement_id`) REFERENCES `announcements`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_aus_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notification_broadcasts` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(200) NOT NULL,
  `body`         TEXT         NULL,
  `link_url`     VARCHAR(500) NULL,
  `type`         ENUM('manual','course','pay_stub') NOT NULL DEFAULT 'manual',
  `starts_at`    DATETIME     NULL,
  `ends_at`      DATETIME     NULL,
  `target_all`   TINYINT(1)   NOT NULL DEFAULT 0,
  `send_email`   TINYINT(1)   NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`   INT(11)      NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notification_targets` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `broadcast_id`  INT(11) NOT NULL,
  `target_type`   ENUM('company','area','user') NOT NULL,
  `target_id`     INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nt_b` (`broadcast_id`),
  CONSTRAINT `fk_nt_b` FOREIGN KEY (`broadcast_id`) REFERENCES `notification_broadcasts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`       INT(11)      NOT NULL,
  `broadcast_id`  INT(11)      NULL,
  `title`         VARCHAR(200) NOT NULL,
  `body`          TEXT         NULL,
  `link_url`      VARCHAR(500) NULL,
  `type`          ENUM('manual','course','pay_stub') NOT NULL DEFAULT 'manual',
  `reference_id`  INT(11)      NULL,
  `read_at`       DATETIME     NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_notif_read` (`user_id`, `read_at`),
  KEY `idx_user_notif_created` (`user_id`, `created_at`),
  UNIQUE KEY `uq_user_notif_type_ref` (`user_id`, `type`, `reference_id`),
  CONSTRAINT `fk_user_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_notifications_broadcast` FOREIGN KEY (`broadcast_id`) REFERENCES `notification_broadcasts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pay_stubs` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`         INT(11)      NOT NULL,
  `company_id`      INT(11)      NOT NULL,
  `period`          CHAR(7)      NOT NULL COMMENT 'YYYY-MM',
  `file_path`       VARCHAR(500) NOT NULL,
  `file_type`       ENUM('pdf','image') NOT NULL DEFAULT 'pdf',
  `admin_note`      TEXT         NULL COMMENT 'Nota o devolución del administrador para el empleado en este período',
  `uploaded_by`     INT(11)      NULL,
  `uploaded_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`          ENUM('pending_signature','signed') NOT NULL DEFAULT 'pending_signature',
  `signed_at`       DATETIME     NULL,
  `signature_path`  VARCHAR(500) NULL,
  `signer_ip`       VARCHAR(45)  NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pay_stub_user_period` (`user_id`, `period`),
  KEY `idx_ps_user_status` (`user_id`, `status`),
  CONSTRAINT `fk_ps_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ps_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================================================
-- STEP 21 — migration_employee_incidents.sql
-- ==========================================================================
-- Incidencias / novedades disciplinarias por empleado (solo administración).
-- Ejecutar tras migration_v2.sql (requiere tabla users).

CREATE TABLE IF NOT EXISTS `employee_incidents` (
  `id`                        INT(11)       NOT NULL AUTO_INCREMENT,
  `user_id`                   INT(11)       NOT NULL,
  `admin_id`                  INT(11)       NOT NULL,
  `incident_type`             VARCHAR(40)   NOT NULL,
  `title`                     VARCHAR(255)  DEFAULT NULL,
  `description`               TEXT          NOT NULL,
  `incident_date`             DATE          NOT NULL,
  `attachment_path`           VARCHAR(255)  DEFAULT NULL,
  `attachment_original_name`  VARCHAR(255)  DEFAULT NULL,
  `created_at`                TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ei_user_date` (`user_id`, `incident_date`),
  KEY `idx_ei_type` (`incident_type`),
  CONSTRAINT `fk_ei_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ei_admin` FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================================================
-- STEP 22 — migration_collective_agreements.sql
-- ==========================================================================
-- Convenios colectivos, períodos de vacaciones (Oct–Sep), saldo multi-período.
-- Ejecutar tras migration_v2.sql y migration_companies_grupo.sql

CREATE TABLE IF NOT EXISTS `collective_agreements` (
  `id`                 INT(11)      NOT NULL AUTO_INCREMENT,
  `code`               VARCHAR(40)  NOT NULL,
  `name`               VARCHAR(255) NOT NULL,
  `description`        TEXT         NULL,
  `period_start_month` TINYINT      NOT NULL DEFAULT 10 COMMENT 'Mes inicio período (10=octubre CEC)',
  `period_start_day`   TINYINT      NOT NULL DEFAULT 1,
  `is_active`          TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ca_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `collective_agreement_rules` (
  `id`                   INT(11)     NOT NULL AUTO_INCREMENT,
  `agreement_id`         INT(11)     NOT NULL,
  `min_months`           INT(11)     NOT NULL DEFAULT 0,
  `max_months`           INT(11)     NULL COMMENT 'NULL = sin tope superior',
  `days_entitled`        INT(11)     NOT NULL,
  `day_count_mode`       ENUM('weekdays','calendar') NOT NULL DEFAULT 'weekdays',
  `allows_split`         TINYINT(1)  NOT NULL DEFAULT 1,
  `allows_carryover`     TINYINT(1)  NOT NULL DEFAULT 1,
  `min_consecutive_days` INT(11)     NULL,
  `notes`                TEXT        NULL,
  PRIMARY KEY (`id`),
  KEY `idx_car_agreement` (`agreement_id`, `min_months`),
  CONSTRAINT `fk_car_agreement` FOREIGN KEY (`agreement_id`) REFERENCES `collective_agreements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `company_agreement_defaults` (
  `company_id`   INT(11) NOT NULL,
  `agreement_id` INT(11) NOT NULL,
  PRIMARY KEY (`company_id`),
  CONSTRAINT `fk_cad_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cad_agreement` FOREIGN KEY (`agreement_id`) REFERENCES `collective_agreements`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `hire_date` DATE NULL COMMENT 'Fecha de ingreso' AFTER `birth_date`,
  ADD COLUMN IF NOT EXISTS `agreement_id` INT(11) NULL COMMENT 'Override convenio' AFTER `hire_date`;

CREATE TABLE IF NOT EXISTS `vacation_balance_periods` (
  `id`                INT(11)    NOT NULL AUTO_INCREMENT,
  `user_id`           INT(11)    NOT NULL,
  `period_label`      VARCHAR(20) NOT NULL COMMENT 'Ej. 2024-2025',
  `period_start`      DATE       NOT NULL,
  `period_end`        DATE       NOT NULL,
  `agreement_id`      INT(11)    NOT NULL,
  `agreement_rule_id` INT(11)    NULL,
  `days_entitled`     DECIMAL(5,1) NOT NULL DEFAULT 0,
  `days_taken`        DECIMAL(5,1) NOT NULL DEFAULT 0,
  `days_pending`      DECIMAL(5,1) NOT NULL DEFAULT 0,
  `status`            ENUM('open','closed') NOT NULL DEFAULT 'open',
  `liquidated_at`     TIMESTAMP  NULL,
  `liquidated_by`     INT(11)    NULL,
  `created_at`        TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vbp_user_period` (`user_id`, `period_label`),
  KEY `idx_vbp_user_status` (`user_id`, `status`),
  CONSTRAINT `fk_vbp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vbp_agreement` FOREIGN KEY (`agreement_id`) REFERENCES `collective_agreements`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `vacation_balance_movements` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `period_id`      INT(11)      NOT NULL,
  `user_id`        INT(11)      NOT NULL,
  `movement_type`  ENUM('accrual','take','adjustment','reversal','opening_balance','import') NOT NULL,
  `source`         ENUM('liquidation','request','planner','manual','import') NOT NULL,
  `days`           DECIMAL(5,1) NOT NULL,
  `request_id`     INT(11)      NULL,
  `schedule_dates` JSON         NULL,
  `notes`          VARCHAR(500) NULL,
  `created_by`     INT(11)      NOT NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vbm_user` (`user_id`, `created_at`),
  KEY `idx_vbm_period` (`period_id`),
  CONSTRAINT `fk_vbm_period` FOREIGN KEY (`period_id`) REFERENCES `vacation_balance_periods`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vbm_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Convenio CEC (reglas configurables por RRHH)
INSERT INTO `collective_agreements` (`code`, `name`, `description`, `period_start_month`, `period_start_day`)
SELECT 'CEC', 'Empleados de Comercio (CEC)', 'Período de vacaciones desde octubre. Ajustar tramos según convenio vigente.', 10, 1
WHERE NOT EXISTS (SELECT 1 FROM `collective_agreements` WHERE `code` = 'CEC');

INSERT INTO `collective_agreement_rules` (`agreement_id`, `min_months`, `max_months`, `days_entitled`, `day_count_mode`, `allows_split`, `allows_carryover`, `notes`)
SELECT ca.id, 0, 59, 14, 'weekdays', 1, 1, 'Menos de 5 años (0-59 meses)'
FROM `collective_agreements` ca WHERE ca.code = 'CEC'
  AND NOT EXISTS (SELECT 1 FROM `collective_agreement_rules` r WHERE r.agreement_id = ca.id AND r.min_months = 0 AND r.max_months = 59);

INSERT INTO `collective_agreement_rules` (`agreement_id`, `min_months`, `max_months`, `days_entitled`, `day_count_mode`, `allows_split`, `allows_carryover`, `notes`)
SELECT ca.id, 60, 119, 21, 'weekdays', 1, 1, '5 a 9 años (60-119 meses)'
FROM `collective_agreements` ca WHERE ca.code = 'CEC'
  AND NOT EXISTS (SELECT 1 FROM `collective_agreement_rules` r WHERE r.agreement_id = ca.id AND r.min_months = 60 AND r.max_months = 119);

INSERT INTO `collective_agreement_rules` (`agreement_id`, `min_months`, `max_months`, `days_entitled`, `day_count_mode`, `allows_split`, `allows_carryover`, `notes`)
SELECT ca.id, 120, 239, 28, 'weekdays', 1, 1, '10 a 20 años (120-239 meses)'
FROM `collective_agreements` ca WHERE ca.code = 'CEC'
  AND NOT EXISTS (SELECT 1 FROM `collective_agreement_rules` r WHERE r.agreement_id = ca.id AND r.min_months = 120 AND r.max_months = 239);

INSERT INTO `collective_agreement_rules` (`agreement_id`, `min_months`, `max_months`, `days_entitled`, `day_count_mode`, `allows_split`, `allows_carryover`, `notes`)
SELECT ca.id, 240, NULL, 35, 'weekdays', 1, 1, 'Más de 20 años (240+ meses)'
FROM `collective_agreements` ca WHERE ca.code = 'CEC'
  AND NOT EXISTS (SELECT 1 FROM `collective_agreement_rules` r WHERE r.agreement_id = ca.id AND r.min_months = 240 AND r.max_months IS NULL);


-- ==========================================================================
-- STEP 23 — migration_schedule_vacation_types.sql
-- ==========================================================================
-- Tipos vacation/leave en planificación (tras migration_collective_agreements.sql)
ALTER TABLE `employee_schedules`
  MODIFY COLUMN `type` ENUM('shift','custom','overtime','vacation','leave') NOT NULL DEFAULT 'shift';


-- ==========================================================================
-- STEP 24 — migration_users_probation_date_phpmyadmin.sql
-- ==========================================================================
ALTER TABLE `users` ADD COLUMN `probation_start_date` DATE NULL COMMENT 'Inicio plan de prueba' AFTER `birth_date`;


-- ==========================================================================
-- STEP 25 — migration_role_supervisor.sql
-- ==========================================================================
-- Rol supervisor (jefe de área): alcance limitado en panel admin.
-- Ejecutar en phpMyAdmin tras las migraciones base.

ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('admin','empleado','supervisor') NOT NULL DEFAULT 'empleado';


-- ==========================================================================
-- STEP 26 — migration_areas_agreement.sql
-- ==========================================================================
-- Convenio colectivo por área (prioridad entre usuario y empresa).
ALTER TABLE `areas`
  ADD COLUMN `agreement_id` INT UNSIGNED NULL DEFAULT NULL AFTER `name`,
  ADD KEY `idx_areas_agreement` (`agreement_id`);


-- ==========================================================================
-- STEP 27 — migration_users_plex_operator.sql
-- ==========================================================================
-- Vínculo operador Ecofarma (API) ↔ empleado RRHH.
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `plex_operator_name` VARCHAR(120) NULL DEFAULT NULL
    COMMENT 'Nombre operador en API Ecofarma (resumen-operadores)' AFTER `agreement_id`;


-- ==========================================================================
-- STEP 28 — migration_peer_stars.sql
-- ==========================================================================
-- Reconocimiento entre pares (saldo separado de estrellas de cursos/premios).

CREATE TABLE IF NOT EXISTS `peer_star_scores` (
  `user_id`      INT(11)   NOT NULL,
  `total_score`  INT(11)   NOT NULL DEFAULT 0,
  `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_peer_score_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `peer_star_ledger` (
  `id`               INT(11)     NOT NULL AUTO_INCREMENT,
  `giver_user_id`    INT(11)     NOT NULL,
  `receiver_user_id` INT(11)     NOT NULL,
  `delta`            INT(11)     NOT NULL,
  `reason_category`  ENUM('objetivo','buena_accion','extraordinario','negativo','otro') NOT NULL DEFAULT 'buena_accion',
  `comment`          VARCHAR(255) NULL,
  `period_ym`        CHAR(7)     NOT NULL COMMENT 'YYYY-MM',
  `created_at`       TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_peer_receiver` (`receiver_user_id`, `period_ym`),
  KEY `idx_peer_giver_pair` (`giver_user_id`, `receiver_user_id`, `period_ym`),
  CONSTRAINT `fk_peer_giver` FOREIGN KEY (`giver_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_peer_receiver` FOREIGN KEY (`receiver_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================================================
-- STEP 29 — migration_surveys.sql
-- ==========================================================================
-- Encuestas (formularios tipo Google Forms) — Notifications Admin.

CREATE TABLE IF NOT EXISTS `surveys` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `company_id`    INT(11)      NOT NULL,
  `title`         VARCHAR(200) NOT NULL,
  `description`   TEXT         NULL,
  `is_anonymous`  TINYINT(1)   NOT NULL DEFAULT 0,
  `status`        ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
  `open_at`       DATETIME     NULL,
  `close_at`      DATETIME     NULL,
  `created_by`    INT(11)      NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_surveys_company` (`company_id`, `status`),
  CONSTRAINT `fk_surveys_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `survey_questions` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `survey_id`   INT(11)      NOT NULL,
  `sort_order`  INT(11)      NOT NULL DEFAULT 0,
  `question_type` ENUM('short_text','long_text','single_choice','multiple_choice','scale','date') NOT NULL,
  `label`       VARCHAR(500) NOT NULL,
  `is_required` TINYINT(1)   NOT NULL DEFAULT 1,
  `config_json` TEXT         NULL COMMENT 'opciones, min/max escala',
  PRIMARY KEY (`id`),
  KEY `idx_sq_survey` (`survey_id`, `sort_order`),
  CONSTRAINT `fk_sq_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `survey_assignments` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `survey_id`   INT(11) NOT NULL,
  `target_type` ENUM('company','area','user') NOT NULL,
  `target_id`   INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sa_survey` (`survey_id`),
  CONSTRAINT `fk_sa_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `survey_responses` (
  `id`           INT(11)   NOT NULL AUTO_INCREMENT,
  `survey_id`    INT(11)   NOT NULL,
  `user_id`      INT(11)   NULL COMMENT 'NULL si encuesta anónima',
  `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sr_survey` (`survey_id`),
  KEY `idx_sr_user` (`survey_id`, `user_id`),
  CONSTRAINT `fk_sr_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `survey_answers` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `response_id` INT(11) NOT NULL,
  `question_id` INT(11) NOT NULL,
  `answer_text` TEXT    NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sa_response` (`response_id`),
  CONSTRAINT `fk_sans_response` FOREIGN KEY (`response_id`) REFERENCES `survey_responses`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sans_question` FOREIGN KEY (`question_id`) REFERENCES `survey_questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `survey_completion_tokens` (
  `id`         INT(11)     NOT NULL AUTO_INCREMENT,
  `survey_id`  INT(11)     NOT NULL,
  `user_id`    INT(11)     NOT NULL,
  `token_hash` CHAR(64)    NOT NULL,
  `created_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_survey_user_token` (`survey_id`, `user_id`),
  CONSTRAINT `fk_sct_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sct_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Campana: tipo survey (ejecutar solo si ya tenés #20)
ALTER TABLE `user_notifications`
  MODIFY COLUMN `type` ENUM('manual','course','pay_stub','survey') NOT NULL DEFAULT 'manual';


-- ==========================================================================
-- STEP 30 — migration_casapav_tasks.sql
-- ==========================================================================
-- Casa Paviotti: extras por tarea (importes en pesos)
-- Ejecutar en phpMyAdmin tras migration_companies_grupo.sql y holidays.
-- Ver MIGRATIONS.md #30

ALTER TABLE `companies`
  ADD COLUMN IF NOT EXISTS `extras_mode` ENUM('hours','casapav_tasks') NOT NULL DEFAULT 'hours'
  COMMENT 'hours = horas 50/100; casapav_tasks = módulo cp_*';

UPDATE `companies` SET `extras_mode` = 'casapav_tasks' WHERE `name` = 'Casa Paviotti';

CREATE TABLE IF NOT EXISTS `cp_task_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `legacy_code` TINYINT UNSIGNED NOT NULL COMMENT 'id_tarea legacy',
  `name` VARCHAR(120) NOT NULL,
  `form_key` VARCHAR(32) NOT NULL,
  `holiday_multiplier_eligible` TINYINT(1) NOT NULL DEFAULT 1,
  `is_manual_amount` TINYINT(1) NOT NULL DEFAULT 0,
  `mvp_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cp_task_legacy` (`legacy_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `cp_task_types` (`legacy_code`, `name`, `form_key`, `holiday_multiplier_eligible`, `is_manual_amount`, `mvp_enabled`, `sort_order`) VALUES
(1, 'Armar servicio de sepelio', 'armar', 1, 0, 1, 10),
(7, 'Realizar servicio de sepelio', 'realizar', 1, 0, 1, 20),
(9, 'Traslado ambulancia', 'ambulancia', 1, 0, 0, 30),
(2, 'Cambio metálica', 'metalica', 1, 0, 0, 40),
(4, 'Cremación', 'cremacion', 1, 0, 1, 50),
(10, 'Viajes sanitarios', 'viajes', 1, 0, 0, 60),
(8, 'Tanatopraxia', 'tanato', 1, 0, 0, 70),
(5, 'Gestión / trámite', 'gestion', 1, 0, 0, 80),
(6, 'Mantenimiento / tareas', 'mantenimiento', 0, 1, 0, 90),
(3, 'Comisiones parcela', 'parcelas', 0, 1, 0, 100),
(11, 'Tareas otras empresas', 'externas', 0, 1, 0, 110)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

CREATE TABLE IF NOT EXISTS `cp_localities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `has_additional` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cp_loc_company` (`company_id`),
  CONSTRAINT `fk_cp_loc_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cp_pickup_places` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cp_pickup_company` (`company_id`),
  CONSTRAINT `fk_cp_pickup_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cp_employee_rates` (
  `user_id` INT(11) NOT NULL,
  `armar_s` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `realizar_s` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `cremacion` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `cremacion_adicional` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `localidades` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `covid` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_cp_rates_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cp_task_closures` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) NOT NULL,
  `lot_number` INT(11) NOT NULL,
  `closed_by` INT(11) NULL,
  `closed_at` DATETIME NOT NULL,
  `total_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `iva_rate` DECIMAL(5,4) NOT NULL DEFAULT 0.1900,
  `iva_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `final_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `notes` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cp_closure_company` (`company_id`, `closed_at`),
  CONSTRAINT `fk_cp_closure_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cp_task_entries` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `task_type_id` INT(11) NOT NULL,
  `activity_date` DATE NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `amount_base` DECIMAL(12,2) NOT NULL,
  `is_holiday` TINYINT(1) NOT NULL DEFAULT 0,
  `holiday_multiplier` DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  `status` ENUM('pending','closed') NOT NULL DEFAULT 'pending',
  `closure_id` INT(11) NULL,
  `deceased_code` VARCHAR(32) NULL,
  `deceased_name` VARCHAR(200) NULL,
  `companion_user_id` INT(11) NULL,
  `meta_json` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cp_entry_company_status` (`company_id`, `status`, `activity_date`),
  KEY `idx_cp_entry_user_date` (`user_id`, `activity_date`),
  UNIQUE KEY `uq_cp_entry_dup` (`user_id`, `task_type_id`, `deceased_code`),
  CONSTRAINT `fk_cp_entry_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_entry_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_entry_type` FOREIGN KEY (`task_type_id`) REFERENCES `cp_task_types`(`id`),
  CONSTRAINT `fk_cp_entry_closure` FOREIGN KEY (`closure_id`) REFERENCES `cp_task_closures`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tarifas vacías para empleados Casa Paviotti (completar en admin → Extras CP → Tarifas)
INSERT INTO `cp_employee_rates` (`user_id`, `armar_s`, `realizar_s`, `cremacion`, `cremacion_adicional`, `localidades`, `covid`)
SELECT u.id, 0, 0, 0, 0, 0, 0
FROM `users` u
INNER JOIN `companies` c ON c.id = u.company_id AND c.extras_mode = 'casapav_tasks'
WHERE u.is_active = 1
  AND NOT EXISTS (SELECT 1 FROM `cp_employee_rates` r WHERE r.user_id = u.id);


-- ==========================================================================
-- STEP 31 — migration_casapav_phase_b.sql
-- ==========================================================================
-- Casa Paviotti Fase B: resto de tareas, tarifas extendidas, externas, catálogos
-- Tras migration_casapav_tasks.sql (#30). Ver MIGRATIONS.md #31

UPDATE `cp_task_types` SET `mvp_enabled` = 1 WHERE `is_active` = 1;

ALTER TABLE `cp_employee_rates`
  ADD COLUMN IF NOT EXISTS `cambio_metalica` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `covid`,
  ADD COLUMN IF NOT EXISTS `ambu_localidades` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `cambio_metalica`,
  ADD COLUMN IF NOT EXISTS `ambu_vm` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `ambu_localidades`,
  ADD COLUMN IF NOT EXISTS `viajes_activa` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `ambu_vm`,
  ADD COLUMN IF NOT EXISTS `viajes_pasiva` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `viajes_activa`,
  ADD COLUMN IF NOT EXISTS `tanato` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `viajes_pasiva`,
  ADD COLUMN IF NOT EXISTS `gestion_tramites` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `tanato`;

CREATE TABLE IF NOT EXISTS `cp_external_companies` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_cp_extco_company` (`company_id`),
  CONSTRAINT `fk_cp_extco_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cp_external_entries` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `external_company_id` INT(11) NOT NULL,
  `task_label` VARCHAR(200) NOT NULL,
  `activity_date` DATE NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `amount_base` DECIMAL(12,2) NOT NULL,
  `is_holiday` TINYINT(1) NOT NULL DEFAULT 0,
  `holiday_multiplier` DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  `status` ENUM('pending','closed') NOT NULL DEFAULT 'pending',
  `closure_id` INT(11) NULL,
  `comment` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cp_extentry_company` (`company_id`, `status`),
  CONSTRAINT `fk_cp_extentry_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_extentry_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_extentry_extco` FOREIGN KEY (`external_company_id`) REFERENCES `cp_external_companies`(`id`),
  CONSTRAINT `fk_cp_extentry_closure` FOREIGN KEY (`closure_id`) REFERENCES `cp_task_closures`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catálogos Casa Paviotti (company_id dinámico)
INSERT INTO `cp_localities` (`company_id`, `name`, `has_additional`)
SELECT c.id, v.name, v.adicional FROM `companies` c
CROSS JOIN (
  SELECT 'Casa Central' AS name, 0 AS adicional UNION ALL
  SELECT 'Villa maria', 2 UNION ALL SELECT 'Villa Nueva', 2 UNION ALL
  SELECT 'Los Zorros', 1 UNION ALL SELECT 'Ausonia', 0 UNION ALL
  SELECT 'Alto Alegre', 0 UNION ALL SELECT 'Arroyo Algodón', 0 UNION ALL
  SELECT 'Arroyo Cabral', 0 UNION ALL SELECT 'Ballesteros Sud', 0 UNION ALL
  SELECT 'Carrilobo', 1 UNION ALL SELECT 'Chazon', 1 UNION ALL
  SELECT 'Etruria', 1 UNION ALL SELECT 'Idiazabal', 1 UNION ALL
  SELECT 'La Laguna', 0 UNION ALL SELECT 'La Palestina', 0 UNION ALL
  SELECT 'La Playosa', 0 UNION ALL SELECT 'Luca', 0 UNION ALL
  SELECT 'Pasco', 0 UNION ALL SELECT 'Silvio Pellico', 1 UNION ALL
  SELECT 'Ticino', 0 UNION ALL SELECT 'Tio Pujio', 0 UNION ALL SELECT 'Otro', 0
) v
WHERE c.extras_mode = 'casapav_tasks'
  AND NOT EXISTS (
    SELECT 1 FROM `cp_localities` l WHERE l.company_id = c.id AND l.name = v.name
  );

INSERT INTO `cp_pickup_places` (`company_id`, `name`)
SELECT c.id, v.n FROM `companies` c
CROSS JOIN (
  SELECT 'Domicilio' AS n UNION ALL SELECT 'Morgue' UNION ALL SELECT 'Clínica Fusavin' UNION ALL
  SELECT 'Sanatorio Cruz Azul' UNION ALL SELECT 'Clínica de Especialidades' UNION ALL
  SELECT 'Sanatorio de La Cañada' UNION ALL SELECT 'Clínica Marañon' UNION ALL
  SELECT 'Clínica San Martín' UNION ALL SELECT 'Hospital Regional Pasteur' UNION ALL SELECT 'Otro'
) v
WHERE c.extras_mode = 'casapav_tasks'
  AND NOT EXISTS (SELECT 1 FROM `cp_pickup_places` p WHERE p.company_id = c.id AND p.name = v.n);

INSERT INTO `cp_external_companies` (`company_id`, `name`)
SELECT c.id, v.n FROM `companies` c
CROSS JOIN (
  SELECT 'Servicios Sociales Paviotti' AS n UNION ALL SELECT 'Ecofarma' UNION ALL
  SELECT 'A.M.S.S.I (Div. Salud.)' UNION ALL SELECT 'La Naturaleza' UNION ALL
  SELECT 'Otra' UNION ALL SELECT 'Crematorio Cintra' UNION ALL SELECT 'Alladio Bell Ville'
) v
WHERE c.extras_mode = 'casapav_tasks'
  AND NOT EXISTS (SELECT 1 FROM `cp_external_companies` e WHERE e.company_id = c.id AND e.name = v.n);


-- ==========================================================================
-- STEP 32 — migration_system_settings.sql
-- ==========================================================================
-- #32 Panel de configuración del sistema (system_settings)
-- Ejecutar tras migration_notifications_paystubs.sql (#20) si usás correo.

CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key`       VARCHAR(64)  NOT NULL,
  `setting_value`     TEXT         NULL,
  `value_type`        ENUM('string','int','bool','json') NOT NULL DEFAULT 'string',
  `group_key`         VARCHAR(32)  NOT NULL DEFAULT 'general',
  `is_secret`         TINYINT(1)   NOT NULL DEFAULT 0,
  `updated_at`        TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by_user_id` INT(11)     NULL DEFAULT NULL,
  PRIMARY KEY (`setting_key`),
  KEY `idx_system_settings_group` (`group_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PIN inicial del panel: lautaro (cambiar en Configuración → Seguridad)
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `value_type`, `group_key`, `is_secret`) VALUES
('config_unlock_secret_hash', '$2y$10$sNC9Tno6/.G9C7s2.xlHwursrCQ/6iLYezSiFT3LkGtlHlEEPNBQm', 'string', 'security', 1),
('sitename', 'Paviotti RRHH', 'string', 'general', 0),
('default_company_name', 'Ecofarma', 'string', 'general', 0),
('app_debug', '0', 'bool', 'general', 0),
('cp_deceased_list_limit', '50', 'int', 'casapav', 0),
('extintos_db_host', 'localhost', 'string', 'casapav', 0),
('extintos_db_name', 'paviotti_extintos', 'string', 'casapav', 0),
('extintos_db_user', '', 'string', 'casapav', 0),
('extintos_db_pass', '', 'string', 'casapav', 1),
('cp_extintos_table_sepulio', 'extintosH', 'string', 'casapav', 0),
('cp_extintos_table_tanato', 'extintos', 'string', 'casapav', 0),
('cp_duplicate_check_enabled', '1', 'bool', 'casapav', 0),
('attendance_late_tolerance_min', '5', 'int', 'attendance', 0),
('attendance_early_leave_tolerance_min', '5', 'int', 'attendance', 0),
('clock_api_base_url', 'http://gpaviotti.com.ar:6333', 'string', 'integrations', 0),
('clock_api_email', '', 'string', 'integrations', 0),
('clock_api_password', '', 'string', 'integrations', 1),
('ecofarma_default_obra_social', '999900', 'string', 'integrations', 0),
('ecofarma_default_comision_pct', '7', 'int', 'integrations', 0),
('employee_show_overtime', '1', 'bool', 'employee', 0),
('employee_show_overtime_on_home', '1', 'bool', 'employee', 0),
('employee_show_cp_extras', '1', 'bool', 'employee', 0),
('employee_show_cp_extras_on_home', '1', 'bool', 'employee', 0),
('employee_show_vacation_balance', '1', 'bool', 'employee', 0),
('employee_show_pay_stubs', '1', 'bool', 'employee', 0),
('employee_show_training', '1', 'bool', 'employee', 0),
('employee_show_peer_stars', '1', 'bool', 'employee', 0),
('employee_show_surveys', '1', 'bool', 'employee', 0),
('employee_show_suggestions', '1', 'bool', 'employee', 0),
('employee_show_mi_mes', '1', 'bool', 'employee', 0),
('overtime_visible_admin', '1', 'bool', 'overtime', 0),
('overtime_visible_supervisor', '1', 'bool', 'overtime', 0)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;


-- ==========================================================================
-- STEP 32b — migration_system_settings_employee_portal.sql
-- ==========================================================================
-- #32b Visibilidad del portal empleado (tras migration_system_settings.sql)
-- Todos los valores por defecto 1 = visible.

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `value_type`, `group_key`, `is_secret`) VALUES
('employee_show_overtime', '1', 'bool', 'employee', 0),
('employee_show_overtime_on_home', '1', 'bool', 'employee', 0),
('employee_show_cp_extras', '1', 'bool', 'employee', 0),
('employee_show_cp_extras_on_home', '1', 'bool', 'employee', 0),
('employee_show_vacation_balance', '1', 'bool', 'employee', 0),
('employee_show_pay_stubs', '1', 'bool', 'employee', 0),
('employee_show_training', '1', 'bool', 'employee', 0),
('employee_show_peer_stars', '1', 'bool', 'employee', 0),
('employee_show_surveys', '1', 'bool', 'employee', 0),
('employee_show_suggestions', '1', 'bool', 'employee', 0),
('employee_show_mi_mes', '1', 'bool', 'employee', 0)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;


-- ==========================================================================
-- STEP 33 — migration_overtime_visibility.sql
-- ==========================================================================
-- #33 Horas extras: visibilidad por empresa, área y rol (admin / supervisor / empleado)
-- Ejecutar tras migration_system_settings.sql (#32).
-- Requiere MySQL 8+ o MariaDB 10.3+ (ADD COLUMN IF NOT EXISTS). En versiones viejas, omitir IF NOT EXISTS.

ALTER TABLE `companies`
  ADD COLUMN IF NOT EXISTS `show_overtime` TINYINT(1) NOT NULL DEFAULT 1
  COMMENT '1=empresa usa módulo horas extras clásicas (50/100); 0=oculto para todo el personal';

ALTER TABLE `areas`
  ADD COLUMN IF NOT EXISTS `show_overtime` TINYINT(1) NULL DEFAULT NULL
  COMMENT 'NULL=hereda empresa; 0=oculto en el área; 1=forzar visible en el área';

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `value_type`, `group_key`, `is_secret`) VALUES
('overtime_visible_admin', '1', 'bool', 'overtime', 0),
('overtime_visible_supervisor', '1', 'bool', 'overtime', 0)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;


-- ==========================================================================
-- STEP 34 — migration_cp_extras_visibility.sql
-- ==========================================================================
-- #34 Extras CP: visibilidad por empresa/área + recargo de cierre configurable
-- Ejecutar tras migration_overtime_visibility.sql (#33).

ALTER TABLE `companies`
  ADD COLUMN IF NOT EXISTS `show_cp_extras` TINYINT(1) NOT NULL DEFAULT 1
  COMMENT '1=portal y admin CP visibles (empresas casapav_tasks)';

ALTER TABLE `areas`
  ADD COLUMN IF NOT EXISTS `show_cp_extras` TINYINT(1) NULL DEFAULT NULL
  COMMENT 'NULL=hereda empresa; 0=oculto; 1=visible en el área';

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `value_type`, `group_key`, `is_secret`) VALUES
('cp_closure_markup_pct', '19.5', 'string', 'casapav', 0),
('cp_extras_visible_admin', '1', 'bool', 'casapav', 0),
('cp_extras_visible_supervisor', '1', 'bool', 'casapav', 0)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;


-- ==========================================================================
-- STEP 35 — migration_prode_wc2026.sql
-- ==========================================================================
-- Copa del mundo 2026 — fase de grupos (12 grupos, 72 partidos).
-- Ejecutar una vez. Luego: php scripts/seed_prode_wc2026.php

CREATE TABLE IF NOT EXISTS `prode_editions` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `slug`         VARCHAR(32)  NOT NULL,
  `title`        VARCHAR(120) NOT NULL,
  `status`       ENUM('upcoming','open','closed') NOT NULL DEFAULT 'upcoming',
  `groups_only`  TINYINT(1)   NOT NULL DEFAULT 1,
  `starts_on`    DATE         NULL,
  `ends_on`      DATE         NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prode_edition_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prode_groups` (
  `id`          INT(11)     NOT NULL AUTO_INCREMENT,
  `edition_id`  INT(11)     NOT NULL,
  `code`        CHAR(1)     NOT NULL,
  `sort_order`  TINYINT     NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prode_group_edition_code` (`edition_id`, `code`),
  CONSTRAINT `fk_prode_groups_edition` FOREIGN KEY (`edition_id`) REFERENCES `prode_editions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prode_teams` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `edition_id`  INT(11)      NOT NULL,
  `name`        VARCHAR(80)  NOT NULL,
  `flag_code`   VARCHAR(8)   NOT NULL COMMENT 'ISO o código FIFA (eng, sco)',
  `fifa_code`   VARCHAR(8)   NULL,
  PRIMARY KEY (`id`),
  KEY `idx_prode_teams_edition` (`edition_id`),
  CONSTRAINT `fk_prode_teams_edition` FOREIGN KEY (`edition_id`) REFERENCES `prode_editions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prode_group_teams` (
  `group_id`  INT(11) NOT NULL,
  `team_id`   INT(11) NOT NULL,
  `slot`      TINYINT NOT NULL COMMENT '1-4 posición en grupo',
  PRIMARY KEY (`group_id`, `team_id`),
  UNIQUE KEY `uq_prode_group_slot` (`group_id`, `slot`),
  CONSTRAINT `fk_pgt_group` FOREIGN KEY (`group_id`) REFERENCES `prode_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pgt_team` FOREIGN KEY (`team_id`) REFERENCES `prode_teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prode_matches` (
  `id`                 INT(11)   NOT NULL AUTO_INCREMENT,
  `edition_id`         INT(11)   NOT NULL,
  `group_id`           INT(11)   NOT NULL,
  `match_number`       TINYINT   NOT NULL,
  `home_team_id`       INT(11)   NOT NULL,
  `away_team_id`       INT(11)   NOT NULL,
  `kickoff_at`         DATETIME  NOT NULL,
  `home_score_actual`  TINYINT   NULL,
  `away_score_actual`  TINYINT   NULL,
  `status`             ENUM('scheduled','finished','cancelled') NOT NULL DEFAULT 'scheduled',
  `predictions_locked` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prode_match_group_num` (`group_id`, `match_number`),
  KEY `idx_prode_matches_kickoff` (`kickoff_at`),
  KEY `idx_prode_matches_edition` (`edition_id`),
  CONSTRAINT `fk_prode_matches_edition` FOREIGN KEY (`edition_id`) REFERENCES `prode_editions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prode_matches_group` FOREIGN KEY (`group_id`) REFERENCES `prode_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prode_matches_home` FOREIGN KEY (`home_team_id`) REFERENCES `prode_teams` (`id`),
  CONSTRAINT `fk_prode_matches_away` FOREIGN KEY (`away_team_id`) REFERENCES `prode_teams` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prode_user_entries` (
  `id`                 INT(11)   NOT NULL AUTO_INCREMENT,
  `edition_id`         INT(11)   NOT NULL,
  `user_id`            INT(11)   NOT NULL,
  `status`             ENUM('draft','submitted') NOT NULL DEFAULT 'draft',
  `submitted_at`       DATETIME  NULL,
  `total_points`       INT(11)   NOT NULL DEFAULT 0,
  `exact_hits`         INT(11)   NOT NULL DEFAULT 0,
  `result_hits`        INT(11)   NOT NULL DEFAULT 0,
  `predictions_count`  TINYINT   NOT NULL DEFAULT 0,
  `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prode_user_entry` (`edition_id`, `user_id`),
  KEY `idx_prode_entries_points` (`edition_id`, `total_points` DESC),
  CONSTRAINT `fk_prode_entries_edition` FOREIGN KEY (`edition_id`) REFERENCES `prode_editions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prode_entries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prode_predictions` (
  `id`               INT(11)   NOT NULL AUTO_INCREMENT,
  `user_id`          INT(11)   NOT NULL,
  `match_id`         INT(11)   NOT NULL,
  `home_score_pred`  TINYINT   NULL,
  `away_score_pred`  TINYINT   NULL,
  `points_earned`    TINYINT   NOT NULL DEFAULT 0,
  `is_submitted`     TINYINT(1) NOT NULL DEFAULT 0,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prode_pred_user_match` (`user_id`, `match_id`),
  KEY `idx_prode_pred_match` (`match_id`),
  CONSTRAINT `fk_prode_pred_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prode_pred_match` FOREIGN KEY (`match_id`) REFERENCES `prode_matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `value_type`, `group_key`, `is_secret`) VALUES
('employee_show_prode', '1', 'bool', 'employee', 0)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;


-- ==========================================================================
-- STEP 36 — migration_survey_responses_unique.sql
-- ==========================================================================
-- Evita respuestas duplicadas en encuestas identificadas (user_id NOT NULL).
-- Ejecutar tras migration_surveys.sql (#21).

DELETE sr1 FROM survey_responses sr1
INNER JOIN survey_responses sr2
  ON sr1.survey_id = sr2.survey_id
 AND sr1.user_id = sr2.user_id
 AND sr1.user_id IS NOT NULL
 AND sr1.id > sr2.id;

ALTER TABLE survey_responses
  ADD UNIQUE KEY uq_survey_user_response (survey_id, user_id);


-- ==========================================================================
-- STEP 37 — migration_cp_closure_lot_unique.sql
-- ==========================================================================
-- Evita lotes duplicados por empresa bajo cierres concurrentes.
-- Ejecutar tras migration_casapav_tasks.sql.

ALTER TABLE cp_task_closures
  ADD UNIQUE KEY uq_cp_closure_company_lot (company_id, lot_number);


SET FOREIGN_KEY_CHECKS = 1;
SELECT 'migration_hosting_full completada.' AS resultado;
