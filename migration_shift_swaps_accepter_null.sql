-- Permite solicitar cambio solo con el compañero (sin elegir su turno).
-- El turno del compañero se resuelve al aprobar (mismo día que tu turno).

ALTER TABLE `shift_swaps`
  MODIFY COLUMN `accepter_schedule_id` INT(11) NULL;
