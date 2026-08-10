-- Tipos vacation/leave en planificación (tras migration_collective_agreements.sql)
ALTER TABLE `employee_schedules`
  MODIFY COLUMN `type` ENUM('shift','custom','overtime','vacation','leave') NOT NULL DEFAULT 'shift';
