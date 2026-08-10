-- Evita lotes duplicados por empresa bajo cierres concurrentes.
-- Ejecutar tras migration_casapav_tasks.sql.

ALTER TABLE cp_task_closures
  ADD UNIQUE KEY uq_cp_closure_company_lot (company_id, lot_number);
