ALTER TABLE `fechamentos_diarios`
  ADD COLUMN `total_manha_informado` decimal(10,2) DEFAULT NULL,
  ADD COLUMN `total_tarde_informado` decimal(10,2) DEFAULT NULL,
  ADD COLUMN `total_dia_informado` decimal(10,2) DEFAULT NULL;
