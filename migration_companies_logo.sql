-- Logo por empresa (opcional). Archivos en public/img/companies/{slug}.png
-- o ruta relativa en logo_path (ej. img/companies/ecofarma.png)

ALTER TABLE `companies`
  ADD COLUMN IF NOT EXISTS `logo_path` VARCHAR(255) NULL DEFAULT NULL
  COMMENT 'Ruta bajo public/ (ej. img/companies/casa-paviotti.png)';
