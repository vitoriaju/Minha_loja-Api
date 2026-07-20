ALTER TABLE `movimentacoes_financeiras`
  ADD COLUMN `turno` enum('geral','manha','tarde') NOT NULL DEFAULT 'geral' AFTER `data_movimento`,
  ADD KEY `idx_mov_fin_turno` (`data_movimento`, `turno`);
