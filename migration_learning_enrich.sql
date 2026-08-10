-- Enriquecimiento capacitación: materiales, anotaciones, comunidad, notas empleado
-- Ejecutar después de migration_learning.sql

ALTER TABLE `course_lessons`
  ADD COLUMN IF NOT EXISTS `objectives` TEXT NULL AFTER `content_body`,
  ADD COLUMN IF NOT EXISTS `instructor_notes` MEDIUMTEXT NULL AFTER `objectives`,
  ADD COLUMN IF NOT EXISTS `key_points` TEXT NULL AFTER `instructor_notes`;

CREATE TABLE IF NOT EXISTS `course_resources` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `course_id`     INT(11)      NOT NULL,
  `lesson_id`     INT(11)      NULL,
  `title`         VARCHAR(200) NOT NULL,
  `description`   VARCHAR(500) NULL,
  `resource_type` ENUM('link','video','pdf','document','spreadsheet','archive','image','other') NOT NULL DEFAULT 'document',
  `external_url`  TEXT         NULL,
  `file_path`     VARCHAR(500) NULL,
  `file_name`     VARCHAR(255) NULL,
  `file_size`     INT(11)      NULL,
  `sort_order`    INT(11)      NOT NULL DEFAULT 0,
  `is_visible`    TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`    INT(11)      NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cr_course` (`course_id`, `sort_order`),
  KEY `idx_cr_lesson` (`lesson_id`),
  CONSTRAINT `fk_cr_res_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cr_res_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cr_res_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_discussions` (
  `id`          INT(11)     NOT NULL AUTO_INCREMENT,
  `course_id`   INT(11)     NOT NULL,
  `lesson_id`   INT(11)     NULL,
  `user_id`     INT(11)     NOT NULL,
  `post_type`   ENUM('question','suggestion','comment') NOT NULL DEFAULT 'question',
  `body`        TEXT        NOT NULL,
  `admin_reply` TEXT        NULL,
  `replied_by`  INT(11)     NULL,
  `replied_at`  DATETIME    NULL,
  `is_resolved` TINYINT(1)  NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cd_course` (`course_id`, `created_at`),
  KEY `idx_cd_lesson` (`lesson_id`),
  CONSTRAINT `fk_cd_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cd_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cd_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cd_replier` FOREIGN KEY (`replied_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_lesson_notes` (
  `id`         INT(11)     NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)     NOT NULL,
  `lesson_id`  INT(11)     NOT NULL,
  `body`       MEDIUMTEXT  NOT NULL,
  `updated_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_lesson_note` (`user_id`, `lesson_id`),
  CONSTRAINT `fk_uln_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uln_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
