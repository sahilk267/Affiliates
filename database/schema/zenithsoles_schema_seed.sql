-- ZenithSoles Affiliate Management System
-- Schema + Seed for MySQL 5.7+/8.0 (MariaDB compatible)

SET NAMES utf8mb4;
SET time_zone = "+00:00";
SET foreign_key_checks = 0;

-- Drop tables (respecting FK order)
DROP TABLE IF EXISTS `commissions`;
DROP TABLE IF EXISTS `conversions`;
DROP TABLE IF EXISTS `clicks`;
DROP TABLE IF EXISTS `links`;
DROP TABLE IF EXISTS `programs`;
DROP TABLE IF EXISTS `users`;

SET foreign_key_checks = 1;

-- users
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','affiliate','sub_affiliate') NOT NULL DEFAULT 'affiliate',
  `parent_id` bigint unsigned DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `bank_account` varchar(255) DEFAULT NULL,
  `ifsc_code` varchar(255) DEFAULT NULL,
  `pan_number` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `settings` json DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`),
  KEY `users_is_active_index` (`is_active`),
  KEY `users_parent_id_index` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- programs
CREATE TABLE `programs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` enum('ecommerce','finance','referral','app_download','other') NOT NULL DEFAULT 'ecommerce',
  `description` text DEFAULT NULL,
  `merchant_name` varchar(255) NOT NULL,
  `merchant_url` varchar(255) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `commission_structure` json NOT NULL,
  `supports_sub_affiliate` tinyint(1) NOT NULL DEFAULT 0,
  `api_endpoint` varchar(255) DEFAULT NULL,
  `api_credentials` json DEFAULT NULL,
  `tracking_parameters` json DEFAULT NULL,
  `cookie_duration` int NOT NULL DEFAULT 30,
  `min_payout` decimal(10,2) NOT NULL DEFAULT 100.00,
  `payout_frequency` enum('weekly','monthly','quarterly') NOT NULL DEFAULT 'monthly',
  `restrictions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `programs_slug_unique` (`slug`),
  KEY `programs_type_index` (`type`),
  KEY `programs_status_index` (`status`),
  KEY `programs_supports_sub_affiliate_index` (`supports_sub_affiliate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- links
CREATE TABLE `links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `original_url` varchar(255) NOT NULL,
  `affiliate_url` varchar(255) NOT NULL,
  `short_code` varchar(255) NOT NULL,
  `sub_id` varchar(255) DEFAULT NULL,
  `campaign_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tracking_parameters` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `click_count` int NOT NULL DEFAULT 0,
  `conversion_count` int NOT NULL DEFAULT 0,
  `total_commission` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `links_short_code_unique` (`short_code`),
  KEY `links_user_id_index` (`user_id`),
  KEY `links_program_id_index` (`program_id`),
  KEY `links_is_active_index` (`is_active`),
  KEY `links_sub_id_index` (`sub_id`),
  CONSTRAINT `links_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `links_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- clicks
CREATE TABLE `clicks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `link_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `program_id` bigint unsigned NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `user_agent` text NOT NULL,
  `referrer` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `device_type` varchar(255) DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `os` varchar(255) DEFAULT NULL,
  `tracking_data` json DEFAULT NULL,
  `is_unique` tinyint(1) NOT NULL DEFAULT 1,
  `is_converted` tinyint(1) NOT NULL DEFAULT 0,
  `clicked_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clicks_user_id_index` (`user_id`),
  KEY `clicks_program_id_index` (`program_id`),
  KEY `clicks_ip_address_index` (`ip_address`),
  KEY `clicks_clicked_at_index` (`clicked_at`),
  KEY `clicks_is_converted_index` (`is_converted`),
  KEY `clicks_is_unique_index` (`is_unique`),
  CONSTRAINT `clicks_link_id_foreign` FOREIGN KEY (`link_id`) REFERENCES `links` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clicks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clicks_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- conversions
CREATE TABLE `conversions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `click_id` bigint unsigned NOT NULL,
  `program_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `conversion_id` varchar(255) NOT NULL,
  `event_type` enum('purchase','signup','install','download','other') NOT NULL DEFAULT 'purchase',
  `event_data` json NOT NULL,
  `order_value` decimal(10,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'INR',
  `commission_amount` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL,
  `status` enum('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `converted_at` timestamp NOT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversions_conversion_id_unique` (`conversion_id`),
  KEY `conversions_user_id_index` (`user_id`),
  KEY `conversions_program_id_index` (`program_id`),
  KEY `conversions_status_index` (`status`),
  KEY `conversions_converted_at_index` (`converted_at`),
  CONSTRAINT `conversions_click_id_foreign` FOREIGN KEY (`click_id`) REFERENCES `clicks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversions_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- commissions
CREATE TABLE `commissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversion_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `parent_user_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `parent_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sub_affiliate_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','approved','paid','cancelled') NOT NULL DEFAULT 'pending',
  `currency` char(3) NOT NULL DEFAULT 'INR',
  `payment_method` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `commissions_user_id_index` (`user_id`),
  KEY `commissions_status_index` (`status`),
  KEY `commissions_paid_at_index` (`paid_at`),
  KEY `commissions_conversion_id_index` (`conversion_id`),
  CONSTRAINT `commissions_conversion_id_foreign` FOREIGN KEY (`conversion_id`) REFERENCES `conversions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `commissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `commissions_parent_user_id_foreign` FOREIGN KEY (`parent_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add FK to users.parent_id
ALTER TABLE `users`
  ADD CONSTRAINT `users_parent_id_foreign`
  FOREIGN KEY (`parent_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

-- Seed: Admin User
INSERT INTO `users` (`name`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
  ('Super Admin', 'admin@zenithsoles.in', '$2y$10$x2zS27i3puxRt7g615EnruTgssYxsiSbtYu8LiIfVU4YcsoN.USSe', 'admin', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Sample Seed Data

-- Programs (unique by slug)
INSERT IGNORE INTO `programs` (`name`,`slug`,`type`,`description`,`merchant_name`,`merchant_url`,`status`,`commission_structure`,`supports_sub_affiliate`,`created_at`,`updated_at`) VALUES
  ('Amazon','amazon','ecommerce','Amazon Affiliate Program','Amazon','https://www.amazon.in','active','{"default_rate": 2.5}',0,NOW(),NOW()),
  ('Myntra','myntra','ecommerce','Myntra Affiliate Program','Myntra','https://www.myntra.com','active','{"default_rate": 3.0}',0,NOW(),NOW()),
  ('Upstox','upstox','finance','Upstox Referral Program','Upstox','https://upstox.com','active','{"signup_bonus": 200}',1,NOW(),NOW());

-- Links for admin user (user email unique)
INSERT IGNORE INTO `links` (`program_id`,`user_id`,`original_url`,`affiliate_url`,`short_code`,`sub_id`,`campaign_name`,`is_active`,`created_at`,`updated_at`)
SELECT p.id, u.id, p.merchant_url, CONCAT(p.merchant_url, '?tag=zenithsoles-21'), 'AMZ-DEMO-1', NULL, 'Launch Campaign', 1, NOW(), NOW()
FROM `programs` p JOIN `users` u ON u.email='admin@zenithsoles.in' WHERE p.slug='amazon';

INSERT IGNORE INTO `links` (`program_id`,`user_id`,`original_url`,`affiliate_url`,`short_code`,`sub_id`,`campaign_name`,`is_active`,`created_at`,`updated_at`)
SELECT p.id, u.id, p.merchant_url, CONCAT(p.merchant_url, '?tag=zenithsoles-myntra'), 'MYN-DEMO-1', NULL, 'Fashion Campaign', 1, NOW(), NOW()
FROM `programs` p JOIN `users` u ON u.email='admin@zenithsoles.in' WHERE p.slug='myntra';

-- Demo click for Amazon link
INSERT IGNORE INTO `clicks` (`link_id`,`user_id`,`program_id`,`ip_address`,`user_agent`,`referrer`,`country`,`city`,`device_type`,`browser`,`os`,`tracking_data`,`is_unique`,`is_converted`,`clicked_at`,`created_at`,`updated_at`)
SELECT l.id, l.user_id, l.program_id, '127.0.0.1', 'Mozilla/5.0', 'https://zenithsoles.in', 'IN', 'Mumbai', 'desktop', 'Chrome', 'Windows', NULL, 1, 0, NOW(), NOW(), NOW()
FROM `links` l WHERE l.short_code='AMZ-DEMO-1';

-- Demo conversion for the above click
INSERT IGNORE INTO `conversions` (`click_id`,`program_id`,`user_id`,`conversion_id`,`event_type`,`event_data`,`order_value`,`currency`,`commission_amount`,`commission_rate`,`status`,`notes`,`converted_at`,`approved_at`,`approved_by`,`created_at`,`updated_at`)
SELECT c.id, c.program_id, c.user_id, 'DEMO-CONV-1', 'purchase', '{"items":1}', 3999.00, 'INR', 100.00, 2.50, 'approved', 'Demo conversion', NOW(), NOW(), u.id, NOW(), NOW()
FROM `clicks` c JOIN `users` u ON u.email='admin@zenithsoles.in' WHERE c.link_id = (SELECT id FROM `links` WHERE short_code='AMZ-DEMO-1' LIMIT 1) LIMIT 1;

-- Demo commission for the conversion
INSERT IGNORE INTO `commissions` (`conversion_id`,`user_id`,`parent_user_id`,`amount`,`parent_amount`,`sub_affiliate_amount`,`status`,`currency`,`payment_method`,`transaction_id`,`paid_at`,`notes`,`created_at`,`updated_at`)
SELECT v.id, v.user_id, NULL, v.commission_amount, 0.00, 0.00, 'approved', 'INR', 'bank_transfer', 'TXN-DEMO-1', NULL, 'Demo payout record', NOW(), NOW()
FROM `conversions` v WHERE v.conversion_id='DEMO-CONV-1' LIMIT 1;
