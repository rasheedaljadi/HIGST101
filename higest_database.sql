-- ==============================================
-- Bagisto Standalone Database Dump: higest
-- Generated: 2026-07-23 16:49:25
-- ==============================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET TIME_ZONE = "+00:00";

-- ----------------------------------------------
-- Table structure for `addresses`
-- ----------------------------------------------
DROP TABLE IF EXISTS `addresses`;
CREATE TABLE `addresses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `address_type` varchar(255) NOT NULL,
  `parent_address_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL COMMENT 'null if guest checkout',
  `cart_id` int(10) unsigned DEFAULT NULL COMMENT 'only for cart_addresses',
  `order_id` int(10) unsigned DEFAULT NULL COMMENT 'only for order_addresses',
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `postcode` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `vat_id` varchar(255) DEFAULT NULL,
  `default_address` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'only for customer_addresses',
  `use_for_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `additional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `addresses_customer_id_foreign` (`customer_id`),
  KEY `addresses_cart_id_foreign` (`cart_id`),
  KEY `addresses_order_id_foreign` (`order_id`),
  KEY `addresses_parent_address_id_foreign` (`parent_address_id`),
  CONSTRAINT `addresses_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  CONSTRAINT `addresses_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `addresses_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `addresses_parent_address_id_foreign` FOREIGN KEY (`parent_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=247 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `admin_password_resets`
-- ----------------------------------------------
DROP TABLE IF EXISTS `admin_password_resets`;
CREATE TABLE `admin_password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `admin_password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `admins`
-- ----------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `api_token` varchar(80) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `role_id` int(10) unsigned NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_backup_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`two_factor_backup_codes`)),
  `two_factor_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`),
  UNIQUE KEY `admins_api_token_unique` (`api_token`)
) ENGINE=InnoDB AUTO_INCREMENT=1375 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `admins` (1 rows)
INSERT INTO `admins` (`id`, `name`, `email`, `password`, `api_token`, `status`, `role_id`, `image`, `remember_token`, `two_factor_secret`, `two_factor_enabled`, `two_factor_backup_codes`, `two_factor_verified_at`, `created_at`, `updated_at`) VALUES
('1', 'مثال', 'admin@example.com', '$2y$12$nK4gAvuVYFzbnFb8IiWUu.10PA2SRV2iIpW0RzpfyawPCdS7BVw8O', '7m0aRplNFq9YEvOiAp8m3T1xou7hRtEBSXwmzWriu7QPsV9Gx1pkswkkuuvO8HclILHDLkiOWq2tqaR3', '1', '1', NULL, NULL, NULL, '0', NULL, NULL, '2026-07-19 00:21:56', '2026-07-19 00:21:56');

-- ----------------------------------------------
-- Table structure for `agent_conversation_messages`
-- ----------------------------------------------
DROP TABLE IF EXISTS `agent_conversation_messages`;
CREATE TABLE `agent_conversation_messages` (
  `id` varchar(36) NOT NULL,
  `conversation_id` varchar(36) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `agent` varchar(255) NOT NULL,
  `role` varchar(25) NOT NULL,
  `content` text NOT NULL,
  `attachments` text NOT NULL,
  `tool_calls` text NOT NULL,
  `tool_results` text NOT NULL,
  `usage` text NOT NULL,
  `meta` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_index` (`conversation_id`,`user_id`,`updated_at`),
  KEY `agent_conversation_messages_user_id_index` (`user_id`),
  KEY `agent_conversation_messages_conversation_id_index` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `agent_conversations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `agent_conversations`;
CREATE TABLE `agent_conversations` (
  `id` varchar(36) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_conversations_user_id_updated_at_index` (`user_id`,`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `aliexpress_product_imports`
-- ----------------------------------------------
DROP TABLE IF EXISTS `aliexpress_product_imports`;
CREATE TABLE `aliexpress_product_imports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aliexpress_product_id` varchar(255) NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `sku` varchar(255) DEFAULT NULL,
  `variants_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `images_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `base_shipping_cost` decimal(12,4) DEFAULT NULL,
  `shipping_currency` varchar(8) DEFAULT NULL,
  `shipping_min_days` smallint(5) unsigned DEFAULT NULL,
  `shipping_max_days` smallint(5) unsigned DEFAULT NULL,
  `shipping_company` varchar(255) DEFAULT NULL,
  `shipping_tracking` tinyint(1) DEFAULT NULL,
  `shipping_synced_at` timestamp NULL DEFAULT NULL,
  `error` text DEFAULT NULL,
  `payload_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_snapshot`)),
  `snapshot_hash` varchar(64) DEFAULT NULL,
  `supplier_snapshot_version` varchar(32) DEFAULT NULL,
  `external_product_version` varchar(64) DEFAULT NULL,
  `provider_updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aliexpress_product_imports_aliexpress_product_id_unique` (`aliexpress_product_id`),
  KEY `aliexpress_product_imports_product_id_index` (`product_id`),
  KEY `aliexpress_product_imports_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `aliexpress_settings`
-- ----------------------------------------------
DROP TABLE IF EXISTS `aliexpress_settings`;
CREATE TABLE `aliexpress_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `app_key` varchar(255) DEFAULT NULL,
  `app_secret` text DEFAULT NULL,
  `redirect_uri` varchar(255) DEFAULT NULL,
  `authorize_url` varchar(255) DEFAULT NULL,
  `token_url` varchar(255) DEFAULT NULL,
  `business_url` varchar(255) DEFAULT NULL,
  `sign_method` varchar(255) DEFAULT NULL,
  `shipping_margin` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `shipping_extra_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `shipping_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sync_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `sync_schedule` varchar(255) NOT NULL DEFAULT 'daily',
  `inventory_buffer` int(11) NOT NULL DEFAULT 5,
  `price_change_limit` decimal(12,4) NOT NULL DEFAULT 20.0000,
  `stock_sync_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `aliexpress_settings` (1 rows)
INSERT INTO `aliexpress_settings` (`id`, `app_key`, `app_secret`, `redirect_uri`, `authorize_url`, `token_url`, `business_url`, `sign_method`, `shipping_margin`, `shipping_extra_days`, `shipping_enabled`, `sync_enabled`, `sync_schedule`, `inventory_buffer`, `price_change_limit`, `stock_sync_enabled`, `created_at`, `updated_at`) VALUES
('1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0.0000', '0', '1', '0', 'daily', '5', '20.0000', '1', '2026-07-18 23:59:26', '2026-07-18 23:59:26');

-- ----------------------------------------------
-- Table structure for `aliexpress_tokens`
-- ----------------------------------------------
DROP TABLE IF EXISTS `aliexpress_tokens`;
CREATE TABLE `aliexpress_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(255) DEFAULT NULL,
  `account_id` varchar(255) DEFAULT NULL,
  `seller_id` varchar(255) DEFAULT NULL,
  `access_token` text NOT NULL,
  `refresh_token` text DEFAULT NULL,
  `expires_in` bigint(20) unsigned DEFAULT NULL,
  `access_token_expires_at` timestamp NULL DEFAULT NULL,
  `refresh_expires_in` bigint(20) unsigned DEFAULT NULL,
  `refresh_token_expires_at` timestamp NULL DEFAULT NULL,
  `payload` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aliexpress_tokens_account_index` (`account`)
) ENGINE=InnoDB AUTO_INCREMENT=167 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `allocation_logs`
-- ----------------------------------------------
DROP TABLE IF EXISTS `allocation_logs`;
CREATE TABLE `allocation_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_allocation_id` bigint(20) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `old_qty` int(10) unsigned NOT NULL,
  `new_qty` int(10) unsigned NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `allocation_logs_order_allocation_id_foreign` (`order_allocation_id`),
  CONSTRAINT `allocation_logs_order_allocation_id_foreign` FOREIGN KEY (`order_allocation_id`) REFERENCES `order_allocations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `attribute_families`
-- ----------------------------------------------
DROP TABLE IF EXISTS `attribute_families`;
CREATE TABLE `attribute_families` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `is_user_defined` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `attribute_families` (1 rows)
INSERT INTO `attribute_families` (`id`, `code`, `name`, `status`, `is_user_defined`) VALUES
('1', 'default', 'الافتراضي', '0', '1');

-- ----------------------------------------------
-- Table structure for `attribute_group_mappings`
-- ----------------------------------------------
DROP TABLE IF EXISTS `attribute_group_mappings`;
CREATE TABLE `attribute_group_mappings` (
  `attribute_id` int(10) unsigned NOT NULL,
  `attribute_group_id` int(10) unsigned NOT NULL,
  `position` int(11) DEFAULT NULL,
  PRIMARY KEY (`attribute_id`,`attribute_group_id`),
  KEY `attribute_group_mappings_attribute_group_id_foreign` (`attribute_group_id`),
  CONSTRAINT `attribute_group_mappings_attribute_group_id_foreign` FOREIGN KEY (`attribute_group_id`) REFERENCES `attribute_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attribute_group_mappings_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `attribute_group_mappings` (30 rows)
INSERT INTO `attribute_group_mappings` (`attribute_id`, `attribute_group_id`, `position`) VALUES
('1', '1', '1'),
('2', '1', '3'),
('3', '1', '4'),
('4', '1', '5'),
('5', '6', '1'),
('6', '6', '2'),
('7', '6', '3'),
('8', '6', '4'),
('9', '2', '1'),
('10', '2', '2'),
('11', '4', '1'),
('12', '4', '2'),
('13', '4', '3'),
('14', '4', '4'),
('15', '4', '5'),
('16', '3', '1'),
('17', '3', '2'),
('18', '3', '3'),
('19', '5', '1'),
('20', '5', '2'),
('21', '5', '3'),
('22', '5', '4'),
('23', '1', '6'),
('24', '1', '7'),
('25', '1', '8'),
('26', '6', '5'),
('27', '1', '2'),
('28', '7', '1'),
('29', '8', '1'),
('30', '8', '2');

-- ----------------------------------------------
-- Table structure for `attribute_groups`
-- ----------------------------------------------
DROP TABLE IF EXISTS `attribute_groups`;
CREATE TABLE `attribute_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) DEFAULT NULL,
  `attribute_family_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `column` int(11) NOT NULL DEFAULT 1,
  `position` int(11) NOT NULL,
  `is_user_defined` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attribute_groups_attribute_family_id_name_unique` (`attribute_family_id`,`name`),
  CONSTRAINT `attribute_groups_attribute_family_id_foreign` FOREIGN KEY (`attribute_family_id`) REFERENCES `attribute_families` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `attribute_groups` (8 rows)
INSERT INTO `attribute_groups` (`id`, `code`, `attribute_family_id`, `name`, `column`, `position`, `is_user_defined`) VALUES
('1', 'general', '1', 'عام', '1', '1', '0'),
('2', 'description', '1', 'الوصف', '1', '2', '0'),
('3', 'meta_description', '1', 'الوصف الواجب', '1', '3', '0'),
('4', 'price', '1', 'السعر', '2', '1', '0'),
('5', 'shipping', '1', 'الشحن', '2', '2', '0'),
('6', 'settings', '1', 'الإعدادات', '2', '3', '0'),
('7', 'inventories', '1', 'المخزونات', '2', '4', '0'),
('8', 'rma', '1', 'RMA', '2', '5', '0');

-- ----------------------------------------------
-- Table structure for `attribute_option_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `attribute_option_translations`;
CREATE TABLE `attribute_option_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attribute_option_id` int(10) unsigned NOT NULL,
  `locale` varchar(255) NOT NULL,
  `label` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attribute_option_locale_unique` (`attribute_option_id`,`locale`),
  CONSTRAINT `attribute_option_translations_attribute_option_id_foreign` FOREIGN KEY (`attribute_option_id`) REFERENCES `attribute_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `attribute_option_translations` (49 rows)
INSERT INTO `attribute_option_translations` (`id`, `attribute_option_id`, `locale`, `label`) VALUES
('1', '1', 'ar', 'أحمر'),
('2', '2', 'ar', 'أخضر'),
('3', '3', 'ar', 'أصفر'),
('4', '4', 'ar', 'أسود'),
('5', '5', 'ar', 'أبيض'),
('6', '6', 'ar', 'صغير'),
('7', '7', 'ar', 'وسط'),
('8', '8', 'ar', 'كبير'),
('9', '9', 'ar', 'كبير جداً'),
('10', '10', 'ar', 'سامسونج'),
('11', '11', 'ar', 'آبل'),
('12', '12', 'ar', 'شاومي'),
('13', '13', 'ar', 'هواوي'),
('14', '14', 'ar', 'سوني'),
('15', '15', 'ar', 'لينوفو'),
('16', '16', 'ar', 'إتش بي'),
('17', '17', 'ar', 'أنكر'),
('18', '18', 'ar', 'قطن'),
('19', '19', 'ar', 'بوليستر'),
('20', '20', 'ar', 'جلد'),
('21', '21', 'ar', 'ستانلس ستيل'),
('22', '22', 'ar', 'بلاستيك'),
('23', '23', 'ar', 'سيليكون'),
('24', '24', 'ar', 'زجاج'),
('25', '25', 'ar', 'خشب'),
('26', '26', 'ar', '32 جيجابايت'),
('27', '27', 'ar', '64 جيجابايت'),
('28', '28', 'ar', '128 جيجابايت'),
('29', '29', 'ar', '256 جيجابايت'),
('30', '30', 'ar', '512 جيجابايت'),
('31', '31', 'ar', '1 تيرابايت'),
('32', '32', 'ar', '2 جيجابايت'),
('33', '33', 'ar', '4 جيجابايت'),
('34', '34', 'ar', '6 جيجابايت'),
('35', '35', 'ar', '8 جيجابايت'),
('36', '36', 'ar', '12 جيجابايت'),
('37', '37', 'ar', '16 جيجابايت'),
('38', '38', 'ar', 'الصين'),
('39', '39', 'ar', 'السعودية'),
('40', '40', 'ar', 'الإمارات'),
('41', '41', 'ar', 'تركيا'),
('42', '42', 'ar', 'الولايات المتحدة'),
('43', '43', 'ar', 'ألمانيا'),
('44', '44', 'ar', 'اليابان'),
('45', '45', 'ar', 'بدون ضمان'),
('46', '46', 'ar', '6 أشهر'),
('47', '47', 'ar', 'سنة واحدة'),
('48', '48', 'ar', 'سنتان'),
('49', '49', 'ar', '3 سنوات');

-- ----------------------------------------------
-- Table structure for `attribute_options`
-- ----------------------------------------------
DROP TABLE IF EXISTS `attribute_options`;
CREATE TABLE `attribute_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attribute_id` int(10) unsigned NOT NULL,
  `admin_name` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `swatch_value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attribute_options_attribute_id_foreign` (`attribute_id`),
  CONSTRAINT `attribute_options_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=207 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `attribute_options` (49 rows)
INSERT INTO `attribute_options` (`id`, `attribute_id`, `admin_name`, `sort_order`, `swatch_value`) VALUES
('1', '23', 'أحمر', '1', NULL),
('2', '23', 'أخضر', '2', NULL),
('3', '23', 'أصفر', '3', NULL),
('4', '23', 'أسود', '4', NULL),
('5', '23', 'أبيض', '5', NULL),
('6', '24', 'صغير', '1', NULL),
('7', '24', 'وسط', '2', NULL),
('8', '24', 'كبير', '3', NULL),
('9', '24', 'كبير جداً', '4', NULL),
('10', '25', 'Samsung', '1', NULL),
('11', '25', 'Apple', '2', NULL),
('12', '25', 'Xiaomi', '3', NULL),
('13', '25', 'Huawei', '4', NULL),
('14', '25', 'Sony', '5', NULL),
('15', '25', 'Lenovo', '6', NULL),
('16', '25', 'HP', '7', NULL),
('17', '25', 'Anker', '8', NULL),
('18', '31', 'Cotton', '1', NULL),
('19', '31', 'Polyester', '2', NULL),
('20', '31', 'Leather', '3', NULL),
('21', '31', 'Stainless Steel', '4', NULL),
('22', '31', 'Plastic', '5', NULL),
('23', '31', 'Silicone', '6', NULL),
('24', '31', 'Glass', '7', NULL),
('25', '31', 'Wood', '8', NULL),
('26', '32', '32GB', '1', NULL),
('27', '32', '64GB', '2', NULL),
('28', '32', '128GB', '3', NULL),
('29', '32', '256GB', '4', NULL),
('30', '32', '512GB', '5', NULL),
('31', '32', '1TB', '6', NULL),
('32', '33', '2GB', '1', NULL),
('33', '33', '4GB', '2', NULL),
('34', '33', '6GB', '3', NULL),
('35', '33', '8GB', '4', NULL),
('36', '33', '12GB', '5', NULL),
('37', '33', '16GB', '6', NULL),
('38', '34', 'China', '1', NULL),
('39', '34', 'Saudi Arabia', '2', NULL),
('40', '34', 'UAE', '3', NULL),
('41', '34', 'Turkey', '4', NULL),
('42', '34', 'USA', '5', NULL),
('43', '34', 'Germany', '6', NULL),
('44', '34', 'Japan', '7', NULL),
('45', '35', 'No Warranty', '1', NULL),
('46', '35', '6 Months', '2', NULL),
('47', '35', '1 Year', '3', NULL),
('48', '35', '2 Years', '4', NULL),
('49', '35', '3 Years', '5', NULL);

-- ----------------------------------------------
-- Table structure for `attribute_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `attribute_translations`;
CREATE TABLE `attribute_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attribute_id` int(10) unsigned NOT NULL,
  `locale` varchar(255) NOT NULL,
  `name` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attribute_translations_attribute_id_locale_unique` (`attribute_id`,`locale`),
  CONSTRAINT `attribute_translations_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `attribute_translations` (35 rows)
INSERT INTO `attribute_translations` (`id`, `attribute_id`, `locale`, `name`) VALUES
('3', '1', 'ar', 'رمز المنتج'),
('4', '2', 'ar', 'الاسم'),
('5', '3', 'ar', 'الرابط المميز'),
('6', '4', 'ar', 'فئة الضريبة'),
('7', '5', 'ar', 'جديد'),
('8', '6', 'ar', 'مميز'),
('9', '7', 'ar', 'مرئي بشكل فردي'),
('10', '8', 'ar', 'الحالة'),
('11', '9', 'ar', 'وصف مختصر'),
('12', '10', 'ar', 'الوصف'),
('13', '11', 'ar', 'السعر'),
('14', '12', 'ar', 'التكلفة'),
('15', '13', 'ar', 'السعر الخاص'),
('16', '14', 'ar', 'السعر الخاص من'),
('17', '15', 'ar', 'السعر الخاص حتى'),
('18', '16', 'ar', 'العنوان الواجب'),
('19', '17', 'ar', 'الكلمات الرئيسية الواجبة'),
('20', '18', 'ar', 'الوصف الواجب'),
('21', '19', 'ar', 'الطول'),
('22', '20', 'ar', 'العرض'),
('23', '21', 'ar', 'الارتفاع'),
('24', '22', 'ar', 'الوزن'),
('25', '23', 'ar', 'اللون'),
('26', '24', 'ar', 'المقاس'),
('27', '25', 'ar', 'العلامة التجارية'),
('28', '26', 'ar', 'الدفع كضيف'),
('29', '27', 'ar', 'رقم المنتج'),
('30', '28', 'ar', 'إدارة المخزون'),
('31', '29', 'ar', 'السماح بالإرجاع'),
('32', '30', 'ar', 'قواعد الإرجاع'),
('33', '31', 'ar', 'الخامة'),
('34', '32', 'ar', 'سعة التخزين'),
('35', '33', 'ar', 'الذاكرة العشوائية'),
('36', '34', 'ar', 'بلد المنشأ'),
('37', '35', 'ar', 'الضمان');

-- ----------------------------------------------
-- Table structure for `attributes`
-- ----------------------------------------------
DROP TABLE IF EXISTS `attributes`;
CREATE TABLE `attributes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `admin_name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `swatch_type` varchar(255) DEFAULT NULL,
  `validation` varchar(255) DEFAULT NULL,
  `regex` varchar(255) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `is_unique` tinyint(1) NOT NULL DEFAULT 0,
  `is_filterable` tinyint(1) NOT NULL DEFAULT 0,
  `is_comparable` tinyint(1) NOT NULL DEFAULT 0,
  `is_configurable` tinyint(1) NOT NULL DEFAULT 0,
  `is_user_defined` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible_on_front` tinyint(1) NOT NULL DEFAULT 0,
  `value_per_locale` tinyint(1) NOT NULL DEFAULT 0,
  `value_per_channel` tinyint(1) NOT NULL DEFAULT 0,
  `default_value` int(11) DEFAULT NULL,
  `enable_wysiwyg` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attributes_code_unique` (`code`),
  KEY `attributes_code_index` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `attributes` (35 rows)
INSERT INTO `attributes` (`id`, `code`, `admin_name`, `type`, `swatch_type`, `validation`, `regex`, `position`, `is_required`, `is_unique`, `is_filterable`, `is_comparable`, `is_configurable`, `is_user_defined`, `is_visible_on_front`, `value_per_locale`, `value_per_channel`, `default_value`, `enable_wysiwyg`, `created_at`, `updated_at`) VALUES
('1', 'sku', 'رمز المنتج', 'text', NULL, NULL, NULL, '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('2', 'name', 'الاسم', 'text', NULL, NULL, NULL, '3', '1', '0', '0', '1', '0', '0', '0', '1', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('3', 'url_key', 'الرابط المميز', 'text', NULL, NULL, NULL, '4', '1', '1', '0', '0', '0', '0', '0', '1', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('4', 'tax_category_id', 'فئة الضريبة', 'select', NULL, NULL, NULL, '5', '0', '0', '0', '0', '0', '0', '0', '0', '1', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('5', 'new', 'جديد', 'boolean', NULL, NULL, NULL, '6', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('6', 'featured', 'مميز', 'boolean', NULL, NULL, NULL, '7', '0', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('7', 'visible_individually', 'مرئي بشكل فردي', 'boolean', NULL, NULL, NULL, '9', '1', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('8', 'status', 'الحالة', 'boolean', NULL, NULL, NULL, '10', '1', '0', '0', '0', '0', '0', '0', '0', '1', '1', '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('9', 'short_description', 'وصف مختصر', 'textarea', NULL, NULL, NULL, '11', '1', '0', '0', '0', '0', '0', '0', '1', '0', NULL, '1', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('10', 'description', 'الوصف', 'textarea', NULL, NULL, NULL, '12', '1', '0', '0', '1', '0', '0', '0', '1', '0', NULL, '1', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('11', 'price', 'السعر', 'price', NULL, 'decimal', NULL, '13', '1', '0', '1', '1', '0', '0', '0', '0', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('12', 'cost', 'التكلفة', 'price', NULL, 'decimal', NULL, '14', '0', '0', '0', '0', '0', '1', '0', '0', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('13', 'special_price', 'السعر الخاص', 'price', NULL, 'decimal', NULL, '15', '0', '0', '0', '0', '0', '0', '0', '0', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('14', 'special_price_from', 'السعر الخاص من', 'date', NULL, NULL, NULL, '16', '0', '0', '0', '0', '0', '0', '0', '0', '1', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('15', 'special_price_to', 'السعر الخاص حتى', 'date', NULL, NULL, NULL, '17', '0', '0', '0', '0', '0', '0', '0', '0', '1', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('16', 'meta_title', 'العنوان الواجب', 'textarea', NULL, NULL, NULL, '18', '0', '0', '0', '0', '0', '0', '0', '1', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('17', 'meta_keywords', 'الكلمات الرئيسية الواجبة', 'textarea', NULL, NULL, NULL, '20', '0', '0', '0', '0', '0', '0', '0', '1', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('18', 'meta_description', 'الوصف الواجب', 'textarea', NULL, NULL, NULL, '21', '0', '0', '0', '0', '0', '1', '0', '1', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('19', 'length', 'الطول', 'text', NULL, 'decimal', NULL, '22', '0', '0', '0', '0', '0', '1', '0', '0', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('20', 'width', 'العرض', 'text', NULL, 'decimal', NULL, '23', '0', '0', '0', '0', '0', '1', '0', '0', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('21', 'height', 'الارتفاع', 'text', NULL, 'decimal', NULL, '24', '0', '0', '0', '0', '0', '1', '0', '0', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('22', 'weight', 'الوزن', 'text', NULL, 'decimal', NULL, '25', '1', '0', '0', '0', '0', '0', '0', '0', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('23', 'color', 'اللون', 'select', 'color', NULL, NULL, '26', '0', '0', '1', '0', '1', '1', '0', '0', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:58'),
('24', 'size', 'الحجم', 'select', NULL, NULL, NULL, '27', '0', '0', '1', '0', '1', '1', '0', '0', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('25', 'brand', 'العلامة التجارية', 'select', NULL, NULL, NULL, '28', '0', '0', '1', '0', '0', '1', '1', '0', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('26', 'guest_checkout', 'الدفع كضيف', 'boolean', NULL, NULL, NULL, '8', '1', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('27', 'product_number', 'رقم المنتج', 'text', NULL, NULL, NULL, '2', '0', '1', '0', '0', '0', '0', '0', '0', '0', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('28', 'manage_stock', 'إدارة المخزون', 'boolean', NULL, NULL, NULL, '1', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('29', 'allow_rma', 'السماح بالإرجاع', 'boolean', NULL, NULL, NULL, '1', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0', '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('30', 'rma_rule_id', 'قواعد الإرجاع', 'select', NULL, NULL, NULL, '5', '0', '0', '0', '0', '0', '0', '0', '0', '1', NULL, '0', '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('31', 'material', 'Material', 'select', 'dropdown', NULL, NULL, '29', '0', '0', '1', '1', '0', '1', '1', '0', '0', NULL, '0', '2026-07-19 00:21:59', '2026-07-19 00:21:59'),
('32', 'storage_capacity', 'Storage Capacity', 'select', 'dropdown', NULL, NULL, '30', '0', '0', '1', '1', '0', '1', '1', '0', '0', NULL, '0', '2026-07-19 00:22:01', '2026-07-19 00:22:01'),
('33', 'ram', 'RAM', 'select', 'dropdown', NULL, NULL, '31', '0', '0', '1', '1', '0', '1', '1', '0', '0', NULL, '0', '2026-07-19 00:22:02', '2026-07-19 00:22:02'),
('34', 'country_of_origin', 'Country of Origin', 'select', 'dropdown', NULL, NULL, '32', '0', '0', '1', '1', '0', '1', '1', '0', '0', NULL, '0', '2026-07-19 00:22:04', '2026-07-19 00:22:04'),
('35', 'warranty', 'Warranty', 'select', 'dropdown', NULL, NULL, '33', '0', '0', '1', '1', '0', '1', '1', '0', '0', NULL, '0', '2026-07-19 00:22:05', '2026-07-19 00:22:05');

-- ----------------------------------------------
-- Table structure for `booking_product_appointment_slots`
-- ----------------------------------------------
DROP TABLE IF EXISTS `booking_product_appointment_slots`;
CREATE TABLE `booking_product_appointment_slots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_product_id` int(10) unsigned NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `break_time` int(11) DEFAULT NULL,
  `same_slot_all_days` tinyint(1) DEFAULT NULL,
  `slots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`slots`)),
  PRIMARY KEY (`id`),
  KEY `booking_product_appointment_slots_booking_product_id_foreign` (`booking_product_id`),
  CONSTRAINT `booking_product_appointment_slots_booking_product_id_foreign` FOREIGN KEY (`booking_product_id`) REFERENCES `booking_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `booking_product_default_slots`
-- ----------------------------------------------
DROP TABLE IF EXISTS `booking_product_default_slots`;
CREATE TABLE `booking_product_default_slots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_product_id` int(10) unsigned NOT NULL,
  `booking_type` varchar(255) NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `break_time` int(11) DEFAULT NULL,
  `slots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`slots`)),
  PRIMARY KEY (`id`),
  KEY `booking_product_default_slots_booking_product_id_foreign` (`booking_product_id`),
  CONSTRAINT `booking_product_default_slots_booking_product_id_foreign` FOREIGN KEY (`booking_product_id`) REFERENCES `booking_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `booking_product_event_ticket_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `booking_product_event_ticket_translations`;
CREATE TABLE `booking_product_event_ticket_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_product_event_ticket_id` bigint(20) unsigned NOT NULL,
  `locale` varchar(255) NOT NULL,
  `name` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bpet_locale_unique` (`booking_product_event_ticket_id`,`locale`),
  CONSTRAINT `bpet_translations_fk` FOREIGN KEY (`booking_product_event_ticket_id`) REFERENCES `booking_product_event_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `booking_product_event_tickets`
-- ----------------------------------------------
DROP TABLE IF EXISTS `booking_product_event_tickets`;
CREATE TABLE `booking_product_event_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_product_id` int(10) unsigned NOT NULL,
  `price` decimal(12,4) DEFAULT 0.0000,
  `qty` int(11) DEFAULT 0,
  `special_price` decimal(12,4) DEFAULT NULL,
  `special_price_from` datetime DEFAULT NULL,
  `special_price_to` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_product_event_tickets_booking_product_id_foreign` (`booking_product_id`),
  CONSTRAINT `booking_product_event_tickets_booking_product_id_foreign` FOREIGN KEY (`booking_product_id`) REFERENCES `booking_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `booking_product_rental_slots`
-- ----------------------------------------------
DROP TABLE IF EXISTS `booking_product_rental_slots`;
CREATE TABLE `booking_product_rental_slots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_product_id` int(10) unsigned NOT NULL,
  `renting_type` varchar(255) NOT NULL,
  `daily_price` decimal(12,4) DEFAULT 0.0000,
  `hourly_price` decimal(12,4) DEFAULT 0.0000,
  `same_slot_all_days` tinyint(1) DEFAULT NULL,
  `slots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`slots`)),
  PRIMARY KEY (`id`),
  KEY `booking_product_rental_slots_booking_product_id_foreign` (`booking_product_id`),
  CONSTRAINT `booking_product_rental_slots_booking_product_id_foreign` FOREIGN KEY (`booking_product_id`) REFERENCES `booking_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `booking_product_table_slots`
