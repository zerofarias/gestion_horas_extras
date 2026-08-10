-- Reconocimiento entre pares (saldo separado de estrellas de cursos/premios).

CREATE TABLE IF NOT EXISTS `peer_star_scores` (
  `user_id`      INT(11)   NOT NULL,
  `total_score`  INT(11)   NOT NULL DEFAULT 0,
  `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_peer_score_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `peer_star_ledger` (
  `id`               INT(11)     NOT NULL AUTO_INCREMENT,
  `giver_user_id`    INT(11)     NOT NULL,
  `receiver_user_id` INT(11)     NOT NULL,
  `delta`            INT(11)     NOT NULL,
  `reason_category`  ENUM('objetivo','buena_accion','extraordinario','negativo','otro') NOT NULL DEFAULT 'buena_accion',
  `comment`          VARCHAR(255) NULL,
  `period_ym`        CHAR(7)     NOT NULL COMMENT 'YYYY-MM',
  `created_at`       TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_peer_receiver` (`receiver_user_id`, `period_ym`),
  KEY `idx_peer_giver_pair` (`giver_user_id`, `receiver_user_id`, `period_ym`),
  CONSTRAINT `fk_peer_giver` FOREIGN KEY (`giver_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_peer_receiver` FOREIGN KEY (`receiver_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
