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
