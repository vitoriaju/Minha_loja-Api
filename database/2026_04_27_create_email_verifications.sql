USE `minha_loja2`;

SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuarios'
    AND COLUMN_NAME = 'email_verificado'
);

SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `usuarios` ADD COLUMN `email_verificado` tinyint(1) NOT NULL DEFAULT 0 AFTER `perfil`',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `email_verifications` (
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

UPDATE `usuarios` u
JOIN `email_verifications` ev ON ev.usuario_id = u.id
SET u.email_verificado = 0
WHERE ev.used_at IS NULL;

ALTER TABLE `usuarios`
  ALTER `email_verificado` SET DEFAULT 0;
