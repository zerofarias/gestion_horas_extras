-- #32 Panel de configuración del sistema (system_settings)
-- Ejecutar tras migration_notifications_paystubs.sql (#20) si usás correo.

CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key`       VARCHAR(64)  NOT NULL,
  `setting_value`     TEXT         NULL,
  `value_type`        ENUM('string','int','bool','json') NOT NULL DEFAULT 'string',
  `group_key`         VARCHAR(32)  NOT NULL DEFAULT 'general',
  `is_secret`         TINYINT(1)   NOT NULL DEFAULT 0,
  `updated_at`        TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by_user_id` INT(11)     NULL DEFAULT NULL,
  PRIMARY KEY (`setting_key`),
  KEY `idx_system_settings_group` (`group_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PIN inicial del panel: lautaro (cambiar en Configuración → Seguridad)
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `value_type`, `group_key`, `is_secret`) VALUES
('config_unlock_secret_hash', '$2y$10$sNC9Tno6/.G9C7s2.xlHwursrCQ/6iLYezSiFT3LkGtlHlEEPNBQm', 'string', 'security', 1),
('sitename', 'Paviotti RRHH', 'string', 'general', 0),
('default_company_name', 'Ecofarma', 'string', 'general', 0),
('app_debug', '0', 'bool', 'general', 0),
('cp_deceased_list_limit', '50', 'int', 'casapav', 0),
('extintos_db_host', 'localhost', 'string', 'casapav', 0),
('extintos_db_name', 'paviotti_extintos', 'string', 'casapav', 0),
('extintos_db_user', '', 'string', 'casapav', 0),
('extintos_db_pass', '', 'string', 'casapav', 1),
('cp_extintos_table_sepulio', 'extintosH', 'string', 'casapav', 0),
('cp_extintos_table_tanato', 'extintos', 'string', 'casapav', 0),
('cp_duplicate_check_enabled', '1', 'bool', 'casapav', 0),
('attendance_late_tolerance_min', '5', 'int', 'attendance', 0),
('attendance_early_leave_tolerance_min', '5', 'int', 'attendance', 0),
('clock_api_base_url', 'http://gpaviotti.com.ar:6333', 'string', 'integrations', 0),
('clock_api_email', '', 'string', 'integrations', 0),
('clock_api_password', '', 'string', 'integrations', 1),
('ecofarma_default_obra_social', '999900', 'string', 'integrations', 0),
('ecofarma_default_comision_pct', '7', 'int', 'integrations', 0),
('employee_show_overtime', '1', 'bool', 'employee', 0),
('employee_show_overtime_on_home', '1', 'bool', 'employee', 0),
('employee_show_cp_extras', '1', 'bool', 'employee', 0),
('employee_show_cp_extras_on_home', '1', 'bool', 'employee', 0),
('employee_show_vacation_balance', '1', 'bool', 'employee', 0),
('employee_show_pay_stubs', '1', 'bool', 'employee', 0),
('employee_show_training', '1', 'bool', 'employee', 0),
('employee_show_peer_stars', '1', 'bool', 'employee', 0),
('employee_show_surveys', '1', 'bool', 'employee', 0),
('employee_show_suggestions', '1', 'bool', 'employee', 0),
('employee_show_mi_mes', '1', 'bool', 'employee', 0),
('overtime_visible_admin', '1', 'bool', 'overtime', 0),
('overtime_visible_supervisor', '1', 'bool', 'overtime', 0)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
