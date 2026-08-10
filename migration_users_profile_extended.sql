-- Datos personales del empleado (ficha / editar usuario)
-- Ejecutar en phpMyAdmin sobre la base del proyecto.

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `email`                    VARCHAR(120)   NULL AFTER `full_name`,
  ADD COLUMN IF NOT EXISTS `phone_number`             VARCHAR(40)    NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `address`                  VARCHAR(255)   NULL AFTER `phone_number`,
  ADD COLUMN IF NOT EXISTS `document_number`          VARCHAR(20)    NULL COMMENT 'DNI / documento' AFTER `address`,
  ADD COLUMN IF NOT EXISTS `cuil`                       VARCHAR(15)    NULL AFTER `document_number`,
  ADD COLUMN IF NOT EXISTS `sex`                        ENUM('M','F','X') NULL COMMENT 'Sexo registrado (legal/admin)' AFTER `cuil`,
  ADD COLUMN IF NOT EXISTS `gender`                     VARCHAR(40)    NULL COMMENT 'Identidad de género' AFTER `sex`,
  ADD COLUMN IF NOT EXISTS `emergency_contact_name`   VARCHAR(120)   NULL AFTER `gender`,
  ADD COLUMN IF NOT EXISTS `emergency_contact_phone`  VARCHAR(40)    NULL AFTER `emergency_contact_name`;
