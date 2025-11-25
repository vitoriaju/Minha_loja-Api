-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 02/10/2025 às 19:21
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `minha_loja`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `qualidade` varchar(50) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `validade` date DEFAULT NULL,
  `estoque` int(11) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT IGNORE INTO `produtos` (`id`, `nome`, `preco`, `qualidade`, `categoria`, `validade`, `estoque`, `criado_em`) VALUES
(2, 'pao ', 0.90, 'A', 'Pao', '2026-03-12', 1000, '2025-09-20 14:56:34'),
(3, 'Leite Quata', 5.50, 'A', 'Laticinio', '2026-03-12', 500, '2025-09-20 16:24:17'),
(5, 'Requeijão', 14.00, 'A', 'Laticinio', '2025-09-21', 3, '2025-09-20 17:51:55'),
(7, 'coca cola', 12.99, 'A', 'Refrigerante', '2026-03-12', 100, '2025-09-21 00:56:41'),
(8, 'Guarana 1L', 6.99, 'A', 'Refrigerante', '2027-03-12', 100, '2025-09-27 19:54:46');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `perfil` enum('admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT IGNORE INTO `usuarios` (`id`, `email`, `senha_hash`, `perfil`) VALUES
(5, 'julia@gmail.com', '$2y$10$CFJi1JY/nb8gbUGkDL/UrOHa5Oxn7anwkocQ0RnwtnSB.DwX1pNsm', 'user'),
(6, 'admin@teste.com', '$2y$10$Ku8tfCOkwf.wS.DReGslX.SMuvyHvNXT46RVK8ewHwieBKdv1Aygq', 'user'),
(7, 'admin@teste1.com', '$2y$10$job9jMUhEIs1Tmw8abfFKuMLpCGumY34vt5HfGG9GVMcuuRG.PGfu', 'admin');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
