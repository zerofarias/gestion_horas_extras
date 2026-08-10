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
