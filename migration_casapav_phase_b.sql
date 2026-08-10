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
