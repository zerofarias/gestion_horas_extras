-- #20b Solo si ya ejecutaste migration_notifications_paystubs.sql SIN el índice uq_user_notif_type_ref.
-- Evita duplicar notificaciones de curso/recibo por empleado (reference_id no nulo).

-- 1) Eliminar duplicados previos (conserva la fila con id más bajo)
DELETE un1 FROM user_notifications un1
INNER JOIN user_notifications un2
  ON un1.user_id = un2.user_id
  AND un1.type = un2.type
  AND un1.reference_id = un2.reference_id
  AND un1.reference_id IS NOT NULL
  AND un1.id > un2.id;

-- 2) Agregar índice (ignorar error si ya existe)
ALTER TABLE `user_notifications`
  ADD UNIQUE KEY `uq_user_notif_type_ref` (`user_id`, `type`, `reference_id`);
