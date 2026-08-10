-- #20 Notificaciones, avisos modales y recibos de sueldo
-- Ejecutar tras migration_learning.sql (y #18 areas global si aplica).

CREATE TABLE IF NOT EXISTS `mail_settings` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `smtp_host`     VARCHAR(255) NULL,
  `smtp_port`     INT(11)      NOT NULL DEFAULT 587,
  `smtp_encryption` ENUM('none','tls','ssl') NOT NULL DEFAULT 'tls',
  `smtp_user`     VARCHAR(255) NULL,
  `smtp_password` VARCHAR(500) NULL,
  `from_email`    VARCHAR(255) NULL,
  `from_name`     VARCHAR(120) NULL,
  `is_enabled`    TINYINT(1)   NOT NULL DEFAULT 0,
  `updated_at`    TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `mail_settings` (
  `id`, `smtp_host`, `smtp_port`, `smtp_encryption`, `smtp_user`,
  `from_email`, `from_name`, `is_enabled`
) VALUES (
  1, 'mail.paviotti.com.ar', 587, 'tls', 'notificaciones@paviotti.com.ar',
  'notificaciones@paviotti.com.ar', 'Paviotti Notificaciones', 0
) ON DUPLICATE KEY UPDATE `id` = `id`;
-- Contraseña SMTP: ejecutar seed_mail_paviotti.sql y activar is_enabled = 1

CREATE TABLE IF NOT EXISTS `announcements` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(200) NOT NULL,
  `body`         TEXT         NOT NULL,
  `image_path`   VARCHAR(500) NULL,
  `link_url`     VARCHAR(500) NULL,
  `link_label`   VARCHAR(120) NULL,
  `starts_at`    DATETIME     NOT NULL,
  `ends_at`      DATETIME     NOT NULL,
  `display_mode` ENUM('once','sessions_3','always') NOT NULL DEFAULT 'once',
  `target_all`   TINYINT(1)   NOT NULL DEFAULT 0,
  `send_email`   TINYINT(1)   NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`   INT(11)      NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ann_dates` (`starts_at`, `ends_at`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `announcement_targets` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` INT(11) NOT NULL,
  `target_type`     ENUM('company','area','user') NOT NULL,
  `target_id`       INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_at_ann` (`announcement_id`),
  CONSTRAINT `fk_at_ann` FOREIGN KEY (`announcement_id`) REFERENCES `announcements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `announcement_user_state` (
  `announcement_id` INT(11) NOT NULL,
  `user_id`         INT(11) NOT NULL,
  `times_shown`     INT(11) NOT NULL DEFAULT 0,
  `dismissed_at`    DATETIME NULL,
  `last_shown_at`   DATETIME NULL,
  PRIMARY KEY (`announcement_id`, `user_id`),
  CONSTRAINT `fk_aus_ann` FOREIGN KEY (`announcement_id`) REFERENCES `announcements`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_aus_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notification_broadcasts` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(200) NOT NULL,
  `body`         TEXT         NULL,
  `link_url`     VARCHAR(500) NULL,
  `type`         ENUM('manual','course','pay_stub') NOT NULL DEFAULT 'manual',
  `starts_at`    DATETIME     NULL,
  `ends_at`      DATETIME     NULL,
  `target_all`   TINYINT(1)   NOT NULL DEFAULT 0,
  `send_email`   TINYINT(1)   NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`   INT(11)      NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notification_targets` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `broadcast_id`  INT(11) NOT NULL,
  `target_type`   ENUM('company','area','user') NOT NULL,
  `target_id`     INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nt_b` (`broadcast_id`),
  CONSTRAINT `fk_nt_b` FOREIGN KEY (`broadcast_id`) REFERENCES `notification_broadcasts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_notifications` (
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

CREATE TABLE IF NOT EXISTS `pay_stubs` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`         INT(11)      NOT NULL,
  `company_id`      INT(11)      NOT NULL,
  `period`          CHAR(7)      NOT NULL COMMENT 'YYYY-MM',
  `file_path`       VARCHAR(500) NOT NULL,
  `file_type`       ENUM('pdf','image') NOT NULL DEFAULT 'pdf',
  `admin_note`      TEXT         NULL COMMENT 'Nota o devolución del administrador para el empleado en este período',
  `uploaded_by`     INT(11)      NULL,
  `uploaded_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`          ENUM('pending_signature','signed') NOT NULL DEFAULT 'pending_signature',
  `signed_at`       DATETIME     NULL,
  `signature_path`  VARCHAR(500) NULL,
  `signer_ip`       VARCHAR(45)  NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pay_stub_user_period` (`user_id`, `period`),
  KEY `idx_ps_user_status` (`user_id`, `status`),
  CONSTRAINT `fk_ps_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ps_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
