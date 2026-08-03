CREATE TABLE IF NOT EXISTS `lotes_estoque` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_entrada_id` int(11) DEFAULT NULL,
  `produto_id` int(11) NOT NULL,
  `validade` date DEFAULT NULL,
  `quantidade_inicial` decimal(10,3) NOT NULL,
  `quantidade_restante` decimal(10,3) NOT NULL,
  `origem` enum('entrada','migracao','producao','cadastro') NOT NULL DEFAULT 'entrada',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_lotes_item_entrada` (`item_entrada_id`),
  KEY `idx_lotes_produto_saldo_validade` (`produto_id`, `quantidade_restante`, `validade`),
  KEY `idx_lotes_validade_saldo` (`validade`, `quantidade_restante`),
  CONSTRAINT `fk_lotes_item_entrada` FOREIGN KEY (`item_entrada_id`) REFERENCES `itens_entrada` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_lotes_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_lotes_quantidades` CHECK (`quantidade_inicial` >= 0 AND `quantidade_restante` >= 0 AND `quantidade_restante` <= `quantidade_inicial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `lotes_estoque`
  MODIFY `origem` enum('entrada','migracao','producao','cadastro') NOT NULL DEFAULT 'entrada';

-- Converte o estoque legado em um lote inicial. A data disponivel no cadastro
-- e preservada, embora historicamente ela possa ja ter sido sobrescrita.
INSERT INTO `lotes_estoque`
  (`item_entrada_id`, `produto_id`, `validade`, `quantidade_inicial`, `quantidade_restante`, `origem`)
SELECT NULL, p.id, p.validade, p.estoque, p.estoque, 'migracao'
FROM produtos p
WHERE p.estoque > 0
  AND NOT EXISTS (SELECT 1 FROM lotes_estoque l WHERE l.produto_id = p.id);
