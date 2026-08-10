-- Copa del mundo 2026 — fase de grupos (12 grupos, 72 partidos).
-- Ejecutar una vez. Luego: php scripts/seed_prode_wc2026.php

CREATE TABLE IF NOT EXISTS `prode_editions` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `slug`         VARCHAR(32)  NOT NULL,
  `title`        VARCHAR(120) NOT NULL,
  `status`       ENUM('upcoming','open','closed') NOT NULL DEFAULT 'upcoming',
  `groups_only`  TINYINT(1)   NOT NULL DEFAULT 1,
  `starts_on`    DATE         NULL,
  `ends_on`      DATE         NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prode_edition_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prode_groups` (
  `id`          INT(11)     NOT NULL AUTO_INCREMENT,
  `edition_id`  INT(11)     NOT NULL,
  `code`        CHAR(1)     NOT NULL,
  `sort_order`  TINYINT     NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prode_group_edition_code` (`edition_id`, `code`),
  CONSTRAINT `fk_prode_groups_edition` FOREIGN KEY (`edition_id`) REFERENCES `prode_editions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prode_teams` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `edition_id`  INT(11)      NOT NULL,
  `name`        VARCHAR(80)  NOT NULL,
  `flag_code`   VARCHAR(8)   NOT NULL COMMENT 'ISO o código FIFA (eng, sco)',
  `fifa_code`   VARCHAR(8)   NULL,
  PRIMARY KEY (`id`),
  KEY `idx_prode_teams_edition` (`edition_id`),
  CONSTRAINT `fk_prode_teams_edition` FOREIGN KEY (`edition_id`) REFERENCES `prode_editions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prode_group_teams` (
  `group_id`  INT(11) NOT NULL,
  `team_id`   INT(11) NOT NULL,
  `slot`      TINYINT NOT NULL COMMENT '1-4 posición en grupo',
  PRIMARY KEY (`group_id`, `team_id`),
  UNIQUE KEY `uq_prode_group_slot` (`group_id`, `slot`),
  CONSTRAINT `fk_pgt_group` FOREIGN KEY (`group_id`) REFERENCES `prode_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pgt_team` FOREIGN KEY (`team_id`) REFERENCES `prode_teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prode_matches` (
  `id`                 INT(11)   NOT NULL AUTO_INCREMENT,
  `edition_id`         INT(11)   NOT NULL,
  `group_id`           INT(11)   NOT NULL,
  `match_number`       TINYINT   NOT NULL,
  `home_team_id`       INT(11)   NOT NULL,
  `away_team_id`       INT(11)   NOT NULL,
  `kickoff_at`         DATETIME  NOT NULL,
  `home_score_actual`  TINYINT   NULL,
  `away_score_actual`  TINYINT   NULL,
  `status`             ENUM('scheduled','finished','cancelled') NOT NULL DEFAULT 'scheduled',
  `predictions_locked` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prode_match_group_num` (`group_id`, `match_number`),
  KEY `idx_prode_matches_kickoff` (`kickoff_at`),
  KEY `idx_prode_matches_edition` (`edition_id`),
  CONSTRAINT `fk_prode_matches_edition` FOREIGN KEY (`edition_id`) REFERENCES `prode_editions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prode_matches_group` FOREIGN KEY (`group_id`) REFERENCES `prode_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prode_matches_home` FOREIGN KEY (`home_team_id`) REFERENCES `prode_teams` (`id`),
  CONSTRAINT `fk_prode_matches_away` FOREIGN KEY (`away_team_id`) REFERENCES `prode_teams` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prode_user_entries` (
  `id`                 INT(11)   NOT NULL AUTO_INCREMENT,
  `edition_id`         INT(11)   NOT NULL,
  `user_id`            INT(11)   NOT NULL,
  `status`             ENUM('draft','submitted') NOT NULL DEFAULT 'draft',
  `submitted_at`       DATETIME  NULL,
  `total_points`       INT(11)   NOT NULL DEFAULT 0,
  `exact_hits`         INT(11)   NOT NULL DEFAULT 0,
  `result_hits`        INT(11)   NOT NULL DEFAULT 0,
  `predictions_count`  TINYINT   NOT NULL DEFAULT 0,
  `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prode_user_entry` (`edition_id`, `user_id`),
  KEY `idx_prode_entries_points` (`edition_id`, `total_points` DESC),
  CONSTRAINT `fk_prode_entries_edition` FOREIGN KEY (`edition_id`) REFERENCES `prode_editions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prode_entries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prode_predictions` (
  `id`               INT(11)   NOT NULL AUTO_INCREMENT,
  `user_id`          INT(11)   NOT NULL,
  `match_id`         INT(11)   NOT NULL,
  `home_score_pred`  TINYINT   NULL,
  `away_score_pred`  TINYINT   NULL,
  `points_earned`    TINYINT   NOT NULL DEFAULT 0,
  `is_submitted`     TINYINT(1) NOT NULL DEFAULT 0,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prode_pred_user_match` (`user_id`, `match_id`),
  KEY `idx_prode_pred_match` (`match_id`),
  CONSTRAINT `fk_prode_pred_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prode_pred_match` FOREIGN KEY (`match_id`) REFERENCES `prode_matches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `value_type`, `group_key`, `is_secret`) VALUES
('employee_show_prode', '1', 'bool', 'employee', 0)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
