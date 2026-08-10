-- #32b Visibilidad del portal empleado (tras migration_system_settings.sql)
-- Todos los valores por defecto 1 = visible.

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `value_type`, `group_key`, `is_secret`) VALUES
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
('employee_show_mi_mes', '1', 'bool', 'employee', 0)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
