-- Capacitación gamificada + tareas (áreas, cursos, quiz, estrellas, premios)
-- Ejecutar en phpMyAdmin después de migration_v2 y companies.

-- Áreas / departamentos por empresa
CREATE TABLE IF NOT EXISTS `areas` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `company_id` INT(11)      NOT NULL,
  `name`       VARCHAR(120) NOT NULL,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_areas_company` (`company_id`),
  CONSTRAINT `fk_areas_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `area_id` INT(11) NULL AFTER `company_id`;

-- FK area (puede fallar si ya existe; ignorar en ese caso)
-- ALTER TABLE `users` ADD CONSTRAINT `fk_users_area` FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`) ON DELETE SET NULL;

-- Cursos
CREATE TABLE IF NOT EXISTS `courses` (
  `id`                INT(11)       NOT NULL AUTO_INCREMENT,
  `company_id`        INT(11)       NOT NULL,
  `area_id`           INT(11)       NULL,
  `title`             VARCHAR(200)  NOT NULL,
  `slug`              VARCHAR(200)  NOT NULL,
  `description`       TEXT          NULL,
  `thumbnail_url`     VARCHAR(500)  NULL,
  `stars_on_complete` INT(11)       NOT NULL DEFAULT 5,
  `passing_score`     TINYINT(3)    NOT NULL DEFAULT 70,
  `estimated_minutes` INT(11)       NOT NULL DEFAULT 60,
  `require_quiz`      TINYINT(1)    NOT NULL DEFAULT 1,
  `max_quiz_attempts` TINYINT(3)    NOT NULL DEFAULT 3,
  `is_published`      TINYINT(1)    NOT NULL DEFAULT 0,
  `sort_order`        INT(11)       NOT NULL DEFAULT 0,
  `created_by`        INT(11)       NULL,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP     NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_courses_slug_company` (`company_id`, `slug`),
  KEY `idx_courses_company` (`company_id`),
  CONSTRAINT `fk_courses_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_courses_area` FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_courses_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_lessons` (
  `id`               INT(11)      NOT NULL AUTO_INCREMENT,
  `course_id`        INT(11)      NOT NULL,
  `position`         INT(11)      NOT NULL DEFAULT 1,
  `title`            VARCHAR(200) NOT NULL,
  `content_type`     ENUM('video','text','file') NOT NULL DEFAULT 'text',
  `content_url`      TEXT         NULL,
  `content_body`     MEDIUMTEXT   NULL,
  `duration_minutes` INT(11)      NOT NULL DEFAULT 5,
  `is_required`      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_lessons_course` (`course_id`, `position`),
  CONSTRAINT `fk_lessons_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_quiz_questions` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `course_id`     INT(11)      NOT NULL,
  `position`      INT(11)      NOT NULL DEFAULT 1,
  `question_text` TEXT         NOT NULL,
  `explanation`   TEXT         NULL,
  PRIMARY KEY (`id`),
  KEY `idx_quiz_q_course` (`course_id`, `position`),
  CONSTRAINT `fk_quiz_q_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_quiz_options` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `question_id` INT(11)      NOT NULL,
  `option_text` VARCHAR(500) NOT NULL,
  `is_correct`  TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_quiz_opt_q` (`question_id`),
  CONSTRAINT `fk_quiz_opt_q` FOREIGN KEY (`question_id`) REFERENCES `course_quiz_questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_assignments` (
  `id`          INT(11)     NOT NULL AUTO_INCREMENT,
  `course_id`   INT(11)     NOT NULL,
  `target_type` ENUM('company','area','user') NOT NULL,
  `target_id`   INT(11)     NOT NULL,
  `due_date`    DATE        NULL,
  `created_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ca_course` (`course_id`),
  KEY `idx_ca_target` (`target_type`, `target_id`),
  CONSTRAINT `fk_ca_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_enrollments` (
  `id`                     INT(11)     NOT NULL AUTO_INCREMENT,
  `course_id`              INT(11)     NOT NULL,
  `user_id`                INT(11)     NOT NULL,
  `status`                 ENUM('not_started','in_progress','completed','failed_quiz') NOT NULL DEFAULT 'not_started',
  `current_lesson_position` INT(11)    NOT NULL DEFAULT 1,
  `progress_percent`       TINYINT(3)  NOT NULL DEFAULT 0,
  `quiz_score`             TINYINT(3)  NULL,
  `quiz_attempts`          TINYINT(3)  NOT NULL DEFAULT 0,
  `quiz_passed_at`         DATETIME    NULL,
  `completed_at`           DATETIME    NULL,
  `stars_awarded`          INT(11)     NOT NULL DEFAULT 0,
  `started_at`             DATETIME    NULL,
  `updated_at`             TIMESTAMP   NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollment` (`course_id`, `user_id`),
  KEY `idx_enroll_user` (`user_id`),
  CONSTRAINT `fk_enroll_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enroll_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lesson_completions` (
  `id`            INT(11)   NOT NULL AUTO_INCREMENT,
  `enrollment_id` INT(11)   NOT NULL,
  `lesson_id`     INT(11)   NOT NULL,
  `completed_at`  DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lesson_complete` (`enrollment_id`, `lesson_id`),
  CONSTRAINT `fk_lc_enroll` FOREIGN KEY (`enrollment_id`) REFERENCES `course_enrollments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lc_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_star_wallets` (
  `user_id`     INT(11)   NOT NULL,
  `total_stars` INT(11)   NOT NULL DEFAULT 0,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `star_transactions` (
  `id`          INT(11)     NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)     NOT NULL,
  `delta`       INT(11)     NOT NULL,
  `source_type` ENUM('course','task','reward','manual') NOT NULL,
  `source_id`   INT(11)     NULL,
  `note`        VARCHAR(255) NULL,
  `created_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_st_user` (`user_id`),
  CONSTRAINT `fk_st_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rewards` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `company_id`     INT(11)      NOT NULL,
  `title`          VARCHAR(200) NOT NULL,
  `description`    TEXT         NULL,
  `stars_required` INT(11)      NOT NULL DEFAULT 100,
  `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rewards_company` (`company_id`),
  CONSTRAINT `fk_rewards_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reward_redemptions` (
  `id`           INT(11)     NOT NULL AUTO_INCREMENT,
  `user_id`      INT(11)     NOT NULL,
  `reward_id`    INT(11)     NOT NULL,
  `stars_spent`  INT(11)     NOT NULL,
  `status`       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `redeemed_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at`  DATETIME    NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rr_user` (`user_id`),
  CONSTRAINT `fk_rr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rr_reward` FOREIGN KEY (`reward_id`) REFERENCES `rewards`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tareas
CREATE TABLE IF NOT EXISTS `tasks` (
  `id`                INT(11)      NOT NULL AUTO_INCREMENT,
  `company_id`        INT(11)      NOT NULL,
  `title`             VARCHAR(200) NOT NULL,
  `description`       TEXT         NULL,
  `due_date`          DATE         NULL,
  `stars_on_complete` INT(11)      NOT NULL DEFAULT 0,
  `is_active`         TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`        INT(11)      NULL,
  `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_company` (`company_id`),
  CONSTRAINT `fk_tasks_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasks_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `task_assignments` (
  `id`          INT(11)     NOT NULL AUTO_INCREMENT,
  `task_id`     INT(11)     NOT NULL,
  `target_type` ENUM('company','area','user') NOT NULL,
  `target_id`   INT(11)     NOT NULL,
  `created_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ta_task` (`task_id`),
  CONSTRAINT `fk_ta_task` FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `task_completions` (
  `id`           INT(11)   NOT NULL AUTO_INCREMENT,
  `task_id`      INT(11)   NOT NULL,
  `user_id`      INT(11)   NOT NULL,
  `note`         TEXT      NULL,
  `completed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_task_user` (`task_id`, `user_id`),
  CONSTRAINT `fk_tc_task` FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tc_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
