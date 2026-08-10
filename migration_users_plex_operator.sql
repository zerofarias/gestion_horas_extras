-- Vínculo operador Ecofarma (API) ↔ empleado RRHH.
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `plex_operator_name` VARCHAR(120) NULL DEFAULT NULL
    COMMENT 'Nombre operador en API Ecofarma (resumen-operadores)' AFTER `agreement_id`;