-- ----------------------------------------------
DROP TABLE IF EXISTS `booking_product_table_slots`;
CREATE TABLE `booking_product_table_slots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_product_id` int(10) unsigned NOT NULL,
  `price_type` varchar(255) NOT NULL,
  `guest_limit` int(11) NOT NULL DEFAULT 0,
  `duration` int(11) NOT NULL,
  `break_time` int(11) NOT NULL,
  `prevent_scheduling_before` int(11) NOT NULL,
  `same_slot_all_days` tinyint(1) DEFAULT NULL,
  `slots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`slots`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_product_table_slots_booking_product_id_foreign` (`booking_product_id`),
  CONSTRAINT `booking_product_table_slots_booking_product_id_foreign` FOREIGN KEY (`booking_product_id`) REFERENCES `booking_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `booking_products`
-- ----------------------------------------------
DROP TABLE IF EXISTS `booking_products`;
CREATE TABLE `booking_products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `qty` int(11) DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `show_location` tinyint(1) NOT NULL DEFAULT 0,
  `available_every_week` tinyint(1) DEFAULT NULL,
  `available_from` datetime DEFAULT NULL,
  `available_to` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_products_product_id_foreign` (`product_id`),
  CONSTRAINT `booking_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `bookings`
-- ----------------------------------------------
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned DEFAULT NULL,
  `order_item_id` int(10) unsigned DEFAULT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  `qty` int(11) DEFAULT 0,
  `from` int(11) DEFAULT NULL,
  `to` int(11) DEFAULT NULL,
  `booking_product_event_ticket_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bookings_order_item_id_foreign` (`order_item_id`),
  KEY `bookings_booking_product_event_ticket_id_foreign` (`booking_product_event_ticket_id`),
  KEY `bookings_order_id_foreign` (`order_id`),
  KEY `bookings_product_id_foreign` (`product_id`),
  CONSTRAINT `bookings_booking_product_event_ticket_id_foreign` FOREIGN KEY (`booking_product_event_ticket_id`) REFERENCES `booking_product_event_tickets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `cart`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_first_name` varchar(255) DEFAULT NULL,
  `customer_last_name` varchar(255) DEFAULT NULL,
  `shipping_method` varchar(255) DEFAULT NULL,
  `coupon_code` varchar(255) DEFAULT NULL,
  `is_gift` tinyint(1) NOT NULL DEFAULT 0,
  `items_count` int(11) DEFAULT NULL,
  `items_qty` decimal(12,4) DEFAULT NULL,
  `exchange_rate` decimal(12,4) DEFAULT NULL,
  `global_currency_code` varchar(255) DEFAULT NULL,
  `base_currency_code` varchar(255) DEFAULT NULL,
  `channel_currency_code` varchar(255) DEFAULT NULL,
  `cart_currency_code` varchar(255) DEFAULT NULL,
  `grand_total` decimal(12,4) DEFAULT 0.0000,
  `base_grand_total` decimal(12,4) DEFAULT 0.0000,
  `sub_total` decimal(12,4) DEFAULT 0.0000,
  `base_sub_total` decimal(12,4) DEFAULT 0.0000,
  `tax_total` decimal(12,4) DEFAULT 0.0000,
  `base_tax_total` decimal(12,4) DEFAULT 0.0000,
  `discount_amount` decimal(12,4) DEFAULT 0.0000,
  `base_discount_amount` decimal(12,4) DEFAULT 0.0000,
  `shipping_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_shipping_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `shipping_amount_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_shipping_amount_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sub_total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_sub_total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `checkout_method` varchar(255) DEFAULT NULL,
  `is_guest` tinyint(1) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `applied_cart_rule_ids` varchar(255) DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `channel_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cart_customer_id_foreign` (`customer_id`),
  KEY `cart_channel_id_foreign` (`channel_id`),
  CONSTRAINT `cart_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=377 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `cart_item_inventories`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cart_item_inventories`;
CREATE TABLE `cart_item_inventories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qty` int(10) unsigned NOT NULL DEFAULT 0,
  `inventory_source_id` int(10) unsigned DEFAULT NULL,
  `cart_item_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `cart_items`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE `cart_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `sku` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `coupon_code` varchar(255) DEFAULT NULL,
  `weight` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `total_weight` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_total_weight` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `price` decimal(12,4) NOT NULL DEFAULT 1.0000,
  `base_price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `custom_price` decimal(12,4) DEFAULT NULL,
  `total` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_total` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `tax_percent` decimal(12,4) DEFAULT 0.0000,
  `tax_amount` decimal(12,4) DEFAULT 0.0000,
  `base_tax_amount` decimal(12,4) DEFAULT 0.0000,
  `discount_percent` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_discount_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `price_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_price_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `applied_tax_rate` varchar(255) DEFAULT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `cart_id` int(10) unsigned NOT NULL,
  `tax_category_id` int(10) unsigned DEFAULT NULL,
  `applied_cart_rule_ids` varchar(255) DEFAULT NULL,
  `additional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cart_items_parent_id_foreign` (`parent_id`),
  KEY `cart_items_product_id_foreign` (`product_id`),
  KEY `cart_items_cart_id_foreign` (`cart_id`),
  KEY `cart_items_tax_category_id_foreign` (`tax_category_id`),
  CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `cart_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_tax_category_id_foreign` FOREIGN KEY (`tax_category_id`) REFERENCES `tax_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=429 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `cart_payment`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cart_payment`;
CREATE TABLE `cart_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `method` varchar(255) NOT NULL,
  `method_title` varchar(255) DEFAULT NULL,
  `cart_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cart_payment_cart_id_foreign` (`cart_id`),
  CONSTRAINT `cart_payment_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `cart_rule_channels`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cart_rule_channels`;
CREATE TABLE `cart_rule_channels` (
  `cart_rule_id` int(10) unsigned NOT NULL,
  `channel_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`cart_rule_id`,`channel_id`),
  KEY `cart_rule_channels_channel_id_foreign` (`channel_id`),
  CONSTRAINT `cart_rule_channels_cart_rule_id_foreign` FOREIGN KEY (`cart_rule_id`) REFERENCES `cart_rules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_rule_channels_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `cart_rule_coupon_usage`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cart_rule_coupon_usage`;
CREATE TABLE `cart_rule_coupon_usage` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `times_used` int(11) NOT NULL DEFAULT 0,
  `cart_rule_coupon_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cart_rule_coupon_usage_cart_rule_coupon_id_foreign` (`cart_rule_coupon_id`),
  KEY `cart_rule_coupon_usage_customer_id_foreign` (`customer_id`),
  CONSTRAINT `cart_rule_coupon_usage_cart_rule_coupon_id_foreign` FOREIGN KEY (`cart_rule_coupon_id`) REFERENCES `cart_rule_coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_rule_coupon_usage_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `cart_rule_coupons`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cart_rule_coupons`;
CREATE TABLE `cart_rule_coupons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) DEFAULT NULL,
  `usage_limit` int(10) unsigned NOT NULL DEFAULT 0,
  `usage_per_customer` int(10) unsigned NOT NULL DEFAULT 0,
  `times_used` int(10) unsigned NOT NULL DEFAULT 0,
  `type` int(10) unsigned NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `expired_at` date DEFAULT NULL,
  `cart_rule_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cart_rule_coupons_cart_rule_id_foreign` (`cart_rule_id`),
  CONSTRAINT `cart_rule_coupons_cart_rule_id_foreign` FOREIGN KEY (`cart_rule_id`) REFERENCES `cart_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `cart_rule_customer_groups`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cart_rule_customer_groups`;
CREATE TABLE `cart_rule_customer_groups` (
  `cart_rule_id` int(10) unsigned NOT NULL,
  `customer_group_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`cart_rule_id`,`customer_group_id`),
  KEY `cart_rule_customer_groups_customer_group_id_foreign` (`customer_group_id`),
  CONSTRAINT `cart_rule_customer_groups_cart_rule_id_foreign` FOREIGN KEY (`cart_rule_id`) REFERENCES `cart_rules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_rule_customer_groups_customer_group_id_foreign` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `cart_rule_customers`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cart_rule_customers`;
CREATE TABLE `cart_rule_customers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `times_used` bigint(20) unsigned NOT NULL DEFAULT 0,
  `customer_id` int(10) unsigned NOT NULL,
  `cart_rule_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cart_rule_customers_cart_rule_id_foreign` (`cart_rule_id`),
  KEY `cart_rule_customers_customer_id_foreign` (`customer_id`),
  CONSTRAINT `cart_rule_customers_cart_rule_id_foreign` FOREIGN KEY (`cart_rule_id`) REFERENCES `cart_rules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_rule_customers_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `cart_rule_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cart_rule_translations`;
CREATE TABLE `cart_rule_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(255) NOT NULL,
  `label` text DEFAULT NULL,
  `cart_rule_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_rule_translations_cart_rule_id_locale_unique` (`cart_rule_id`,`locale`),
  CONSTRAINT `cart_rule_translations_cart_rule_id_foreign` FOREIGN KEY (`cart_rule_id`) REFERENCES `cart_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `cart_rules`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cart_rules`;
CREATE TABLE `cart_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `starts_from` datetime DEFAULT NULL,
  `ends_till` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `coupon_type` int(11) NOT NULL DEFAULT 1,
  `use_auto_generation` tinyint(1) NOT NULL DEFAULT 0,
  `usage_per_customer` int(11) NOT NULL DEFAULT 0,
  `uses_per_coupon` int(11) NOT NULL DEFAULT 0,
  `times_used` int(10) unsigned NOT NULL DEFAULT 0,
  `condition_type` tinyint(1) NOT NULL DEFAULT 1,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `end_other_rules` tinyint(1) NOT NULL DEFAULT 0,
  `uses_attribute_conditions` tinyint(1) NOT NULL DEFAULT 0,
  `action_type` varchar(255) DEFAULT NULL,
  `discount_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `discount_quantity` int(11) NOT NULL DEFAULT 1,
  `discount_step` varchar(255) NOT NULL DEFAULT '1',
  `apply_to_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `free_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `cart_shipping_rates`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cart_shipping_rates`;
CREATE TABLE `cart_shipping_rates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `carrier` varchar(255) NOT NULL,
  `carrier_title` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  `method_title` varchar(255) NOT NULL,
  `method_description` varchar(255) DEFAULT NULL,
  `price` double DEFAULT 0,
  `base_price` double DEFAULT 0,
  `discount_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_discount_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `tax_percent` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_tax_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `price_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_price_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `applied_tax_rate` varchar(255) DEFAULT NULL,
  `is_calculate_tax` tinyint(1) NOT NULL DEFAULT 1,
  `cart_address_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cart_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cart_shipping_rates_cart_id_foreign` (`cart_id`),
  CONSTRAINT `cart_shipping_rates_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `catalog_rule_channels`
-- ----------------------------------------------
DROP TABLE IF EXISTS `catalog_rule_channels`;
CREATE TABLE `catalog_rule_channels` (
  `catalog_rule_id` int(10) unsigned NOT NULL,
  `channel_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`catalog_rule_id`,`channel_id`),
  KEY `catalog_rule_channels_channel_id_foreign` (`channel_id`),
  CONSTRAINT `catalog_rule_channels_catalog_rule_id_foreign` FOREIGN KEY (`catalog_rule_id`) REFERENCES `catalog_rules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `catalog_rule_channels_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `catalog_rule_customer_groups`
