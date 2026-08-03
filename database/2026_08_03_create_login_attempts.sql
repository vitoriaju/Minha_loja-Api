CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email_hash` char(64) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_email_time` (`email_hash`, `attempted_at`),
  KEY `idx_login_attempts_ip_time` (`ip`, `attempted_at`),
  KEY `idx_login_attempts_time` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
