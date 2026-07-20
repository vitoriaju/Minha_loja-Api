CREATE TABLE IF NOT EXISTS `auditoria` (
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

-- Execute como administrador do MySQL e ajuste a senha:
-- CREATE USER 'minha_loja_app'@'localhost' IDENTIFIED BY 'SENHA_FORTE_AQUI';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON minha_loja2.* TO 'minha_loja_app'@'localhost';
-- FLUSH PRIVILEGES;
