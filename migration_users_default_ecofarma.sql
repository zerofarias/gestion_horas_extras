-- Asignar Ecofarma a todos los usuarios (ejecutar después de migration_companies_grupo.sql)

INSERT INTO `companies` (`name`)
SELECT 'Ecofarma' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'Ecofarma');

UPDATE `users` u
INNER JOIN `companies` c ON c.name = 'Ecofarma'
SET u.company_id = c.id;