-- ----------------------------------------------
DROP TABLE IF EXISTS `catalog_rule_customer_groups`;
CREATE TABLE `catalog_rule_customer_groups` (
  `catalog_rule_id` int(10) unsigned NOT NULL,
  `customer_group_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`catalog_rule_id`,`customer_group_id`),
  KEY `catalog_rule_customer_groups_customer_group_id_foreign` (`customer_group_id`),
  CONSTRAINT `catalog_rule_customer_groups_catalog_rule_id_foreign` FOREIGN KEY (`catalog_rule_id`) REFERENCES `catalog_rules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `catalog_rule_customer_groups_customer_group_id_foreign` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `catalog_rule_product_prices`
-- ----------------------------------------------
DROP TABLE IF EXISTS `catalog_rule_product_prices`;
CREATE TABLE `catalog_rule_product_prices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `rule_date` date NOT NULL,
  `starts_from` datetime DEFAULT NULL,
  `ends_till` datetime DEFAULT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `customer_group_id` int(10) unsigned NOT NULL,
  `catalog_rule_id` int(10) unsigned NOT NULL,
  `channel_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `catalog_rule_product_prices_product_id_foreign` (`product_id`),
  KEY `catalog_rule_product_prices_customer_group_id_foreign` (`customer_group_id`),
  KEY `catalog_rule_product_prices_catalog_rule_id_foreign` (`catalog_rule_id`),
  KEY `catalog_rule_product_prices_channel_id_foreign` (`channel_id`),
  CONSTRAINT `catalog_rule_product_prices_catalog_rule_id_foreign` FOREIGN KEY (`catalog_rule_id`) REFERENCES `catalog_rules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `catalog_rule_product_prices_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `catalog_rule_product_prices_customer_group_id_foreign` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `catalog_rule_product_prices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `catalog_rule_products`
-- ----------------------------------------------
DROP TABLE IF EXISTS `catalog_rule_products`;
CREATE TABLE `catalog_rule_products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `starts_from` datetime DEFAULT NULL,
  `ends_till` datetime DEFAULT NULL,
  `end_other_rules` tinyint(1) NOT NULL DEFAULT 0,
  `action_type` varchar(255) DEFAULT NULL,
  `discount_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `product_id` int(10) unsigned NOT NULL,
  `customer_group_id` int(10) unsigned NOT NULL,
  `catalog_rule_id` int(10) unsigned NOT NULL,
  `channel_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `catalog_rule_products_product_id_foreign` (`product_id`),
  KEY `catalog_rule_products_customer_group_id_foreign` (`customer_group_id`),
  KEY `catalog_rule_products_catalog_rule_id_foreign` (`catalog_rule_id`),
  KEY `catalog_rule_products_channel_id_foreign` (`channel_id`),
  CONSTRAINT `catalog_rule_products_catalog_rule_id_foreign` FOREIGN KEY (`catalog_rule_id`) REFERENCES `catalog_rules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `catalog_rule_products_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `catalog_rule_products_customer_group_id_foreign` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `catalog_rule_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `catalog_rules`
-- ----------------------------------------------
DROP TABLE IF EXISTS `catalog_rules`;
CREATE TABLE `catalog_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `starts_from` date DEFAULT NULL,
  `ends_till` date DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `condition_type` tinyint(1) NOT NULL DEFAULT 1,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `end_other_rules` tinyint(1) NOT NULL DEFAULT 0,
  `action_type` varchar(255) DEFAULT NULL,
  `discount_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=337 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `categories`
-- ----------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `position` int(11) NOT NULL DEFAULT 0,
  `logo_path` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `display_mode` varchar(255) DEFAULT 'products_and_description',
  `_lft` int(10) unsigned NOT NULL DEFAULT 0,
  `_rgt` int(10) unsigned NOT NULL DEFAULT 0,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `aliexpress_category_id` bigint(20) unsigned DEFAULT NULL,
  `additional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional`)),
  `banner_path` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categories__lft__rgt_parent_id_index` (`_lft`,`_rgt`,`parent_id`),
  KEY `categories_aliexpress_category_id_index` (`aliexpress_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=165 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `categories` (6 rows)
INSERT INTO `categories` (`id`, `position`, `logo_path`, `status`, `display_mode`, `_lft`, `_rgt`, `parent_id`, `aliexpress_category_id`, `additional`, `banner_path`, `created_at`, `updated_at`) VALUES
('1', '1', NULL, '1', 'products_and_description', '1', '94', NULL, NULL, NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('2', '1', NULL, '1', 'products_and_description', '84', '85', '1', NULL, NULL, NULL, '2026-07-19 00:21:57', '2026-07-19 00:21:57'),
('3', '2', NULL, '1', 'products_and_description', '86', '87', '1', NULL, NULL, NULL, '2026-07-19 00:21:57', '2026-07-19 00:21:57'),
('4', '3', NULL, '1', 'products_and_description', '88', '89', '1', NULL, NULL, NULL, '2026-07-19 00:21:57', '2026-07-19 00:21:57'),
('5', '4', NULL, '1', 'products_and_description', '90', '91', '1', NULL, NULL, NULL, '2026-07-19 00:21:57', '2026-07-19 00:21:57'),
('6', '5', NULL, '1', 'products_and_description', '92', '93', '1', NULL, NULL, NULL, '2026-07-19 00:21:58', '2026-07-19 00:21:58');

-- ----------------------------------------------
-- Table structure for `category_filterable_attributes`
-- ----------------------------------------------
DROP TABLE IF EXISTS `category_filterable_attributes`;
CREATE TABLE `category_filterable_attributes` (
  `category_id` int(10) unsigned NOT NULL,
  `attribute_id` int(10) unsigned NOT NULL,
  KEY `category_filterable_attributes_category_id_foreign` (`category_id`),
  KEY `category_filterable_attributes_attribute_id_foreign` (`attribute_id`),
  CONSTRAINT `category_filterable_attributes_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_filterable_attributes_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `category_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `category_translations`;
CREATE TABLE `category_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `name` text NOT NULL,
  `slug` varchar(255) NOT NULL,
  `url_path` varchar(2048) NOT NULL,
  `description` text DEFAULT NULL,
  `meta_title` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `locale_id` int(10) unsigned DEFAULT NULL,
  `locale` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_translations_category_id_slug_locale_unique` (`category_id`,`slug`,`locale`),
  KEY `category_translations_locale_id_foreign` (`locale_id`),
  CONSTRAINT `category_translations_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_translations_locale_id_foreign` FOREIGN KEY (`locale_id`) REFERENCES `locales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=165 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `category_translations` (6 rows)
INSERT INTO `category_translations` (`id`, `category_id`, `name`, `slug`, `url_path`, `description`, `meta_title`, `meta_description`, `meta_keywords`, `locale_id`, `locale`) VALUES
('1', '1', 'الرئيسية', 'root', '', 'وصف الفئة الرئيسية', '', '', '', NULL, 'ar'),
('2', '2', 'الإلكترونيات والأجهزة', 'electronics', '', 'تشكيلة واسعة من الأجهزة الإلكترونية وأحدث التقنيات: أجهزة الحاسوب المحمولة، السماعات، الساعات الذكية، الكاميرات، وملحقات الألعاب بأفضل الأسعار وجودة مضمونة.', 'الإلكترونيات والأجهزة | تسوق أحدث التقنيات أونلاين', 'اكتشف أحدث الأجهزة الإلكترونية والتقنيات الذكية بأسعار تنافسية مع شحن سريع وضمان الجودة.', 'إلكترونيات, أجهزة ذكية, لابتوب, سماعات, ساعات ذكية, كاميرات', '1', 'ar'),
('3', '3', 'الجوّالات وملحقاتها', 'mobiles-accessories', '', 'أحدث الهواتف الذكية وملحقاتها من أشهر العلامات التجارية، تشمل الأغطية الواقية، الشواحن، سماعات البلوتوث، والبطاريات المتنقلة لتلبية كل احتياجاتك.', 'الجوّالات وملحقاتها | أحدث الهواتف الذكية', 'تسوّق أحدث الهواتف الذكية وملحقاتها الأصلية بأسعار مميزة وعروض حصرية مع توصيل سريع.', 'جوالات, هواتف ذكية, ملحقات الجوال, شواحن, أغطية حماية, سماعات', '1', 'ar'),
('4', '4', 'أزياء النساء', 'women-fashion', '', 'إطلالات عصرية تناسب كل المناسبات: ملابس نسائية، فساتين، أحذية، حقائب، وإكسسوارات من أحدث صيحات الموضة وبجودة عالية تناسب ذوقك.', 'أزياء النساء | أحدث صيحات الموضة النسائية', 'تألقي بأحدث صيحات الموضة النسائية من ملابس وأحذية وحقائب وإكسسوارات بأسعار في متناول الجميع.', 'أزياء نسائية, ملابس نساء, فساتين, أحذية نسائية, حقائب, موضة', '1', 'ar'),
('5', '5', 'المنزل والحديقة', 'home-garden', '', 'كل ما يحتاجه منزلك ليصبح أكثر راحة وأناقة: أدوات المطبخ، المفروشات، الإضاءة، الديكور، ومستلزمات الحديقة بتصاميم عصرية وأسعار مناسبة.', 'المنزل والحديقة | مستلزمات منزلية وديكور', 'جهّز منزلك وحديقتك بأفضل المستلزمات من أدوات مطبخ ومفروشات وديكورات بأسعار تنافسية وجودة عالية.', 'مستلزمات منزلية, ديكور, أدوات مطبخ, مفروشات, إضاءة, حديقة', '1', 'ar'),
('6', '6', 'الجمال والعناية الشخصية', 'beauty-health', '', 'منتجات العناية والجمال التي تستحقينها: مستحضرات التجميل، العناية بالبشرة والشعر، العطور، وأدوات التجميل من علامات موثوقة لإطلالة مثالية.', 'الجمال والعناية الشخصية | مستحضرات تجميل وعناية', 'اعتني بجمالك مع تشكيلة واسعة من مستحضرات التجميل ومنتجات العناية بالبشرة والشعر والعطور بأسعار مميزة.', 'مستحضرات تجميل, العناية بالبشرة, العناية بالشعر, عطور, أدوات تجميل, جمال', '1', 'ar');

-- ----------------------------------------------
-- Table structure for `channel_currencies`
-- ----------------------------------------------
DROP TABLE IF EXISTS `channel_currencies`;
CREATE TABLE `channel_currencies` (
  `channel_id` int(10) unsigned NOT NULL,
  `currency_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`channel_id`,`currency_id`),
  KEY `channel_currencies_currency_id_foreign` (`currency_id`),
  KEY `channel_currencies_cid_cyid_idx` (`channel_id`,`currency_id`),
  CONSTRAINT `channel_currencies_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `channel_currencies_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `channel_currencies` (1 rows)
INSERT INTO `channel_currencies` (`channel_id`, `currency_id`) VALUES
('1', '1');

-- ----------------------------------------------
-- Table structure for `channel_inventory_sources`
-- ----------------------------------------------
DROP TABLE IF EXISTS `channel_inventory_sources`;
CREATE TABLE `channel_inventory_sources` (
  `channel_id` int(10) unsigned NOT NULL,
  `inventory_source_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `channel_inventory_source_unique` (`channel_id`,`inventory_source_id`),
  KEY `channel_inventory_sources_inventory_source_id_foreign` (`inventory_source_id`),
  CONSTRAINT `channel_inventory_sources_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `channel_inventory_sources_inventory_source_id_foreign` FOREIGN KEY (`inventory_source_id`) REFERENCES `inventory_sources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `channel_inventory_sources` (1 rows)
INSERT INTO `channel_inventory_sources` (`channel_id`, `inventory_source_id`) VALUES
('1', '1');

-- ----------------------------------------------
-- Table structure for `channel_locales`
-- ----------------------------------------------
DROP TABLE IF EXISTS `channel_locales`;
CREATE TABLE `channel_locales` (
  `channel_id` int(10) unsigned NOT NULL,
  `locale_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`channel_id`,`locale_id`),
  KEY `channel_locales_locale_id_foreign` (`locale_id`),
  KEY `channel_locales_cid_lid_idx` (`channel_id`,`locale_id`),
  CONSTRAINT `channel_locales_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `channel_locales_locale_id_foreign` FOREIGN KEY (`locale_id`) REFERENCES `locales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `channel_locales` (1 rows)
INSERT INTO `channel_locales` (`channel_id`, `locale_id`) VALUES
('1', '1');

-- ----------------------------------------------
-- Table structure for `channel_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `channel_translations`;
CREATE TABLE `channel_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` int(10) unsigned NOT NULL,
  `locale` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `maintenance_mode_text` text DEFAULT NULL,
  `home_seo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`home_seo`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `channel_translations_channel_id_locale_unique` (`channel_id`,`locale`),
  KEY `channel_translations_locale_index` (`locale`),
  CONSTRAINT `channel_translations_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `channel_translations` (1 rows)
INSERT INTO `channel_translations` (`id`, `channel_id`, `locale`, `name`, `description`, `maintenance_mode_text`, `home_seo`, `created_at`, `updated_at`) VALUES
('2', '1', 'ar', 'افتراضي', NULL, NULL, '{\"meta_title\":\"\\u0645\\u062a\\u062c\\u0631 \\u062a\\u062c\\u0631\\u064a\\u0628\\u064a\",\"meta_description\":\"\\u0648\\u0635\\u0641 \\u0645\\u062a\\u062c\\u0631 \\u062a\\u062c\\u0631\\u064a\\u0628\\u064a\",\"meta_keywords\":\"\\u0627\\u0644\\u0643\\u0644\\u0645\\u0627\\u062a \\u0627\\u0644\\u0631\\u0626\\u064a\\u0633\\u064a\\u0629 \\u0644\\u0644\\u0645\\u062a\\u062c\\u0631 \\u0627\\u0644\\u062a\\u062c\\u0631\\u064a\\u0628\\u064a\"}', NULL, NULL);

-- ----------------------------------------------
-- Table structure for `channels`
-- ----------------------------------------------
DROP TABLE IF EXISTS `channels`;
CREATE TABLE `channels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `timezone` varchar(255) DEFAULT NULL,
  `theme` varchar(255) DEFAULT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `home_seo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`home_seo`)),
  `is_maintenance_on` tinyint(1) NOT NULL DEFAULT 0,
  `allowed_ips` text DEFAULT NULL,
  `root_category_id` int(10) unsigned DEFAULT NULL,
  `default_locale_id` int(10) unsigned NOT NULL,
  `base_currency_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `channels_root_category_id_foreign` (`root_category_id`),
  KEY `channels_default_locale_id_foreign` (`default_locale_id`),
  KEY `channels_base_currency_id_foreign` (`base_currency_id`),
  KEY `channels_hostname_idx` (`hostname`),
  CONSTRAINT `channels_base_currency_id_foreign` FOREIGN KEY (`base_currency_id`) REFERENCES `currencies` (`id`),
  CONSTRAINT `channels_default_locale_id_foreign` FOREIGN KEY (`default_locale_id`) REFERENCES `locales` (`id`),
  CONSTRAINT `channels_root_category_id_foreign` FOREIGN KEY (`root_category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `channels` (1 rows)
INSERT INTO `channels` (`id`, `code`, `timezone`, `theme`, `hostname`, `logo`, `favicon`, `home_seo`, `is_maintenance_on`, `allowed_ips`, `root_category_id`, `default_locale_id`, `base_currency_id`, `created_at`, `updated_at`) VALUES
('1', 'default', NULL, 'default', 'https://zoologist-decathlon-eclair.ngrok-free.dev', NULL, NULL, NULL, '0', NULL, '1', '1', '1', '2026-07-19 00:21:50', '2026-07-19 00:21:50');

-- ----------------------------------------------
-- Table structure for `cms_page_channels`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cms_page_channels`;
CREATE TABLE `cms_page_channels` (
  `cms_page_id` int(10) unsigned NOT NULL,
  `channel_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `cms_page_channels_cms_page_id_channel_id_unique` (`cms_page_id`,`channel_id`),
  KEY `cms_page_channels_channel_id_foreign` (`channel_id`),
  CONSTRAINT `cms_page_channels_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cms_page_channels_cms_page_id_foreign` FOREIGN KEY (`cms_page_id`) REFERENCES `cms_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `cms_page_channels` (10 rows)
INSERT INTO `cms_page_channels` (`cms_page_id`, `channel_id`) VALUES
('1', '1'),
('2', '1'),
('3', '1'),
('4', '1'),
('5', '1'),
('6', '1'),
('7', '1'),
('8', '1'),
('9', '1'),
('10', '1');

-- ----------------------------------------------
-- Table structure for `cms_page_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cms_page_translations`;
CREATE TABLE `cms_page_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_title` varchar(255) NOT NULL,
  `url_key` varchar(255) NOT NULL,
  `html_content` longtext DEFAULT NULL,
  `meta_title` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `locale` varchar(255) NOT NULL,
  `cms_page_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_page_translations_cms_page_id_url_key_locale_unique` (`cms_page_id`,`url_key`,`locale`),
  CONSTRAINT `cms_page_translations_cms_page_id_foreign` FOREIGN KEY (`cms_page_id`) REFERENCES `cms_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `cms_page_translations` (10 rows)
INSERT INTO `cms_page_translations` (`id`, `page_title`, `url_key`, `html_content`, `meta_title`, `meta_description`, `meta_keywords`, `locale`, `cms_page_id`) VALUES
('1', 'من نحن', 'about-us', '<div class=\"static-container\"><div class=\"mb-5\">محتوى صفحة من نحن</div></div>', 'about us', '', 'aboutus', 'ar', '1'),
('2', 'سياسة الإرجاع', 'return-policy', '<div class=\"static-container\"><div class=\"mb-5\">محتوى صفحة سياسة الإرجاع</div></div>', 'return policy', '', 'return, policy', 'ar', '2'),
('3', 'سياسة الاسترداد', 'refund-policy', '<div class=\"static-container\"><div class=\"mb-5\">محتوى صفحة سياسة الاسترداد</div></div>', 'Refund policy', '', 'refund, policy', 'ar', '3'),
('4', 'الشروط والأحكام', 'terms-conditions', '<div class=\"static-container\"><div class=\"mb-5\">محتوى صفحة الشروط والأحكام</div></div>', 'Terms & Conditions', '', 'term, conditions', 'ar', '4'),
('5', 'شروط الاستخدام', 'terms-of-use', '<div class=\"static-container\"><div class=\"mb-5\">محتوى صفحة شروط الاستخدام</div></div>', 'Terms of use', '', 'term, use', 'ar', '5'),
('6', 'خدمة العملاء', 'customer-service', '<div class=\"static-container\"><div class=\"mb-5\">محتوى صفحة خدمة العملاء</div></div>', 'Customer Service', '', 'customer, service', 'ar', '6'),
('7', 'ما الجديد', 'whats-new', '<div class=\"static-container\"><div class=\"mb-5\">محتوى صفحة ما الجديد</div></div>', 'What\'s New', '', 'new', 'ar', '7'),
('8', 'سياسة الدفع', 'payment-policy', '<div class=\"static-container\"><div class=\"mb-5\">محتوى صفحة سياسة الدفع</div></div>', 'Payment Policy', '', 'payment, policy', 'ar', '8'),
('9', 'سياسة الشحن', 'shipping-policy', '<div class=\"static-container\"><div class=\"mb-5\">محتوى صفحة سياسة الشحن</div></div>', 'Shipping Policy', '', 'shipping, policy', 'ar', '9'),
('10', 'سياسة الخصوصية', 'privacy-policy', '<div class=\"static-container\"><div class=\"mb-5\">محتوى صفحة سياسة الخصوصية</div></div>', 'Privacy Policy', '', 'privacy, policy', 'ar', '10');

-- ----------------------------------------------
-- Table structure for `cms_pages`
-- ----------------------------------------------
DROP TABLE IF EXISTS `cms_pages`;
CREATE TABLE `cms_pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `layout` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `cms_pages` (10 rows)
INSERT INTO `cms_pages` (`id`, `layout`, `created_at`, `updated_at`) VALUES
('1', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('2', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('3', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('4', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('5', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('6', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('7', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('8', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('9', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('10', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51');

-- ----------------------------------------------
-- Table structure for `compare_items`
-- ----------------------------------------------
DROP TABLE IF EXISTS `compare_items`;
CREATE TABLE `compare_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compare_items_product_id_foreign` (`product_id`),
  KEY `compare_items_customer_id_foreign` (`customer_id`),
  CONSTRAINT `compare_items_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `compare_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `core_config`
-- ----------------------------------------------
DROP TABLE IF EXISTS `core_config`;
CREATE TABLE `core_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `channel_code` varchar(255) DEFAULT NULL,
  `locale_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=983 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `core_config` (21 rows)
INSERT INTO `core_config` (`id`, `code`, `value`, `channel_code`, `locale_code`, `created_at`, `updated_at`) VALUES
('1', 'sales.checkout.shopping_cart.allow_guest_checkout', '1', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('2', 'emails.general.notifications.emails.general.notifications.registration', '1', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('3', 'emails.general.notifications.emails.general.notifications.customer_registration_confirmation_mail_to_admin', '0', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('4', 'emails.general.notifications.emails.general.notifications.customer_account_credentials', '1', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('5', 'emails.general.notifications.emails.general.notifications.new_order', '1', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('6', 'emails.general.notifications.emails.general.notifications.new_order_mail_to_admin', '1', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('7', 'emails.general.notifications.emails.general.notifications.new_invoice', '1', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('8', 'emails.general.notifications.emails.general.notifications.new_invoice_mail_to_admin', '0', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('9', 'emails.general.notifications.emails.general.notifications.new_refund', '1', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('10', 'emails.general.notifications.emails.general.notifications.new_refund_mail_to_admin', '0', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('11', 'emails.general.notifications.emails.general.notifications.new_shipment', '1', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('12', 'emails.general.notifications.emails.general.notifications.new_shipment_mail_to_admin', '0', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('13', 'emails.general.notifications.emails.general.notifications.new_inventory_source', '1', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('14', 'emails.general.notifications.emails.general.notifications.cancel_order', '1', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('15', 'emails.general.notifications.emails.general.notifications.cancel_order_mail_to_admin', '0', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('16', 'general.design.categories.category_view', 'sidebar', NULL, NULL, '2026-07-19 00:21:50', '2026-07-19 00:21:50'),
('414', 'customer.settings.social_login.enable_facebook', '1', 'default', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('415', 'customer.settings.social_login.enable_twitter', '1', 'default', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('416', 'customer.settings.social_login.enable_google', '1', 'default', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('417', 'customer.settings.social_login.enable_linkedin', '1', 'default', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('418', 'customer.settings.social_login.enable_github', '1', 'default', NULL, '2026-07-19 00:21:51', '2026-07-19 00:21:51');

-- ----------------------------------------------
-- Table structure for `countries`
-- ----------------------------------------------
DROP TABLE IF EXISTS `countries`;
CREATE TABLE `countries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=256 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `countries` (254 rows)
INSERT INTO `countries` (`id`, `code`, `name`) VALUES
('1', 'AF', 'Afghanistan'),
('2', 'AX', 'Åland Islands'),
('3', 'AL', 'Albania'),
('4', 'DZ', 'Algeria'),
('5', 'AS', 'American Samoa'),
('6', 'AD', 'Andorra'),
('7', 'AO', 'Angola'),
('8', 'AI', 'Anguilla'),
('9', 'AQ', 'Antarctica'),
('10', 'AG', 'Antigua & Barbuda'),
('11', 'AR', 'Argentina'),
('12', 'AM', 'Armenia'),
('13', 'AW', 'Aruba'),
('14', 'AC', 'Ascension Island'),
('15', 'AU', 'Australia'),
('16', 'AT', 'Austria'),
('17', 'AZ', 'Azerbaijan'),
('18', 'BS', 'Bahamas'),
('19', 'BH', 'Bahrain'),
('20', 'BD', 'Bangladesh'),
('21', 'BB', 'Barbados'),
('22', 'BY', 'Belarus'),
('23', 'BE', 'Belgium'),
('24', 'BZ', 'Belize'),
('25', 'BJ', 'Benin'),
('26', 'BM', 'Bermuda'),
('27', 'BT', 'Bhutan'),
('28', 'BO', 'Bolivia'),
('29', 'BA', 'Bosnia & Herzegovina'),
('30', 'BW', 'Botswana'),
('31', 'BR', 'Brazil'),
('32', 'IO', 'British Indian Ocean Territory'),
('33', 'VG', 'British Virgin Islands'),
('34', 'BN', 'Brunei'),
('35', 'BG', 'Bulgaria'),
('36', 'BF', 'Burkina Faso'),
('37', 'BI', 'Burundi'),
('38', 'KH', 'Cambodia'),
('39', 'CM', 'Cameroon'),
('40', 'CA', 'Canada'),
('41', 'IC', 'Canary Islands'),
('42', 'CV', 'Cape Verde'),
('43', 'BQ', 'Caribbean Netherlands'),
('44', 'KY', 'Cayman Islands'),
('45', 'CF', 'Central African Republic'),
('46', 'EA', 'Ceuta & Melilla'),
('47', 'TD', 'Chad'),
('48', 'CL', 'Chile'),
('49', 'CN', 'China'),
('50', 'CX', 'Christmas Island'),
('51', 'CC', 'Cocos (Keeling) Islands'),
('52', 'CO', 'Colombia'),
('53', 'KM', 'Comoros'),
('54', 'CG', 'Congo - Brazzaville'),
('55', 'CD', 'Congo - Kinshasa'),
('56', 'CK', 'Cook Islands'),
('57', 'CR', 'Costa Rica'),
('58', 'CI', 'Côte d’Ivoire'),
('59', 'HR', 'Croatia'),
('60', 'CU', 'Cuba'),
('61', 'CW', 'Curaçao'),
('62', 'CY', 'Cyprus'),
('63', 'CZ', 'Czechia'),
('64', 'DK', 'Denmark'),
('65', 'DG', 'Diego Garcia'),
('66', 'DJ', 'Djibouti'),
('67', 'DM', 'Dominica'),
('68', 'DO', 'Dominican Republic'),
('69', 'EC', 'Ecuador'),
('70', 'EG', 'Egypt'),
('71', 'SV', 'El Salvador'),
('72', 'GQ', 'Equatorial Guinea'),
('73', 'ER', 'Eritrea'),
('74', 'EE', 'Estonia'),
('75', 'ET', 'Ethiopia'),
('76', 'EZ', 'Eurozone'),
('77', 'FK', 'Falkland Islands'),
('78', 'FO', 'Faroe Islands'),
('79', 'FJ', 'Fiji'),
('80', 'FI', 'Finland'),
('81', 'FR', 'France'),
('82', 'GF', 'French Guiana'),
('83', 'PF', 'French Polynesia'),
('84', 'TF', 'French Southern Territories'),
('85', 'GA', 'Gabon'),
('86', 'GM', 'Gambia'),
('87', 'GE', 'Georgia'),
('88', 'DE', 'Germany'),
('89', 'GH', 'Ghana'),
('90', 'GI', 'Gibraltar'),
('91', 'GR', 'Greece'),
('92', 'GL', 'Greenland'),
('93', 'GD', 'Grenada'),
('94', 'GP', 'Guadeloupe'),
('95', 'GU', 'Guam'),
('96', 'GT', 'Guatemala'),
('97', 'GG', 'Guernsey'),
('98', 'GN', 'Guinea'),
('99', 'GW', 'Guinea-Bissau'),
('100', 'GY', 'Guyana');
INSERT INTO `countries` (`id`, `code`, `name`) VALUES
('101', 'HT', 'Haiti'),
('102', 'HN', 'Honduras'),
('103', 'HK', 'Hong Kong SAR China'),
('104', 'HU', 'Hungary'),
('105', 'IS', 'Iceland'),
('106', 'IN', 'India'),
('107', 'ID', 'Indonesia'),
('108', 'IR', 'Iran'),
('109', 'IQ', 'Iraq'),
('110', 'IE', 'Ireland'),
('111', 'IM', 'Isle of Man'),
('112', 'IL', 'Israel'),
('113', 'IT', 'Italy'),
('114', 'JM', 'Jamaica'),
('115', 'JP', 'Japan'),
('116', 'JE', 'Jersey'),
('117', 'JO', 'Jordan'),
('118', 'KZ', 'Kazakhstan'),
('119', 'KE', 'Kenya'),
('120', 'KI', 'Kiribati'),
('121', 'XK', 'Kosovo'),
('122', 'KW', 'Kuwait'),
('123', 'KG', 'Kyrgyzstan'),
('124', 'LA', 'Laos'),
('125', 'LV', 'Latvia'),
('126', 'LB', 'Lebanon'),
('127', 'LS', 'Lesotho'),
('128', 'LR', 'Liberia'),
('129', 'LY', 'Libya'),
('130', 'LI', 'Liechtenstein'),
('131', 'LT', 'Lithuania'),
('132', 'LU', 'Luxembourg'),
('133', 'MO', 'Macau SAR China'),
('134', 'MK', 'Macedonia'),
('135', 'MG', 'Madagascar'),
('136', 'MW', 'Malawi'),
('137', 'MY', 'Malaysia'),
('138', 'MV', 'Maldives'),
('139', 'ML', 'Mali'),
('140', 'MT', 'Malta'),
('141', 'MH', 'Marshall Islands'),
('142', 'MQ', 'Martinique'),
('143', 'MR', 'Mauritania'),
('144', 'MU', 'Mauritius'),
('145', 'YT', 'Mayotte'),
('146', 'MX', 'Mexico'),
('147', 'FM', 'Micronesia'),
('148', 'MD', 'Moldova'),
('149', 'MC', 'Monaco'),
('150', 'MN', 'Mongolia'),
('151', 'ME', 'Montenegro'),
('152', 'MS', 'Montserrat'),
('153', 'MA', 'Morocco'),
('154', 'MZ', 'Mozambique'),
('155', 'MM', 'Myanmar (Burma)'),
('156', 'NA', 'Namibia'),
('157', 'NR', 'Nauru'),
('158', 'NP', 'Nepal'),
('159', 'NL', 'Netherlands'),
('160', 'NC', 'New Caledonia'),
('161', 'NZ', 'New Zealand'),
('162', 'NI', 'Nicaragua'),
('163', 'NE', 'Niger'),
('164', 'NG', 'Nigeria'),
('165', 'NU', 'Niue'),
('166', 'NF', 'Norfolk Island'),
('167', 'KP', 'North Korea'),
('168', 'MP', 'Northern Mariana Islands'),
('169', 'NO', 'Norway'),
('170', 'OM', 'Oman'),
('171', 'PK', 'Pakistan'),
('172', 'PW', 'Palau'),
('173', 'PS', 'Palestinian Territories'),
('174', 'PA', 'Panama'),
('175', 'PG', 'Papua New Guinea'),
('176', 'PY', 'Paraguay'),
('177', 'PE', 'Peru'),
('178', 'PH', 'Philippines'),
('179', 'PN', 'Pitcairn Islands'),
('180', 'PL', 'Poland'),
('181', 'PT', 'Portugal'),
('182', 'PR', 'Puerto Rico'),
('183', 'QA', 'Qatar'),
('184', 'RE', 'Réunion'),
('185', 'RO', 'Romania'),
('186', 'RU', 'Russia'),
('187', 'RW', 'Rwanda'),
('188', 'WS', 'Samoa'),
('189', 'SM', 'San Marino'),
('190', 'ST', 'São Tomé & Príncipe'),
('191', 'SA', 'Saudi Arabia'),
('192', 'SN', 'Senegal'),
('193', 'RS', 'Serbia'),
('194', 'SC', 'Seychelles'),
('195', 'SL', 'Sierra Leone'),
('196', 'SG', 'Singapore'),
('197', 'SX', 'Sint Maarten'),
('198', 'SK', 'Slovakia'),
('199', 'SI', 'Slovenia'),
('200', 'SB', 'Solomon Islands');
INSERT INTO `countries` (`id`, `code`, `name`) VALUES
('201', 'SO', 'Somalia'),
('202', 'ZA', 'South Africa'),
('203', 'GS', 'South Georgia & South Sandwich Islands'),
('204', 'KR', 'South Korea'),
('205', 'SS', 'South Sudan'),
('206', 'ES', 'Spain'),
('207', 'LK', 'Sri Lanka'),
('208', 'BL', 'St. Barthélemy'),
('209', 'SH', 'St. Helena'),
('210', 'KN', 'St. Kitts & Nevis'),
('211', 'LC', 'St. Lucia'),
('212', 'MF', 'St. Martin'),
('213', 'PM', 'St. Pierre & Miquelon'),
('214', 'VC', 'St. Vincent & Grenadines'),
('215', 'SD', 'Sudan'),
('216', 'SR', 'Suriname'),
('217', 'SJ', 'Svalbard & Jan Mayen'),
('218', 'SZ', 'Swaziland'),
('219', 'SE', 'Sweden'),
('220', 'CH', 'Switzerland'),
('221', 'SY', 'Syria'),
('222', 'TW', 'Taiwan'),
('223', 'TJ', 'Tajikistan'),
('224', 'TZ', 'Tanzania'),
('225', 'TH', 'Thailand'),
('226', 'TL', 'Timor-Leste'),
('227', 'TG', 'Togo'),
('228', 'TK', 'Tokelau'),
('229', 'TO', 'Tonga'),
('230', 'TT', 'Trinidad & Tobago'),
('231', 'TA', 'Tristan da Cunha'),
('232', 'TN', 'Tunisia'),
('233', 'TR', 'Turkey'),
('234', 'TM', 'Turkmenistan'),
('235', 'TC', 'Turks & Caicos Islands'),
('236', 'TV', 'Tuvalu'),
('237', 'UM', 'U.S. Outlying Islands'),
('238', 'VI', 'U.S. Virgin Islands'),
('239', 'UG', 'Uganda'),
('240', 'UA', 'Ukraine'),
('241', 'AE', 'United Arab Emirates'),
('242', 'GB', 'United Kingdom'),
('244', 'US', 'United States'),
('245', 'UY', 'Uruguay'),
('246', 'UZ', 'Uzbekistan'),
('247', 'VU', 'Vanuatu'),
('248', 'VA', 'Vatican City'),
('249', 'VE', 'Venezuela'),
('250', 'VN', 'Vietnam'),
('251', 'WF', 'Wallis & Futuna'),
('252', 'EH', 'Western Sahara'),
('253', 'YE', 'Yemen'),
('254', 'ZM', 'Zambia'),
('255', 'ZW', 'Zimbabwe');

-- ----------------------------------------------
-- Table structure for `country_state_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `country_state_translations`;
CREATE TABLE `country_state_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_state_id` int(10) unsigned NOT NULL,
  `locale` varchar(255) NOT NULL,
  `default_name` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `country_state_translations_country_state_id_foreign` (`country_state_id`),
  CONSTRAINT `country_state_translations_country_state_id_foreign` FOREIGN KEY (`country_state_id`) REFERENCES `country_states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `country_states`
-- ----------------------------------------------
DROP TABLE IF EXISTS `country_states`;
CREATE TABLE `country_states` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(10) unsigned DEFAULT NULL,
  `country_code` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `default_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `country_states_country_id_foreign` (`country_id`),
  CONSTRAINT `country_states_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=587 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `country_states` (586 rows)
INSERT INTO `country_states` (`id`, `country_id`, `country_code`, `code`, `default_name`) VALUES
('1', '244', 'US', 'AL', 'Alabama'),
('2', '244', 'US', 'AK', 'Alaska'),
('3', '244', 'US', 'AS', 'American Samoa'),
('4', '244', 'US', 'AZ', 'Arizona'),
('5', '244', 'US', 'AR', 'Arkansas'),
('6', '244', 'US', 'AE', 'Armed Forces Africa'),
('7', '244', 'US', 'AA', 'Armed Forces Americas'),
('8', '244', 'US', 'AE', 'Armed Forces Canada'),
('9', '244', 'US', 'AE', 'Armed Forces Europe'),
('10', '244', 'US', 'AE', 'Armed Forces Middle East'),
('11', '244', 'US', 'AP', 'Armed Forces Pacific'),
('12', '244', 'US', 'CA', 'California'),
('13', '244', 'US', 'CO', 'Colorado'),
('14', '244', 'US', 'CT', 'Connecticut'),
('15', '244', 'US', 'DE', 'Delaware'),
('16', '244', 'US', 'DC', 'District of Columbia'),
('17', '244', 'US', 'FM', 'Federated States Of Micronesia'),
('18', '244', 'US', 'FL', 'Florida'),
('19', '244', 'US', 'GA', 'Georgia'),
('20', '244', 'US', 'GU', 'Guam'),
('21', '244', 'US', 'HI', 'Hawaii'),
('22', '244', 'US', 'ID', 'Idaho'),
('23', '244', 'US', 'IL', 'Illinois'),
('24', '244', 'US', 'IN', 'Indiana'),
('25', '244', 'US', 'IA', 'Iowa'),
('26', '244', 'US', 'KS', 'Kansas'),
('27', '244', 'US', 'KY', 'Kentucky'),
('28', '244', 'US', 'LA', 'Louisiana'),
('29', '244', 'US', 'ME', 'Maine'),
('30', '244', 'US', 'MH', 'Marshall Islands'),
('31', '244', 'US', 'MD', 'Maryland'),
('32', '244', 'US', 'MA', 'Massachusetts'),
('33', '244', 'US', 'MI', 'Michigan'),
('34', '244', 'US', 'MN', 'Minnesota'),
('35', '244', 'US', 'MS', 'Mississippi'),
('36', '244', 'US', 'MO', 'Missouri'),
('37', '244', 'US', 'MT', 'Montana'),
('38', '244', 'US', 'NE', 'Nebraska'),
('39', '244', 'US', 'NV', 'Nevada'),
('40', '244', 'US', 'NH', 'New Hampshire'),
('41', '244', 'US', 'NJ', 'New Jersey'),
('42', '244', 'US', 'NM', 'New Mexico'),
('43', '244', 'US', 'NY', 'New York'),
('44', '244', 'US', 'NC', 'North Carolina'),
('45', '244', 'US', 'ND', 'North Dakota'),
('46', '244', 'US', 'MP', 'Northern Mariana Islands'),
('47', '244', 'US', 'OH', 'Ohio'),
('48', '244', 'US', 'OK', 'Oklahoma'),
('49', '244', 'US', 'OR', 'Oregon'),
('50', '244', 'US', 'PW', 'Palau'),
('51', '244', 'US', 'PA', 'Pennsylvania'),
('52', '244', 'US', 'PR', 'Puerto Rico'),
('53', '244', 'US', 'RI', 'Rhode Island'),
('54', '244', 'US', 'SC', 'South Carolina'),
('55', '244', 'US', 'SD', 'South Dakota'),
('56', '244', 'US', 'TN', 'Tennessee'),
('57', '244', 'US', 'TX', 'Texas'),
('58', '244', 'US', 'UT', 'Utah'),
('59', '244', 'US', 'VT', 'Vermont'),
('60', '244', 'US', 'VI', 'Virgin Islands'),
('61', '244', 'US', 'VA', 'Virginia'),
('62', '244', 'US', 'WA', 'Washington'),
('63', '244', 'US', 'WV', 'West Virginia'),
('64', '244', 'US', 'WI', 'Wisconsin'),
('65', '244', 'US', 'WY', 'Wyoming'),
('66', '40', 'CA', 'AB', 'Alberta'),
('67', '40', 'CA', 'BC', 'British Columbia'),
('68', '40', 'CA', 'MB', 'Manitoba'),
('69', '40', 'CA', 'NL', 'Newfoundland and Labrador'),
('70', '40', 'CA', 'NB', 'New Brunswick'),
('71', '40', 'CA', 'NS', 'Nova Scotia'),
('72', '40', 'CA', 'NT', 'Northwest Territories'),
('73', '40', 'CA', 'NU', 'Nunavut'),
('74', '40', 'CA', 'ON', 'Ontario'),
('75', '40', 'CA', 'PE', 'Prince Edward Island'),
('76', '40', 'CA', 'QC', 'Quebec'),
('77', '40', 'CA', 'SK', 'Saskatchewan'),
('78', '40', 'CA', 'YT', 'Yukon Territory'),
('79', '88', 'DE', 'NDS', 'Niedersachsen'),
('80', '88', 'DE', 'BAW', 'Baden-Württemberg'),
('81', '88', 'DE', 'BAY', 'Bayern'),
('82', '88', 'DE', 'BER', 'Berlin'),
('83', '88', 'DE', 'BRG', 'Brandenburg'),
('84', '88', 'DE', 'BRE', 'Bremen'),
('85', '88', 'DE', 'HAM', 'Hamburg'),
('86', '88', 'DE', 'HES', 'Hessen'),
('87', '88', 'DE', 'MEC', 'Mecklenburg-Vorpommern'),
('88', '88', 'DE', 'NRW', 'Nordrhein-Westfalen'),
('89', '88', 'DE', 'RHE', 'Rheinland-Pfalz'),
('90', '88', 'DE', 'SAR', 'Saarland'),
('91', '88', 'DE', 'SAS', 'Sachsen'),
('92', '88', 'DE', 'SAC', 'Sachsen-Anhalt'),
('93', '88', 'DE', 'SCN', 'Schleswig-Holstein'),
('94', '88', 'DE', 'THE', 'Thüringen'),
('95', '16', 'AT', 'WI', 'Wien'),
('96', '16', 'AT', 'NO', 'Niederösterreich'),
('97', '16', 'AT', 'OO', 'Oberösterreich'),
('98', '16', 'AT', 'SB', 'Salzburg'),
('99', '16', 'AT', 'KN', 'Kärnten'),
('100', '16', 'AT', 'ST', 'Steiermark');
INSERT INTO `country_states` (`id`, `country_id`, `country_code`, `code`, `default_name`) VALUES
('101', '16', 'AT', 'TI', 'Tirol'),
('102', '16', 'AT', 'BL', 'Burgenland'),
('103', '16', 'AT', 'VB', 'Vorarlberg'),
('104', '220', 'CH', 'AG', 'Aargau'),
('105', '220', 'CH', 'AI', 'Appenzell Innerrhoden'),
('106', '220', 'CH', 'AR', 'Appenzell Ausserrhoden'),
('107', '220', 'CH', 'BE', 'Bern'),
('108', '220', 'CH', 'BL', 'Basel-Landschaft'),
('109', '220', 'CH', 'BS', 'Basel-Stadt'),
('110', '220', 'CH', 'FR', 'Freiburg'),
('111', '220', 'CH', 'GE', 'Genf'),
('112', '220', 'CH', 'GL', 'Glarus'),
('113', '220', 'CH', 'GR', 'Graubünden'),
('114', '220', 'CH', 'JU', 'Jura'),
('115', '220', 'CH', 'LU', 'Luzern'),
('116', '220', 'CH', 'NE', 'Neuenburg'),
('117', '220', 'CH', 'NW', 'Nidwalden'),
('118', '220', 'CH', 'OW', 'Obwalden'),
('119', '220', 'CH', 'SG', 'St. Gallen'),
('120', '220', 'CH', 'SH', 'Schaffhausen'),
('121', '220', 'CH', 'SO', 'Solothurn'),
('122', '220', 'CH', 'SZ', 'Schwyz'),
('123', '220', 'CH', 'TG', 'Thurgau'),
('124', '220', 'CH', 'TI', 'Tessin'),
('125', '220', 'CH', 'UR', 'Uri'),
('126', '220', 'CH', 'VD', 'Waadt'),
('127', '220', 'CH', 'VS', 'Wallis'),
('128', '220', 'CH', 'ZG', 'Zug'),
('129', '220', 'CH', 'ZH', 'Zürich'),
('130', '206', 'ES', 'A Coruсa', 'A Coruña'),
('131', '206', 'ES', 'Alava', 'Alava'),
('132', '206', 'ES', 'Albacete', 'Albacete'),
('133', '206', 'ES', 'Alicante', 'Alicante'),
('134', '206', 'ES', 'Almeria', 'Almeria'),
('135', '206', 'ES', 'Asturias', 'Asturias'),
('136', '206', 'ES', 'Avila', 'Avila'),
('137', '206', 'ES', 'Badajoz', 'Badajoz'),
('138', '206', 'ES', 'Baleares', 'Baleares'),
('139', '206', 'ES', 'Barcelona', 'Barcelona'),
('140', '206', 'ES', 'Burgos', 'Burgos'),
('141', '206', 'ES', 'Caceres', 'Caceres'),
('142', '206', 'ES', 'Cadiz', 'Cadiz'),
('143', '206', 'ES', 'Cantabria', 'Cantabria'),
('144', '206', 'ES', 'Castellon', 'Castellon'),
('145', '206', 'ES', 'Ceuta', 'Ceuta'),
('146', '206', 'ES', 'Ciudad Real', 'Ciudad Real'),
('147', '206', 'ES', 'Cordoba', 'Cordoba'),
('148', '206', 'ES', 'Cuenca', 'Cuenca'),
('149', '206', 'ES', 'Girona', 'Girona'),
('150', '206', 'ES', 'Granada', 'Granada'),
('151', '206', 'ES', 'Guadalajara', 'Guadalajara'),
('152', '206', 'ES', 'Guipuzcoa', 'Guipuzcoa'),
('153', '206', 'ES', 'Huelva', 'Huelva'),
('154', '206', 'ES', 'Huesca', 'Huesca'),
('155', '206', 'ES', 'Jaen', 'Jaen'),
('156', '206', 'ES', 'La Rioja', 'La Rioja'),
('157', '206', 'ES', 'Las Palmas', 'Las Palmas'),
('158', '206', 'ES', 'Leon', 'Leon'),
('159', '206', 'ES', 'Lleida', 'Lleida'),
('160', '206', 'ES', 'Lugo', 'Lugo'),
('161', '206', 'ES', 'Madrid', 'Madrid'),
('162', '206', 'ES', 'Malaga', 'Malaga'),
('163', '206', 'ES', 'Melilla', 'Melilla'),
('164', '206', 'ES', 'Murcia', 'Murcia'),
('165', '206', 'ES', 'Navarra', 'Navarra'),
('166', '206', 'ES', 'Ourense', 'Ourense'),
('167', '206', 'ES', 'Palencia', 'Palencia'),
('168', '206', 'ES', 'Pontevedra', 'Pontevedra'),
('169', '206', 'ES', 'Salamanca', 'Salamanca'),
('170', '206', 'ES', 'Santa Cruz de Tenerife', 'Santa Cruz de Tenerife'),
('171', '206', 'ES', 'Segovia', 'Segovia'),
('172', '206', 'ES', 'Sevilla', 'Sevilla'),
('173', '206', 'ES', 'Soria', 'Soria'),
('174', '206', 'ES', 'Tarragona', 'Tarragona'),
('175', '206', 'ES', 'Teruel', 'Teruel'),
('176', '206', 'ES', 'Toledo', 'Toledo'),
('177', '206', 'ES', 'Valencia', 'Valencia'),
('178', '206', 'ES', 'Valladolid', 'Valladolid'),
('179', '206', 'ES', 'Vizcaya', 'Vizcaya'),
('180', '206', 'ES', 'Zamora', 'Zamora'),
('181', '206', 'ES', 'Zaragoza', 'Zaragoza'),
('182', '81', 'FR', '1', 'Ain'),
('183', '81', 'FR', '2', 'Aisne'),
('184', '81', 'FR', '3', 'Allier'),
('185', '81', 'FR', '4', 'Alpes-de-Haute-Provence'),
('186', '81', 'FR', '5', 'Hautes-Alpes'),
('187', '81', 'FR', '6', 'Alpes-Maritimes'),
('188', '81', 'FR', '7', 'Ardèche'),
('189', '81', 'FR', '8', 'Ardennes'),
('190', '81', 'FR', '9', 'Ariège'),
('191', '81', 'FR', '10', 'Aube'),
('192', '81', 'FR', '11', 'Aude'),
('193', '81', 'FR', '12', 'Aveyron'),
('194', '81', 'FR', '13', 'Bouches-du-Rhône'),
('195', '81', 'FR', '14', 'Calvados'),
('196', '81', 'FR', '15', 'Cantal'),
('197', '81', 'FR', '16', 'Charente'),
('198', '81', 'FR', '17', 'Charente-Maritime'),
('199', '81', 'FR', '18', 'Cher'),
('200', '81', 'FR', '19', 'Corrèze');
INSERT INTO `country_states` (`id`, `country_id`, `country_code`, `code`, `default_name`) VALUES
('201', '81', 'FR', '2A', 'Corse-du-Sud'),
('202', '81', 'FR', '2B', 'Haute-Corse'),
('203', '81', 'FR', '21', 'Côte-d\'Or'),
('204', '81', 'FR', '22', 'Côtes-d\'Armor'),
('205', '81', 'FR', '23', 'Creuse'),
('206', '81', 'FR', '24', 'Dordogne'),
('207', '81', 'FR', '25', 'Doubs'),
('208', '81', 'FR', '26', 'Drôme'),
('209', '81', 'FR', '27', 'Eure'),
('210', '81', 'FR', '28', 'Eure-et-Loir'),
('211', '81', 'FR', '29', 'Finistère'),
('212', '81', 'FR', '30', 'Gard'),
('213', '81', 'FR', '31', 'Haute-Garonne'),
('214', '81', 'FR', '32', 'Gers'),
('215', '81', 'FR', '33', 'Gironde'),
('216', '81', 'FR', '34', 'Hérault'),
('217', '81', 'FR', '35', 'Ille-et-Vilaine'),
('218', '81', 'FR', '36', 'Indre'),
('219', '81', 'FR', '37', 'Indre-et-Loire'),
('220', '81', 'FR', '38', 'Isère'),
('221', '81', 'FR', '39', 'Jura'),
('222', '81', 'FR', '40', 'Landes'),
('223', '81', 'FR', '41', 'Loir-et-Cher'),
('224', '81', 'FR', '42', 'Loire'),
('225', '81', 'FR', '43', 'Haute-Loire'),
('226', '81', 'FR', '44', 'Loire-Atlantique'),
('227', '81', 'FR', '45', 'Loiret'),
('228', '81', 'FR', '46', 'Lot'),
('229', '81', 'FR', '47', 'Lot-et-Garonne'),
('230', '81', 'FR', '48', 'Lozère'),
('231', '81', 'FR', '49', 'Maine-et-Loire'),
('232', '81', 'FR', '50', 'Manche'),
('233', '81', 'FR', '51', 'Marne'),
('234', '81', 'FR', '52', 'Haute-Marne'),
('235', '81', 'FR', '53', 'Mayenne'),
('236', '81', 'FR', '54', 'Meurthe-et-Moselle'),
('237', '81', 'FR', '55', 'Meuse'),
('238', '81', 'FR', '56', 'Morbihan'),
('239', '81', 'FR', '57', 'Moselle'),
('240', '81', 'FR', '58', 'Nièvre'),
('241', '81', 'FR', '59', 'Nord'),
('242', '81', 'FR', '60', 'Oise'),
('243', '81', 'FR', '61', 'Orne'),
('244', '81', 'FR', '62', 'Pas-de-Calais'),
('245', '81', 'FR', '63', 'Puy-de-Dôme'),
('246', '81', 'FR', '64', 'Pyrénées-Atlantiques'),
('247', '81', 'FR', '65', 'Hautes-Pyrénées'),
('248', '81', 'FR', '66', 'Pyrénées-Orientales'),
('249', '81', 'FR', '67', 'Bas-Rhin'),
('250', '81', 'FR', '68', 'Haut-Rhin'),
('251', '81', 'FR', '69', 'Rhône'),
('252', '81', 'FR', '70', 'Haute-Saône'),
('253', '81', 'FR', '71', 'Saône-et-Loire'),
('254', '81', 'FR', '72', 'Sarthe'),
('255', '81', 'FR', '73', 'Savoie'),
('256', '81', 'FR', '74', 'Haute-Savoie'),
('257', '81', 'FR', '75', 'Paris'),
('258', '81', 'FR', '76', 'Seine-Maritime'),
('259', '81', 'FR', '77', 'Seine-et-Marne'),
('260', '81', 'FR', '78', 'Yvelines'),
('261', '81', 'FR', '79', 'Deux-Sèvres'),
('262', '81', 'FR', '80', 'Somme'),
('263', '81', 'FR', '81', 'Tarn'),
('264', '81', 'FR', '82', 'Tarn-et-Garonne'),
('265', '81', 'FR', '83', 'Var'),
('266', '81', 'FR', '84', 'Vaucluse'),
('267', '81', 'FR', '85', 'Vendée'),
('268', '81', 'FR', '86', 'Vienne'),
('269', '81', 'FR', '87', 'Haute-Vienne'),
('270', '81', 'FR', '88', 'Vosges'),
('271', '81', 'FR', '89', 'Yonne'),
('272', '81', 'FR', '90', 'Territoire-de-Belfort'),
('273', '81', 'FR', '91', 'Essonne'),
('274', '81', 'FR', '92', 'Hauts-de-Seine'),
('275', '81', 'FR', '93', 'Seine-Saint-Denis'),
('276', '81', 'FR', '94', 'Val-de-Marne'),
('277', '81', 'FR', '95', 'Val-d\'Oise'),
('278', '185', 'RO', 'AB', 'Alba'),
('279', '185', 'RO', 'AR', 'Arad'),
('280', '185', 'RO', 'AG', 'Argeş'),
('281', '185', 'RO', 'BC', 'Bacău'),
('282', '185', 'RO', 'BH', 'Bihor'),
('283', '185', 'RO', 'BN', 'Bistriţa-Năsăud'),
('284', '185', 'RO', 'BT', 'Botoşani'),
('285', '185', 'RO', 'BV', 'Braşov'),
('286', '185', 'RO', 'BR', 'Brăila'),
('287', '185', 'RO', 'B', 'Bucureşti'),
('288', '185', 'RO', 'BZ', 'Buzău'),
('289', '185', 'RO', 'CS', 'Caraş-Severin'),
('290', '185', 'RO', 'CL', 'Călăraşi'),
('291', '185', 'RO', 'CJ', 'Cluj'),
('292', '185', 'RO', 'CT', 'Constanţa'),
('293', '185', 'RO', 'CV', 'Covasna'),
('294', '185', 'RO', 'DB', 'Dâmboviţa'),
('295', '185', 'RO', 'DJ', 'Dolj'),
('296', '185', 'RO', 'GL', 'Galaţi'),
('297', '185', 'RO', 'GR', 'Giurgiu'),
('298', '185', 'RO', 'GJ', 'Gorj'),
('299', '185', 'RO', 'HR', 'Harghita'),
('300', '185', 'RO', 'HD', 'Hunedoara');
INSERT INTO `country_states` (`id`, `country_id`, `country_code`, `code`, `default_name`) VALUES
('301', '185', 'RO', 'IL', 'Ialomiţa'),
('302', '185', 'RO', 'IS', 'Iaşi'),
('303', '185', 'RO', 'IF', 'Ilfov'),
('304', '185', 'RO', 'MM', 'Maramureş'),
('305', '185', 'RO', 'MH', 'Mehedinţi'),
('306', '185', 'RO', 'MS', 'Mureş'),
('307', '185', 'RO', 'NT', 'Neamţ'),
('308', '185', 'RO', 'OT', 'Olt'),
('309', '185', 'RO', 'PH', 'Prahova'),
('310', '185', 'RO', 'SM', 'Satu-Mare'),
('311', '185', 'RO', 'SJ', 'Sălaj'),
('312', '185', 'RO', 'SB', 'Sibiu'),
('313', '185', 'RO', 'SV', 'Suceava'),
('314', '185', 'RO', 'TR', 'Teleorman'),
('315', '185', 'RO', 'TM', 'Timiş'),
('316', '185', 'RO', 'TL', 'Tulcea'),
('317', '185', 'RO', 'VS', 'Vaslui'),
('318', '185', 'RO', 'VL', 'Vâlcea'),
('319', '185', 'RO', 'VN', 'Vrancea'),
('320', '80', 'FI', 'Lappi', 'Lappi'),
('321', '80', 'FI', 'Pohjois-Pohjanmaa', 'Pohjois-Pohjanmaa'),
('322', '80', 'FI', 'Kainuu', 'Kainuu'),
('323', '80', 'FI', 'Pohjois-Karjala', 'Pohjois-Karjala'),
('324', '80', 'FI', 'Pohjois-Savo', 'Pohjois-Savo'),
('325', '80', 'FI', 'Etelä-Savo', 'Etelä-Savo'),
('326', '80', 'FI', 'Etelä-Pohjanmaa', 'Etelä-Pohjanmaa'),
('327', '80', 'FI', 'Pohjanmaa', 'Pohjanmaa'),
('328', '80', 'FI', 'Pirkanmaa', 'Pirkanmaa'),
('329', '80', 'FI', 'Satakunta', 'Satakunta'),
('330', '80', 'FI', 'Keski-Pohjanmaa', 'Keski-Pohjanmaa'),
('331', '80', 'FI', 'Keski-Suomi', 'Keski-Suomi'),
('332', '80', 'FI', 'Varsinais-Suomi', 'Varsinais-Suomi'),
('333', '80', 'FI', 'Etelä-Karjala', 'Etelä-Karjala'),
('334', '80', 'FI', 'Päijät-Häme', 'Päijät-Häme'),
('335', '80', 'FI', 'Kanta-Häme', 'Kanta-Häme'),
('336', '80', 'FI', 'Uusimaa', 'Uusimaa'),
('337', '80', 'FI', 'Itä-Uusimaa', 'Itä-Uusimaa'),
('338', '80', 'FI', 'Kymenlaakso', 'Kymenlaakso'),
('339', '80', 'FI', 'Ahvenanmaa', 'Ahvenanmaa'),
('340', '74', 'EE', 'EE-37', 'Harjumaa'),
('341', '74', 'EE', 'EE-39', 'Hiiumaa'),
('342', '74', 'EE', 'EE-44', 'Ida-Virumaa'),
('343', '74', 'EE', 'EE-49', 'Jõgevamaa'),
('344', '74', 'EE', 'EE-51', 'Järvamaa'),
('345', '74', 'EE', 'EE-57', 'Läänemaa'),
('346', '74', 'EE', 'EE-59', 'Lääne-Virumaa'),
('347', '74', 'EE', 'EE-65', 'Põlvamaa'),
('348', '74', 'EE', 'EE-67', 'Pärnumaa'),
('349', '74', 'EE', 'EE-70', 'Raplamaa'),
('350', '74', 'EE', 'EE-74', 'Saaremaa'),
('351', '74', 'EE', 'EE-78', 'Tartumaa'),
('352', '74', 'EE', 'EE-82', 'Valgamaa'),
('353', '74', 'EE', 'EE-84', 'Viljandimaa'),
('354', '74', 'EE', 'EE-86', 'Võrumaa'),
('355', '125', 'LV', 'LV-DGV', 'Daugavpils'),
('356', '125', 'LV', 'LV-JEL', 'Jelgava'),
('357', '125', 'LV', 'Jēkabpils', 'Jēkabpils'),
('358', '125', 'LV', 'LV-JUR', 'Jūrmala'),
('359', '125', 'LV', 'LV-LPX', 'Liepāja'),
('360', '125', 'LV', 'LV-LE', 'Liepājas novads'),
('361', '125', 'LV', 'LV-REZ', 'Rēzekne'),
('362', '125', 'LV', 'LV-RIX', 'Rīga'),
('363', '125', 'LV', 'LV-RI', 'Rīgas novads'),
('364', '125', 'LV', 'Valmiera', 'Valmiera'),
('365', '125', 'LV', 'LV-VEN', 'Ventspils'),
('366', '125', 'LV', 'Aglonas novads', 'Aglonas novads'),
('367', '125', 'LV', 'LV-AI', 'Aizkraukles novads'),
('368', '125', 'LV', 'Aizputes novads', 'Aizputes novads'),
('369', '125', 'LV', 'Aknīstes novads', 'Aknīstes novads'),
('370', '125', 'LV', 'Alojas novads', 'Alojas novads'),
('371', '125', 'LV', 'Alsungas novads', 'Alsungas novads'),
('372', '125', 'LV', 'LV-AL', 'Alūksnes novads'),
('373', '125', 'LV', 'Amatas novads', 'Amatas novads'),
('374', '125', 'LV', 'Apes novads', 'Apes novads'),
('375', '125', 'LV', 'Auces novads', 'Auces novads'),
('376', '125', 'LV', 'Babītes novads', 'Babītes novads'),
('377', '125', 'LV', 'Baldones novads', 'Baldones novads'),
('378', '125', 'LV', 'Baltinavas novads', 'Baltinavas novads'),
('379', '125', 'LV', 'LV-BL', 'Balvu novads'),
('380', '125', 'LV', 'LV-BU', 'Bauskas novads'),
('381', '125', 'LV', 'Beverīnas novads', 'Beverīnas novads'),
('382', '125', 'LV', 'Brocēnu novads', 'Brocēnu novads'),
('383', '125', 'LV', 'Burtnieku novads', 'Burtnieku novads'),
('384', '125', 'LV', 'Carnikavas novads', 'Carnikavas novads'),
('385', '125', 'LV', 'Cesvaines novads', 'Cesvaines novads'),
('386', '125', 'LV', 'Ciblas novads', 'Ciblas novads'),
('387', '125', 'LV', 'LV-CE', 'Cēsu novads'),
('388', '125', 'LV', 'Dagdas novads', 'Dagdas novads'),
('389', '125', 'LV', 'LV-DA', 'Daugavpils novads'),
('390', '125', 'LV', 'LV-DO', 'Dobeles novads'),
('391', '125', 'LV', 'Dundagas novads', 'Dundagas novads'),
('392', '125', 'LV', 'Durbes novads', 'Durbes novads'),
('393', '125', 'LV', 'Engures novads', 'Engures novads'),
('394', '125', 'LV', 'Garkalnes novads', 'Garkalnes novads'),
('395', '125', 'LV', 'Grobiņas novads', 'Grobiņas novads'),
('396', '125', 'LV', 'LV-GU', 'Gulbenes novads'),
('397', '125', 'LV', 'Iecavas novads', 'Iecavas novads'),
('398', '125', 'LV', 'Ikšķiles novads', 'Ikšķiles novads'),
('399', '125', 'LV', 'Ilūkstes novads', 'Ilūkstes novads'),
('400', '125', 'LV', 'Inčukalna novads', 'Inčukalna novads');
INSERT INTO `country_states` (`id`, `country_id`, `country_code`, `code`, `default_name`) VALUES
('401', '125', 'LV', 'Jaunjelgavas novads', 'Jaunjelgavas novads'),
('402', '125', 'LV', 'Jaunpiebalgas novads', 'Jaunpiebalgas novads'),
('403', '125', 'LV', 'Jaunpils novads', 'Jaunpils novads'),
('404', '125', 'LV', 'LV-JL', 'Jelgavas novads'),
('405', '125', 'LV', 'LV-JK', 'Jēkabpils novads'),
('406', '125', 'LV', 'Kandavas novads', 'Kandavas novads'),
('407', '125', 'LV', 'Kokneses novads', 'Kokneses novads'),
('408', '125', 'LV', 'Krimuldas novads', 'Krimuldas novads'),
('409', '125', 'LV', 'Krustpils novads', 'Krustpils novads'),
('410', '125', 'LV', 'LV-KR', 'Krāslavas novads'),
('411', '125', 'LV', 'LV-KU', 'Kuldīgas novads'),
('412', '125', 'LV', 'Kārsavas novads', 'Kārsavas novads'),
('413', '125', 'LV', 'Lielvārdes novads', 'Lielvārdes novads'),
('414', '125', 'LV', 'LV-LM', 'Limbažu novads'),
('415', '125', 'LV', 'Lubānas novads', 'Lubānas novads'),
('416', '125', 'LV', 'LV-LU', 'Ludzas novads'),
('417', '125', 'LV', 'Līgatnes novads', 'Līgatnes novads'),
('418', '125', 'LV', 'Līvānu novads', 'Līvānu novads'),
('419', '125', 'LV', 'LV-MA', 'Madonas novads'),
('420', '125', 'LV', 'Mazsalacas novads', 'Mazsalacas novads'),
('421', '125', 'LV', 'Mālpils novads', 'Mālpils novads'),
('422', '125', 'LV', 'Mārupes novads', 'Mārupes novads'),
('423', '125', 'LV', 'Naukšēnu novads', 'Naukšēnu novads'),
('424', '125', 'LV', 'Neretas novads', 'Neretas novads'),
('425', '125', 'LV', 'Nīcas novads', 'Nīcas novads'),
('426', '125', 'LV', 'LV-OG', 'Ogres novads'),
('427', '125', 'LV', 'Olaines novads', 'Olaines novads'),
('428', '125', 'LV', 'Ozolnieku novads', 'Ozolnieku novads'),
('429', '125', 'LV', 'LV-PR', 'Preiļu novads'),
('430', '125', 'LV', 'Priekules novads', 'Priekules novads'),
('431', '125', 'LV', 'Priekuļu novads', 'Priekuļu novads'),
('432', '125', 'LV', 'Pārgaujas novads', 'Pārgaujas novads'),
('433', '125', 'LV', 'Pāvilostas novads', 'Pāvilostas novads'),
('434', '125', 'LV', 'Pļaviņu novads', 'Pļaviņu novads'),
('435', '125', 'LV', 'Raunas novads', 'Raunas novads'),
('436', '125', 'LV', 'Riebiņu novads', 'Riebiņu novads'),
('437', '125', 'LV', 'Rojas novads', 'Rojas novads'),
('438', '125', 'LV', 'Ropažu novads', 'Ropažu novads'),
('439', '125', 'LV', 'Rucavas novads', 'Rucavas novads'),
('440', '125', 'LV', 'Rugāju novads', 'Rugāju novads'),
('441', '125', 'LV', 'Rundāles novads', 'Rundāles novads'),
('442', '125', 'LV', 'LV-RE', 'Rēzeknes novads'),
('443', '125', 'LV', 'Rūjienas novads', 'Rūjienas novads'),
('444', '125', 'LV', 'Salacgrīvas novads', 'Salacgrīvas novads'),
('445', '125', 'LV', 'Salas novads', 'Salas novads'),
('446', '125', 'LV', 'Salaspils novads', 'Salaspils novads'),
('447', '125', 'LV', 'LV-SA', 'Saldus novads'),
('448', '125', 'LV', 'Saulkrastu novads', 'Saulkrastu novads'),
('449', '125', 'LV', 'Siguldas novads', 'Siguldas novads'),
('450', '125', 'LV', 'Skrundas novads', 'Skrundas novads'),
('451', '125', 'LV', 'Skrīveru novads', 'Skrīveru novads'),
('452', '125', 'LV', 'Smiltenes novads', 'Smiltenes novads'),
('453', '125', 'LV', 'Stopiņu novads', 'Stopiņu novads'),
('454', '125', 'LV', 'Strenču novads', 'Strenču novads'),
('455', '125', 'LV', 'Sējas novads', 'Sējas novads'),
('456', '125', 'LV', 'LV-TA', 'Talsu novads'),
('457', '125', 'LV', 'LV-TU', 'Tukuma novads'),
('458', '125', 'LV', 'Tērvetes novads', 'Tērvetes novads'),
('459', '125', 'LV', 'Vaiņodes novads', 'Vaiņodes novads'),
('460', '125', 'LV', 'LV-VK', 'Valkas novads'),
('461', '125', 'LV', 'LV-VM', 'Valmieras novads'),
('462', '125', 'LV', 'Varakļānu novads', 'Varakļānu novads'),
('463', '125', 'LV', 'Vecpiebalgas novads', 'Vecpiebalgas novads'),
('464', '125', 'LV', 'Vecumnieku novads', 'Vecumnieku novads'),
('465', '125', 'LV', 'LV-VE', 'Ventspils novads'),
('466', '125', 'LV', 'Viesītes novads', 'Viesītes novads'),
('467', '125', 'LV', 'Viļakas novads', 'Viļakas novads'),
('468', '125', 'LV', 'Viļānu novads', 'Viļānu novads'),
('469', '125', 'LV', 'Vārkavas novads', 'Vārkavas novads'),
('470', '125', 'LV', 'Zilupes novads', 'Zilupes novads'),
('471', '125', 'LV', 'Ādažu novads', 'Ādažu novads'),
('472', '125', 'LV', 'Ērgļu novads', 'Ērgļu novads'),
('473', '125', 'LV', 'Ķeguma novads', 'Ķeguma novads'),
('474', '125', 'LV', 'Ķekavas novads', 'Ķekavas novads'),
('475', '131', 'LT', 'LT-AL', 'Alytaus Apskritis'),
('476', '131', 'LT', 'LT-KU', 'Kauno Apskritis'),
('477', '131', 'LT', 'LT-KL', 'Klaipėdos Apskritis'),
('478', '131', 'LT', 'LT-MR', 'Marijampolės Apskritis'),
('479', '131', 'LT', 'LT-PN', 'Panevėžio Apskritis'),
('480', '131', 'LT', 'LT-SA', 'Šiaulių Apskritis'),
('481', '131', 'LT', 'LT-TA', 'Tauragės Apskritis'),
('482', '131', 'LT', 'LT-TE', 'Telšių Apskritis'),
('483', '131', 'LT', 'LT-UT', 'Utenos Apskritis'),
('484', '131', 'LT', 'LT-VL', 'Vilniaus Apskritis'),
('485', '31', 'BR', 'AC', 'Acre'),
('486', '31', 'BR', 'AL', 'Alagoas'),
('487', '31', 'BR', 'AP', 'Amapá'),
('488', '31', 'BR', 'AM', 'Amazonas'),
('489', '31', 'BR', 'BA', 'Bahia'),
('490', '31', 'BR', 'CE', 'Ceará'),
('491', '31', 'BR', 'ES', 'Espírito Santo'),
('492', '31', 'BR', 'GO', 'Goiás'),
('493', '31', 'BR', 'MA', 'Maranhão'),
('494', '31', 'BR', 'MT', 'Mato Grosso'),
('495', '31', 'BR', 'MS', 'Mato Grosso do Sul'),
('496', '31', 'BR', 'MG', 'Minas Gerais'),
('497', '31', 'BR', 'PA', 'Pará'),
('498', '31', 'BR', 'PB', 'Paraíba'),
('499', '31', 'BR', 'PR', 'Paraná'),
('500', '31', 'BR', 'PE', 'Pernambuco');
INSERT INTO `country_states` (`id`, `country_id`, `country_code`, `code`, `default_name`) VALUES
('501', '31', 'BR', 'PI', 'Piauí'),
('502', '31', 'BR', 'RJ', 'Rio de Janeiro'),
('503', '31', 'BR', 'RN', 'Rio Grande do Norte'),
('504', '31', 'BR', 'RS', 'Rio Grande do Sul'),
('505', '31', 'BR', 'RO', 'Rondônia'),
('506', '31', 'BR', 'RR', 'Roraima'),
('507', '31', 'BR', 'SC', 'Santa Catarina'),
('508', '31', 'BR', 'SP', 'São Paulo'),
('509', '31', 'BR', 'SE', 'Sergipe'),
('510', '31', 'BR', 'TO', 'Tocantins'),
('511', '31', 'BR', 'DF', 'Distrito Federal'),
('512', '59', 'HR', 'HR-01', 'Zagrebačka županija'),
('513', '59', 'HR', 'HR-02', 'Krapinsko-zagorska županija'),
('514', '59', 'HR', 'HR-03', 'Sisačko-moslavačka županija'),
('515', '59', 'HR', 'HR-04', 'Karlovačka županija'),
('516', '59', 'HR', 'HR-05', 'Varaždinska županija'),
('517', '59', 'HR', 'HR-06', 'Koprivničko-križevačka županija'),
('518', '59', 'HR', 'HR-07', 'Bjelovarsko-bilogorska županija'),
('519', '59', 'HR', 'HR-08', 'Primorsko-goranska županija'),
('520', '59', 'HR', 'HR-09', 'Ličko-senjska županija'),
('521', '59', 'HR', 'HR-10', 'Virovitičko-podravska županija'),
('522', '59', 'HR', 'HR-11', 'Požeško-slavonska županija'),
('523', '59', 'HR', 'HR-12', 'Brodsko-posavska županija'),
('524', '59', 'HR', 'HR-13', 'Zadarska županija'),
('525', '59', 'HR', 'HR-14', 'Osječko-baranjska županija'),
('526', '59', 'HR', 'HR-15', 'Šibensko-kninska županija'),
('527', '59', 'HR', 'HR-16', 'Vukovarsko-srijemska županija'),
('528', '59', 'HR', 'HR-17', 'Splitsko-dalmatinska županija'),
('529', '59', 'HR', 'HR-18', 'Istarska županija'),
('530', '59', 'HR', 'HR-19', 'Dubrovačko-neretvanska županija'),
('531', '59', 'HR', 'HR-20', 'Međimurska županija'),
('532', '59', 'HR', 'HR-21', 'Grad Zagreb'),
('533', '106', 'IN', 'AN', 'Andaman and Nicobar Islands'),
('534', '106', 'IN', 'AP', 'Andhra Pradesh'),
('535', '106', 'IN', 'AR', 'Arunachal Pradesh'),
('536', '106', 'IN', 'AS', 'Assam'),
('537', '106', 'IN', 'BR', 'Bihar'),
('538', '106', 'IN', 'CH', 'Chandigarh'),
('539', '106', 'IN', 'CT', 'Chhattisgarh'),
('540', '106', 'IN', 'DN', 'Dadra and Nagar Haveli'),
('541', '106', 'IN', 'DD', 'Daman and Diu'),
('542', '106', 'IN', 'DL', 'Delhi'),
('543', '106', 'IN', 'GA', 'Goa'),
('544', '106', 'IN', 'GJ', 'Gujarat'),
('545', '106', 'IN', 'HR', 'Haryana'),
('546', '106', 'IN', 'HP', 'Himachal Pradesh'),
('547', '106', 'IN', 'JK', 'Jammu and Kashmir'),
('548', '106', 'IN', 'JH', 'Jharkhand'),
('549', '106', 'IN', 'KA', 'Karnataka'),
('550', '106', 'IN', 'KL', 'Kerala'),
('551', '106', 'IN', 'LD', 'Lakshadweep'),
('552', '106', 'IN', 'MP', 'Madhya Pradesh'),
('553', '106', 'IN', 'MH', 'Maharashtra'),
('554', '106', 'IN', 'MN', 'Manipur'),
('555', '106', 'IN', 'ML', 'Meghalaya'),
('556', '106', 'IN', 'MZ', 'Mizoram'),
('557', '106', 'IN', 'NL', 'Nagaland'),
('558', '106', 'IN', 'OR', 'Odisha'),
('559', '106', 'IN', 'PY', 'Puducherry'),
('560', '106', 'IN', 'PB', 'Punjab'),
('561', '106', 'IN', 'RJ', 'Rajasthan'),
('562', '106', 'IN', 'SK', 'Sikkim'),
('563', '106', 'IN', 'TN', 'Tamil Nadu'),
('564', '106', 'IN', 'TG', 'Telangana'),
('565', '106', 'IN', 'TR', 'Tripura'),
('566', '106', 'IN', 'UP', 'Uttar Pradesh'),
('567', '106', 'IN', 'UT', 'Uttarakhand'),
('568', '106', 'IN', 'WB', 'West Bengal'),
('569', '176', 'PY', 'PY-16', 'Alto Paraguay'),
('570', '176', 'PY', 'PY-10', 'Alto Paraná'),
('571', '176', 'PY', 'PY-13', 'Amambay'),
('572', '176', 'PY', 'PY-ASU', 'Asunción'),
('573', '176', 'PY', 'PY-19', 'Boquerón'),
('574', '176', 'PY', 'PY-5', 'Caaguazú'),
('575', '176', 'PY', 'PY-6', 'Caazapá'),
('576', '176', 'PY', 'PY-14', 'Canindeyú'),
('577', '176', 'PY', 'PY-11', 'Central'),
('578', '176', 'PY', 'PY-1', 'Concepción'),
('579', '176', 'PY', 'PY-3', 'Cordillera'),
('580', '176', 'PY', 'PY-4', 'Guairá'),
('581', '176', 'PY', 'PY-7', 'Itapúa'),
('582', '176', 'PY', 'PY-8', 'Misiones'),
('583', '176', 'PY', 'PY-9', 'Paraguarí'),
('584', '176', 'PY', 'PY-15', 'Presidente Hayes'),
('585', '176', 'PY', 'PY-2', 'San Pedro'),
('586', '176', 'PY', 'PY-12', 'Ñeembucú');

-- ----------------------------------------------
-- Table structure for `country_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `country_translations`;
CREATE TABLE `country_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(10) unsigned NOT NULL,
  `locale` varchar(255) NOT NULL,
  `name` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `country_translations_country_id_foreign` (`country_id`),
  CONSTRAINT `country_translations_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `currencies`
-- ----------------------------------------------
DROP TABLE IF EXISTS `currencies`;
CREATE TABLE `currencies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `symbol` varchar(255) DEFAULT NULL,
  `decimal` int(10) unsigned NOT NULL DEFAULT 2,
  `group_separator` varchar(255) NOT NULL DEFAULT ',',
  `decimal_separator` varchar(255) NOT NULL DEFAULT '.',
  `currency_position` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `currencies` (1 rows)
INSERT INTO `currencies` (`id`, `code`, `name`, `symbol`, `decimal`, `group_separator`, `decimal_separator`, `currency_position`, `created_at`, `updated_at`) VALUES
('1', 'USD', 'الدولار الأمريكي', '$', '2', ',', '.', NULL, NULL, NULL);

-- ----------------------------------------------
-- Table structure for `currency_exchange_rates`
-- ----------------------------------------------
DROP TABLE IF EXISTS `currency_exchange_rates`;
CREATE TABLE `currency_exchange_rates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rate` decimal(24,12) NOT NULL,
  `target_currency` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currency_exchange_rates_target_currency_unique` (`target_currency`),
  CONSTRAINT `currency_exchange_rates_target_currency_foreign` FOREIGN KEY (`target_currency`) REFERENCES `currencies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `customer_groups`
-- ----------------------------------------------
DROP TABLE IF EXISTS `customer_groups`;
CREATE TABLE `customer_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_user_defined` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_groups_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `customer_groups` (3 rows)
INSERT INTO `customer_groups` (`id`, `code`, `name`, `is_user_defined`, `created_at`, `updated_at`) VALUES
('1', 'guest', 'زائر', '0', NULL, NULL),
('2', 'general', 'عام', '0', NULL, NULL),
('3', 'wholesale', 'جملة', '0', NULL, NULL);

-- ----------------------------------------------
-- Table structure for `customer_notes`
-- ----------------------------------------------
DROP TABLE IF EXISTS `customer_notes`;
CREATE TABLE `customer_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `note` text NOT NULL,
  `customer_notified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_notes_customer_id_foreign` (`customer_id`),
  CONSTRAINT `customer_notes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `customer_password_resets`
-- ----------------------------------------------
DROP TABLE IF EXISTS `customer_password_resets`;
CREATE TABLE `customer_password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `customer_password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `customer_social_accounts`
-- ----------------------------------------------
DROP TABLE IF EXISTS `customer_social_accounts`;
CREATE TABLE `customer_social_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `provider_name` varchar(255) DEFAULT NULL,
  `provider_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_social_accounts_provider_id_unique` (`provider_id`),
  KEY `customer_social_accounts_customer_id_foreign` (`customer_id`),
  CONSTRAINT `customer_social_accounts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `customers`
-- ----------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `password` varchar(255) DEFAULT NULL,
  `api_token` varchar(80) DEFAULT NULL,
  `customer_group_id` int(10) unsigned DEFAULT NULL,
  `channel_id` int(10) unsigned DEFAULT NULL,
  `subscribed_to_news_letter` tinyint(1) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_suspended` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `token` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_phone_unique` (`phone`),
  UNIQUE KEY `customers_api_token_unique` (`api_token`),
  UNIQUE KEY `customers_email_channel_unique` (`email`,`channel_id`),
  KEY `customers_customer_group_id_foreign` (`customer_group_id`),
  KEY `customers_channel_id_foreign` (`channel_id`),
  CONSTRAINT `customers_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_customer_group_id_foreign` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=895 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `customers` (5 rows)
INSERT INTO `customers` (`id`, `first_name`, `last_name`, `gender`, `date_of_birth`, `email`, `phone`, `image`, `status`, `password`, `api_token`, `customer_group_id`, `channel_id`, `subscribed_to_news_letter`, `is_verified`, `is_suspended`, `token`, `remember_token`, `created_at`, `updated_at`) VALUES
('283', 'رشيد', 'غالب', 'Male', '1990-01-15', 'rasheed.ghaleb@example.com', '0500000001', NULL, '1', '$2y$12$uNj51.WOOgj9PTVaU/GpkuKGkMbm5XL6uC.Bs7gZj22z83AFE7qFO', NULL, '2', '1', '0', '1', '0', NULL, NULL, '2026-07-19 00:22:06', '2026-07-19 00:22:06'),
('284', 'صلاح', 'منصور', 'Male', '1988-05-22', 'salah.mansour@example.com', '0500000002', NULL, '1', '$2y$12$ReW2RgCHY8d7caFrVvdqG.3jKmcIZCLMhgxYz4m8UzpklSjN0jPie', NULL, '2', '1', '0', '1', '0', NULL, NULL, '2026-07-19 00:22:07', '2026-07-19 00:22:07'),
('285', 'اكرم', 'الصبري', 'Male', '1992-09-10', 'akram.alsabri@example.com', '0500000003', NULL, '1', '$2y$12$curAMcl1.1skrErwfTWnf.hflv77dYnZmvPdhqnyeZ6Qis9lpJbAO', NULL, '2', '1', '0', '1', '0', NULL, NULL, '2026-07-19 00:22:08', '2026-07-19 00:22:08'),
('286', 'محمد', 'مارش', 'Male', '1995-03-30', 'mohammed.marsh@example.com', '0500000004', NULL, '1', '$2y$12$HboL08BgYBaZEA.WBzm1re6z4STFw3celcUm7m603naRywGXIxFNy', NULL, '2', '1', '0', '1', '0', NULL, NULL, '2026-07-19 00:22:08', '2026-07-19 00:22:08'),
('287', 'جمال', 'الشرعبي', 'Male', '1985-11-05', 'jamal.alsharabi@example.com', '0500000005', NULL, '1', '$2y$12$RaDIzuNvsT4zleRZUzo4aew1plV6.9q3IeHKpLCV3stI6L9RwsFyC', NULL, '2', '1', '0', '1', '0', NULL, NULL, '2026-07-19 00:22:09', '2026-07-19 00:22:09');

-- ----------------------------------------------
-- Table structure for `datagrid_saved_filters`
-- ----------------------------------------------
DROP TABLE IF EXISTS `datagrid_saved_filters`;
CREATE TABLE `datagrid_saved_filters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `src` varchar(255) NOT NULL,
  `applied` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`applied`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `datagrid_saved_filters_user_id_name_src_unique` (`user_id`,`name`,`src`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `domain_outbox_event_attempts`
-- ----------------------------------------------
DROP TABLE IF EXISTS `domain_outbox_event_attempts`;
CREATE TABLE `domain_outbox_event_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outbox_event_id` bigint(20) unsigned NOT NULL,
  `listener` varchar(255) NOT NULL,
  `attempt_number` int(11) NOT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `outbox_attempts_event_id_fk` (`outbox_event_id`),
  CONSTRAINT `outbox_attempts_event_id_fk` FOREIGN KEY (`outbox_event_id`) REFERENCES `domain_outbox_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `domain_outbox_events`
-- ----------------------------------------------
DROP TABLE IF EXISTS `domain_outbox_events`;
CREATE TABLE `domain_outbox_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_version` int(11) NOT NULL DEFAULT 1,
  `aggregate_type` varchar(255) DEFAULT NULL,
  `aggregate_id` varchar(255) DEFAULT NULL,
  `correlation_id` varchar(255) DEFAULT NULL,
  `causation_id` varchar(255) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain_outbox_events_event_id_unique` (`event_id`),
  KEY `outbox_event_name_status_idx` (`event_name`,`status`),
  KEY `outbox_aggregate_idx` (`aggregate_type`,`aggregate_id`),
  KEY `outbox_status_created_idx` (`status`,`created_at`),
  KEY `outbox_events_status_created_idx` (`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=173 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `downloadable_link_purchased`
-- ----------------------------------------------
DROP TABLE IF EXISTS `downloadable_link_purchased`;
CREATE TABLE `downloadable_link_purchased` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `download_bought` int(11) NOT NULL DEFAULT 0,
  `download_used` int(11) NOT NULL DEFAULT 0,
  `status` varchar(255) DEFAULT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned NOT NULL,
  `order_item_id` int(10) unsigned NOT NULL,
  `download_canceled` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `downloadable_link_purchased_customer_id_foreign` (`customer_id`),
  KEY `downloadable_link_purchased_order_id_foreign` (`order_id`),
  KEY `downloadable_link_purchased_order_item_id_foreign` (`order_item_id`),
  CONSTRAINT `downloadable_link_purchased_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `downloadable_link_purchased_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `downloadable_link_purchased_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `external_api_logs`
-- ----------------------------------------------
DROP TABLE IF EXISTS `external_api_logs`;
CREATE TABLE `external_api_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  `api_version` varchar(255) DEFAULT NULL,
  `provider_api_version` varchar(255) DEFAULT NULL,
  `request_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_payload`)),
  `response_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_payload`)),
  `status_code` int(10) unsigned DEFAULT NULL,
  `latency_ms` decimal(10,2) DEFAULT NULL,
  `correlation_id` varchar(255) DEFAULT NULL,
  `causation_id` varchar(255) DEFAULT NULL,
  `trace_id` varchar(255) DEFAULT NULL,
  `span_id` varchar(255) DEFAULT NULL,
  `procurement_session_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `provider_account_id` bigint(20) unsigned DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `external_api_logs_procurement_session_id_index` (`procurement_session_id`),
  KEY `external_api_logs_purchase_order_id_index` (`purchase_order_id`),
  KEY `external_api_logs_provider_account_id_index` (`provider_account_id`),
  KEY `external_api_logs_correlation_id_index` (`correlation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=224 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `external_health_checks`
