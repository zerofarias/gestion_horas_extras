-- #40 Estado Finalizado en adelantos (tras #39 migration_salary_advance_installments.sql)
-- En phpMyAdmin: Importar este archivo completo (no usar SOURCE).

ALTER TABLE `salary_advances`
  MODIFY `status` ENUM('Pendiente','Aprobado','Rechazado','Finalizado') NOT NULL DEFAULT 'Pendiente';

ALTER TABLE `salary_advances`
  ADD COLUMN `finalized_at` DATETIME NULL DEFAULT NULL AFTER `rejected_at`;

-- Adelantos ya cobrados en su totalidad
UPDATE `salary_advances` sa
SET sa.`status` = 'Finalizado',
    sa.`finalized_at` = COALESCE(sa.`updated_at`, sa.`approved_at`, NOW())
WHERE sa.`status` = 'Aprobado'
  AND EXISTS (
    SELECT 1 FROM `salary_advance_installments` sai
    WHERE sai.`salary_advance_id` = sa.`id`
  )
  AND NOT EXISTS (
    SELECT 1 FROM `salary_advance_installments` sai
    WHERE sai.`salary_advance_id` = sa.`id` AND sai.`is_deducted` = 0
  );
