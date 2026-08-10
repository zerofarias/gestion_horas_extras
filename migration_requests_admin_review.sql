-- Certificado admin, descarte de bandeja y notas internas en solicitudes
-- Ejecutar una vez en MySQL.

ALTER TABLE `requests`
  ADD COLUMN `certificate_path` VARCHAR(500) NULL COMMENT 'Certificado adjunto (admin)' AFTER `reason`,
  ADD COLUMN `admin_dismissed_at` DATETIME NULL COMMENT 'Quitada de prioridad sin aprobar/rechazar' AFTER `status`,
  ADD COLUMN `admin_notes` TEXT NULL COMMENT 'Notas internas del administrador' AFTER `admin_dismissed_at`;