-- ----------------------------------------------
DROP TABLE IF EXISTS `external_health_checks`;
CREATE TABLE `external_health_checks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL,
  `last_success` timestamp NULL DEFAULT NULL,
  `last_failure` timestamp NULL DEFAULT NULL,
  `failure_rate` double NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'healthy',
  `latency_ms` int(11) NOT NULL DEFAULT 0,
  `last_http_status` int(11) DEFAULT NULL,
  `last_error_code` varchar(255) DEFAULT NULL,
  `consecutive_failures` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `external_health_checks_provider_unique` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `external_inbox_events`
-- ----------------------------------------------
DROP TABLE IF EXISTS `external_inbox_events`;
CREATE TABLE `external_inbox_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `aggregate_type` varchar(255) DEFAULT NULL,
  `aggregate_id` varchar(255) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `signature` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `processing_started_at` timestamp NULL DEFAULT NULL,
  `processing_lock_id` varchar(255) DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `external_inbox_events_provider_event_id_unique` (`provider`,`event_id`),
  KEY `external_inbox_events_status_created_at_index` (`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `external_order_projections`
-- ----------------------------------------------
DROP TABLE IF EXISTS `external_order_projections`;
CREATE TABLE `external_order_projections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `external_order_id` varchar(255) NOT NULL,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `carrier` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `external_order_projections_external_order_id_unique` (`external_order_id`),
  KEY `external_order_projections_purchase_order_id_index` (`purchase_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `external_orders`
-- ----------------------------------------------
DROP TABLE IF EXISTS `external_orders`;
CREATE TABLE `external_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL,
  `provider_account_id` bigint(20) unsigned NOT NULL,
  `external_order_id` varchar(255) NOT NULL,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `procurement_session_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `raw_reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `external_orders_provider_account_id_index` (`provider_account_id`),
  KEY `external_orders_external_order_id_index` (`external_order_id`),
  KEY `external_orders_purchase_order_id_index` (`purchase_order_id`),
  KEY `external_orders_procurement_session_id_index` (`procurement_session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `external_payload_archives`
-- ----------------------------------------------
DROP TABLE IF EXISTS `external_payload_archives`;
CREATE TABLE `external_payload_archives` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_payload`)),
  `response_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_payload`)),
  `normalized_dto` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`normalized_dto`)),
  `request_hash` varchar(255) DEFAULT NULL,
  `response_hash` varchar(255) DEFAULT NULL,
  `provider_version` varchar(255) DEFAULT NULL,
  `contract_version` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `external_systems`
