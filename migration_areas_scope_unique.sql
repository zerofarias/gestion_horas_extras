-- Opcional tras migration_areas_global.sql: mismo nombre permitido en empresas distintas
-- (ej. "Depósito" solo en Ecofarma y otro "Depósito" en otra empresa).

ALTER TABLE `areas` DROP INDEX `uq_areas_name`;

ALTER TABLE `areas` ADD UNIQUE KEY `uq_areas_name_company` (`name`, `company_id`);
