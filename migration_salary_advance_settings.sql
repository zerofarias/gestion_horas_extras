-- #38b Configuración adelantos de sueldo (tras #38 y migration_system_settings.sql #32)

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `value_type`, `group_key`, `is_secret`) VALUES
('salary_advance_enabled', '1', 'bool', 'salary_advance', 0),
('employee_show_salary_advance', '1', 'bool', 'employee', 0),
('salary_advance_max_annual', '2', 'int', 'salary_advance', 0),
('salary_advance_max_salary_pct', '50', 'int', 'salary_advance', 0),
('salary_advance_max_installments_employee', '2', 'int', 'salary_advance', 0),
('salary_advance_max_installments_hr', '6', 'int', 'salary_advance', 0),
('salary_advance_one_pending_only', '1', 'bool', 'salary_advance', 0),
('salary_advance_require_reference_salary', '1', 'bool', 'salary_advance', 0),
('salary_advance_min_amount', '1', 'int', 'salary_advance', 0)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
