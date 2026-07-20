-- Dump completo do banco `minha_loja2`
-- Compatível com o codigo PHP atual do projeto.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `minha_loja2`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `minha_loja2`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `itens_producao`;
DROP TABLE IF EXISTS `producao`;
DROP TABLE IF EXISTS `itens_fechamento`;
DROP TABLE IF EXISTS `fechamentos_diarios`;
DROP TABLE IF EXISTS `produto_xml_vinculos`;
DROP TABLE IF EXISTS `itens_entrada`;
DROP TABLE IF EXISTS `entradas`;
DROP TABLE IF EXISTS `itens_venda`;
DROP TABLE IF EXISTS `vendas`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `email_verifications`;
DROP TABLE IF EXISTS `auditoria`;
DROP TABLE IF EXISTS `movimentacoes_financeiras`;
DROP TABLE IF EXISTS `produtos`;
DROP TABLE IF EXISTS `usuarios`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `perfil` enum('admin','user') NOT NULL DEFAULT 'user',
  `email_verificado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `auditoria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(50) NOT NULL,
  `entidade` varchar(80) NOT NULL,
  `entidade_id` varchar(80) DEFAULT NULL,
  `detalhes` json DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_auditoria_usuario` (`usuario_id`),
  KEY `idx_auditoria_entidade` (`entidade`, `entidade_id`),
  KEY `idx_auditoria_criado_em` (`criado_em`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `movimentacoes_financeiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('entrada','saida') NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `responsavel` varchar(120) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `forma_pagamento` enum('dinheiro','cartao','pix','outro') NOT NULL DEFAULT 'outro',
  `data_movimento` date NOT NULL,
  `turno` enum('geral','manha','tarde') NOT NULL DEFAULT 'geral',
  `observacao` text DEFAULT NULL,
  `incluir_fechamento` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mov_fin_data` (`data_movimento`),
  KEY `idx_mov_fin_turno` (`data_movimento`, `turno`),
  KEY `idx_mov_fin_fechamento` (`data_movimento`, `incluir_fechamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `senha_hash`, `perfil`, `email_verificado`, `criado_em`) VALUES