-- ----------------------------------------------
DROP TABLE IF EXISTS `external_systems`;
CREATE TABLE `external_systems` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `external_systems_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `external_systems` (1 rows)
INSERT INTO `external_systems` (`id`, `code`, `name`, `type`, `enabled`, `configuration`, `created_at`, `updated_at`) VALUES
('1', 'aliexpress', 'AliExpress Dropshipping System', 'supplier', '1', NULL, '2026-07-18 23:59:19', '2026-07-18 23:59:19');

-- ----------------------------------------------
-- Table structure for `external_variant_projections`
-- ----------------------------------------------
DROP TABLE IF EXISTS `external_variant_projections`;
CREATE TABLE `external_variant_projections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `variant_product_id` int(10) unsigned NOT NULL,
  `provider` varchar(255) NOT NULL DEFAULT 'aliexpress',
  `external_sku_id` varchar(255) NOT NULL,
  `external_product_id` varchar(255) NOT NULL,
  `external_variant_version` varchar(255) DEFAULT NULL,
  `projection_version` int(10) unsigned NOT NULL DEFAULT 1,
  `provider_updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ext_variant_provider_sku_unique` (`provider`,`external_sku_id`),
  UNIQUE KEY `ext_variant_prod_unique` (`variant_product_id`),
  KEY `ext_variant_parent_sku_idx` (`product_id`,`external_sku_id`),
  KEY `ext_projections_provider_prod_idx` (`provider`,`external_product_id`),
  CONSTRAINT `external_variant_projections_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `external_variant_projections_variant_product_id_foreign` FOREIGN KEY (`variant_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `failed_jobs`
-- ----------------------------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `financial_timeline`
-- ----------------------------------------------
DROP TABLE IF EXISTS `financial_timeline`;
CREATE TABLE `financial_timeline` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) NOT NULL,
  `order_id` int(10) unsigned NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `amount` decimal(12,4) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `correlation_id` varchar(255) DEFAULT NULL,
  `causation_id` varchar(255) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `outbox_event_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `financial_timeline_order_id_event_type_index` (`order_id`,`event_type`),
  CONSTRAINT `financial_timeline_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `fulfillment_approval_requests`
-- ----------------------------------------------
DROP TABLE IF EXISTS `fulfillment_approval_requests`;
CREATE TABLE `fulfillment_approval_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `requested_by` int(10) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `changes_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes_payload`)),
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `approved_by` int(10) unsigned DEFAULT NULL,
  `decision_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fulfillment_approval_requests_requested_by_foreign` (`requested_by`),
  KEY `fulfillment_approval_requests_approved_by_foreign` (`approved_by`),
  KEY `fulfillment_approval_requests_purchase_order_id_index` (`purchase_order_id`),
  KEY `fulfillment_approval_requests_status_index` (`status`),
  CONSTRAINT `fulfillment_approval_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fulfillment_approval_requests_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fulfillment_approval_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `fulfillment_attempts`
-- ----------------------------------------------
DROP TABLE IF EXISTS `fulfillment_attempts`;
CREATE TABLE `fulfillment_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `attempt_no` int(10) unsigned NOT NULL,
  `result` varchar(255) NOT NULL,
  `error_type` varchar(255) DEFAULT NULL,
  `provider_code` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fulfillment_attempts_purchase_order_id_index` (`purchase_order_id`),
  CONSTRAINT `fulfillment_attempts_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `fulfillment_audit_logs`
-- ----------------------------------------------
DROP TABLE IF EXISTS `fulfillment_audit_logs`;
CREATE TABLE `fulfillment_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `reason` text DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `changes_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes_payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fulfillment_audit_logs_purchase_order_id_index` (`purchase_order_id`),
  KEY `fulfillment_audit_logs_user_id_index` (`user_id`),
  CONSTRAINT `fulfillment_audit_logs_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fulfillment_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `fulfillment_provider_events`
-- ----------------------------------------------
DROP TABLE IF EXISTS `fulfillment_provider_events`;
CREATE TABLE `fulfillment_provider_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `provider` varchar(255) NOT NULL,
  `external_state` varchar(255) NOT NULL,
  `source_type` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `received_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fulfillment_provider_events_purchase_order_id_index` (`purchase_order_id`),
  KEY `fulfillment_provider_events_source_type_index` (`source_type`),
  CONSTRAINT `fulfillment_provider_events_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `gdpr_data_request`
-- ----------------------------------------------
DROP TABLE IF EXISTS `gdpr_data_request`;
CREATE TABLE `gdpr_data_request` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `message` varchar(500) NOT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gdpr_data_request_customer_id_foreign` (`customer_id`),
  CONSTRAINT `gdpr_data_request_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `import_batches`
-- ----------------------------------------------
DROP TABLE IF EXISTS `import_batches`;
CREATE TABLE `import_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `state` varchar(255) NOT NULL DEFAULT 'pending',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`summary`)),
  `import_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `import_batches_import_id_foreign` (`import_id`),
  CONSTRAINT `import_batches_import_id_foreign` FOREIGN KEY (`import_id`) REFERENCES `imports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `imports`
-- ----------------------------------------------
DROP TABLE IF EXISTS `imports`;
CREATE TABLE `imports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `state` varchar(255) NOT NULL DEFAULT 'pending',
  `process_in_queue` tinyint(1) NOT NULL DEFAULT 1,
  `type` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `validation_strategy` varchar(255) NOT NULL,
  `allowed_errors` int(11) NOT NULL DEFAULT 0,
  `processed_rows_count` int(11) NOT NULL DEFAULT 0,
  `invalid_rows_count` int(11) NOT NULL DEFAULT 0,
  `errors_count` int(11) NOT NULL DEFAULT 0,
  `errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`errors`)),
  `field_separator` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `images_directory_path` varchar(255) DEFAULT NULL,
  `error_file_path` varchar(255) DEFAULT NULL,
  `summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`summary`)),
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `inventory_sources`
-- ----------------------------------------------
DROP TABLE IF EXISTS `inventory_sources`;
CREATE TABLE `inventory_sources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `contact_name` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `contact_number` varchar(255) NOT NULL,
  `contact_fax` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `street` varchar(255) NOT NULL,
  `postcode` varchar(255) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT 0,
  `latitude` decimal(10,5) DEFAULT NULL,
  `longitude` decimal(10,5) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_sources_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `inventory_sources` (1 rows)
INSERT INTO `inventory_sources` (`id`, `code`, `name`, `description`, `contact_name`, `contact_email`, `contact_number`, `contact_fax`, `country`, `state`, `city`, `street`, `postcode`, `priority`, `latitude`, `longitude`, `status`, `created_at`, `updated_at`) VALUES
('1', 'default', 'افتراضي', NULL, 'افتراضي', 'warehouse@example.com', '1234567899', NULL, 'US', 'MI', 'Detroit', '12th Street', '48127', '0', NULL, NULL, '1', NULL, NULL);

-- ----------------------------------------------
-- Table structure for `invoice_items`
-- ----------------------------------------------
DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `total` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_total` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` decimal(12,4) DEFAULT 0.0000,
  `base_tax_amount` decimal(12,4) DEFAULT 0.0000,
  `discount_percent` decimal(12,4) DEFAULT 0.0000,
  `discount_amount` decimal(12,4) DEFAULT 0.0000,
  `base_discount_amount` decimal(12,4) DEFAULT 0.0000,
  `price_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_price_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `product_id` int(10) unsigned DEFAULT NULL,
  `product_type` varchar(255) DEFAULT NULL,
  `order_item_id` int(10) unsigned DEFAULT NULL,
  `invoice_id` int(10) unsigned DEFAULT NULL,
  `additional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  KEY `invoice_items_parent_id_foreign` (`parent_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `invoice_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `invoices`
-- ----------------------------------------------
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `increment_id` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `total_qty` int(11) DEFAULT NULL,
  `base_currency_code` varchar(255) DEFAULT NULL,
  `channel_currency_code` varchar(255) DEFAULT NULL,
  `order_currency_code` varchar(255) DEFAULT NULL,
  `sub_total` decimal(12,4) DEFAULT 0.0000,
  `base_sub_total` decimal(12,4) DEFAULT 0.0000,
  `grand_total` decimal(12,4) DEFAULT 0.0000,
  `base_grand_total` decimal(12,4) DEFAULT 0.0000,
  `shipping_amount` decimal(12,4) DEFAULT 0.0000,
  `base_shipping_amount` decimal(12,4) DEFAULT 0.0000,
  `tax_amount` decimal(12,4) DEFAULT 0.0000,
  `base_tax_amount` decimal(12,4) DEFAULT 0.0000,
  `discount_amount` decimal(12,4) DEFAULT 0.0000,
  `base_discount_amount` decimal(12,4) DEFAULT 0.0000,
  `shipping_tax_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_shipping_tax_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sub_total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_sub_total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `shipping_amount_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_shipping_amount_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `order_id` int(10) unsigned DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `reminders` int(11) NOT NULL DEFAULT 0,
  `next_reminder_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoices_order_id_foreign` (`order_id`),
  CONSTRAINT `invoices_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `job_batches`
-- ----------------------------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` text NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `jobs`
-- ----------------------------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `ledger_entries`
-- ----------------------------------------------
DROP TABLE IF EXISTS `ledger_entries`;
CREATE TABLE `ledger_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `correlation_id` varchar(255) DEFAULT NULL,
  `account_code` varchar(255) NOT NULL,
  `debit` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `credit` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ledger_entries_order_id_account_code_index` (`order_id`,`account_code`),
  KEY `ledger_entries_purchase_order_id_foreign` (`purchase_order_id`),
  CONSTRAINT `ledger_entries_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ledger_entries_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=286 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `locales`
-- ----------------------------------------------
DROP TABLE IF EXISTS `locales`;
CREATE TABLE `locales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `direction` enum('ltr','rtl') NOT NULL DEFAULT 'ltr',
  `logo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locales_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `locales` (1 rows)
INSERT INTO `locales` (`id`, `code`, `name`, `direction`, `logo_path`, `created_at`, `updated_at`) VALUES
('1', 'ar', 'العربية', 'rtl', NULL, NULL, NULL);

-- ----------------------------------------------
-- Table structure for `marketing_campaigns`
-- ----------------------------------------------
DROP TABLE IF EXISTS `marketing_campaigns`;
CREATE TABLE `marketing_campaigns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `type` varchar(255) NOT NULL,
  `mail_to` varchar(255) NOT NULL,
  `spooling` varchar(255) DEFAULT NULL,
  `channel_id` int(10) unsigned DEFAULT NULL,
  `customer_group_id` int(10) unsigned DEFAULT NULL,
  `marketing_template_id` int(10) unsigned DEFAULT NULL,
  `marketing_event_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `marketing_campaigns_channel_id_foreign` (`channel_id`),
  KEY `marketing_campaigns_customer_group_id_foreign` (`customer_group_id`),
  KEY `marketing_campaigns_marketing_template_id_foreign` (`marketing_template_id`),
  KEY `marketing_campaigns_marketing_event_id_foreign` (`marketing_event_id`),
  CONSTRAINT `marketing_campaigns_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketing_campaigns_customer_group_id_foreign` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketing_campaigns_marketing_event_id_foreign` FOREIGN KEY (`marketing_event_id`) REFERENCES `marketing_events` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketing_campaigns_marketing_template_id_foreign` FOREIGN KEY (`marketing_template_id`) REFERENCES `marketing_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `marketing_events`
-- ----------------------------------------------
DROP TABLE IF EXISTS `marketing_events`;
CREATE TABLE `marketing_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `marketing_events` (1 rows)
INSERT INTO `marketing_events` (`id`, `name`, `description`, `date`, `created_at`, `updated_at`) VALUES
('1', 'Birthday', 'Birthday', NULL, NULL, NULL);

-- ----------------------------------------------
-- Table structure for `marketing_templates`
-- ----------------------------------------------
DROP TABLE IF EXISTS `marketing_templates`;
CREATE TABLE `marketing_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `migrations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=215 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `migrations` (214 rows)
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
('1', '2014_10_12_000000_create_users_table', '1'),
('2', '2014_10_12_100000_create_admin_password_resets_table', '1'),
('3', '2014_10_12_100000_create_password_resets_table', '1'),
('4', '2018_06_12_111907_create_admins_table', '1'),
('5', '2018_06_13_055341_create_roles_table', '1'),
('6', '2018_07_05_130148_create_attributes_table', '1'),
('7', '2018_07_05_132854_create_attribute_translations_table', '1'),
('8', '2018_07_05_135150_create_attribute_families_table', '1'),
('9', '2018_07_05_135152_create_attribute_groups_table', '1'),
('10', '2018_07_05_140832_create_attribute_options_table', '1'),
('11', '2018_07_05_140856_create_attribute_option_translations_table', '1'),
('12', '2018_07_05_142820_create_categories_table', '1'),
('13', '2018_07_10_055143_create_locales_table', '1'),
('14', '2018_07_20_054426_create_countries_table', '1'),
('15', '2018_07_20_054502_create_currencies_table', '1'),
('16', '2018_07_20_054542_create_currency_exchange_rates_table', '1'),
('17', '2018_07_20_064849_create_channels_table', '1'),
('18', '2018_07_21_142836_create_category_translations_table', '1'),
('19', '2018_07_23_110040_create_inventory_sources_table', '1'),
('20', '2018_07_24_082635_create_customer_groups_table', '1'),
('21', '2018_07_24_082930_create_customers_table', '1'),
('22', '2018_07_27_065727_create_products_table', '1'),
('23', '2018_07_27_070011_create_product_attribute_values_table', '1'),
('24', '2018_07_27_092623_create_product_reviews_table', '1'),
('25', '2018_07_27_113941_create_product_images_table', '1'),
('26', '2018_07_27_113956_create_product_inventories_table', '1'),
('27', '2018_08_30_064755_create_tax_categories_table', '1'),
('28', '2018_08_30_065042_create_tax_rates_table', '1'),
('29', '2018_08_30_065840_create_tax_mappings_table', '1'),
('30', '2018_09_05_150444_create_cart_table', '1'),
('31', '2018_09_05_150915_create_cart_items_table', '1'),
('32', '2018_09_11_064045_customer_password_resets', '1'),
('33', '2018_09_19_093453_create_cart_payment', '1'),
('34', '2018_09_19_093508_create_cart_shipping_rates_table', '1'),
('35', '2018_09_20_060658_create_core_config_table', '1'),
('36', '2018_09_27_113154_create_orders_table', '1'),
('37', '2018_09_27_113207_create_order_items_table', '1'),
('38', '2018_09_27_115022_create_shipments_table', '1'),
('39', '2018_09_27_115029_create_shipment_items_table', '1'),
('40', '2018_09_27_115135_create_invoices_table', '1'),
('41', '2018_09_27_115144_create_invoice_items_table', '1'),
('42', '2018_10_01_095504_create_order_payment_table', '1'),
('43', '2018_10_03_025230_create_wishlist_table', '1'),
('44', '2018_10_12_101803_create_country_translations_table', '1'),
('45', '2018_10_12_101913_create_country_states_table', '1'),
('46', '2018_10_12_101923_create_country_state_translations_table', '1'),
('47', '2018_11_16_173504_create_subscribers_list_table', '1'),
('48', '2018_11_21_144411_create_cart_item_inventories_table', '1'),
('49', '2018_12_06_185202_create_product_flat_table', '1'),
('50', '2018_12_24_123812_create_channel_inventory_sources_table', '1'),
('51', '2018_12_26_165327_create_product_ordered_inventories_table', '1'),
('52', '2019_05_13_024321_create_cart_rules_table', '1'),
('53', '2019_05_13_024322_create_cart_rule_channels_table', '1'),
('54', '2019_05_13_024323_create_cart_rule_customer_groups_table', '1'),
('55', '2019_05_13_024324_create_cart_rule_translations_table', '1'),
('56', '2019_05_13_024325_create_cart_rule_customers_table', '1'),
('57', '2019_05_13_024326_create_cart_rule_coupons_table', '1'),
('58', '2019_05_13_024327_create_cart_rule_coupon_usage_table', '1'),
('59', '2019_06_17_180258_create_product_downloadable_samples_table', '1'),
('60', '2019_06_17_180314_create_product_downloadable_sample_translations_table', '1'),
('61', '2019_06_17_180325_create_product_downloadable_links_table', '1'),
('62', '2019_06_17_180346_create_product_downloadable_link_translations_table', '1'),
('63', '2019_06_21_202249_create_downloadable_link_purchased_table', '1'),
('64', '2019_07_02_180307_create_booking_products_table', '1'),
('65', '2019_07_05_154415_create_booking_product_default_slots_table', '1'),
('66', '2019_07_05_154429_create_booking_product_appointment_slots_table', '1'),
('67', '2019_07_05_154440_create_booking_product_event_tickets_table', '1'),
('68', '2019_07_05_154451_create_booking_product_rental_slots_table', '1'),
('69', '2019_07_05_154502_create_booking_product_table_slots_table', '1'),
('70', '2019_07_30_153530_create_cms_pages_table', '1'),
('71', '2019_07_31_143339_create_category_filterable_attributes_table', '1'),
('72', '2019_08_02_105320_create_product_grouped_products_table', '1'),
('73', '2019_08_20_170510_create_product_bundle_options_table', '1'),
('74', '2019_08_20_170520_create_product_bundle_option_translations_table', '1'),
('75', '2019_08_20_170528_create_product_bundle_option_products_table', '1'),
('76', '2019_09_11_184511_create_refunds_table', '1'),
('77', '2019_09_11_184519_create_refund_items_table', '1'),
('78', '2019_12_03_184613_create_catalog_rules_table', '1'),
('79', '2019_12_03_184651_create_catalog_rule_channels_table', '1'),
('80', '2019_12_03_184732_create_catalog_rule_customer_groups_table', '1'),
('81', '2019_12_06_101110_create_catalog_rule_products_table', '1'),
('82', '2019_12_06_110507_create_catalog_rule_product_prices_table', '1'),
('83', '2019_12_14_000001_create_personal_access_tokens_table', '1'),
('84', '2020_01_14_191854_create_cms_page_translations_table', '1'),
('85', '2020_01_15_130209_create_cms_page_channels_table', '1'),
('86', '2020_02_18_165639_create_bookings_table', '1'),
('87', '2020_02_21_121201_create_booking_product_event_ticket_translations_table', '1'),
('88', '2020_04_16_185147_add_table_addresses', '1'),
('89', '2020_05_06_171638_create_order_comments_table', '1'),
('90', '2020_05_21_171500_create_product_customer_group_prices_table', '1'),
('91', '2020_06_25_162154_create_customer_social_accounts_table', '1'),
('92', '2020_08_07_174804_create_gdpr_data_request_table', '1'),
('93', '2020_11_19_112228_create_product_videos_table', '1'),
('94', '2020_11_26_141455_create_marketing_templates_table', '1'),
('95', '2020_11_26_150534_create_marketing_events_table', '1'),
('96', '2020_11_26_150644_create_marketing_campaigns_table', '1'),
('97', '2020_12_21_000200_create_channel_translations_table', '1'),
('98', '2020_12_27_121950_create_jobs_table', '1'),
('99', '2021_03_11_212124_create_order_transactions_table', '1'),
('100', '2021_04_07_132010_create_product_review_images_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
('101', '2021_12_15_104544_notifications', '1'),
('102', '2022_03_15_160510_create_failed_jobs_table', '1'),
('103', '2022_04_01_094622_create_sitemaps_table', '1'),
('104', '2022_10_03_144232_create_product_price_indices_table', '1'),
('105', '2022_10_04_144444_create_job_batches_table', '1'),
('106', '2022_10_08_134150_create_product_inventory_indices_table', '1'),
('107', '2023_05_26_213105_create_wishlist_items_table', '1'),
('108', '2023_05_26_213120_create_compare_items_table', '1'),
('109', '2023_06_27_163529_rename_product_review_images_to_product_review_attachments', '1'),
('110', '2023_07_06_140013_add_logo_path_column_to_locales', '1'),
('111', '2023_07_10_184256_create_theme_customizations_table', '1'),
('112', '2023_07_12_181722_remove_home_page_and_footer_content_column_from_channel_translations_table', '1'),
('113', '2023_07_20_185324_add_column_column_in_attribute_groups_table', '1'),
('114', '2023_07_25_145943_add_regex_column_in_attributes_table', '1'),
('115', '2023_07_25_165945_drop_notes_column_from_customers_table', '1'),
('116', '2023_07_25_171058_create_customer_notes_table', '1'),
('117', '2023_07_31_125232_rename_image_and_category_banner_columns_from_categories_table', '1'),
('118', '2023_09_15_170053_create_theme_customization_translations_table', '1'),
('119', '2023_09_20_102031_add_default_value_column_in_attributes_table', '1'),
('120', '2023_09_20_102635_add_inventories_group_in_attribute_groups_table', '1'),
('121', '2023_09_26_155709_add_columns_to_currencies', '1'),
('122', '2023_10_12_090446_add_tax_category_id_column_in_order_items_table', '1'),
('123', '2023_11_08_054614_add_code_column_in_attribute_groups_table', '1'),
('124', '2023_11_08_140116_create_search_terms_table', '1'),
('125', '2023_11_09_162805_create_url_rewrites_table', '1'),
('126', '2023_11_17_150401_create_search_synonyms_table', '1'),
('127', '2023_12_11_054614_add_channel_id_column_in_product_price_indices_table', '1'),
('128', '2024_01_11_154640_create_imports_table', '1'),
('129', '2024_01_11_154741_create_import_batches_table', '1'),
('130', '2024_01_19_170350_add_unique_id_column_in_product_attribute_values_table', '1'),
('131', '2024_01_19_170350_add_unique_id_column_in_product_customer_group_prices_table', '1'),
('132', '2024_01_22_170814_add_unique_index_in_mapping_tables', '1'),
('133', '2024_02_26_153000_add_columns_to_addresses_table', '1'),
('134', '2024_03_07_193421_rename_address1_column_in_addresses_table', '1'),
('135', '2024_04_16_144400_add_cart_id_column_in_cart_shipping_rates_table', '1'),
('136', '2024_04_19_102939_add_incl_tax_columns_in_orders_table', '1'),
('137', '2024_04_19_135405_add_incl_tax_columns_in_cart_items_table', '1'),
('138', '2024_04_19_144641_add_incl_tax_columns_in_order_items_table', '1'),
('139', '2024_04_23_133154_add_incl_tax_columns_in_cart_table', '1'),
('140', '2024_04_23_150945_add_incl_tax_columns_in_cart_shipping_rates_table', '1'),
('141', '2024_04_24_102939_add_incl_tax_columns_in_invoices_table', '1'),
('142', '2024_04_24_102939_add_incl_tax_columns_in_refunds_table', '1'),
('143', '2024_04_24_144641_add_incl_tax_columns_in_invoice_items_table', '1'),
('144', '2024_04_24_144641_add_incl_tax_columns_in_refund_items_table', '1'),
('145', '2024_04_24_144641_add_incl_tax_columns_in_shipment_items_table', '1'),
('146', '2024_05_10_152848_create_saved_filters_table', '1'),
('147', '2024_06_03_174128_create_product_channels_table', '1'),
('148', '2024_06_04_130527_add_channel_id_column_in_customers_table', '1'),
('149', '2024_06_04_130600_make_email_unique_per_channel', '1'),
('150', '2024_06_13_184426_add_theme_column_into_theme_customizations_table', '1'),
('151', '2024_07_17_172645_add_additional_column_to_sitemaps_table', '1'),
('152', '2024_10_11_135010_create_product_customizable_options_table', '1'),
('153', '2024_10_11_135110_create_product_customizable_option_translations_table', '1'),
('154', '2024_10_11_135228_create_product_customizable_option_prices_table', '1'),
('155', '2025_01_01_000001_create_purchase_orders_table', '1'),
('156', '2025_01_01_000002_create_purchase_order_items_table', '1'),
('157', '2025_01_01_000003_create_fulfillment_attempts_table', '1'),
('158', '2025_01_01_000004_create_fulfillment_provider_events_table', '1'),
('159', '2025_01_01_000005_create_fulfillment_audit_logs_table', '1'),
('160', '2025_01_01_000006_create_fulfillment_approval_requests_table', '1'),
('161', '2025_01_01_000007_create_order_allocations_table', '1'),
('162', '2025_01_01_000008_create_allocation_logs_table', '1'),
('163', '2025_01_01_000009_create_processed_events_table', '1'),
('164', '2025_01_01_000010_create_financial_timeline_table', '1'),
('165', '2025_01_01_000011_create_ledger_entries_table', '1'),
('166', '2025_01_01_000012_alter_processed_events_table', '1'),
('167', '2025_01_01_000013_create_domain_outbox_events_table', '1'),
('168', '2025_01_01_000014_create_external_systems_table', '1'),
('169', '2025_01_01_000015_create_external_inbox_events_table', '1'),
('170', '2025_01_01_000016_create_external_health_checks_table', '1'),
('171', '2025_01_01_000017_add_trace_columns_to_financial_timeline_table', '1'),
('172', '2025_01_01_000025_create_order_processes_table', '1'),
('173', '2025_05_07_121250_update_total_weight_columns_in_shipments_and_weight_shipment_items_tables', '1'),
('174', '2025_09_05_000100_add_indexes_to_channels_tables', '1'),
('175', '2025_09_05_000200_add_indexes_to_product_relation_tables', '1'),
('176', '2025_09_05_000300_add_indexes_to_product_media_and_attributes', '1'),
('177', '2025_09_05_000400_add_indexes_to_attributes_and_product_types', '1'),
('178', '2025_09_05_000500_add_indexes_to_product_grouped_products_and_product_bundle_option_products', '1'),
('179', '2025_09_05_000500_add_indexes_to_url_rewrites_and_visits', '1'),
('180', '2025_09_11_140301_add_two_factor_to_admins', '1'),
('181', '2025_11_14_173810_create_rma_statuses_table', '1'),
('182', '2025_11_14_173812_create_rma_table', '1'),
('183', '2025_11_14_173906_create_rma_reasons_table', '1'),
('184', '2025_11_14_173959_create_rma_items_table', '1'),
('185', '2025_11_14_174030_create_rma_images_table', '1'),
('186', '2025_11_14_174059_create_rma_messages_table', '1'),
('187', '2025_11_14_174134_create_rma_reason_resolutions_table', '1'),
('188', '2025_11_14_174205_create_rma_rules_table', '1'),
('189', '2025_11_14_174355_create_rma_custom_fields_table', '1'),
('190', '2025_11_14_174426_create_rma_custom_field_options_table', '1'),
('191', '2025_11_14_174509_create_rma_additional_fields_table', '1'),
('192', '2026_02_03_151924_create_sessions_table', '1'),
('193', '2026_02_11_095547_add_rma_return_period_to_order_items_table', '1'),
('194', '2026_03_11_113926_create_agent_conversations_table', '1'),
('195', '2026_04_09_120000_change_tax_category_id_fk_on_cart_items_to_null_on_delete', '1'),
('196', '2026_04_09_120100_change_tax_category_id_fk_on_order_items_to_null_on_delete', '1'),
('197', '2026_06_08_000000_create_aliexpress_tokens_table', '1'),
('198', '2026_06_09_000000_create_aliexpress_product_imports_table', '1'),
('199', '2026_06_10_000000_add_aliexpress_category_id_to_categories_table', '1'),
('200', '2026_06_11_214721_create_aliexpress_settings_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
('201', '2026_06_11_232550_add_shipping_to_aliexpress_product_imports', '1'),
('202', '2026_06_11_232550_add_shipping_to_aliexpress_settings', '1'),
('203', '2026_07_01_012943_add_sync_scheduling_to_aliexpress_settings_table', '1'),
('204', '2026_07_10_053340_add_aliexpress_sku_id_and_review_attributes', '1'),
('205', '2026_07_10_053409_create_external_variant_projections_table', '1'),
('206', '2026_07_10_053435_add_variant_ids_and_snapshot_to_order_allocations', '1'),
('207', '2026_07_10_053501_add_buffer_settings_to_aliexpress_settings', '1'),
('208', '2026_07_10_053528_add_hash_and_version_to_aliexpress_product_imports', '1'),
('209', '2026_07_10_060000_create_sync_runs_table', '1'),
('210', '2026_07_10_060050_create_provider_sync_states_table', '1'),
('211', '2026_07_10_060100_create_sync_benchmarks_table', '1'),
('212', '2026_07_10_070000_add_indexes_to_sync_tables', '1'),
('213', '2026_07_10_080000_create_procurement_platform_tables', '1'),
('214', '2026_07_10_090000_add_fields_to_ledger_entries_table', '1');

-- ----------------------------------------------
-- Table structure for `notifications`
-- ----------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `read` tinyint(1) NOT NULL DEFAULT 0,
  `order_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_order_id_foreign` (`order_id`),
  CONSTRAINT `notifications_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `order_allocations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `order_allocations`;
CREATE TABLE `order_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `order_item_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `variant_product_id` int(10) unsigned DEFAULT NULL,
  `allocation_type` varchar(255) NOT NULL,
  `source_code` varchar(255) NOT NULL,
  `supplier_signature` varchar(255) DEFAULT NULL,
  `reserved_qty` int(10) unsigned NOT NULL DEFAULT 0,
  `fulfilled_qty` int(10) unsigned NOT NULL DEFAULT 0,
  `canceled_qty` int(10) unsigned NOT NULL DEFAULT 0,
  `state` varchar(255) NOT NULL DEFAULT 'reserved',
  `supplier_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supplier_snapshot`)),
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_allocations_order_id_state_index` (`order_id`,`state`),
  KEY `order_allocations_order_item_id_index` (`order_item_id`),
  KEY `order_allocations_product_id_foreign` (`product_id`),
  KEY `alloc_variant_state_idx` (`variant_product_id`,`state`),
  CONSTRAINT `order_allocations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_allocations_variant_product_id_foreign` FOREIGN KEY (`variant_product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12417 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `order_comments`
-- ----------------------------------------------
DROP TABLE IF EXISTS `order_comments`;
CREATE TABLE `order_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned DEFAULT NULL,
  `comment` text NOT NULL,
  `customer_notified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_comments_order_id_foreign` (`order_id`),
  CONSTRAINT `order_comments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `order_items`
-- ----------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `coupon_code` varchar(255) DEFAULT NULL,
  `weight` decimal(12,4) DEFAULT 0.0000,
  `total_weight` decimal(12,4) DEFAULT 0.0000,
  `qty_ordered` int(11) DEFAULT 0,
  `qty_shipped` int(11) DEFAULT 0,
  `qty_invoiced` int(11) DEFAULT 0,
  `qty_canceled` int(11) DEFAULT 0,
  `qty_refunded` int(11) DEFAULT 0,
  `price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `total` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_total` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `total_invoiced` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_total_invoiced` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `amount_refunded` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_amount_refunded` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `discount_percent` decimal(12,4) DEFAULT 0.0000,
  `discount_amount` decimal(12,4) DEFAULT 0.0000,
  `base_discount_amount` decimal(12,4) DEFAULT 0.0000,
  `discount_invoiced` decimal(12,4) DEFAULT 0.0000,
  `base_discount_invoiced` decimal(12,4) DEFAULT 0.0000,
  `discount_refunded` decimal(12,4) DEFAULT 0.0000,
  `base_discount_refunded` decimal(12,4) DEFAULT 0.0000,
  `tax_percent` decimal(12,4) DEFAULT 0.0000,
  `tax_amount` decimal(12,4) DEFAULT 0.0000,
  `base_tax_amount` decimal(12,4) DEFAULT 0.0000,
  `tax_amount_invoiced` decimal(12,4) DEFAULT 0.0000,
  `base_tax_amount_invoiced` decimal(12,4) DEFAULT 0.0000,
  `tax_amount_refunded` decimal(12,4) DEFAULT 0.0000,
  `base_tax_amount_refunded` decimal(12,4) DEFAULT 0.0000,
  `price_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_price_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `product_id` int(10) unsigned DEFAULT NULL,
  `product_type` varchar(255) DEFAULT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  `tax_category_id` int(10) unsigned DEFAULT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `additional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional`)),
  `rma_return_period` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_parent_id_foreign` (`parent_id`),
  KEY `order_items_tax_category_id_foreign` (`tax_category_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_tax_category_id_foreign` FOREIGN KEY (`tax_category_id`) REFERENCES `tax_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `order_payment`
