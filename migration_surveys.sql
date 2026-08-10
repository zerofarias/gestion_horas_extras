-- Encuestas (formularios tipo Google Forms) — Notifications Admin.

CREATE TABLE IF NOT EXISTS `surveys` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `company_id`    INT(11)      NOT NULL,
  `title`         VARCHAR(200) NOT NULL,
  `description`   TEXT         NULL,
  `is_anonymous`  TINYINT(1)   NOT NULL DEFAULT 0,
  `status`        ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
  `open_at`       DATETIME     NULL,
  `close_at`      DATETIME     NULL,
  `created_by`    INT(11)      NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_surveys_company` (`company_id`, `status`),
  CONSTRAINT `fk_surveys_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `survey_questions` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `survey_id`   INT(11)      NOT NULL,
  `sort_order`  INT(11)      NOT NULL DEFAULT 0,
  `question_type` ENUM('short_text','long_text','single_choice','multiple_choice','scale','date') NOT NULL,
  `label`       VARCHAR(500) NOT NULL,
  `is_required` TINYINT(1)   NOT NULL DEFAULT 1,
  `config_json` TEXT         NULL COMMENT 'opciones, min/max escala',
  PRIMARY KEY (`id`),
  KEY `idx_sq_survey` (`survey_id`, `sort_order`),
  CONSTRAINT `fk_sq_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `survey_assignments` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `survey_id`   INT(11) NOT NULL,
  `target_type` ENUM('company','area','user') NOT NULL,
  `target_id`   INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sa_survey` (`survey_id`),
  CONSTRAINT `fk_sa_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `survey_responses` (
  `id`           INT(11)   NOT NULL AUTO_INCREMENT,
  `survey_id`    INT(11)   NOT NULL,
  `user_id`      INT(11)   NULL COMMENT 'NULL si encuesta anónima',
  `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sr_survey` (`survey_id`),
  KEY `idx_sr_user` (`survey_id`, `user_id`),
  CONSTRAINT `fk_sr_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `survey_answers` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `response_id` INT(11) NOT NULL,
  `question_id` INT(11) NOT NULL,
  `answer_text` TEXT    NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sa_response` (`response_id`),
  CONSTRAINT `fk_sans_response` FOREIGN KEY (`response_id`) REFERENCES `survey_responses`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sans_question` FOREIGN KEY (`question_id`) REFERENCES `survey_questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `survey_completion_tokens` (
  `id`         INT(11)     NOT NULL AUTO_INCREMENT,
  `survey_id`  INT(11)     NOT NULL,
  `user_id`    INT(11)     NOT NULL,
  `token_hash` CHAR(64)    NOT NULL,
  `created_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_survey_user_token` (`survey_id`, `user_id`),
  CONSTRAINT `fk_sct_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sct_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Campana: tipo survey (ejecutar solo si ya tenés #20)
ALTER TABLE `user_notifications`
  MODIFY COLUMN `type` ENUM('manual','course','pay_stub','survey') NOT NULL DEFAULT 'manual';
