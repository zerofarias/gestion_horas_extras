-- Ampliación: permisos, salida temprana (médico, trámite), aviso previo
-- Ejecutar si ya creó attendance_justifications con la migración inicial.

ALTER TABLE `attendance_justifications`
  MODIFY COLUMN `justification_type` ENUM(
    'medical_certificate',
    'attended_no_clock',
    'authorized_absence',
    'early_leave_medical',
    'early_leave_errand',
    'early_leave_authorized',
    'other'
  ) NOT NULL;

-- Si leave_time ya existe, omitir la siguiente línea:
ALTER TABLE `attendance_justifications`
  ADD COLUMN `leave_time` TIME NULL COMMENT 'Hora de salida (permiso anticipado)' AFTER `justification_type`;

-- Si prior_notice ya existe, omitir la siguiente línea:
ALTER TABLE `attendance_justifications`
  ADD COLUMN `prior_notice` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=hubo aviso previo' AFTER `leave_time`;
