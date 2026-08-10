-- Nota / devolución del administrador al cargar el recibo (mes calendario del período).
ALTER TABLE `pay_stubs`
  ADD COLUMN `admin_note` TEXT NULL DEFAULT NULL AFTER `file_type`;
