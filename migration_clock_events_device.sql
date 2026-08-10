-- Columnas de dispositivo/dirección en marcaciones (tras migration_v2.sql).
-- Requeridas por Schedule.php, SyncModel y ficha de empleado.

ALTER TABLE `clock_events`
  ADD COLUMN IF NOT EXISTS `device_name` VARCHAR(100) NULL AFTER `clock_id`,
  ADD COLUMN IF NOT EXISTS `direction` VARCHAR(50) NULL AFTER `device_name`,
  ADD COLUMN IF NOT EXISTS `direction_label` VARCHAR(100) NULL AFTER `direction`;
