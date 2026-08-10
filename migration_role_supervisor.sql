-- Rol supervisor (jefe de área): alcance limitado en panel admin.
-- Ejecutar en phpMyAdmin tras las migraciones base.

ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('admin','empleado','supervisor') NOT NULL DEFAULT 'empleado';
