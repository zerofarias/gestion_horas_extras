-- Incidencias / novedades disciplinarias por empleado (solo administración).
-- Ejecutar tras migration_v2.sql (requiere tabla users).

CREATE TABLE IF NOT EXISTS `employee_incidents` (
  `id`                        INT(11)       NOT NULL AUTO_INCREMENT,
  `user_id`                   INT(11)       NOT NULL,
  `admin_id`                  INT(11)       NOT NULL,
  `incident_type`             VARCHAR(40)   NOT NULL,
  `title`                     VARCHAR(255)  DEFAULT NULL,
  `description`               TEXT          NOT NULL,
  `incident_date`             DATE          NOT NULL,
  `attachment_path`           VARCHAR(255)  DEFAULT NULL,
  `attachment_original_name`  VARCHAR(255)  DEFAULT NULL,
  `created_at`                TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ei_user_date` (`user_id`, `incident_date`),
  KEY `idx_ei_type` (`incident_type`),
  CONSTRAINT `fk_ei_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ei_admin` FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
