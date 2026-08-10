-- Usar SOLO si migration_shift_swaps.sql no alcanzó (tabla vieja con requester_id/target_id).
-- Borra la tabla anterior y crea el esquema correcto para cambios de turno.

DROP TABLE IF EXISTS `shift_swaps`;

CREATE TABLE `shift_swaps` (
  `id`                    INT(11) NOT NULL AUTO_INCREMENT,
  `proposer_user_id`      INT(11) NOT NULL,
  `accepter_user_id`      INT(11) NOT NULL,
  `proposer_schedule_id`  INT(11) NOT NULL,
  `accepter_schedule_id`  INT(11) NOT NULL,
  `notes`                 TEXT NULL,
  `status`                ENUM('Pendiente','Aprobado','Rechazado') NOT NULL DEFAULT 'Pendiente',
  `reviewed_by`           INT(11) NULL,
  `created_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ss_status` (`status`),
  KEY `idx_ss_proposer` (`proposer_user_id`),
  CONSTRAINT `fk_ss_proposer` FOREIGN KEY (`proposer_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ss_accepter` FOREIGN KEY (`accepter_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ss_ps` FOREIGN KEY (`proposer_schedule_id`) REFERENCES `employee_schedules`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ss_as` FOREIGN KEY (`accepter_schedule_id`) REFERENCES `employee_schedules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
