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
DROP TABLE IF EXISTS `itens_entrada`;
DROP TABLE IF EXISTS `entradas`;
DROP TABLE IF EXISTS `itens_venda`;
DROP TABLE IF EXISTS `vendas`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `email_verifications`;
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
  `fornecedor` varchar(120) NOT NULL,
  `data_entrada` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_entradas_data_entrada` (`data_entrada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `itens_entrada` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entrada_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` decimal(10,3) NOT NULL,
  `validade` date DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fk_itens_entrada_entrada` (`entrada_id`),
  KEY `fk_itens_entrada_produto` (`produto_id`),
  CONSTRAINT `fk_itens_entrada_entrada`
    FOREIGN KEY (`entrada_id`) REFERENCES `entradas` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_itens_entrada_produto`
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
