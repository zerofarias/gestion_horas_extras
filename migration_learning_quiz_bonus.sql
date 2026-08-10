-- Quiz aleatorio por empleado + bonus primer completador
-- Ejecutar después de migration_learning.sql

ALTER TABLE `courses`
  ADD COLUMN IF NOT EXISTS `first_finisher_bonus` INT(11) NOT NULL DEFAULT 2
    COMMENT 'Estrellas extra para el primero en aprobar el curso' AFTER `stars_on_complete`;

ALTER TABLE `course_enrollments`
  ADD COLUMN IF NOT EXISTS `quiz_order_seed` INT(11) UNSIGNED NULL
    COMMENT 'Semilla para orden aleatorio de preguntas/opciones' AFTER `quiz_attempts`,
  ADD COLUMN IF NOT EXISTS `completion_rank` INT(11) NULL
    COMMENT '1 = primer empleado en completar el curso' AFTER `completed_at`,
  ADD COLUMN IF NOT EXISTS `bonus_stars` INT(11) NOT NULL DEFAULT 0
    COMMENT 'Estrellas extra (ej. primer puesto)' AFTER `stars_awarded`;