-- ----------------------------------------------
DROP TABLE IF EXISTS `order_payment`;
CREATE TABLE `order_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned DEFAULT NULL,
  `method` varchar(255) NOT NULL,
  `method_title` varchar(255) DEFAULT NULL,
  `additional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_payment_order_id_foreign` (`order_id`),
  CONSTRAINT `order_payment_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `order_processes`
-- ----------------------------------------------
DROP TABLE IF EXISTS `order_processes`;
CREATE TABLE `order_processes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `payment_mode` varchar(255) NOT NULL,
  `lifecycle_state` varchar(255) NOT NULL DEFAULT 'waiting_payment',
  `accepted_at` timestamp NULL DEFAULT NULL,
  `accepted_by` varchar(255) DEFAULT NULL,
  `blocked_reason` varchar(255) DEFAULT NULL,
  `correlation_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_processes_order_id_unique` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `order_transactions`
-- ----------------------------------------------
DROP TABLE IF EXISTS `order_transactions`;
CREATE TABLE `order_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(255) NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `amount` decimal(12,4) DEFAULT 0.0000,
  `payment_method` varchar(255) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `invoice_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_transactions_order_id_foreign` (`order_id`),
  CONSTRAINT `order_transactions_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `orders`
-- ----------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `increment_id` varchar(255) NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  `channel_name` varchar(255) DEFAULT NULL,
  `is_guest` tinyint(1) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_first_name` varchar(255) DEFAULT NULL,
  `customer_last_name` varchar(255) DEFAULT NULL,
  `shipping_method` varchar(255) DEFAULT NULL,
  `shipping_title` varchar(255) DEFAULT NULL,
  `shipping_description` varchar(255) DEFAULT NULL,
  `coupon_code` varchar(255) DEFAULT NULL,
  `is_gift` tinyint(1) NOT NULL DEFAULT 0,
  `total_item_count` int(11) DEFAULT NULL,
  `total_qty_ordered` int(11) DEFAULT NULL,
  `base_currency_code` varchar(255) DEFAULT NULL,
  `channel_currency_code` varchar(255) DEFAULT NULL,
  `order_currency_code` varchar(255) DEFAULT NULL,
  `grand_total` decimal(12,4) DEFAULT 0.0000,
  `base_grand_total` decimal(12,4) DEFAULT 0.0000,
  `grand_total_invoiced` decimal(12,4) DEFAULT 0.0000,
  `base_grand_total_invoiced` decimal(12,4) DEFAULT 0.0000,
  `grand_total_refunded` decimal(12,4) DEFAULT 0.0000,
  `base_grand_total_refunded` decimal(12,4) DEFAULT 0.0000,
  `sub_total` decimal(12,4) DEFAULT 0.0000,
  `base_sub_total` decimal(12,4) DEFAULT 0.0000,
  `sub_total_invoiced` decimal(12,4) DEFAULT 0.0000,
  `base_sub_total_invoiced` decimal(12,4) DEFAULT 0.0000,
  `sub_total_refunded` decimal(12,4) DEFAULT 0.0000,
  `base_sub_total_refunded` decimal(12,4) DEFAULT 0.0000,
  `discount_percent` decimal(12,4) DEFAULT 0.0000,
  `discount_amount` decimal(12,4) DEFAULT 0.0000,
  `base_discount_amount` decimal(12,4) DEFAULT 0.0000,
  `discount_invoiced` decimal(12,4) DEFAULT 0.0000,
  `base_discount_invoiced` decimal(12,4) DEFAULT 0.0000,
  `discount_refunded` decimal(12,4) DEFAULT 0.0000,
  `base_discount_refunded` decimal(12,4) DEFAULT 0.0000,
  `tax_amount` decimal(12,4) DEFAULT 0.0000,
  `base_tax_amount` decimal(12,4) DEFAULT 0.0000,
  `tax_amount_invoiced` decimal(12,4) DEFAULT 0.0000,
  `base_tax_amount_invoiced` decimal(12,4) DEFAULT 0.0000,
  `tax_amount_refunded` decimal(12,4) DEFAULT 0.0000,
  `base_tax_amount_refunded` decimal(12,4) DEFAULT 0.0000,
  `shipping_amount` decimal(12,4) DEFAULT 0.0000,
  `base_shipping_amount` decimal(12,4) DEFAULT 0.0000,
  `shipping_invoiced` decimal(12,4) DEFAULT 0.0000,
  `base_shipping_invoiced` decimal(12,4) DEFAULT 0.0000,
  `shipping_refunded` decimal(12,4) DEFAULT 0.0000,
  `base_shipping_refunded` decimal(12,4) DEFAULT 0.0000,
  `shipping_discount_amount` decimal(12,4) DEFAULT 0.0000,
  `base_shipping_discount_amount` decimal(12,4) DEFAULT 0.0000,
  `shipping_tax_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_shipping_tax_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `shipping_tax_refunded` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_shipping_tax_refunded` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sub_total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_sub_total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `shipping_amount_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_shipping_amount_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `customer_type` varchar(255) DEFAULT NULL,
  `channel_id` int(10) unsigned DEFAULT NULL,
  `channel_type` varchar(255) DEFAULT NULL,
  `cart_id` int(11) DEFAULT NULL,
  `applied_cart_rule_ids` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_increment_id_unique` (`increment_id`),
  KEY `orders_customer_id_foreign` (`customer_id`),
  KEY `orders_channel_id_foreign` (`channel_id`),
  CONSTRAINT `orders_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=235 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `outgoing_requests`
-- ----------------------------------------------
DROP TABLE IF EXISTS `outgoing_requests`;
CREATE TABLE `outgoing_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_hash` varchar(255) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `idempotency_key` varchar(255) NOT NULL,
  `response_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_payload`)),
  `response_hash` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `outgoing_requests_request_hash_index` (`request_hash`),
  KEY `outgoing_requests_idempotency_key_index` (`idempotency_key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `password_resets`
-- ----------------------------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `personal_access_tokens`
-- ----------------------------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `processed_events`
-- ----------------------------------------------
DROP TABLE IF EXISTS `processed_events`;
CREATE TABLE `processed_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL DEFAULT 'aliexpress',
  `event_id` varchar(255) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `processed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `processed_events_provider_event_unique` (`provider`,`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `procurement_aggregates`
-- ----------------------------------------------
DROP TABLE IF EXISTS `procurement_aggregates`;
CREATE TABLE `procurement_aggregates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `procurement_aggregates_purchase_order_id_index` (`purchase_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `procurement_commands`
-- ----------------------------------------------
DROP TABLE IF EXISTS `procurement_commands`;
CREATE TABLE `procurement_commands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `command_type` varchar(255) NOT NULL,
  `idempotency_key` varchar(255) NOT NULL,
  `procurement_session_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `procurement_commands_idempotency_key_unique` (`idempotency_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `procurement_dashboard_projections`
-- ----------------------------------------------
DROP TABLE IF EXISTS `procurement_dashboard_projections`;
CREATE TABLE `procurement_dashboard_projections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `supplier_code` varchar(255) NOT NULL,
  `current_step` varchar(255) NOT NULL,
  `current_status` varchar(255) NOT NULL,
  `progress_percent` int(10) unsigned NOT NULL DEFAULT 0,
  `tracking_number` varchar(255) DEFAULT NULL,
  `retries_count` int(10) unsigned NOT NULL DEFAULT 0,
  `health_status` varchar(255) NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estimated_delivery_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `procurement_dashboard_projections_purchase_order_id_unique` (`purchase_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `procurement_dead_letters`
-- ----------------------------------------------
DROP TABLE IF EXISTS `procurement_dead_letters`;
CREATE TABLE `procurement_dead_letters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procurement_session_id` bigint(20) unsigned NOT NULL,
  `reason` text DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `retries` int(10) unsigned NOT NULL DEFAULT 0,
  `stack` text DEFAULT NULL,
  `correlation_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `procurement_dead_letters_procurement_session_id_index` (`procurement_session_id`),
  KEY `procurement_dead_letters_correlation_id_index` (`correlation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `procurement_inbox_events`
-- ----------------------------------------------
DROP TABLE IF EXISTS `procurement_inbox_events`;
CREATE TABLE `procurement_inbox_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `external_order_id` varchar(255) DEFAULT NULL,
  `payload_hash` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `received_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inbox_provider_event_unique` (`provider`,`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `procurement_metrics`
-- ----------------------------------------------
DROP TABLE IF EXISTS `procurement_metrics`;
CREATE TABLE `procurement_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL,
  `total_orders` int(10) unsigned NOT NULL DEFAULT 0,
  `success_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `average_submit_time` decimal(10,2) NOT NULL DEFAULT 0.00,
  `average_shipping_time` decimal(10,2) NOT NULL DEFAULT 0.00,
  `failure_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `last_failure_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `procurement_sagas`
-- ----------------------------------------------
DROP TABLE IF EXISTS `procurement_sagas`;
CREATE TABLE `procurement_sagas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `state` varchar(255) NOT NULL,
  `correlation_id` varchar(255) NOT NULL,
  `causation_id` varchar(255) NOT NULL,
  `trace_id` varchar(255) DEFAULT NULL,
  `span_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `procurement_sagas_purchase_order_id_index` (`purchase_order_id`),
  KEY `procurement_sagas_correlation_id_index` (`correlation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `procurement_sessions`
-- ----------------------------------------------
DROP TABLE IF EXISTS `procurement_sessions`;
CREATE TABLE `procurement_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procurement_aggregate_id` bigint(20) unsigned DEFAULT NULL,
  `order_allocation_id` bigint(20) unsigned NOT NULL,
  `provider_account_id` bigint(20) unsigned DEFAULT NULL,
  `external_payload_archive_id` bigint(20) unsigned DEFAULT NULL,
  `state` varchar(255) NOT NULL DEFAULT 'CREATED',
  `contract_version` varchar(255) DEFAULT NULL,
  `policy_version` varchar(255) DEFAULT NULL,
  `policy_hash` varchar(255) DEFAULT NULL,
  `policy_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`policy_snapshot`)),
  `supplier_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supplier_snapshot`)),
  `shipping_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`shipping_snapshot`)),
  `price_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`price_snapshot`)),
  `snapshot_hash` varchar(255) DEFAULT NULL,
  `snapshot_finalized_at` timestamp NULL DEFAULT NULL,
  `metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metrics`)),
  `error_message` text DEFAULT NULL,
  `correlation_id` varchar(255) NOT NULL,
  `causation_id` varchar(255) NOT NULL,
  `trace_id` varchar(255) DEFAULT NULL,
  `span_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `procurement_sessions_procurement_aggregate_id_index` (`procurement_aggregate_id`),
  KEY `procurement_sessions_order_allocation_id_index` (`order_allocation_id`),
  KEY `procurement_sessions_provider_account_id_index` (`provider_account_id`),
  KEY `procurement_sessions_external_payload_archive_id_index` (`external_payload_archive_id`),
  KEY `procurement_sessions_correlation_id_index` (`correlation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `procurement_timelines`
-- ----------------------------------------------
DROP TABLE IF EXISTS `procurement_timelines`;
CREATE TABLE `procurement_timelines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procurement_session_id` bigint(20) unsigned NOT NULL,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `stage` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `correlation_id` varchar(255) NOT NULL,
  `causation_id` varchar(255) NOT NULL,
  `trace_id` varchar(255) DEFAULT NULL,
  `span_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `procurement_timelines_procurement_session_id_index` (`procurement_session_id`),
  KEY `procurement_timelines_purchase_order_id_index` (`purchase_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_attribute_values`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_attribute_values`;
CREATE TABLE `product_attribute_values` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(255) DEFAULT NULL,
  `channel` varchar(255) DEFAULT NULL,
  `text_value` text DEFAULT NULL,
  `boolean_value` tinyint(1) DEFAULT NULL,
  `integer_value` int(11) DEFAULT NULL,
  `float_value` decimal(12,4) DEFAULT NULL,
  `datetime_value` datetime DEFAULT NULL,
  `date_value` date DEFAULT NULL,
  `json_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`json_value`)),
  `product_id` int(10) unsigned NOT NULL,
  `attribute_id` int(10) unsigned NOT NULL,
  `unique_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chanel_locale_attribute_value_index_unique` (`channel`,`locale`,`attribute_id`,`product_id`),
  UNIQUE KEY `product_attribute_values_unique_id_unique` (`unique_id`),
  KEY `product_attribute_values_attribute_id_foreign` (`attribute_id`),
  KEY `prod_attr_product_id_idx` (`product_id`),
  CONSTRAINT `product_attribute_values_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_attribute_values_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=73818 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_bundle_option_products`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_bundle_option_products`;
CREATE TABLE `product_bundle_option_products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `product_bundle_option_id` int(10) unsigned NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `is_user_defined` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bundle_option_products_product_id_bundle_option_id_unique` (`product_id`,`product_bundle_option_id`),
  KEY `pbop_option_id_idx` (`product_bundle_option_id`),
  CONSTRAINT `product_bundle_option_id_foreign` FOREIGN KEY (`product_bundle_option_id`) REFERENCES `product_bundle_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_bundle_option_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=461 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_bundle_option_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_bundle_option_translations`;
CREATE TABLE `product_bundle_option_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(255) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `product_bundle_option_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_bundle_option_translations_option_id_locale_unique` (`product_bundle_option_id`,`locale`),
  UNIQUE KEY `bundle_option_translations_locale_label_bundle_option_id_unique` (`locale`,`label`,`product_bundle_option_id`),
  CONSTRAINT `product_bundle_option_translations_option_id_foreign` FOREIGN KEY (`product_bundle_option_id`) REFERENCES `product_bundle_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=461 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_bundle_options`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_bundle_options`;
