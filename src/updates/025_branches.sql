-- The main shop's address, phone and map position always come from Admin > Settings > Shop details
-- (branches() reads them over its row), and an empty branch email means the order notification
-- email in Settings > Emails, so nothing is kept in two places.
--
-- Two shops, one website. Every order, custom cake request and conversation belongs to a branch.
-- The shop that already exists becomes branch 1 (its details are copied from Settings), so every
-- order placed before this update stays with it. A staff account with a branch only sees that
-- branch; an empty branch means every branch (the owner).

CREATE TABLE IF NOT EXISTS `branches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(60) NOT NULL COMMENT 'Web address of the branch page: /locations/{slug}',
  `name` varchar(80) NOT NULL COMMENT 'Short name customers see, e.g. Greenway',
  `street` varchar(120) NOT NULL DEFAULT '',
  `suburb` varchar(80) NOT NULL DEFAULT '',
  `state` varchar(10) NOT NULL DEFAULT 'ACT',
  `postcode` varchar(10) NOT NULL DEFAULT '',
  `phone` varchar(40) NOT NULL DEFAULT '',
  `email` varchar(190) NOT NULL DEFAULT '' COMMENT 'Orders, custom cake requests and messages for this branch are emailed here',
  `latitude` varchar(12) NOT NULL DEFAULT '',
  `longitude` varchar(12) NOT NULL DEFAULT '',
  `maps_url` varchar(500) NOT NULL DEFAULT '' COMMENT 'Google Business Profile or Google Maps link',
  `reviews_url` varchar(500) NOT NULL DEFAULT '' COMMENT 'Google review link of this branch, used in the review request email',
  `intro` text DEFAULT NULL COMMENT 'Text on the branch page',
  `photo` varchar(255) DEFAULT NULL COMMENT 'Path inside public/uploads',
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Shown to customers',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_branches_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `branches` (`id`, `slug`, `name`, `street`, `suburb`, `state`, `postcode`, `phone`, `email`, `latitude`, `longitude`, `reviews_url`, `is_main`, `is_active`, `sort_order`)
SELECT 1, 'greenway',
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'store_suburb'), ''), 'Greenway'),
       COALESCE((SELECT setting_value FROM settings WHERE setting_key = 'store_street'), ''),
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'store_suburb'), ''), 'Greenway'),
       COALESCE(NULLIF((SELECT setting_value FROM settings WHERE setting_key = 'store_state'), ''), 'ACT'),
       COALESCE((SELECT setting_value FROM settings WHERE setting_key = 'store_postcode'), ''),
       COALESCE((SELECT setting_value FROM settings WHERE setting_key = 'store_phone'), ''),
       '',
       COALESCE((SELECT setting_value FROM settings WHERE setting_key = 'store_latitude'), ''),
       COALESCE((SELECT setting_value FROM settings WHERE setting_key = 'store_longitude'), ''),
       COALESCE((SELECT setting_value FROM settings WHERE setting_key = 'google_reviews_url'), ''),
       1, 1, 1;

-- The Wright shop starts hidden: fill in its street, phone and email in Admin > Branches, then switch it on.
INSERT IGNORE INTO `branches` (`id`, `slug`, `name`, `street`, `suburb`, `state`, `postcode`, `latitude`, `longitude`, `is_main`, `is_active`, `sort_order`)
VALUES (2, 'wright', 'Wright', '', 'Wright', 'ACT', '2611', '-35.3222', '149.0347', 0, 0, 2);

ALTER TABLE `orders` ADD COLUMN `branch_id` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'The shop that makes and hands over the order' AFTER `id`;
ALTER TABLE `orders` ADD KEY `idx_orders_branch` (`branch_id`, `pickup_date`);
ALTER TABLE `custom_requests` ADD COLUMN `branch_id` int(10) unsigned NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `custom_requests` ADD KEY `idx_custom_branch` (`branch_id`, `status`);
ALTER TABLE `threads` ADD COLUMN `branch_id` int(10) unsigned NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `threads` ADD KEY `idx_threads_branch` (`branch_id`, `status`);
ALTER TABLE `admins` ADD COLUMN `branch_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL = every branch' AFTER `role`;
