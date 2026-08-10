-- #38 Adelantos de sueldo (tras migration_system_settings.sql #32)

CREATE TABLE IF NOT EXISTS `salary_advances` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `company_id` INT(11) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `reference_salary` DECIMAL(12,2) NULL COMMENT 'Sueldo mensual declarado por el empleado',
  `installments_requested` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `installments_approved` TINYINT UNSIGNED NULL,
  `hr_installments_override` TINYINT(1) NOT NULL DEFAULT 0,
  `reason` TEXT NULL,
  `status` ENUM('Pendiente','Aprobado','Rechazado') NOT NULL DEFAULT 'Pendiente',
  `admin_notes` TEXT NULL,
  `approved_by` INT(11) NULL,
  `approved_at` DATETIME NULL,
  `rejected_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_salary_advances_company_status` (`company_id`, `status`),
  KEY `idx_salary_advances_user_created` (`user_id`, `created_at`),
  CONSTRAINT `fk_salary_advances_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_salary_advances_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
