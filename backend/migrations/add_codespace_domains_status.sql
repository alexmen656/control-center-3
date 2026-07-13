-- Tracks async nginx/TLS provisioning state for a codespace's connected domain.
-- status: pending (queued for provisioning) -> active (serving) or error (provisioning failed)
ALTER TABLE `codespace_domains`
  ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER `domain`,
  ADD COLUMN `status_message` VARCHAR(255) DEFAULT NULL AFTER `status`;

-- Queue of domains that need their nginx vhost/cert/Cloudflare record torn down,
-- decoupled from codespace_domains so replacing a domain never fights its unique keys.
CREATE TABLE IF NOT EXISTS `codespace_domain_teardowns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
