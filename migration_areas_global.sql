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
