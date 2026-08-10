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
