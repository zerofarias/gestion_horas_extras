-- #34 Extras CP: visibilidad por empresa/área + recargo de cierre configurable
-- Ejecutar tras migration_overtime_visibility.sql (#33).

ALTER TABLE `companies`
  ADD COLUMN IF NOT EXISTS `show_cp_extras` TINYINT(1) NOT NULL DEFAULT 1
  COMMENT '1=portal y admin CP visibles (empresas casapav_tasks)';

ALTER TABLE `areas`
  ADD COLUMN IF NOT EXISTS `show_cp_extras` TINYINT(1) NULL DEFAULT NULL
  COMMENT 'NULL=hereda empresa; 0=oculto; 1=visible en el área';

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `value_type`, `group_key`, `is_secret`) VALUES
('cp_closure_markup_pct', '19.5', 'string', 'casapav', 0),
('cp_extras_visible_admin', '1', 'bool', 'casapav', 0),
('cp_extras_visible_supervisor', '1', 'bool', 'casapav', 0)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
