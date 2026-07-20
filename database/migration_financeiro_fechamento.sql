ALTER TABLE `movimentacoes_financeiras`
  ADD COLUMN `responsavel` varchar(120) DEFAULT NULL AFTER `descricao`,
  ADD COLUMN `incluir_fechamento` tinyint(1) NOT NULL DEFAULT 1 AFTER `observacao`,
  ADD KEY `idx_mov_fin_fechamento` (`data_movimento`, `incluir_fechamento`);