(5, 'Julia', 'julia@gmail.com', NULL, '$2y$10$CFJi1JY/nb8gbUGkDL/UrOHa5Oxn7anwkocQ0RnwtnSB.DwX1pNsm', 'user', 1, NOW()),
(6, 'Usuario Teste', 'admin@teste.com', NULL, '$2y$10$Ku8tfCOkwf.wS.DReGslX.SMuvyHvNXT46RVK8ewHwieBKdv1Aygq', 'user', 1, NOW()),
(7, 'Admin Teste', 'admin@teste1.com', NULL, '$2y$10$job9jMUhEIs1Tmw8abfFKuMLpCGumY34vt5HfGG9GVMcuuRG.PGfu', 'admin', 1, NOW());

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `fk_email_verifications_usuario` (`usuario_id`),
  KEY `idx_email_verifications_expires_at` (`expires_at`),
  CONSTRAINT `fk_email_verifications_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `fk_password_resets_usuario` (`usuario_id`),
  KEY `idx_password_resets_expires_at` (`expires_at`),
  CONSTRAINT `fk_password_resets_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unidade_medida` enum('unidade','kg') NOT NULL DEFAULT 'unidade',
  `categoria` varchar(50) DEFAULT NULL,
  `validade` date DEFAULT NULL,
  `estoque` decimal(10,3) NOT NULL DEFAULT 0.000,
  `estoque_minimo` decimal(10,3) NOT NULL DEFAULT 5.000,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_produtos_nome` (`nome`),
  KEY `idx_produtos_categoria` (`categoria`),
  KEY `idx_produtos_validade` (`validade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `produtos` (`id`, `nome`, `preco`, `unidade_medida`, `categoria`, `validade`, `estoque`, `estoque_minimo`, `criado_em`) VALUES
(2, 'pao', 0.90, 'unidade', 'Padaria', '2026-03-12', 1000.000, 20.000, '2025-09-20 14:56:34'),
(3, 'Leite Quata', 5.50, 'unidade', 'Laticinio', '2026-03-12', 500.000, 5.000, '2025-09-20 16:24:17'),
(5, 'Requeijao', 14.00, 'unidade', 'Laticinio', '2025-09-21', 3.000, 5.000, '2025-09-20 17:51:55'),
(7, 'coca cola', 12.99, 'unidade', 'Refrigerante', '2026-03-12', 100.000, 5.000, '2025-09-21 00:56:41'),
(8, 'Guarana 1L', 6.99, 'unidade', 'Refrigerante', '2027-03-12', 100.000, 5.000, '2025-09-27 19:54:46');

CREATE TABLE `vendas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_venda` datetime NOT NULL DEFAULT current_timestamp(),
  `forma_pagamento` enum('dinheiro','cartao','pix') DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_recebido` decimal(10,2) NOT NULL DEFAULT 0.00,
  `troco` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_vendas_data_venda` (`data_venda`),
  KEY `fk_vendas_usuario` (`usuario_id`),
  CONSTRAINT `fk_vendas_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `itens_venda` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venda_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` decimal(10,3) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_itens_venda_venda` (`venda_id`),
  KEY `fk_itens_venda_produto` (`produto_id`),
  CONSTRAINT `fk_itens_venda_venda`
    FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_itens_venda_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `entradas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_nota` varchar(60) NOT NULL,
  `serie` varchar(20) DEFAULT NULL,
  `chave_acesso` char(44) DEFAULT NULL,
  `fornecedor` varchar(120) NOT NULL,
  `cnpj_fornecedor` varchar(20) DEFAULT NULL,
  `data_emissao` date DEFAULT NULL,
  `data_entrada` datetime NOT NULL DEFAULT current_timestamp(),
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `origem` enum('manual','xml') NOT NULL DEFAULT 'manual',
  `xml_nome_arquivo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_entradas_chave_acesso` (`chave_acesso`),
  KEY `idx_entradas_data_entrada` (`data_entrada`),
  KEY `idx_entradas_fornecedor` (`fornecedor`),
  KEY `idx_entradas_cnpj_fornecedor` (`cnpj_fornecedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `itens_entrada` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entrada_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` decimal(10,3) NOT NULL,
  `validade` date DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descricao_xml` varchar(255) DEFAULT NULL,
  `codigo_xml` varchar(60) DEFAULT NULL,
  `ncm` varchar(20) DEFAULT NULL,
  `cfop` varchar(10) DEFAULT NULL,
  `unidade_xml` varchar(20) DEFAULT NULL,
  `valor_total_item` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fk_itens_entrada_entrada` (`entrada_id`),
  KEY `fk_itens_entrada_produto` (`produto_id`),
  KEY `idx_itens_entrada_codigo_xml` (`codigo_xml`),
  CONSTRAINT `fk_itens_entrada_entrada`
    FOREIGN KEY (`entrada_id`) REFERENCES `entradas` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_itens_entrada_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `produto_xml_vinculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cnpj_fornecedor` varchar(20) NOT NULL DEFAULT '',
  `codigo_xml` varchar(60) NOT NULL,
  `descricao_xml` varchar(255) DEFAULT NULL,
  `produto_id` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_produto_xml_fornecedor_codigo` (`cnpj_fornecedor`, `codigo_xml`),
  KEY `fk_produto_xml_vinculos_produto` (`produto_id`),
  CONSTRAINT `fk_produto_xml_vinculos_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fechamentos_diarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_fechamento` date NOT NULL,
  `horario_fechamento` time NOT NULL DEFAULT '20:30:00',
  `total_vendido` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_dinheiro` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cartao` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_pix` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantidade_vendas` int(11) NOT NULL DEFAULT 0,
  `total_manha_informado` decimal(10,2) DEFAULT NULL,
  `total_tarde_informado` decimal(10,2) DEFAULT NULL,
  `total_dia_informado` decimal(10,2) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_fechamentos_diarios_data` (`data_fechamento`),
  KEY `idx_fechamentos_diarios_data` (`data_fechamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `itens_fechamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fechamento_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade_vendida` decimal(10,3) NOT NULL DEFAULT 0.000,
  `valor_vendido` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sugestao_producao` decimal(10,3) NOT NULL DEFAULT 0.000,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_itens_fechamento_produto` (`fechamento_id`, `produto_id`),
  KEY `fk_itens_fechamento_produto` (`produto_id`),
  KEY `idx_itens_fechamento_sugestao` (`sugestao_producao`),
  CONSTRAINT `fk_itens_fechamento_fechamento`
    FOREIGN KEY (`fechamento_id`) REFERENCES `fechamentos_diarios` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_itens_fechamento_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `producao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_producao_data` (`data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `itens_producao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producao_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` decimal(10,3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_itens_producao_producao` (`producao_id`),
  KEY `fk_itens_producao_produto` (`produto_id`),
  CONSTRAINT `fk_itens_producao_producao`
    FOREIGN KEY (`producao_id`) REFERENCES `producao` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_itens_producao_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `usuarios` AUTO_INCREMENT = 8;
ALTER TABLE `produtos` AUTO_INCREMENT = 9;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
