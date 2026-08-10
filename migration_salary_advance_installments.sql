-- #39 Cuotas de devolución de adelantos (tras #38 migration_salary_advances.sql)

CREATE TABLE IF NOT EXISTS `salary_advance_installments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `salary_advance_id` INT(11) NOT NULL,
  `installment_number` TINYINT UNSIGNED NOT NULL,
  `due_month` CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `amount` DECIMAL(12,2) NOT NULL,
  `is_deducted` TINYINT(1) NOT NULL DEFAULT 0,
  `deducted_at` DATETIME NULL,
  `deducted_by` INT(11) NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_salary_advance_installment` (`salary_advance_id`, `installment_number`),
  KEY `idx_sai_due_month` (`due_month`),
  CONSTRAINT `fk_sai_advance` FOREIGN KEY (`salary_advance_id`) REFERENCES `salary_advances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
