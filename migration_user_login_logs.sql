-- Registro de cada inicio de sesión exitoso (consulta interna por BD, sin pantalla en la app)
CREATE TABLE IF NOT EXISTS `user_login_logs` (
  `id`         BIGINT(20)   NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      NOT NULL,
  `ip_address` VARCHAR(45)  NULL,
  `user_agent` VARCHAR(500) NULL,
  `logged_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_user_date` (`user_id`, `logged_at`),
  KEY `idx_login_logged_at` (`logged_at`),
  CONSTRAINT `fk_login_log_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
