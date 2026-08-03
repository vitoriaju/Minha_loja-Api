USE `minha_loja2`;

ALTER TABLE `usuarios`
  ALTER `email_verificado` SET DEFAULT 0;

UPDATE `usuarios` u
JOIN `email_verifications` ev ON ev.usuario_id = u.id
SET u.email_verificado = 0
WHERE ev.used_at IS NULL;

UPDATE `usuarios` u
JOIN `email_verifications` ev ON ev.usuario_id = u.id
SET u.email_verificado = 1
WHERE ev.used_at IS NOT NULL;
