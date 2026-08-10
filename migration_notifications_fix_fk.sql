-- Reparación: si user_notifications falló por FK duplicada (fk_un_user ya usada en user_notes).
-- Ejecutar en phpMyAdmin sobre la base paviotti_lanaturaleza (o la tuya).

-- Si quedó una tabla a medias, eliminarla y volver a crear:
DROP TABLE IF EXISTS `user_notifications`;

CREATE TABLE `user_notifications` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`       INT(11)      NOT NULL,
  `broadcast_id`  INT(11)      NULL,
  `title`         VARCHAR(200) NOT NULL,
  `body`          TEXT         NULL,
  `link_url`      VARCHAR(500) NULL,
  `type`          ENUM('manual','course','pay_stub') NOT NULL DEFAULT 'manual',
  `reference_id`  INT(11)      NULL,
  `read_at`       DATETIME     NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_notif_read` (`user_id`, `read_at`),
  KEY `idx_user_notif_created` (`user_id`, `created_at`),
  UNIQUE KEY `uq_user_notif_type_ref` (`user_id`, `type`, `reference_id`),
  CONSTRAINT `fk_user_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_notifications_broadcast` FOREIGN KEY (`broadcast_id`) REFERENCES `notification_broadcasts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