CREATE TABLE `product_bundle_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_bundle_options_product_id_foreign` (`product_id`),
  CONSTRAINT `product_bundle_options_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=461 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_categories`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_categories`;
CREATE TABLE `product_categories` (
  `product_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `product_categories_product_id_category_id_unique` (`product_id`,`category_id`),
  KEY `product_categories_category_id_foreign` (`category_id`),
  CONSTRAINT `product_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_categories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_channels`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_channels`;
CREATE TABLE `product_channels` (
  `product_id` int(10) unsigned NOT NULL,
  `channel_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `product_channels_product_id_channel_id_unique` (`product_id`,`channel_id`),
  KEY `product_channels_channel_id_foreign` (`channel_id`),
  KEY `pc_product_id_channel_id_idx` (`product_id`,`channel_id`),
  CONSTRAINT `product_channels_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_channels_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_cross_sells`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_cross_sells`;
CREATE TABLE `product_cross_sells` (
  `parent_id` int(10) unsigned NOT NULL,
  `child_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `product_cross_sells_parent_id_child_id_unique` (`parent_id`,`child_id`),
  KEY `product_cross_sells_child_id_foreign` (`child_id`),
  CONSTRAINT `product_cross_sells_child_id_foreign` FOREIGN KEY (`child_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_cross_sells_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_customer_group_prices`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_customer_group_prices`;
CREATE TABLE `product_customer_group_prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `qty` int(11) NOT NULL DEFAULT 0,
  `value_type` varchar(255) NOT NULL,
  `value` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `product_id` int(10) unsigned NOT NULL,
  `customer_group_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `unique_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_customer_group_prices_unique_id_unique` (`unique_id`),
  KEY `product_customer_group_prices_product_id_foreign` (`product_id`),
  KEY `product_customer_group_prices_customer_group_id_foreign` (`customer_group_id`),
  CONSTRAINT `product_customer_group_prices_customer_group_id_foreign` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_customer_group_prices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_customizable_option_prices`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_customizable_option_prices`;
CREATE TABLE `product_customizable_option_prices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` text DEFAULT NULL,
  `price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `product_customizable_option_id` int(10) unsigned NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `pcop_product_customizable_option_id_foreign` (`product_customizable_option_id`),
  CONSTRAINT `pcop_product_customizable_option_id_foreign` FOREIGN KEY (`product_customizable_option_id`) REFERENCES `product_customizable_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_customizable_option_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_customizable_option_translations`;
CREATE TABLE `product_customizable_option_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(255) NOT NULL,
  `label` text DEFAULT NULL,
  `product_customizable_option_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_customizable_option_id_locale_unique` (`product_customizable_option_id`,`locale`),
  CONSTRAINT `pcot_product_customizable_option_id_foreign` FOREIGN KEY (`product_customizable_option_id`) REFERENCES `product_customizable_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_customizable_options`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_customizable_options`;
CREATE TABLE `product_customizable_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `max_characters` text DEFAULT NULL,
  `supported_file_extensions` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_customizable_options_product_id_foreign` (`product_id`),
  CONSTRAINT `product_customizable_options_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_downloadable_link_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_downloadable_link_translations`;
CREATE TABLE `product_downloadable_link_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_downloadable_link_id` int(10) unsigned NOT NULL,
  `locale` varchar(255) NOT NULL,
  `title` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `link_translations_link_id_foreign` (`product_downloadable_link_id`),
  CONSTRAINT `link_translations_link_id_foreign` FOREIGN KEY (`product_downloadable_link_id`) REFERENCES `product_downloadable_links` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_downloadable_links`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_downloadable_links`;
CREATE TABLE `product_downloadable_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sample_url` varchar(255) DEFAULT NULL,
  `sample_file` varchar(255) DEFAULT NULL,
  `sample_file_name` varchar(255) DEFAULT NULL,
  `sample_type` varchar(255) DEFAULT NULL,
  `downloads` int(11) NOT NULL DEFAULT 0,
  `sort_order` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_downloadable_links_product_id_foreign` (`product_id`),
  CONSTRAINT `product_downloadable_links_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_downloadable_sample_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_downloadable_sample_translations`;
CREATE TABLE `product_downloadable_sample_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_downloadable_sample_id` int(10) unsigned NOT NULL,
  `locale` varchar(255) NOT NULL,
  `title` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sample_translations_sample_id_foreign` (`product_downloadable_sample_id`),
  CONSTRAINT `sample_translations_sample_id_foreign` FOREIGN KEY (`product_downloadable_sample_id`) REFERENCES `product_downloadable_samples` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_downloadable_samples`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_downloadable_samples`;
CREATE TABLE `product_downloadable_samples` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_downloadable_samples_product_id_foreign` (`product_id`),
  CONSTRAINT `product_downloadable_samples_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_flat`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_flat`;
CREATE TABLE `product_flat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `product_number` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `url_key` varchar(255) DEFAULT NULL,
  `new` tinyint(1) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `meta_title` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `price` decimal(12,4) DEFAULT NULL,
  `special_price` decimal(12,4) DEFAULT NULL,
  `special_price_from` date DEFAULT NULL,
  `special_price_to` date DEFAULT NULL,
  `weight` decimal(12,4) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `locale` varchar(255) DEFAULT NULL,
  `channel` varchar(255) DEFAULT NULL,
  `attribute_family_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `visible_individually` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_flat_unique_index` (`product_id`,`channel`,`locale`),
  KEY `product_flat_attribute_family_id_foreign` (`attribute_family_id`),
  KEY `product_flat_parent_id_foreign` (`parent_id`),
  CONSTRAINT `product_flat_attribute_family_id_foreign` FOREIGN KEY (`attribute_family_id`) REFERENCES `attribute_families` (`id`),
  CONSTRAINT `product_flat_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `product_flat` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_flat_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_grouped_products`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_grouped_products`;
CREATE TABLE `product_grouped_products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `associated_product_id` int(10) unsigned NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grouped_products_product_id_associated_product_id_unique` (`product_id`,`associated_product_id`),
  KEY `product_grouped_products_associated_product_id_foreign` (`associated_product_id`),
  KEY `pgp_product_id_idx` (`product_id`),
  CONSTRAINT `product_grouped_products_associated_product_id_foreign` FOREIGN KEY (`associated_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_grouped_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=481 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_images`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `position` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `prod_img_product_id_idx` (`product_id`),
  CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=280 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_inventories`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_inventories`;
CREATE TABLE `product_inventories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qty` int(11) NOT NULL DEFAULT 0,
  `product_id` int(10) unsigned NOT NULL,
  `vendor_id` int(11) NOT NULL DEFAULT 0,
  `inventory_source_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_source_vendor_index_unique` (`product_id`,`inventory_source_id`,`vendor_id`),
  KEY `product_inventories_inventory_source_id_foreign` (`inventory_source_id`),
  CONSTRAINT `product_inventories_inventory_source_id_foreign` FOREIGN KEY (`inventory_source_id`) REFERENCES `inventory_sources` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_inventories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2587 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_inventory_indices`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_inventory_indices`;
CREATE TABLE `product_inventory_indices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qty` int(11) NOT NULL DEFAULT 0,
  `product_id` int(10) unsigned NOT NULL,
  `channel_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_inventory_indices_product_id_channel_id_unique` (`product_id`,`channel_id`),
  KEY `product_inventory_indices_channel_id_foreign` (`channel_id`),
  KEY `prod_inv_product_id_idx` (`product_id`),
  CONSTRAINT `product_inventory_indices_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_inventory_indices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2654 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_ordered_inventories`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_ordered_inventories`;
CREATE TABLE `product_ordered_inventories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `qty` int(11) NOT NULL DEFAULT 0,
  `product_id` int(10) unsigned NOT NULL,
  `channel_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_ordered_inventories_product_id_channel_id_unique` (`product_id`,`channel_id`),
  KEY `product_ordered_inventories_channel_id_foreign` (`channel_id`),
  CONSTRAINT `product_ordered_inventories_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_ordered_inventories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_price_indices`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_price_indices`;
CREATE TABLE `product_price_indices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `customer_group_id` int(10) unsigned DEFAULT NULL,
  `channel_id` int(10) unsigned NOT NULL DEFAULT 1,
  `min_price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `regular_min_price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `max_price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `regular_max_price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `price_indices_product_id_customer_group_id_channel_id_unique` (`product_id`,`customer_group_id`,`channel_id`),
  KEY `product_price_indices_customer_group_id_foreign` (`customer_group_id`),
  KEY `product_price_indices_channel_id_foreign` (`channel_id`),
  KEY `ppi_product_id_customer_group_id_idx` (`product_id`,`customer_group_id`),
  CONSTRAINT `product_price_indices_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_price_indices_customer_group_id_foreign` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_price_indices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7909 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_relations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_relations`;
CREATE TABLE `product_relations` (
  `parent_id` int(10) unsigned NOT NULL,
  `child_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `product_relations_parent_id_child_id_unique` (`parent_id`,`child_id`),
  KEY `product_relations_child_id_foreign` (`child_id`),
  CONSTRAINT `product_relations_child_id_foreign` FOREIGN KEY (`child_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_relations_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_review_attachments`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_review_attachments`;
CREATE TABLE `product_review_attachments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` int(10) unsigned NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'image',
  `mime_type` varchar(255) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_review_images_review_id_foreign` (`review_id`),
  CONSTRAINT `product_review_images_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_reviews`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_reviews`;
CREATE TABLE `product_reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prod_rev_product_id_idx` (`product_id`),
  CONSTRAINT `product_reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_super_attributes`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_super_attributes`;
CREATE TABLE `product_super_attributes` (
  `product_id` int(10) unsigned NOT NULL,
  `attribute_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `product_super_attributes_product_id_attribute_id_unique` (`product_id`,`attribute_id`),
  KEY `product_super_attributes_attribute_id_foreign` (`attribute_id`),
  CONSTRAINT `product_super_attributes_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`),
  CONSTRAINT `product_super_attributes_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_up_sells`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_up_sells`;
CREATE TABLE `product_up_sells` (
  `parent_id` int(10) unsigned NOT NULL,
  `child_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `product_up_sells_parent_id_child_id_unique` (`parent_id`,`child_id`),
  KEY `product_up_sells_child_id_foreign` (`child_id`),
  CONSTRAINT `product_up_sells_child_id_foreign` FOREIGN KEY (`child_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_up_sells_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `product_videos`
-- ----------------------------------------------
DROP TABLE IF EXISTS `product_videos`;
CREATE TABLE `product_videos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `position` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `prod_vid_product_id_idx` (`product_id`),
  CONSTRAINT `product_videos_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `products`
-- ----------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `attribute_family_id` int(10) unsigned DEFAULT NULL,
  `additional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  KEY `products_attribute_family_id_foreign` (`attribute_family_id`),
  KEY `products_parent_id_foreign` (`parent_id`),
  CONSTRAINT `products_attribute_family_id_foreign` FOREIGN KEY (`attribute_family_id`) REFERENCES `attribute_families` (`id`),
  CONSTRAINT `products_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3160 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `provider_accounts`
-- ----------------------------------------------
DROP TABLE IF EXISTS `provider_accounts`;
CREATE TABLE `provider_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `app_key` varchar(255) DEFAULT NULL,
  `app_secret` text DEFAULT NULL,
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `provider_sync_states`
-- ----------------------------------------------
DROP TABLE IF EXISTS `provider_sync_states`;
CREATE TABLE `provider_sync_states` (
  `provider` varchar(255) NOT NULL,
  `last_attempt_cursor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`last_attempt_cursor`)),
  `last_successful_cursor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`last_successful_cursor`)),
  `last_attempt_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_successful_at` timestamp NULL DEFAULT NULL,
  `last_full_sync_at` timestamp NULL DEFAULT NULL,
  `schema_version` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `purchase_order_items`
-- ----------------------------------------------
DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE `purchase_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `aliexpress_product_id` varchar(255) DEFAULT NULL,
  `sku_id` varchar(255) DEFAULT NULL,
  `qty` int(10) unsigned NOT NULL,
  `supplier_unit_cost` decimal(12,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_order_items_purchase_order_id_order_item_id_unique` (`purchase_order_id`,`order_item_id`),
  KEY `purchase_order_items_purchase_order_id_index` (`purchase_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `purchase_orders`
-- ----------------------------------------------
DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `provider` varchar(255) NOT NULL,
  `provider_account_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_signature` varchar(255) DEFAULT NULL,
  `idempotency_key` varchar(255) NOT NULL,
  `internal_reference` varchar(255) NOT NULL,
  `external_order_id` varchar(255) DEFAULT NULL,
  `state` varchar(255) NOT NULL DEFAULT 'pending',
  `supplier_state_raw` varchar(255) DEFAULT NULL,
  `supplier_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supplier_snapshot`)),
  `attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `tracking_company` varchar(255) DEFAULT NULL,
  `supplier_cost` decimal(12,4) DEFAULT NULL,
  `supplier_currency` varchar(3) DEFAULT NULL,
  `payload_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_snapshot`)),
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_idempotency_key_unique` (`idempotency_key`),
  UNIQUE KEY `purchase_orders_internal_reference_unique` (`internal_reference`),
  UNIQUE KEY `po_order_provider_supplier_unique` (`order_id`,`provider`,`supplier_signature`),
  KEY `purchase_orders_order_id_provider_index` (`order_id`,`provider`),
  KEY `purchase_orders_external_order_id_index` (`external_order_id`),
  KEY `purchase_orders_provider_account_id_index` (`provider_account_id`),
  KEY `purchase_orders_state_created_at_index` (`state`,`created_at`),
  KEY `purchase_orders_provider_state_index` (`provider`,`state`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `refund_items`
-- ----------------------------------------------
DROP TABLE IF EXISTS `refund_items`;
CREATE TABLE `refund_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_price` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `total` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_total` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `tax_amount` decimal(12,4) DEFAULT 0.0000,
  `base_tax_amount` decimal(12,4) DEFAULT 0.0000,
  `discount_percent` decimal(12,4) DEFAULT 0.0000,
  `discount_amount` decimal(12,4) DEFAULT 0.0000,
  `base_discount_amount` decimal(12,4) DEFAULT 0.0000,
  `price_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_price_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `product_id` int(10) unsigned DEFAULT NULL,
  `product_type` varchar(255) DEFAULT NULL,
  `order_item_id` int(10) unsigned DEFAULT NULL,
  `refund_id` int(10) unsigned DEFAULT NULL,
  `additional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `refund_items_parent_id_foreign` (`parent_id`),
  KEY `refund_items_order_item_id_foreign` (`order_item_id`),
  KEY `refund_items_refund_id_foreign` (`refund_id`),
  CONSTRAINT `refund_items_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refund_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `refund_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refund_items_refund_id_foreign` FOREIGN KEY (`refund_id`) REFERENCES `refunds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `refunds`
-- ----------------------------------------------
DROP TABLE IF EXISTS `refunds`;
CREATE TABLE `refunds` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `increment_id` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `total_qty` int(11) DEFAULT NULL,
  `base_currency_code` varchar(255) DEFAULT NULL,
  `channel_currency_code` varchar(255) DEFAULT NULL,
  `order_currency_code` varchar(255) DEFAULT NULL,
  `adjustment_refund` decimal(12,4) DEFAULT 0.0000,
  `base_adjustment_refund` decimal(12,4) DEFAULT 0.0000,
  `adjustment_fee` decimal(12,4) DEFAULT 0.0000,
  `base_adjustment_fee` decimal(12,4) DEFAULT 0.0000,
  `sub_total` decimal(12,4) DEFAULT 0.0000,
  `base_sub_total` decimal(12,4) DEFAULT 0.0000,
  `grand_total` decimal(12,4) DEFAULT 0.0000,
  `base_grand_total` decimal(12,4) DEFAULT 0.0000,
  `shipping_amount` decimal(12,4) DEFAULT 0.0000,
  `base_shipping_amount` decimal(12,4) DEFAULT 0.0000,
  `tax_amount` decimal(12,4) DEFAULT 0.0000,
  `base_tax_amount` decimal(12,4) DEFAULT 0.0000,
  `discount_percent` decimal(12,4) DEFAULT 0.0000,
  `discount_amount` decimal(12,4) DEFAULT 0.0000,
  `base_discount_amount` decimal(12,4) DEFAULT 0.0000,
  `shipping_tax_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_shipping_tax_amount` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sub_total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_sub_total_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `shipping_amount_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_shipping_amount_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `order_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `refunds_order_id_foreign` (`order_id`),
  CONSTRAINT `refunds_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `rma`
-- ----------------------------------------------
DROP TABLE IF EXISTS `rma`;
CREATE TABLE `rma` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `rma_status_id` int(10) unsigned DEFAULT NULL,
  `package_condition` varchar(255) DEFAULT NULL,
  `information` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rma_order_id_foreign` (`order_id`),
  KEY `rma_rma_status_id_foreign` (`rma_status_id`),
  CONSTRAINT `rma_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rma_rma_status_id_foreign` FOREIGN KEY (`rma_status_id`) REFERENCES `rma_statuses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `rma_additional_fields`
-- ----------------------------------------------
DROP TABLE IF EXISTS `rma_additional_fields`;
CREATE TABLE `rma_additional_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rma_id` int(10) unsigned DEFAULT NULL,
  `rma_custom_field_id` int(10) unsigned DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rma_additional_fields_rma_id_foreign` (`rma_id`),
  KEY `rma_additional_fields_rma_custom_field_id_foreign` (`rma_custom_field_id`),
  CONSTRAINT `rma_additional_fields_rma_custom_field_id_foreign` FOREIGN KEY (`rma_custom_field_id`) REFERENCES `rma_custom_fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rma_additional_fields_rma_id_foreign` FOREIGN KEY (`rma_id`) REFERENCES `rma` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `rma_custom_field_options`
-- ----------------------------------------------
DROP TABLE IF EXISTS `rma_custom_field_options`;
CREATE TABLE `rma_custom_field_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rma_custom_field_id` int(10) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rma_custom_field_options_rma_custom_field_id_foreign` (`rma_custom_field_id`),
  CONSTRAINT `rma_custom_field_options_rma_custom_field_id_foreign` FOREIGN KEY (`rma_custom_field_id`) REFERENCES `rma_custom_fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `rma_custom_fields`
-- ----------------------------------------------
DROP TABLE IF EXISTS `rma_custom_fields`;
CREATE TABLE `rma_custom_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) DEFAULT 0,
  `code` varchar(255) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `position` int(11) DEFAULT 0,
  `input_validation` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rma_custom_fields_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `rma_images`
-- ----------------------------------------------
DROP TABLE IF EXISTS `rma_images`;
CREATE TABLE `rma_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rma_id` int(10) unsigned NOT NULL,
  `path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rma_images_rma_id_foreign` (`rma_id`),
  CONSTRAINT `rma_images_rma_id_foreign` FOREIGN KEY (`rma_id`) REFERENCES `rma` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `rma_items`
-- ----------------------------------------------
DROP TABLE IF EXISTS `rma_items`;
CREATE TABLE `rma_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rma_id` int(10) unsigned DEFAULT NULL,
  `rma_reason_id` int(10) unsigned DEFAULT NULL,
  `order_item_id` int(10) unsigned DEFAULT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `resolution` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rma_items_rma_id_foreign` (`rma_id`),
  KEY `rma_items_rma_reason_id_foreign` (`rma_reason_id`),
  KEY `rma_items_order_item_id_foreign` (`order_item_id`),
  KEY `rma_items_variant_id_foreign` (`variant_id`),
  CONSTRAINT `rma_items_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rma_items_rma_id_foreign` FOREIGN KEY (`rma_id`) REFERENCES `rma` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rma_items_rma_reason_id_foreign` FOREIGN KEY (`rma_reason_id`) REFERENCES `rma_reasons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rma_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `products` (`id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `rma_messages`
-- ----------------------------------------------
DROP TABLE IF EXISTS `rma_messages`;
CREATE TABLE `rma_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rma_id` int(10) unsigned NOT NULL,
  `message` longtext NOT NULL,
  `attachment_path` longtext DEFAULT NULL,
  `attachment` longtext DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rma_messages_rma_id_foreign` (`rma_id`),
  CONSTRAINT `rma_messages_rma_id_foreign` FOREIGN KEY (`rma_id`) REFERENCES `rma` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `rma_reason_resolutions`
-- ----------------------------------------------
DROP TABLE IF EXISTS `rma_reason_resolutions`;
CREATE TABLE `rma_reason_resolutions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rma_reason_id` int(10) unsigned NOT NULL,
  `resolution_type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rma_reason_resolutions_rma_reason_id_foreign` (`rma_reason_id`),
  CONSTRAINT `rma_reason_resolutions_rma_reason_id_foreign` FOREIGN KEY (`rma_reason_id`) REFERENCES `rma_reasons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `rma_reason_resolutions` (10 rows)
INSERT INTO `rma_reason_resolutions` (`id`, `rma_reason_id`, `resolution_type`, `created_at`, `updated_at`) VALUES
('1', '1', 'return', '2026-07-19 00:21:56', '2026-07-19 00:21:56'),
('2', '1', 'cancel_items', '2026-07-19 00:21:56', '2026-07-19 00:21:56'),
('3', '2', 'return', '2026-07-19 00:21:56', '2026-07-19 00:21:56'),
('4', '2', 'cancel_items', '2026-07-19 00:21:56', '2026-07-19 00:21:56'),
('5', '3', 'return', '2026-07-19 00:21:56', '2026-07-19 00:21:56'),
('6', '3', 'cancel_items', '2026-07-19 00:21:56', '2026-07-19 00:21:56'),
('7', '4', 'return', '2026-07-19 00:21:56', '2026-07-19 00:21:56'),
('8', '4', 'cancel_items', '2026-07-19 00:21:56', '2026-07-19 00:21:56'),
('9', '5', 'return', '2026-07-19 00:21:56', '2026-07-19 00:21:56'),
('10', '5', 'cancel_items', '2026-07-19 00:21:56', '2026-07-19 00:21:56');

-- ----------------------------------------------
-- Table structure for `rma_reasons`
-- ----------------------------------------------
DROP TABLE IF EXISTS `rma_reasons`;
CREATE TABLE `rma_reasons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `rma_reasons` (5 rows)
INSERT INTO `rma_reasons` (`id`, `title`, `status`, `position`, `is_admin`, `created_at`, `updated_at`) VALUES
('1', 'Manufacturer Defect', '1', '1', '0', '2026-07-19 00:21:56', '2026-07-19 00:21:56'),
('2', 'Damaged During Shipping', '1', '2', '0', '2026-07-19 00:21:56', '2026-07-19 00:21:56'),
('3', 'Wrong Description Online', '1', '3', '0', '2026-07-19 00:21:56', '2026-07-19 00:21:56'),
('4', 'Dead On Arrival', '1', '4', '0', '2026-07-19 00:21:56', '2026-07-19 00:21:56'),
('5', 'Product Not Received Yet', '1', '5', '0', '2026-07-19 00:21:56', '2026-07-19 00:21:56');

-- ----------------------------------------------
-- Table structure for `rma_rules`
-- ----------------------------------------------
DROP TABLE IF EXISTS `rma_rules`;
CREATE TABLE `rma_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `return_period` int(11) DEFAULT NULL,
  `default` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `rma_rules` (1 rows)
INSERT INTO `rma_rules` (`id`, `name`, `description`, `status`, `return_period`, `default`, `created_at`, `updated_at`) VALUES
('1', 'Basic', '1', '1', '10', NULL, '2026-07-19 00:21:56', '2026-07-19 00:21:56');

-- ----------------------------------------------
-- Table structure for `rma_statuses`
-- ----------------------------------------------
DROP TABLE IF EXISTS `rma_statuses`;
CREATE TABLE `rma_statuses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `default` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `rma_statuses` (9 rows)
INSERT INTO `rma_statuses` (`id`, `title`, `status`, `color`, `default`, `created_at`, `updated_at`) VALUES
('1', 'Pending', '1', '#efb308', '1', NULL, NULL),
('2', 'Accept', '1', '#12af56', '1', NULL, NULL),
('3', 'Awaiting', '1', '#f59e0b', '1', NULL, NULL),
('4', 'Dispatched Package', '1', '#3b82f6', '1', NULL, NULL),
('5', 'Received Package', '1', '#10b981', '1', NULL, NULL),
('6', 'Solved', '1', '#47b84f', '1', NULL, NULL),
('7', 'Declined', '1', '#e11d48', '1', NULL, NULL),
('8', 'Item Canceled', '1', '#dc2626', '1', NULL, NULL),
('9', 'Canceled', '1', '#991b1b', '1', NULL, NULL);

-- ----------------------------------------------
-- Table structure for `roles`
-- ----------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `permission_type` varchar(255) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `roles` (1 rows)
INSERT INTO `roles` (`id`, `name`, `description`, `permission_type`, `permissions`, `created_at`, `updated_at`) VALUES
('1', 'مدير', 'سيكون لدى مستخدمي هذا الدور وصولًا كاملاً', 'all', NULL, NULL, NULL);

-- ----------------------------------------------
-- Table structure for `search_synonyms`
-- ----------------------------------------------
DROP TABLE IF EXISTS `search_synonyms`;
CREATE TABLE `search_synonyms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `terms` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `search_terms`
-- ----------------------------------------------
DROP TABLE IF EXISTS `search_terms`;
CREATE TABLE `search_terms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `term` varchar(255) NOT NULL,
  `results` int(11) NOT NULL DEFAULT 0,
  `uses` int(11) NOT NULL DEFAULT 0,
  `redirect_url` varchar(255) DEFAULT NULL,
  `display_in_suggested_terms` tinyint(1) NOT NULL DEFAULT 0,
  `locale` varchar(255) NOT NULL,
  `channel_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `search_terms_channel_id_foreign` (`channel_id`),
  CONSTRAINT `search_terms_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `sessions`
-- ----------------------------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `sessions` (3 rows)
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('6698Dp7MHaUbIP70TcQHjTkirQ3pCaKmyoGHVUeG', NULL, '2a09:bac5:4ef1:228::37:23', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.7291', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiV2ZOelFFc1Z0aFh2SlR5Q0VxMnhzOU5EZTdBQjJmamt3VnJYblY5cyI7czo2OiJsb2NhbGUiO3M6MjoiYXIiO3M6ODoiY3VycmVuY3kiO3M6MzoiVVNEIjtzOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czo0OToiaHR0cHM6Ly96b29sb2dpc3QtZGVjYXRobG9uLWVjbGFpci5uZ3Jvay1mcmVlLmRldiI7czo1OiJyb3V0ZSI7czoxNToic2hvcC5ob21lLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', '1784503106'),
('H6CLTs4hXQNFaLjjjYq2ms0HEf2jv8mpJrUtQYqi', NULL, '2a09:bac1:5800:7f8::37:23', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'YTo3OntzOjY6Il90b2tlbiI7czo0MDoiMWhjRUJzMTdlN1hNeVdjaGlrVnhrNzg4Z3M3eDk3cEFIWGl1Q05vRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NzM6Imh0dHBzOi8vem9vbG9naXN0LWRlY2F0aGxvbi1lY2xhaXIubmdyb2stZnJlZS5kZXYvYWRtaW4vZHJvcHNoaXBwaW5nL2tleXMiO3M6NToicm91dGUiO3M6Mjk6ImFkbWluLmRyb3BzaGlwcGluZy5rZXlzLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MDp7fXM6NTI6ImxvZ2luX2FkbWluXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjY6ImxvY2FsZSI7czoyOiJhciI7czo4OiJjdXJyZW5jeSI7czozOiJVU0QiO30=', '1784503373'),
('S6x2j1V7ppwsETM8JTwyoJob2evH3GbPM9FWHop6', NULL, '2a09:bac5:4ef3:228::37:23', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.19041.7291', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTkZvR2ZFcnNZZXFWTmU3SzJqMHVURFlvQ0VFVmU1STVibXdDN3RQdiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjE6Imh0dHBzOi8vem9vbG9naXN0LWRlY2F0aGxvbi1lY2xhaXIubmdyb2stZnJlZS5kZXYvYWRtaW4vbG9naW4iO3M6NToicm91dGUiO3M6MjA6ImFkbWluLnNlc3Npb24uY3JlYXRlIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo1NToiaHR0cHM6Ly96b29sb2dpc3QtZGVjYXRobG9uLWVjbGFpci5uZ3Jvay1mcmVlLmRldi9hZG1pbiI7fX0=', '1784503144');

-- ----------------------------------------------
-- Table structure for `shipment_items`
-- ----------------------------------------------
DROP TABLE IF EXISTS `shipment_items`;
CREATE TABLE `shipment_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `weight` decimal(12,4) DEFAULT NULL,
  `price` decimal(12,4) DEFAULT 0.0000,
  `base_price` decimal(12,4) DEFAULT 0.0000,
  `total` decimal(12,4) DEFAULT 0.0000,
  `base_total` decimal(12,4) DEFAULT 0.0000,
  `price_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `base_price_incl_tax` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `product_id` int(10) unsigned DEFAULT NULL,
  `product_type` varchar(255) DEFAULT NULL,
  `order_item_id` int(10) unsigned DEFAULT NULL,
  `shipment_id` int(10) unsigned NOT NULL,
  `additional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipment_items_shipment_id_foreign` (`shipment_id`),
  CONSTRAINT `shipment_items_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `shipments`
-- ----------------------------------------------
DROP TABLE IF EXISTS `shipments`;
CREATE TABLE `shipments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(255) DEFAULT NULL,
  `total_qty` int(11) DEFAULT NULL,
  `total_weight` decimal(12,4) DEFAULT NULL,
  `carrier_code` varchar(255) DEFAULT NULL,
  `carrier_title` varchar(255) DEFAULT NULL,
  `track_number` text DEFAULT NULL,
  `email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `customer_type` varchar(255) DEFAULT NULL,
  `order_id` int(10) unsigned NOT NULL,
  `order_address_id` int(10) unsigned DEFAULT NULL,
  `inventory_source_id` int(10) unsigned DEFAULT NULL,
  `inventory_source_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipments_order_id_foreign` (`order_id`),
  KEY `shipments_inventory_source_id_foreign` (`inventory_source_id`),
  CONSTRAINT `shipments_inventory_source_id_foreign` FOREIGN KEY (`inventory_source_id`) REFERENCES `inventory_sources` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `sitemaps`
-- ----------------------------------------------
DROP TABLE IF EXISTS `sitemaps`;
CREATE TABLE `sitemaps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `additional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional`)),
  `generated_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `subscribers_list`
-- ----------------------------------------------
DROP TABLE IF EXISTS `subscribers_list`;
CREATE TABLE `subscribers_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `is_subscribed` tinyint(1) NOT NULL DEFAULT 0,
  `token` varchar(255) DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `channel_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscribers_list_customer_id_foreign` (`customer_id`),
  KEY `subscribers_list_channel_id_foreign` (`channel_id`),
  CONSTRAINT `subscribers_list_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscribers_list_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `sync_benchmarks`
-- ----------------------------------------------
DROP TABLE IF EXISTS `sync_benchmarks`;
CREATE TABLE `sync_benchmarks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `provider` varchar(255) NOT NULL,
  `throughput` double NOT NULL,
  `memory_peak_bytes` bigint(20) NOT NULL,
  `latency_avg_ms` int(11) NOT NULL,
  `products_changed` int(11) NOT NULL,
  `products_unchanged` int(11) NOT NULL,
  `stale_events` int(11) NOT NULL,
  `replay_events` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `sync_runs`
-- ----------------------------------------------
DROP TABLE IF EXISTS `sync_runs`;
CREATE TABLE `sync_runs` (
  `id` char(36) NOT NULL,
  `provider` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `lock_owner` varchar(255) DEFAULT NULL,
  `worker_id` varchar(255) DEFAULT NULL,
  `cursor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`cursor`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `health_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`health_snapshot`)),
  `statistics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`statistics`)),
  `started_at` timestamp NULL DEFAULT NULL,
  `heartbeat_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sync_runs_provider_status_heartbeat_idx` (`provider`,`status`,`heartbeat_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `tax_categories`
-- ----------------------------------------------
DROP TABLE IF EXISTS `tax_categories`;
CREATE TABLE `tax_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tax_categories_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `tax_categories_tax_rates`
-- ----------------------------------------------
DROP TABLE IF EXISTS `tax_categories_tax_rates`;
CREATE TABLE `tax_categories_tax_rates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tax_category_id` int(10) unsigned NOT NULL,
  `tax_rate_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tax_map_index_unique` (`tax_category_id`,`tax_rate_id`),
  KEY `tax_categories_tax_rates_tax_rate_id_foreign` (`tax_rate_id`),
  CONSTRAINT `tax_categories_tax_rates_tax_category_id_foreign` FOREIGN KEY (`tax_category_id`) REFERENCES `tax_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_categories_tax_rates_tax_rate_id_foreign` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `tax_rates`
-- ----------------------------------------------
DROP TABLE IF EXISTS `tax_rates`;
CREATE TABLE `tax_rates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL,
  `is_zip` tinyint(1) NOT NULL DEFAULT 0,
  `zip_code` varchar(255) DEFAULT NULL,
  `zip_from` varchar(255) DEFAULT NULL,
  `zip_to` varchar(255) DEFAULT NULL,
  `state` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `tax_rate` decimal(12,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tax_rates_identifier_unique` (`identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `theme_customization_translations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `theme_customization_translations`;
CREATE TABLE `theme_customization_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `theme_customization_id` int(10) unsigned NOT NULL,
  `locale` varchar(255) NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  PRIMARY KEY (`id`),
  KEY `theme_customization_id_foreign` (`theme_customization_id`),
  CONSTRAINT `theme_customization_id_foreign` FOREIGN KEY (`theme_customization_id`) REFERENCES `theme_customizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `theme_customization_translations` (8 rows)
INSERT INTO `theme_customization_translations` (`id`, `theme_customization_id`, `locale`, `options`) VALUES
('1', '1', 'ar', '{\"images\":[{\"title\":\"\\u0627\\u0633\\u062a\\u0639\\u062f \\u0644\\u0644\\u0645\\u062c\\u0645\\u0648\\u0639\\u0629 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629\",\"link\":\"#formal-wear-female\",\"image\":\"storage\\/theme\\/1\\/9FnsgHEDYJiZwAMcAME1BXBTezHOr0k9cGVlOAny.webp\"},{\"title\":\"\\u0627\\u0633\\u062a\\u0639\\u062f \\u0644\\u0644\\u0645\\u062c\\u0645\\u0648\\u0639\\u0629 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629\",\"link\":\"#formal-wear-men\",\"image\":\"storage\\/theme\\/1\\/LgUHuhp15zNbfYvTnlFfZtaMXbWNWMgRvKphmsoL.webp\"},{\"title\":\"\\u0627\\u0633\\u062a\\u0639\\u062f \\u0644\\u0644\\u0645\\u062c\\u0645\\u0648\\u0639\\u0629 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629\",\"link\":\"#active-wear-female\",\"image\":\"storage\\/theme\\/1\\/xjM9yQRfLD9CgTFMEU2isyNzRIwdJefSmnd2PieQ.webp\"},{\"title\":\"\\u0627\\u0633\\u062a\\u0639\\u062f \\u0644\\u0644\\u0645\\u062c\\u0645\\u0648\\u0639\\u0629 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629\",\"link\":\"#smart-home-automation\",\"image\":\"storage\\/theme\\/1\\/Z3b4I6z7ivk9uxx8iBd0j5mCVgK3k2hSFKvQ992c.webp\"},{\"title\":\"\\u0627\\u0633\\u062a\\u0639\\u062f \\u0644\\u0644\\u0645\\u062c\\u0645\\u0648\\u0639\\u0629 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629\",\"link\":\"#mobile-phones-accessories\",\"image\":\"storage\\/theme\\/1\\/irQ9OtdjK1n9L4oGM5JNodpy9hdANod2phEuvazb.webp\"},{\"title\":\"\\u0627\\u0633\\u062a\\u0639\\u062f \\u0644\\u0644\\u0645\\u062c\\u0645\\u0648\\u0639\\u0629 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629\",\"link\":\"#laptops-tablets\",\"image\":\"storage\\/theme\\/1\\/jXjQwh9tOwPVehVfyACk0hiXtrmu81TPFJQPgsQX.webp\"}]}'),
('2', '2', 'ar', '{\"html\":\"<div class=\\\"home-offer\\\"><h1>\\u0627\\u062d\\u0635\\u0644 \\u0639\\u0644\\u0649 \\u062e\\u0635\\u0645 \\u064a\\u0635\\u0644 \\u0625\\u0644\\u0649 40% \\u0639\\u0644\\u0649 \\u0637\\u0644\\u0628\\u0643 \\u0627\\u0644\\u0623\\u0648\\u0644. \\u062a\\u0633\\u0648\\u0642 \\u0627\\u0644\\u0622\\u0646<\\/h1><\\/div>\",\"css\":\".home-offer h1 {display: block;font-weight: 500;text-align: center;font-size: 22px;font-family: DM Serif Display;background-color: #E8EDFE;padding-top: 20px;padding-bottom: 20px;}@media (max-width:768px){.home-offer h1 {font-size:18px;padding-top: 10px;padding-bottom: 10px;}@media (max-width:525px) {.home-offer h1 {font-size:14px;padding-top: 6px;padding-bottom: 6px;}}\"}'),
('3', '3', 'ar', '{\"html\":\"<div class=\\\"top-collection-container\\\">\\n                                <div class=\\\"top-collection-header\\\">\\n                                    <h2>\\u0627\\u0644\\u0644\\u0639\\u0628\\u0629 \\u0645\\u0639 \\u0625\\u0636\\u0627\\u0641\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629!<\\/h2>\\n                                <\\/div>\\n\\n                                <div class=\\\"top-collection-grid container\\\">\\n                                    <div class=\\\"top-collection-card\\\">\\n                                        <a href=\\\"#electronics\\\">\\n                                            <img src=\\\"\\\" data-src=\\\"storage\\/theme\\/5\\/G9Tktx0rP3ViJ3OKgLtSwpPjIK4t1ERMNC1obXe7.webp\\\" class=\\\"lazy\\\" width=\\\"396\\\" height=\\\"396\\\" alt=\\\"\\u0627\\u0644\\u0644\\u0639\\u0628\\u0629 \\u0645\\u0639 \\u0625\\u0636\\u0627\\u0641\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629!\\\">\\n                                        <\\/a>\\n                                    <\\/div>\\n\\n                                    <div class=\\\"top-collection-card\\\">\\n                                        <a href=\\\"#mens\\\">\\n                                            <img src=\\\"\\\" data-src=\\\"storage\\/theme\\/5\\/WQXq7rRHkOltu8S593lidA6kv8UBsE645UMOjIuB.webp\\\" class=\\\"lazy\\\" width=\\\"396\\\" height=\\\"396\\\" alt=\\\"\\u0627\\u0644\\u0644\\u0639\\u0628\\u0629 \\u0645\\u0639 \\u0625\\u0636\\u0627\\u0641\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629!\\\">\\n                                        <\\/a>\\n                                    <\\/div>\\n\\n                                    <div class=\\\"top-collection-card\\\">\\n                                        <a href=\\\"#womens\\\">\\n                                            <img src=\\\"\\\" data-src=\\\"storage\\/theme\\/5\\/LUD8NVhwZC2AsxJ8TB9FLZ4NKuMakfIamxZU9Rnr.webp\\\" class=\\\"lazy\\\" width=\\\"396\\\" height=\\\"396\\\" alt=\\\"\\u0627\\u0644\\u0644\\u0639\\u0628\\u0629 \\u0645\\u0639 \\u0625\\u0636\\u0627\\u0641\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629!\\\">\\n                                        <\\/a>\\n                                    <\\/div>\\n\\n                                    <div class=\\\"top-collection-card\\\">\\n                                        <a href=\\\"#formal-wear-men\\\">\\n                                            <img src=\\\"\\\" data-src=\\\"storage\\/theme\\/5\\/2IOHtFumLySwSJxuNqvX5nn4Bq9DVGS7HCDyREqn.webp\\\" class=\\\"lazy\\\" width=\\\"396\\\" height=\\\"396\\\" alt=\\\"\\u0627\\u0644\\u0644\\u0639\\u0628\\u0629 \\u0645\\u0639 \\u0625\\u0636\\u0627\\u0641\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629!\\\">\\n                                        <\\/a>\\n                                    <\\/div>\\n\\n                                    <div class=\\\"top-collection-card\\\">\\n                                        <a href=\\\"#formal-wear-female\\\">\\n                                            <img src=\\\"\\\" data-src=\\\"storage\\/theme\\/5\\/00u1xhuuLxwh7KdmFh1fcsHuy3DaVRdsvslAktOz.webp\\\" class=\\\"lazy\\\" width=\\\"396\\\" height=\\\"396\\\" alt=\\\"\\u0627\\u0644\\u0644\\u0639\\u0628\\u0629 \\u0645\\u0639 \\u0625\\u0636\\u0627\\u0641\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629!\\\">\\n                                        <\\/a>\\n                                    <\\/div>\\n\\n                                    <div class=\\\"top-collection-card\\\">\\n                                        <a href=\\\"#wellness\\\">\\n                                            <img src=\\\"\\\" data-src=\\\"storage\\/theme\\/5\\/VyP2J0uv4gA9kzQePlqPxqh73GUGXCWwSpf04XJT.webp\\\" class=\\\"lazy\\\" width=\\\"396\\\" height=\\\"396\\\" alt=\\\"\\u0627\\u0644\\u0644\\u0639\\u0628\\u0629 \\u0645\\u0639 \\u0625\\u0636\\u0627\\u0641\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629!\\\">\\n                                        <\\/a>\\n                                    <\\/div>\\n                                <\\/div>\\n                            <\\/div>\",\"css\":\".top-collection-container {overflow: hidden;}.top-collection-header {padding-left: 15px;padding-right: 15px;text-align: center;font-size: 70px;line-height: 90px;color: #060C3B;margin-top: 80px;}.top-collection-header h2 {max-width: 595px;margin-left: auto;margin-right: auto;font-family: DM Serif Display;}.top-collection-grid {display: flex;flex-wrap: wrap;gap: 32px;justify-content: center;margin-top: 60px;width: 100%;margin-right: auto;margin-left: auto;padding-right: 90px;padding-left: 90px;}.top-collection-card {position: relative;background: #f9fafb;overflow:hidden;border-radius:20px;}.top-collection-card img {border-radius: 16px;max-width: 100%;text-indent:-9999px;transition: transform 300ms ease;transform: scale(1);}.top-collection-card:hover img {transform: scale(1.05);transition: all 300ms ease;}.top-collection-card h3 {color: #060C3B;font-size: 30px;font-family: DM Serif Display;transform: translateX(-50%);width: max-content;left: 50%;bottom: 30px;position: absolute;margin: 0;font-weight: inherit;}@media not all and (min-width: 525px) {.top-collection-header {margin-top: 28px;font-size: 20px;line-height: 1.5;}.top-collection-grid {gap: 10px}}@media not all and (min-width: 768px) {.top-collection-header {margin-top: 30px;font-size: 28px;line-height: 3;}.top-collection-header h2 {line-height:2; margin-bottom:20px;} .top-collection-grid {gap: 14px}} @media not all and (min-width: 1024px) {.top-collection-grid {padding-left: 30px;padding-right: 30px;}}@media (max-width: 768px) {.top-collection-grid { row-gap:15px; column-gap:0px;justify-content: space-between;margin-top: 0px;} .top-collection-card{width:48%} .top-collection-card img {width:100%;} .top-collection-card h3 {font-size:24px; bottom: 16px;}}@media (max-width:520px) { .top-collection-grid{padding-left: 15px;padding-right: 15px;} .top-collection-card h3 {font-size:18px; bottom: 10px;}}\"}'),
('4', '4', 'ar', '{\"html\":\"<div class=\\\"section-gap bold-collections container\\\">\\n                                <div class=\\\"inline-col-wrapper\\\">\\n                                    <div class=\\\"inline-col-image-wrapper\\\">\\n                                        <img src=\\\"\\\" data-src=\\\"storage\\/theme\\/6\\/xQyVKhPGMvry2B47lXVSoZhmGGjoKQR35YSXF2aM.webp\\\" class=\\\"lazy\\\" width=\\\"632\\\" height=\\\"510\\\" alt=\\\"\\u0627\\u0633\\u062a\\u0639\\u062f \\u0644\\u0645\\u062c\\u0645\\u0648\\u0639\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u0628\\u0627\\u0631\\u0632\\u0629 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629!\\\">\\n                                    <\\/div>\\n\\n                                    <div class=\\\"inline-col-content-wrapper\\\">\\n                                        <h2 class=\\\"inline-col-title\\\"> \\u0627\\u0633\\u062a\\u0639\\u062f \\u0644\\u0645\\u062c\\u0645\\u0648\\u0639\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u0628\\u0627\\u0631\\u0632\\u0629 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629! <\\/h2> \\n                                        \\n                                        <p class=\\\"inline-col-description\\\">\\u0646\\u0642\\u062f\\u0645 \\u0644\\u0643 \\u0645\\u062c\\u0645\\u0648\\u0639\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u0628\\u0627\\u0631\\u0632\\u0629 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629! \\u0642\\u0645 \\u0628\\u062a\\u062d\\u0633\\u064a\\u0646 \\u0623\\u0646\\u0627\\u0642\\u062a\\u0643 \\u0645\\u0639 \\u062a\\u0635\\u0627\\u0645\\u064a\\u0645 \\u062c\\u0631\\u064a\\u0626\\u0629 \\u0648\\u0639\\u0628\\u0627\\u0631\\u0627\\u062a \\u062d\\u064a\\u0648\\u064a\\u0629. \\u0627\\u0633\\u062a\\u0643\\u0634\\u0641 \\u0623\\u0646\\u0645\\u0627\\u0637\\u064b\\u0627 \\u0628\\u0627\\u0631\\u0632\\u0629 \\u0648\\u0623\\u0644\\u0648\\u0627\\u0646\\u064b\\u0627 \\u062c\\u0631\\u064a\\u0626\\u0629 \\u062a\\u0639\\u064a\\u062f \\u062a\\u0639\\u0631\\u064a\\u0641 \\u062e\\u0632\\u0627\\u0646\\u062a\\u0643. \\u0627\\u0633\\u062a\\u0639\\u062f \\u0644\\u0627\\u0639\\u062a\\u0646\\u0627\\u0642 \\u0627\\u0644\\u0627\\u0633\\u062a\\u062b\\u0646\\u0627\\u0626\\u064a\\u0629!<\\/p>\\n                                        \\n                                        <a href=\\\"#wellness\\\">\\n                                            <button class=\\\"primary-button max-md:rounded-lg max-md:px-4 max-md:py-2.5 max-md:text-sm\\\">\\u0639\\u0631\\u0636 \\u0627\\u0644\\u0645\\u062c\\u0645\\u0648\\u0639\\u0627\\u062a<\\/button>\\n                                        <\\/a>\\n                                    <\\/div>\\n                                <\\/div>\\n                            <\\/div>\",\"css\":\".section-gap{margin-top:80px}.direction-ltr{direction:ltr}.direction-rtl{direction:rtl}.inline-col-wrapper{display:grid;grid-template-columns:auto 1fr;grid-gap:60px;align-items:center}.inline-col-wrapper .inline-col-image-wrapper{overflow:hidden}.inline-col-wrapper .inline-col-image-wrapper img{max-width:100%;height:auto;border-radius:16px;text-indent:-9999px}.inline-col-wrapper .inline-col-content-wrapper{display:flex;flex-wrap:wrap;gap:20px;max-width:464px}.inline-col-wrapper .inline-col-content-wrapper .inline-col-title{max-width:442px;font-size:60px;font-weight:400;color:#060c3b;line-height:70px;font-family:DM Serif Display;margin:0}.inline-col-wrapper .inline-col-content-wrapper .inline-col-description{margin:0;font-size:18px;color:#6e6e6e;font-family:Poppins}@media (max-width:991px){.inline-col-wrapper{grid-template-columns:1fr;grid-gap:16px}.inline-col-wrapper .inline-col-content-wrapper{gap:10px}} @media (max-width:768px){.inline-col-wrapper .inline-col-image-wrapper img {width:100%;} .inline-col-wrapper .inline-col-content-wrapper .inline-col-title{font-size:28px !important;line-height:normal !important}} @media (max-width:525px){.inline-col-wrapper .inline-col-content-wrapper .inline-col-title{font-size:20px !important;} .inline-col-description{font-size:16px} .inline-col-wrapper{grid-gap:10px}}\"}'),
('5', '5', 'ar', '{\"html\":\"<div class=\\\"section-game\\\">\\n                                <div class=\\\"section-title\\\">\\n                                    <h2>\\u0627\\u0644\\u0644\\u0639\\u0628\\u0629 \\u0645\\u0639 \\u0625\\u0636\\u0627\\u0641\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629!<\\/h2> \\n                                <\\/div>\\n\\n                                <div class=\\\"section-gap container\\\">\\n                                    <div class=\\\"collection-card-wrapper\\\">\\n                                        <div class=\\\"single-collection-card\\\">\\n                                            <a href=\\\"#active-wear\\\">\\n                                                <img src=\\\"\\\" data-src=\\\"storage\\/theme\\/8\\/04nhNab5y8zrFAfG7iuTSHlCBQbm4q4VYkMT4zuC.webp\\\" class=\\\"lazy\\\" width=\\\"615\\\" height=\\\"600\\\" alt=\\\"\\u0627\\u0644\\u0644\\u0639\\u0628\\u0629 \\u0645\\u0639 \\u0625\\u0636\\u0627\\u0641\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629!\\\">\\n                                                \\n                                                <h3 class=\\\"overlay-text\\\">\\u0645\\u062c\\u0645\\u0648\\u0639\\u0627\\u062a\\u0646\\u0627<\\/h3> \\n                                            <\\/a>\\n                                        <\\/div>\\n\\n                                        <div class=\\\"single-collection-card\\\">\\n                                            <a href=\\\"#active-wear-female\\\">\\n                                                <img src=\\\"\\\" data-src=\\\"storage\\/theme\\/8\\/s4zTv3PIiTbzTq73pXfZE01hNcx1r7aaERKjhGbM.webp\\\" class=\\\"lazy\\\" width=\\\"615\\\" height=\\\"600\\\" alt=\\\"\\u0627\\u0644\\u0644\\u0639\\u0628\\u0629 \\u0645\\u0639 \\u0625\\u0636\\u0627\\u0641\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629!\\\">\\n                                                \\n                                                <h3 class=\\\"overlay-text\\\"> \\u0645\\u062c\\u0645\\u0648\\u0639\\u0627\\u062a\\u0646\\u0627 <\\/h3> \\n                                            <\\/a>\\n                                        <\\/div>\\n                                    <\\/div>\\n                                <\\/div>\\n                            <\\/div>\",\"css\":\".section-game {overflow: hidden;}.section-title,.section-title h2{font-weight:400;font-family:DM Serif Display}.section-title{margin-top:80px;padding-left:15px;padding-right:15px;text-align:center;line-height:90px}.section-title h2{font-size:70px;color:#060c3b;max-width:595px;margin:auto}.collection-card-wrapper{display:flex;flex-wrap:wrap;justify-content:center;gap:30px}.collection-card-wrapper .single-collection-card{position:relative}.collection-card-wrapper .single-collection-card img{border-radius:16px;background-color:#f5f5f5;max-width:100%;height:auto;text-indent:-9999px}.collection-card-wrapper .single-collection-card .overlay-text{font-size:50px;font-weight:400;max-width:234px;font-style:italic;color:#060c3b;font-family:DM Serif Display;position:absolute;bottom:30px;left:30px;margin:0}@media (max-width:1024px){.section-title{padding:0 30px}}@media (max-width:991px){.collection-card-wrapper{flex-wrap:wrap}}@media (max-width:768px) {.collection-card-wrapper .single-collection-card .overlay-text{font-size:32px; bottom:20px}.section-title{margin-top:32px}.section-title h2{font-size:28px;line-height:normal}} @media (max-width:525px){.collection-card-wrapper .single-collection-card .overlay-text{font-size:18px; bottom:10px} .section-title{margin-top:28px}.section-title h2{font-size:20px;} .collection-card-wrapper{gap:10px; 15px; row-gap:15px; column-gap:0px;justify-content: space-between;margin-top: 15px;} .collection-card-wrapper .single-collection-card {width:48%;}}\"}'),
('6', '6', 'ar', '{\"html\":\"<div class=\\\"section-gap bold-collections container\\\">\\n                                <div class=\\\"inline-col-wrapper direction-rtl\\\">\\n                                    <div class=\\\"inline-col-image-wrapper\\\">\\n                                        <img src=\\\"\\\" data-src=\\\"storage\\/theme\\/10\\/ULWkEf6XKnoIFDGPW8i9FPohGkVq5l0hH7XJs2LE.webp\\\" class=\\\"lazy\\\" width=\\\"632\\\" height=\\\"510\\\" alt=\\\"\\u0623\\u0637\\u0644\\u0642 \\u062c\\u0631\\u0623\\u062a\\u0643 \\u0645\\u0639 \\u0645\\u062c\\u0645\\u0648\\u0639\\u062a\\u0646\\u0627 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629!\\\">\\n                                    <\\/div>\\n\\n                                    <div class=\\\"inline-col-content-wrapper direction-ltr\\\">\\n                                        <h2 class=\\\"inline-col-title\\\">\\u0623\\u0637\\u0644\\u0642 \\u062c\\u0631\\u0623\\u062a\\u0643 \\u0645\\u0639 \\u0645\\u062c\\u0645\\u0648\\u0639\\u062a\\u0646\\u0627 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\\u0629!<\\/h2> \\n                                        \\n                                        <p class=\\\"inline-col-description\\\">\\u0645\\u062c\\u0645\\u0648\\u0639\\u0627\\u062a\\u0646\\u0627 \\u0627\\u0644\\u062c\\u0631\\u064a\\u0626\\u0629 \\u0645\\u0648\\u062c\\u0648\\u062f\\u0629 \\u0647\\u0646\\u0627 \\u0644\\u0625\\u0639\\u0627\\u062f\\u0629 \\u062a\\u0639\\u0631\\u064a\\u0641 \\u062e\\u0632\\u0627\\u0646\\u062a\\u0643 \\u0628\\u062a\\u0635\\u0645\\u064a\\u0645\\u0627\\u062a \\u0634\\u062c\\u0627\\u0639\\u0629 \\u0648\\u0623\\u0644\\u0648\\u0627\\u0646 \\u0646\\u0627\\u0628\\u0636\\u0629 \\u0628\\u0627\\u0644\\u062d\\u064a\\u0627\\u0629. \\u0645\\u0646 \\u0627\\u0644\\u0623\\u0646\\u0645\\u0627\\u0637 \\u0627\\u0644\\u062c\\u0631\\u064a\\u0626\\u0629 \\u0625\\u0644\\u0649 \\u0627\\u0644\\u0623\\u0644\\u0648\\u0627\\u0646 \\u0627\\u0644\\u0642\\u0648\\u064a\\u0629\\u060c \\u0647\\u0630\\u0647 \\u0641\\u0631\\u0635\\u062a\\u0643 \\u0644\\u0644\\u0627\\u0628\\u062a\\u0639\\u0627\\u062f \\u0639\\u0646 \\u0627\\u0644\\u0645\\u0623\\u0644\\u0648\\u0641 \\u0648\\u0627\\u0644\\u062f\\u062e\\u0648\\u0644 \\u0625\\u0644\\u0649 \\u0627\\u0644\\u0627\\u0633\\u062a\\u062b\\u0646\\u0627\\u0626\\u064a.<\\/p>\\n                                        \\n                                        <a href=\\\"#electronics\\\">\\n                                            <button class=\\\"primary-button max-md:rounded-lg max-md:px-4 max-md:py-2.5 max-md:text-sm\\\">\\u0639\\u0631\\u0636 \\u0627\\u0644\\u0645\\u062c\\u0645\\u0648\\u0639\\u0627\\u062a<\\/button>\\n                                        <\\/a>\\n                                    <\\/div>\\n                                <\\/div>\\n                            <\\/div>\",\"css\":\".section-gap{margin-top:80px}.direction-ltr{direction:ltr}.direction-rtl{direction:rtl}.inline-col-wrapper{display:grid;grid-template-columns:auto 1fr;grid-gap:60px;align-items:center}.inline-col-wrapper .inline-col-image-wrapper{overflow:hidden}.inline-col-wrapper .inline-col-image-wrapper img{max-width:100%;height:auto;border-radius:16px;text-indent:-9999px}.inline-col-wrapper .inline-col-content-wrapper{display:flex;flex-wrap:wrap;gap:20px;max-width:464px}.inline-col-wrapper .inline-col-content-wrapper .inline-col-title{max-width:442px;font-size:60px;font-weight:400;color:#060c3b;line-height:70px;font-family:DM Serif Display;margin:0}.inline-col-wrapper .inline-col-content-wrapper .inline-col-description{margin:0;font-size:18px;color:#6e6e6e;font-family:Poppins}@media (max-width:991px){.inline-col-wrapper{grid-template-columns:1fr;grid-gap:16px}.inline-col-wrapper .inline-col-content-wrapper{gap:10px}}@media (max-width:768px) {.inline-col-wrapper .inline-col-image-wrapper img {max-width:100%;}.inline-col-wrapper .inline-col-content-wrapper{max-width:100%;justify-content:center; text-align:center} .section-gap{padding:0 30px; gap:20px;margin-top:24px} .bold-collections{margin-top:32px;}} @media (max-width:525px){.inline-col-wrapper .inline-col-content-wrapper{gap:10px} .inline-col-wrapper .inline-col-content-wrapper .inline-col-title{font-size:20px;line-height:normal} .section-gap{padding:0 15px; gap:15px;margin-top:10px} .bold-collections{margin-top:28px;}  .inline-col-description{font-size:16px !important} .inline-col-wrapper{grid-gap:15px}\"}'),
('7', '7', 'ar', '{\"column_1\":[{\"url\":\"https:\\/\\/zoologist-decathlon-eclair.ngrok-free.dev\\/page\\/about-us\",\"title\":\"\\u0645\\u0639\\u0644\\u0648\\u0645\\u0627\\u062a \\u0639\\u0646\\u0627\",\"sort_order\":1},{\"url\":\"https:\\/\\/zoologist-decathlon-eclair.ngrok-free.dev\\/contact-us\",\"title\":\"\\u0627\\u062a\\u0635\\u0644 \\u0628\\u0646\\u0627\",\"sort_order\":2},{\"url\":\"https:\\/\\/zoologist-decathlon-eclair.ngrok-free.dev\\/page\\/customer-service\",\"title\":\"\\u062e\\u062f\\u0645\\u0629 \\u0627\\u0644\\u0639\\u0645\\u0644\\u0627\\u0621\",\"sort_order\":3},{\"url\":\"https:\\/\\/zoologist-decathlon-eclair.ngrok-free.dev\\/page\\/whats-new\",\"title\":\"\\u0645\\u0627 \\u0627\\u0644\\u062c\\u062f\\u064a\\u062f\",\"sort_order\":4},{\"url\":\"https:\\/\\/zoologist-decathlon-eclair.ngrok-free.dev\\/page\\/terms-of-use\",\"title\":\"\\u0634\\u0631\\u0648\\u0637 \\u0627\\u0644\\u0627\\u0633\\u062a\\u062e\\u062f\\u0627\\u0645\",\"sort_order\":5},{\"url\":\"https:\\/\\/zoologist-decathlon-eclair.ngrok-free.dev\\/page\\/terms-conditions\",\"title\":\"\\u0627\\u0644\\u0634\\u0631\\u0648\\u0637 \\u0648\\u0627\\u0644\\u0623\\u062d\\u0643\\u0627\\u0645\",\"sort_order\":6}],\"column_2\":[{\"url\":\"https:\\/\\/zoologist-decathlon-eclair.ngrok-free.dev\\/page\\/privacy-policy\",\"title\":\"\\u0633\\u064a\\u0627\\u0633\\u0629 \\u0627\\u0644\\u062e\\u0635\\u0648\\u0635\\u064a\\u0629\",\"sort_order\":1},{\"url\":\"https:\\/\\/zoologist-decathlon-eclair.ngrok-free.dev\\/page\\/payment-policy\",\"title\":\"\\u0633\\u064a\\u0627\\u0633\\u0629 \\u0627\\u0644\\u062f\\u0641\\u0639\",\"sort_order\":2},{\"url\":\"https:\\/\\/zoologist-decathlon-eclair.ngrok-free.dev\\/page\\/shipping-policy\",\"title\":\"\\u0633\\u064a\\u0627\\u0633\\u0629 \\u0627\\u0644\\u0634\\u062d\\u0646\",\"sort_order\":3},{\"url\":\"https:\\/\\/zoologist-decathlon-eclair.ngrok-free.dev\\/page\\/refund-policy\",\"title\":\"\\u0633\\u064a\\u0627\\u0633\\u0629 \\u0627\\u0644\\u0627\\u0633\\u062a\\u0631\\u062f\\u0627\\u062f\",\"sort_order\":4},{\"url\":\"https:\\/\\/zoologist-decathlon-eclair.ngrok-free.dev\\/page\\/return-policy\",\"title\":\"\\u0633\\u064a\\u0627\\u0633\\u0629 \\u0627\\u0644\\u0625\\u0631\\u062c\\u0627\\u0639\",\"sort_order\":5}]}'),
('8', '8', 'ar', '{\"services\":[{\"title\":\"\\u0627\\u0644\\u0634\\u062d\\u0646 \\u0627\\u0644\\u0645\\u062c\\u0627\\u0646\\u064a\",\"description\":\"\\u0627\\u0633\\u062a\\u0645\\u062a\\u0639 \\u0628\\u0627\\u0644\\u0634\\u062d\\u0646 \\u0627\\u0644\\u0645\\u062c\\u0627\\u0646\\u064a \\u0639\\u0644\\u0649 \\u062c\\u0645\\u064a\\u0639 \\u0627\\u0644\\u0637\\u0644\\u0628\\u0627\\u062a\",\"service_icon\":\"icon-truck\"},{\"title\":\"\\u0627\\u0633\\u062a\\u0628\\u062f\\u0627\\u0644 \\u0627\\u0644\\u0645\\u0646\\u062a\\u062c\",\"description\":\"\\u0627\\u0633\\u062a\\u0628\\u062f\\u0627\\u0644 \\u0627\\u0644\\u0645\\u0646\\u062a\\u062c \\u0628\\u0633\\u0647\\u0648\\u0644\\u0629 \\u0645\\u062a\\u0627\\u062d!\",\"service_icon\":\"icon-product\"},{\"title\":\"\\u062a\\u0648\\u0641\\u0631 EMI\",\"description\":\"\\u062a\\u0648\\u0641\\u0631 EMI \\u0628\\u062f\\u0648\\u0646 \\u062a\\u0643\\u0644\\u0641\\u0629 \\u0639\\u0644\\u0649 \\u062c\\u0645\\u064a\\u0639 \\u0628\\u0637\\u0627\\u0642\\u0627\\u062a \\u0627\\u0644\\u0627\\u0626\\u062a\\u0645\\u0627\\u0646 \\u0627\\u0644\\u0631\\u0626\\u064a\\u0633\\u064a\\u0629\",\"service_icon\":\"icon-dollar-sign\"},{\"title\":\"\\u0627\\u0644\\u062f\\u0639\\u0645 \\u0639\\u0644\\u0649 \\u0645\\u062f\\u0627\\u0631 \\u0627\\u0644\\u0633\\u0627\\u0639\\u0629\",\"description\":\"\\u062f\\u0639\\u0645 \\u0645\\u062e\\u0635\\u0635 \\u0639\\u0644\\u0649 \\u0645\\u062f\\u0627\\u0631 \\u0627\\u0644\\u0633\\u0627\\u0639\\u0629 \\u0639\\u0628\\u0631 \\u0627\\u0644\\u062f\\u0631\\u062f\\u0634\\u0629 \\u0648\\u0627\\u0644\\u0628\\u0631\\u064a\\u062f \\u0627\\u0644\\u0625\\u0644\\u0643\\u062a\\u0631\\u0648\\u0646\\u064a\",\"service_icon\":\"icon-support\"}]}');

-- ----------------------------------------------
-- Table structure for `theme_customizations`
-- ----------------------------------------------
DROP TABLE IF EXISTS `theme_customizations`;
CREATE TABLE `theme_customizations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `theme_code` varchar(255) DEFAULT 'default',
  `type` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `channel_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `theme_customizations_channel_id_foreign` (`channel_id`),
  CONSTRAINT `theme_customizations_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for `theme_customizations` (8 rows)
INSERT INTO `theme_customizations` (`id`, `theme_code`, `type`, `name`, `sort_order`, `status`, `channel_id`, `created_at`, `updated_at`) VALUES
('1', 'default', 'image_carousel', 'عرض الصور', '1', '1', '1', '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('2', 'default', 'static_content', 'معلومات العرض', '2', '1', '1', '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('3', 'default', 'static_content', 'أفضل المجموعات', '5', '1', '1', '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('4', 'default', 'static_content', 'مجموعات بارزة', '6', '1', '1', '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('5', 'default', 'static_content', 'حاوية اللعبة', '8', '1', '1', '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('6', 'default', 'static_content', 'مجموعات بارزة', '10', '1', '1', '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('7', 'default', 'footer_links', 'روابط الذيل', '11', '1', '1', '2026-07-19 00:21:51', '2026-07-19 00:21:51'),
('8', 'default', 'services_content', 'محتوى الخدمات', '12', '1', '1', '2026-07-19 00:21:51', '2026-07-19 00:21:51');

-- ----------------------------------------------
-- Table structure for `url_rewrites`
-- ----------------------------------------------
DROP TABLE IF EXISTS `url_rewrites`;
CREATE TABLE `url_rewrites` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(255) NOT NULL,
  `request_path` varchar(255) NOT NULL,
  `target_path` varchar(255) NOT NULL,
  `redirect_type` varchar(255) DEFAULT NULL,
  `locale` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `url_rewrites_et_rp_lc_idx` (`entity_type`,`request_path`,`locale`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `users`
-- ----------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `wishlist`
-- ----------------------------------------------
DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE `wishlist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `item_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`item_options`)),
  `moved_to_cart` date DEFAULT NULL,
  `shared` tinyint(1) DEFAULT NULL,
  `time_of_moving` date DEFAULT NULL,
  `additional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wishlist_channel_id_foreign` (`channel_id`),
  KEY `wishlist_product_id_foreign` (`product_id`),
  KEY `wishlist_customer_id_foreign` (`customer_id`),
  CONSTRAINT `wishlist_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------
-- Table structure for `wishlist_items`
-- ----------------------------------------------
DROP TABLE IF EXISTS `wishlist_items`;
CREATE TABLE `wishlist_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `additional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional`)),
  `moved_to_cart` date DEFAULT NULL,
  `shared` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wishlist_items_channel_id_foreign` (`channel_id`),
  KEY `wishlist_items_product_id_foreign` (`product_id`),
  KEY `wishlist_items_customer_id_foreign` (`customer_id`),
  CONSTRAINT `wishlist_items_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_items_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
