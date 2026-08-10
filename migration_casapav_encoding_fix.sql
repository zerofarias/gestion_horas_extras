-- Corrige nombres CP con mojibake (ej. Cl├¡nica → Clínica).
-- Ejecutar con cliente UTF-8: mysql --default-character-set=utf8mb4 ...

UPDATE `cp_pickup_places` SET `name` = REPLACE(`name`, '├¡', 'í') WHERE `name` LIKE '%├%';
UPDATE `cp_pickup_places` SET `name` = REPLACE(`name`, '├▒', 'ñ') WHERE `name` LIKE '%├%';
UPDATE `cp_pickup_places` SET `name` = REPLACE(`name`, '├®', 'é') WHERE `name` LIKE '%├%';
UPDATE `cp_pickup_places` SET `name` = REPLACE(`name`, '├í', 'á') WHERE `name` LIKE '%├%';
UPDATE `cp_pickup_places` SET `name` = REPLACE(`name`, '├│', 'ó') WHERE `name` LIKE '%├%';
UPDATE `cp_pickup_places` SET `name` = REPLACE(`name`, '├║', 'ú') WHERE `name` LIKE '%├%';

UPDATE `cp_localities` SET `name` = REPLACE(`name`, '├¡', 'í') WHERE `name` LIKE '%├%';
UPDATE `cp_localities` SET `name` = REPLACE(`name`, '├▒', 'ñ') WHERE `name` LIKE '%├%';
UPDATE `cp_localities` SET `name` = REPLACE(`name`, '├®', 'é') WHERE `name` LIKE '%├%';
UPDATE `cp_localities` SET `name` = REPLACE(`name`, '├í', 'á') WHERE `name` LIKE '%├%';
UPDATE `cp_localities` SET `name` = REPLACE(`name`, '├│', 'ó') WHERE `name` LIKE '%├%';
UPDATE `cp_localities` SET `name` = REPLACE(`name`, '├║', 'ú') WHERE `name` LIKE '%├%';

UPDATE `cp_task_entries` SET `deceased_name` = REPLACE(`deceased_name`, '├¡', 'í') WHERE `deceased_name` LIKE '%├%';
UPDATE `cp_task_entries` SET `deceased_name` = REPLACE(`deceased_name`, '├▒', 'ñ') WHERE `deceased_name` LIKE '%├%';
UPDATE `cp_task_entries` SET `deceased_name` = REPLACE(`deceased_name`, '├®', 'é') WHERE `deceased_name` LIKE '%├%';
UPDATE `cp_task_entries` SET `deceased_name` = REPLACE(`deceased_name`, '├í', 'á') WHERE `deceased_name` LIKE '%├%';
UPDATE `cp_task_entries` SET `deceased_name` = REPLACE(`deceased_name`, '├│', 'ó') WHERE `deceased_name` LIKE '%├%';
UPDATE `cp_task_entries` SET `deceased_name` = REPLACE(`deceased_name`, '├║', 'ú') WHERE `deceased_name` LIKE '%├%';
