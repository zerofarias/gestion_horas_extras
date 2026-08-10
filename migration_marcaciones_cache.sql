-- Caché de todas las marcaciones importadas desde la API Relojes (mapeadas o no).
-- Ejecutar una vez en la base de datos del proyecto.

CREATE TABLE IF NOT EXISTS `marcaciones_cache` (
  `id`               INT(11)       NOT NULL AUTO_INCREMENT,
  `api_event_id`     INT(11)       NULL,
  `employee_id`      VARCHAR(100)  NOT NULL,
  `person_name`      VARCHAR(200)  NULL,
  `user_id`          INT(11)       NULL,
  `event_time`       DATETIME      NOT NULL,
  `device_name`      VARCHAR(100)  NULL,
  `direction`        VARCHAR(50)   NULL,
  `direction_label`  VARCHAR(100)  NULL,
  `event_serial_no`  VARCHAR(255)  NOT NULL,
  `sync_batch_id`    VARCHAR(100)  NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mc_serial` (`event_serial_no`),
  KEY `idx_mc_event_time` (`event_time`),
  KEY `idx_mc_employee` (`employee_id`),
  KEY `idx_mc_user` (`user_id`),
  CONSTRAINT `fk_mc_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
