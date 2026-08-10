-- Convenio colectivo por área (prioridad entre usuario y empresa).
ALTER TABLE `areas`
  ADD COLUMN `agreement_id` INT UNSIGNED NULL DEFAULT NULL AFTER `name`,
  ADD KEY `idx_areas_agreement` (`agreement_id`);
