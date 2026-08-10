-- Empresas del grupo para asignar empleados y filtrar cambios de turno
-- Ejecutar una vez en MySQL.

INSERT INTO `companies` (`name`)
SELECT 'Servicios Sociales' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'Servicios Sociales');

INSERT INTO `companies` (`name`)
SELECT 'Casa Paviotti' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'Casa Paviotti');

INSERT INTO `companies` (`name`)
SELECT 'A.M.S.S.I' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'A.M.S.S.I');

INSERT INTO `companies` (`name`)
SELECT 'Ecofarma' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'Ecofarma');
