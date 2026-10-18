-- The Continental Cake Shop Canberra: full database (tables + menu, settings, pages, gallery).
-- Import this ONE file in cPanel > phpMyAdmin > your database > Import.
-- It replaces existing tables of the same name, so only use it for a new install.
-- Already live? Do not import this again; run database updates from Admin > System check.


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `admin_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `fk_admin_resets_admin` (`admin_id`),
  CONSTRAINT `fk_admin_resets_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `admin_password_resets` WRITE;
/*!40000 ALTER TABLE `admin_password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_password_resets` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('owner','staff') NOT NULL DEFAULT 'staff',
  `permissions` text DEFAULT NULL COMMENT 'JSON list of admin sections a staff account may open; NULL = all',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `failed_logins` int(10) unsigned NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `blocked_dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blocked_dates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `blocked_date` date NOT NULL,
  `reason` varchar(160) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blocked_date` (`blocked_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `blocked_dates` WRITE;
/*!40000 ALTER TABLE `blocked_dates` DISABLE KEYS */;
/*!40000 ALTER TABLE `blocked_dates` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `meta_title` varchar(160) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_categories_active` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Tortes','tortes','Light layered sponge tortes filled with cream, fruit and our house fillings. A favourite for birthdays and family get-togethers.','categories/tortes.webp',1,1,'Tortes in Canberra | The Continental Cake Shop','Order layered sponge tortes online in Canberra: Black Forest, Lotus Biscoff, Raffaello, Tiramisu and more. Pick up in Greenway.','2026-10-18 13:35:43','2026-10-18 13:35:43'),
(2,'Velvet Cakes','velvet-cakes','Soft velvet sponge layered with smooth cream cheese frosting, in four flavours.','categories/velvet-cakes.webp',2,1,'Red Velvet & Velvet Cakes Canberra | The Continental Cake Shop','Red velvet, pistachio, blueberry and orange mango velvet cakes, made fresh in Greenway, Canberra. Order online for pickup.','2026-10-18 13:35:43','2026-10-18 13:35:43'),
(3,'Mud Cakes','mud-cakes','Dense, moist mud cakes that hold up well for decorating and travel well to parties.','categories/mud-cakes.webp',3,1,'Mud Cakes Canberra | The Continental Cake Shop','Chocolate, white gold, marble and caramel mud cakes made fresh in Canberra. Order online and pick up in Greenway.','2026-10-18 13:35:43','2026-10-18 13:35:43'),
(4,'Baked Cheesecakes','baked-cheesecakes','Classic oven-baked cheesecakes on a biscuit base. Available in full and half sizes.','categories/baked-cheesecakes.webp',4,1,'Baked Cheesecakes Canberra | The Continental Cake Shop','American, blueberry, marble, tropical and hazelnut baked cheesecakes. Full and half sizes, ready for pickup in Greenway.','2026-10-18 13:35:43','2026-10-18 13:35:43'),
(5,'Continental Cheesecakes','continental-cheesecakes','Our light, set continental-style cheesecakes in eight flavours. Full and half sizes.','categories/continental-cheesecakes.webp',5,1,'Continental Cheesecakes Canberra | The Continental Cake Shop','Light continental cheesecakes in eight flavours including pistachio, passionfruit and tangy lemon. Order online in Canberra.','2026-10-18 13:35:43','2026-10-18 13:35:43'),
(6,'Tea Cakes & Desserts','tea-cakes-desserts','Carrot cake, pavlova, profiterole towers and more for morning tea and dessert tables.','categories/tea-cakes-desserts.webp',6,1,'Tea Cakes & Desserts Canberra | The Continental Cake Shop','Carrot cake, pavlova and profiterole towers from The Continental Cake Shop in Greenway, Canberra.','2026-10-18 13:35:43','2026-10-18 13:35:43');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `replied_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `description` varchar(160) DEFAULT NULL,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '0 = any order',
  `max_uses` int(10) unsigned DEFAULT NULL COMMENT 'NULL = unlimited',
  `used_count` int(10) unsigned NOT NULL DEFAULT 0,
  `starts_at` date DEFAULT NULL,
  `expires_at` date DEFAULT NULL COMMENT 'Last day the code works; NULL = no expiry',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `custom_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) NOT NULL,
  `occasion` varchar(60) NOT NULL,
  `event_date` date NOT NULL,
  `fulfilment` varchar(20) DEFAULT NULL,
  `servings` varchar(40) DEFAULT NULL,
  `flavour` varchar(120) DEFAULT NULL,
  `budget` varchar(40) DEFAULT NULL,
  `details` text NOT NULL,
  `reference_image` varchar(255) DEFAULT NULL COMMENT 'Path inside storage/uploads',
  `status` enum('new','quoted','confirmed','closed') NOT NULL DEFAULT 'new',
  `quoted_price` decimal(10,2) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_custom_status` (`status`,`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `custom_requests` WRITE;
/*!40000 ALTER TABLE `custom_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `custom_requests` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(60) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_faqs_question` (`question`),
  KEY `idx_faqs_active` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES
(1,'Ordering','How far in advance should I order?','<p>Please order at least {min_lead_hours} hours ahead for cakes from our menu, and at least {custom_lead_days} days ahead for custom cakes. For weddings and big events, the earlier the better.</p>',1,1),
(2,'Ordering','I need a cake today. Can you help?','<p>Please call us on {store_phone} and we will let you know what we can do.</p>',1,2),
(3,'Ordering','Can I add a message on my cake?','<p>Yes, a message is free on every cake. Add it on the cake\'s page before adding it to your cart.</p>',1,3),
(4,'Ordering','Can I change the size after adding a cake to my cart?','<p>Yes. Open your cart and choose a different size from the dropdown next to the cake.</p>',1,4),
(5,'Pickup and delivery','Where do I collect my order?','<p>From our shop at {store_address}. You choose the pickup date and time at checkout.</p>',1,5),
(6,'Pickup and delivery','Can I change my pickup time?','<p>Please call us on {store_phone} as early as possible and we will do our best to help.</p>',1,6),
(7,'Pickup and delivery','Do you deliver?','<p>We deliver across the ACT, Queanbeyan and surrounding areas. Call us on {store_phone} to arrange delivery.</p>',1,7),
(8,'Payment','How can I pay?','<p>You can pay online at checkout. The payment options shown at checkout are the ones currently available.</p>',1,8),
(9,'Payment','When is my order confirmed?','<p>Card payments are confirmed straight away. For PayID and bank transfer, we confirm your order once we have checked your payment screenshot.</p>',1,9),
(10,'Dietary needs','Are your cakes Halal?','<p>Yes, all our products are made with 100% Halal ingredients.</p>',1,10),
(11,'Dietary needs','Do you make gluten-free, eggless or vegan cakes?','<p>Yes, on request. Please call us before you order so we can confirm the options for your cake.</p>',1,11),
(12,'Dietary needs','I have a food allergy. Is it safe to order?','<p>Our kitchen handles common allergens, so we cannot guarantee any cake is free from traces. Please read our <a href=\"{url:allergens}\">allergen information</a> and call us before ordering.</p>',1,12),
(13,'Custom cakes','How do I order a custom cake?','<p>Fill in our <a href=\"{url:custom-cakes}\">custom cake form</a>, call us, or visit the shop. We will reply with a quote and help you choose the size, flavour and decorations.</p>',1,13),
(14,'Custom cakes','Can you make a cake from a photo?','<p>Yes. Upload a photo with your request and we will match the design as closely as we can.</p>',1,14),
(15,'Cancellations','Can I cancel my order?','<p>Please contact us as early as possible. See our <a href=\"{url:refund-policy}\">Refund and Cancellation Policy</a> for details.</p>',1,15);
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `gallery_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gallery_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `image` varchar(255) NOT NULL COMMENT 'Path inside public/uploads',
  `caption` varchar(160) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gallery_active` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `gallery_images` WRITE;
/*!40000 ALTER TABLE `gallery_images` DISABLE KEYS */;
INSERT INTO `gallery_images` VALUES
(1,'gallery/custom-01.webp','Pink and cream drip birthday cake with macarons',1,1,'2026-10-18 13:35:43'),
(2,'gallery/custom-02.webp','Black Happy Birthday cake with fresh fruit and macarons',1,2,'2026-10-18 13:35:43'),
(3,'gallery/custom-03.webp','Two-tier Spider-Man birthday cake',1,3,'2026-10-18 13:35:43'),
(4,'gallery/custom-04.webp','Three-tier wedding cake with fresh roses',1,4,'2026-10-18 13:35:43'),
(5,'gallery/custom-05.webp','Blippi themed birthday cake',1,5,'2026-10-18 13:35:43'),
(6,'gallery/custom-06.webp','Blue starry birthday cake with name topper',1,6,'2026-10-18 13:35:43'),
(7,'gallery/custom-07.webp','Three-tier ivory wedding cake with piped detail',1,7,'2026-10-18 13:35:43'),
(8,'gallery/custom-08.webp','Two-tier white cake with cream roses',1,8,'2026-10-18 13:35:43'),
(9,'gallery/custom-09.webp','Pink vintage heart cake',1,9,'2026-10-18 13:35:43'),
(10,'gallery/custom-10.webp','Chocolate drip cake with flowers and macarons',1,10,'2026-10-18 13:35:43'),
(11,'gallery/custom-11.webp','Soccer field cake',1,11,'2026-10-18 13:35:43'),
(12,'gallery/custom-12.webp','Blue vintage heart cake',1,12,'2026-10-18 13:35:43'),
(13,'gallery/custom-13.webp','Teal drip cake with pink rosettes',1,13,'2026-10-18 13:35:43'),
(14,'gallery/custom-14.webp','Cream drip birthday cake with yellow flowers',1,14,'2026-10-18 13:35:43'),
(15,'gallery/custom-15.webp','Bluey edible image cake',1,15,'2026-10-18 13:35:43'),
(16,'gallery/custom-16.webp','Soccer themed 11th birthday cake',1,16,'2026-10-18 13:35:43'),
(17,'gallery/custom-17.webp','White drip cake with red roses',1,17,'2026-10-18 13:35:43'),
(18,'gallery/custom-18.webp','Two-tier Super Mario cake',1,18,'2026-10-18 13:35:43'),
(19,'gallery/custom-19.webp','Unicorn cake with rainbow mane',1,19,'2026-10-18 13:35:43'),
(20,'gallery/custom-20.webp','Textured white cake with gold heart topper',1,20,'2026-10-18 13:35:43'),
(21,'gallery/custom-21.webp','Spider-Man web cake',1,21,'2026-10-18 13:35:43'),
(22,'gallery/custom-22.webp','Two-tier green rosette cake',1,22,'2026-10-18 13:35:43'),
(23,'gallery/custom-23.webp','Coral rosette cake',1,23,'2026-10-18 13:35:43'),
(24,'gallery/custom-24.webp','Two-tier first birthday cake',1,24,'2026-10-18 13:35:43'),
(25,'gallery/custom-25.webp','Minnie Mouse edible image cake',1,25,'2026-10-18 13:35:43'),
(26,'gallery/custom-26.webp','Graduation cake',1,26,'2026-10-18 13:35:43'),
(27,'gallery/custom-27.webp','Three-tier white cake with fresh flowers',1,27,'2026-10-18 13:35:43'),
(28,'gallery/custom-28.webp','Spider-Man edible image cake',1,28,'2026-10-18 13:35:43'),
(29,'gallery/custom-29.webp','Two-tier pink rosette cake',1,29,'2026-10-18 13:35:43');
/*!40000 ALTER TABLE `gallery_images` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `order_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `event` varchar(40) NOT NULL,
  `message` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_events_order` (`order_id`,`created_at`),
  KEY `fk_events_admin` (`admin_id`),
  CONSTRAINT `fk_events_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_events_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `order_events` WRITE;
/*!40000 ALTER TABLE `order_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_events` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `size_id` int(10) unsigned DEFAULT NULL,
  `product_name` varchar(160) NOT NULL,
  `size_label` varchar(60) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `cake_message` varchar(60) DEFAULT NULL,
  `line_total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_items_order` (`order_id`),
  KEY `fk_items_product` (`product_id`),
  KEY `fk_items_size` (`size_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_items_size` FOREIGN KEY (`size_id`) REFERENCES `product_sizes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(30) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `access_token` char(32) NOT NULL,
  `customer_name` varchar(120) NOT NULL,
  `customer_email` varchar(190) NOT NULL,
  `customer_phone` varchar(40) NOT NULL,
  `fulfilment` enum('pickup','delivery') NOT NULL DEFAULT 'pickup',
  `delivery_address` varchar(255) DEFAULT NULL,
  `delivery_suburb` varchar(80) DEFAULT NULL,
  `delivery_postcode` varchar(4) DEFAULT NULL,
  `delivery_window` varchar(60) DEFAULT NULL COMMENT 'Customer preference; the shop sets the exact time',
  `pickup_date` date NOT NULL,
  `pickup_time` time DEFAULT NULL COMMENT 'Pickup time, or the delivery time once the shop sets it',
  `delivery_time_to` time DEFAULT NULL COMMENT 'End of the delivery time range',
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code` varchar(40) DEFAULT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `payment_status` enum('pending','awaiting_verification','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `order_status` enum('new','confirmed','in_progress','ready','completed','cancelled') NOT NULL DEFAULT 'new',
  `payment_reference` varchar(190) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL COMMENT 'Path inside storage/uploads',
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_orders_pickup` (`pickup_date`,`pickup_time`),
  KEY `idx_orders_status` (`order_status`,`payment_status`),
  KEY `idx_orders_stripe` (`stripe_session_id`),
  KEY `idx_orders_user` (`user_id`,`created_at`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(80) NOT NULL,
  `title` varchar(160) NOT NULL,
  `summary` varchar(255) DEFAULT NULL,
  `body` mediumtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `meta_title` varchar(160) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES
(1,'privacy-policy','Privacy Policy','How we collect, use and protect your personal information.','<p>{store_name} (\"we\", \"us\") respects your privacy. This policy explains what personal information we collect when you use our website or place an order, and how we handle it in line with the Australian Privacy Principles in the <em>Privacy Act 1988</em> (Cth).</p>\n<h2>Information we collect</h2>\n<ul>\n<li><strong>Contact details:</strong> your name, email address and phone number when you place an order, request a custom cake quote, send us a message or create an account.</li>\n<li><strong>Order details:</strong> the cakes you order, messages for your cake, pickup or delivery date and time, delivery address (if used) and any notes you add.</li>\n<li><strong>Payment information:</strong> card payments are processed by Stripe. We never see or store your full card number. If you pay by PayID or bank transfer, we keep the payment screenshot or receipt you upload.</li>\n<li><strong>Photos you send us:</strong> reference images uploaded with a custom cake request.</li>\n<li><strong>Account details:</strong> if you create an account, we store your details and a securely encrypted version of your password.</li>\n</ul>\n<h2>How we use your information</h2>\n<ul>\n<li>To prepare your order and have it ready at the time you chose.</li>\n<li>To contact you about your order, your custom cake quote or your enquiry.</li>\n<li>To check payments and keep records required for tax and accounting.</li>\n<li>To show your order history when you log in to your account.</li>\n</ul>\n<p>We do not sell your personal information, and we do not send marketing emails unless you ask us to.</p>\n<h2>Who we share it with</h2>\n<p>We only share information with the services we need to run the shop, such as our payment provider (Stripe), our website host and our email provider. They may only use it to provide their service to us.</p>\n<h2>Cookies</h2>\n<p>Our website uses a small session cookie so your cart and login work while you browse. We do not use advertising cookies.</p>\n<h2>Keeping your information safe</h2>\n<p>We store your information on secure servers and limit access to staff who need it. Payment screenshots and reference photos are kept in a private area that is not publicly accessible.</p>\n<h2>Accessing or correcting your information</h2>\n<p>You can update your details from your account page, or contact us to see, correct or delete the information we hold about you.</p>\n<h2>Questions or complaints</h2>\n<p>Please contact us on {store_phone} or {store_email}. If you are not happy with our response, you can contact the Office of the Australian Information Commissioner at <a href=\"https://www.oaic.gov.au\" target=\"_blank\" rel=\"noopener\">oaic.gov.au</a>.</p>',1,NULL,NULL,'2026-10-18 13:35:43'),
(2,'terms','Terms and Conditions','The terms that apply when you order from our website.','<p>These terms apply when you place an order with {store_name} through this website. By placing an order you agree to them.</p>\n<h2>Orders</h2>\n<ul>\n<li>Please order at least {min_lead_hours} hours before your pickup time for cakes from our menu, and at least {custom_lead_days} days ahead for custom cakes.</li>\n<li>Your order is confirmed once payment has been received. For PayID and bank transfer, this is after we have checked your payment screenshot.</li>\n<li>We will contact you if we cannot fulfil your order as placed, for example if an ingredient is unavailable.</li>\n</ul>\n<h2>Prices and payment</h2>\n<ul>\n<li>All prices are in Australian dollars.</li>\n<li>Prices shown at checkout are the prices you pay. Custom cakes are priced by quote.</li>\n</ul>\n<h2>Pickup</h2>\n<ul>\n<li>Please collect your order from {store_address} at the time you selected. If you need to change the time, call us on {store_phone} as early as possible.</li>\n<li>Once collected, please keep cream and cheesecake products refrigerated and transport them on a flat surface.</li>\n</ul>\n<h2>Our cakes</h2>\n<ul>\n<li>Every cake is handmade, so colours, decorations and shapes may differ slightly from photos on our website.</li>\n<li>For custom cakes, we will match your design as closely as we can. Characters and printed images may be adapted.</li>\n<li>Our products are made in a kitchen that handles common allergens. Please read our <a href=\"{url:allergens}\">allergen information</a> before ordering.</li>\n</ul>\n<h2>Cancellations and refunds</h2>\n<p>Please see our <a href=\"{url:refund-policy}\">Refund and Cancellation Policy</a>.</p>\n<h2>Your rights</h2>\n<p>Nothing in these terms limits your rights under the Australian Consumer Law.</p>\n<h2>Contact</h2>\n<p>{store_name}, {store_address}. Phone {store_phone}, email {store_email}.</p>',1,NULL,NULL,'2026-10-18 13:35:43'),
(3,'refund-policy','Refund and Cancellation Policy','What to do if you need to cancel or have a problem with your order.','<p>We want you to love your cake. This policy explains what happens if you need to cancel or if something is not right with your order.</p>\n<h2>Cancelling or changing an order</h2>\n<p>Please contact us on {store_phone} as early as possible if you need to cancel or change your order. Because every cake is made to order, we may not be able to offer a refund once we have started preparing your cake, buying special ingredients or making decorations.</p>\n<h2>Custom cakes</h2>\n<p>Custom cakes take extra preparation. Any cancellation terms for your custom cake will be explained when we send your quote.</p>\n<h2>If something is wrong</h2>\n<p>If your order is not what you ordered, is damaged when you collect it, or there is a problem with quality, please let us know straight away, ideally before you leave the shop or on the same day, and keep the cake if you can. We will work with you to fix it with a replacement, a credit or a refund.</p>\n<h2>Refunds</h2>\n<p>Approved refunds are returned to the original payment method. Card refunds usually appear within 5 to 10 business days, depending on your bank.</p>\n<h2>Your rights</h2>\n<p>Our products come with guarantees that cannot be excluded under the Australian Consumer Law. This policy does not limit those rights.</p>',1,NULL,NULL,'2026-10-18 13:35:43'),
(4,'allergens','Allergen Information','Important information for customers with food allergies or intolerances.','<p>Your safety matters to us. Please read this before ordering if you or your guests have a food allergy or intolerance.</p>\n<h2>Our kitchen</h2>\n<p>Our cakes are made in a kitchen that handles common allergens, including milk, eggs, wheat (gluten), soy and tree nuts such as hazelnuts, almonds and pistachios. Because we use shared equipment and work areas, <strong>we cannot guarantee that any product is completely free from traces of allergens</strong>.</p>\n<h2>Gluten-free, eggless and vegan cakes</h2>\n<p>We can make gluten-free, eggless and vegan options on request. Please call us on {store_phone} before ordering so we can confirm what is possible for your cake. These cakes are made in the same kitchen, so they may still contain traces of other allergens.</p>\n<h2>Halal</h2>\n<p>All our products are made with 100% Halal ingredients.</p>\n<h2>Ask us</h2>\n<p>If you have any questions about ingredients, please call us or ask in store before you order. Add any allergy details to the notes at checkout so our team can see them.</p>',1,NULL,NULL,'2026-10-18 13:35:43');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `fk_resets_user` (`user_id`),
  CONSTRAINT `fk_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `product_sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_sizes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `label` varchar(60) NOT NULL,
  `servings` varchar(60) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_sizes_product` (`product_id`,`is_active`,`sort_order`),
  CONSTRAINT `fk_sizes_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `product_sizes` WRITE;
/*!40000 ALTER TABLE `product_sizes` DISABLE KEYS */;
INSERT INTO `product_sizes` VALUES
(1,1,'Midi','Serves 8',44.95,1,1),
(2,1,'Large','Serves 14',54.95,2,1),
(3,2,'Large','Serves 14',64.95,2,1),
(4,3,'Midi','Serves 8',39.95,1,1),
(5,3,'Large','Serves 14',49.95,2,1),
(6,4,'Large','Serves 14',49.95,2,1),
(7,5,'Midi','Serves 8',49.95,1,1),
(8,5,'Large','Serves 14',59.95,2,1),
(9,6,'Large','Serves 14',64.95,2,1),
(10,7,'Midi','Serves 8',44.95,1,1),
(11,7,'Large','Serves 14',54.95,2,1),
(12,8,'Large','Serves 14',64.95,2,1),
(13,9,'Midi','Serves 8',39.95,1,1),
(14,9,'Large','Serves 14',49.95,2,1),
(15,10,'Large','Serves 14',54.95,2,1),
(16,11,'Midi','Serves 8',59.95,1,1),
(17,11,'Large','Serves 14',69.95,2,1),
(18,12,'Midi','Serves 8',59.95,1,1),
(19,12,'Large','Serves 14',69.95,2,1),
(20,13,'Midi','Serves 8',39.95,1,1),
(21,13,'Large','Serves 14',49.95,2,1),
(22,14,'Midi','Serves 8',39.95,1,1),
(23,14,'Large','Serves 14',49.95,2,1),
(24,15,'Midi','Serves 8',59.95,1,1),
(25,15,'Large','Serves 14',69.95,2,1),
(26,16,'Midi','Serves 8',59.95,1,1),
(27,16,'Large','Serves 14',69.95,2,1),
(28,17,'Midi','Serves 8',59.95,1,1),
(29,17,'Large','Serves 14',69.95,2,1),
(30,18,'Midi','Serves 8',59.95,1,1),
(31,18,'Large','Serves 14',69.95,2,1),
(32,19,'Midi','Serves 8',59.95,1,1),
(33,19,'Large','Serves 14',69.95,2,1),
(34,20,'Midi','Serves 8',39.95,1,1),
(35,20,'Large','Serves 14',49.95,2,1),
(36,21,'Midi','Serves 8',39.95,1,1),
(37,21,'Large','Serves 14',49.95,2,1),
(38,22,'Midi','Serves 8',39.95,1,1),
(39,22,'Large','Serves 14',49.95,2,1),
(40,23,'Midi','Serves 8',39.95,1,1),
(41,23,'Large','Serves 14',49.95,2,1),
(42,24,'Midi','Serves 8',59.95,1,1),
(43,24,'Large','Serves 14',69.95,2,1),
(44,25,'Midi','Serves 8',22.95,1,1),
(45,25,'Large','Serves 14',39.95,2,1),
(46,26,'Midi','Serves 8',22.95,1,1),
(47,26,'Large','Serves 14',39.95,2,1),
(48,27,'Midi','Serves 8',22.95,1,1),
(49,27,'Large','Serves 14',39.95,2,1),
(50,28,'Midi','Serves 8',22.95,1,1),
(51,28,'Large','Serves 14',39.95,2,1),
(52,29,'Midi','Serves 8',24.95,1,1),
(53,29,'Large','Serves 14',44.95,2,1),
(54,30,'Midi','Serves 8',22.95,1,1),
(55,30,'Large','Serves 14',39.95,2,1),
(56,31,'Midi','Serves 8',22.95,1,1),
(57,31,'Large','Serves 14',39.95,2,1),
(58,32,'Midi','Serves 8',22.95,1,1),
(59,32,'Large','Serves 14',39.95,2,1),
(60,33,'Midi','Serves 8',22.95,1,1),
(61,33,'Large','Serves 14',39.95,2,1),
(62,34,'Midi','Serves 8',24.95,1,1),
(63,34,'Large','Serves 14',44.95,2,1),
(64,35,'Midi','Serves 8',22.95,1,1),
(65,35,'Large','Serves 14',39.95,2,1),
(66,36,'Midi','Serves 8',22.95,1,1),
(67,36,'Large','Serves 14',39.95,2,1),
(68,37,'Midi','Serves 8',24.95,1,1),
(69,37,'Large','Serves 14',44.95,2,1),
(70,38,'Midi','Serves 8',34.95,1,1),
(71,38,'Large','Serves 14',44.95,2,1),
(72,39,'Midi','Serves 8',34.95,1,1),
(73,39,'Large','Serves 14',44.95,2,1),
(74,40,'Midi','Serves 8',39.95,1,1),
(79,2,'Midi','Serves 8',54.95,1,1),
(80,4,'Midi','Serves 8',39.95,1,1),
(81,6,'Midi','Serves 8',54.95,1,1),
(82,8,'Midi','Serves 8',54.95,1,1),
(83,10,'Midi','Serves 8',44.95,1,1),
(86,40,'Large','Serves 14',49.95,2,1),
(87,41,'Midi','Serves 8',34.95,1,1),
(88,41,'Large','Serves 14',44.95,2,1);
/*!40000 ALTER TABLE `product_sizes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `name` varchar(160) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `badge` varchar(40) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `allow_message` tinyint(1) NOT NULL DEFAULT 1,
  `is_enquiry` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Priced on request: shows an enquiry button instead of add to cart',
  `lead_time_hours` int(10) unsigned DEFAULT NULL COMMENT 'Overrides the global minimum notice when set',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `meta_title` varchar(160) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_products_listing` (`is_active`,`category_id`,`sort_order`),
  KEY `idx_products_featured` (`is_active`,`is_featured`),
  KEY `fk_products_category` (`category_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(1,1,'Black Forest Torte','black-forest-torte','Chocolate sponge layered with fresh cream and cherries.','A classic Black Forest torte: layers of chocolate sponge, vanilla cream and sour cherries, finished with chocolate on top. Light, not too sweet and always popular for birthdays and family celebrations.','products/black-forest-torte.webp',NULL,1,1,1,0,NULL,1,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(2,1,'Mousse Torte','mousse-torte','Sponge layers with a light, airy mousse filling.','Soft sponge layered with a light, airy mousse filling and finished with cream. A gentle, creamy torte that suits any occasion.','products/mousse-torte.webp',NULL,0,1,1,0,NULL,2,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(3,1,'Strawberry Torte','strawberry-torte','Vanilla sponge with fresh cream and strawberries.','Vanilla sponge layered with fresh cream and strawberries. A fresh, fruity torte that is a favourite for birthdays and morning teas.','products/strawberry-torte.webp',NULL,0,1,1,0,NULL,3,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(4,1,'Caramel Torte','caramel-torte','Sponge layers with smooth caramel cream.','A Hungarian-style sponge torte layered with chocolate buttercream and topped with caramel. Rich, smooth and a great choice for caramel lovers.','products/caramel-torte.webp',NULL,0,1,1,0,NULL,4,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(5,1,'Zebra Torte','zebra-torte','Chocolate and vanilla sponge layered together with cream.','Chocolate and vanilla sponge layered together with cream, covered in chocolate crumbs and finished with cream rosettes. Two favourite flavours in one cake.','products/zebra-torte.webp',NULL,0,1,1,0,NULL,5,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(6,1,'Hazelnut Torte','hazelnut-torte','Sponge layered with rich hazelnut cream.','Sponge layers filled with a rich hazelnut cream. A nutty, chocolatey torte for anyone who loves hazelnut.','products/hazelnut-torte.webp',NULL,1,1,1,0,NULL,6,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(7,1,'Double Choc Strawberry Torte','double-choc-strawberry-torte','Chocolate sponge with chocolate cream and strawberries.','Chocolate sponge with chocolate cream and fresh strawberries. Chocolate and berries in every slice.','products/double-choc-strawberry-torte.webp',NULL,1,1,1,0,NULL,7,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(8,1,'Cookies \'n\' Cream Torte','cookies-n-cream-torte','Sponge layers with cookies and cream filling.','Sponge layered with a cookies and cream filling and decorated with chocolate biscuits. A big hit at kids\' parties.','products/cookies-n-cream-torte.webp',NULL,0,1,1,0,NULL,8,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(9,1,'Vanilla Torte','vanilla-torte','Classic vanilla sponge with fresh cream.','Our classic vanilla sponge layered with fresh cream. Simple, light and easy to personalise with a message on top.','products/vanilla-torte.webp',NULL,0,1,1,0,NULL,9,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(10,1,'Tiramisu Torte','tiramisu-torte','Coffee-soaked sponge with a creamy tiramisu filling.','Coffee-soaked sponge layered with a creamy tiramisu filling and dusted with cocoa. One of our most loved cakes.','products/tiramisu-torte.webp','Customer favourite',1,1,1,0,NULL,10,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(11,1,'Lotus Biscoff Torte','lotus-biscoff-torte','Sponge layers with Biscoff cream and crushed Lotus biscuits.','Sponge layered with Biscoff cream and crushed Lotus biscuits, finished with a caramelised biscuit topping.',NULL,NULL,0,1,1,0,NULL,11,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(12,1,'Rainbow Torte','rainbow-torte','Colourful rainbow sponge layers with cream. Great for kids\' parties.','Bright rainbow sponge layers with cream. A colourful surprise when you cut it, perfect for kids\' birthdays.','products/rainbow-torte.webp',NULL,0,1,1,0,NULL,12,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(13,1,'Honey-Bee Torte','honey-bee-torte','Sponge layers with a honey cream filling.','Our honey-bee (bee sting) torte: three layers of honey flavoured sponge with fresh continental cream and vanilla custard.','products/honey-bee-torte.webp',NULL,0,1,1,0,NULL,13,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(14,1,'Continental Torte','continental-torte','Our signature house torte.','Our signature house torte, made the continental way with soft sponge and cream. A shop favourite since 2015.','products/continental-torte.webp',NULL,0,1,1,0,NULL,14,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(15,1,'Raffaello Torte','raffaello-torte','Coconut and almond cream torte inspired by Raffaello.','A coconut and almond cream torte inspired by Raffaello, finished with coconut. Light, creamy and elegant.',NULL,NULL,0,1,1,0,NULL,15,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(16,2,'Red Velvet Cake','red-velvet-cake','Red velvet sponge with cream cheese frosting.','Soft red velvet sponge layered with smooth cream cheese frosting. A customer favourite for birthdays and anniversaries.','products/red-velvet-cake.webp','Customer favourite',1,1,1,0,NULL,1,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(17,2,'Pistachio Velvet Cake','pistachio-velvet-cake','Pistachio velvet sponge with cream cheese frosting.','Pistachio velvet sponge layered with cream cheese frosting. A soft, nutty twist on the classic velvet cake.',NULL,NULL,0,1,1,0,NULL,2,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(18,2,'Blueberry Velvet Cake','blueberry-velvet-cake','Blueberry velvet sponge with cream cheese frosting.','Blueberry velvet sponge layered with cream cheese frosting, with a gentle berry flavour.',NULL,NULL,0,1,1,0,NULL,3,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(19,2,'Orange Mango Velvet Cake','orange-mango-velvet-cake','Orange and mango velvet sponge with cream cheese frosting.','Orange and mango velvet sponge layered with cream cheese frosting. Fresh, fruity and a little different.',NULL,NULL,0,1,1,0,NULL,4,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(20,3,'Chocolate Mud Cake','chocolate-mud-cake','Rich, moist chocolate mud cake.','A rich, moist chocolate mud cake. Dense enough to decorate and carry, which makes it a great base for birthday cakes.','products/chocolate-mud-cake.webp',NULL,1,1,1,0,NULL,1,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(21,3,'White Gold Mud Cake','white-gold-mud-cake','White chocolate mud cake with a caramel note.','White chocolate mud cake with a soft caramel note. Moist, rich and very popular for celebrations.','products/white-gold-mud-cake.webp',NULL,0,1,1,0,NULL,2,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(22,3,'Marble Mud Cake','marble-mud-cake','Chocolate and vanilla mud cake swirled together.','Chocolate and vanilla mud cake swirled together for a marbled look in every slice.','products/marble-mud-cake.webp',NULL,0,1,1,0,NULL,3,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(23,3,'Caramel Mud Cake','caramel-mud-cake','Moist caramel mud cake.','A moist caramel mud cake with a deep, buttery flavour.','products/caramel-mud-cake.webp',NULL,0,1,1,0,NULL,4,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(24,3,'Treasure Cake','treasure-cake','Our signature Treasure mud cake.','Our signature Treasure cake: a rich mud cake decorated with chocolates and treats on top.','products/treasure-cake.webp',NULL,0,1,1,0,NULL,5,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(25,4,'American Baked Cheesecake','american-baked-cheesecake','Creamy New York style baked cheesecake.','A creamy New York style cheesecake baked on a biscuit base. Smooth, rich and classic.','products/american-baked-cheesecake.webp',NULL,0,1,1,0,NULL,1,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(26,4,'Blueberry Baked Cheesecake','blueberry-baked-cheesecake','Baked cheesecake with blueberries.','Creamy baked cheesecake on a biscuit base with blueberries.','products/blueberry-baked-cheesecake.webp',NULL,0,1,1,0,NULL,2,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(27,4,'Marble Baked Cheesecake','marble-baked-cheesecake','Baked cheesecake swirled with chocolate.','Baked cheesecake swirled with chocolate on a biscuit base.','products/marble-baked-cheesecake.webp',NULL,0,1,1,0,NULL,3,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(28,4,'Tropical Baked Cheesecake','tropical-baked-cheesecake','Baked cheesecake with tropical fruit.','Baked cheesecake on a biscuit base with a tropical fruit topping.','products/tropical-baked-cheesecake.webp',NULL,0,1,1,0,NULL,4,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(29,4,'Hazelnut Baked Cheesecake','hazelnut-baked-cheesecake','Baked cheesecake with hazelnut.','Baked cheesecake with hazelnut on a biscuit base. Rich and nutty.','products/hazelnut-baked-cheesecake.webp',NULL,0,1,1,0,NULL,5,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(30,5,'Choc Chip Cheesecake','choc-chip-cheesecake','Light continental cheesecake with chocolate chips.','A light, set continental cheesecake with chocolate chips on a biscuit base.','products/choc-chip-cheesecake.webp',NULL,0,1,1,0,NULL,1,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(31,5,'Caramel Cheesecake','caramel-cheesecake','Light continental cheesecake with caramel.','A light, set continental cheesecake with caramel on a biscuit base.','products/caramel-cheesecake.webp',NULL,0,1,1,0,NULL,2,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(32,5,'Strawberry Cheesecake','strawberry-cheesecake','Light continental cheesecake with strawberry.','A light, set continental cheesecake with strawberry on a biscuit base. Fresh and fruity.','products/strawberry-cheesecake.webp',NULL,1,1,1,0,NULL,3,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(33,5,'Passionfruit Cheesecake','passionfruit-cheesecake','Light continental cheesecake with passionfruit.','A light, set continental cheesecake with a passionfruit topping. Sweet and a little tangy.','products/passionfruit-cheesecake.webp',NULL,0,1,1,0,NULL,4,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(34,5,'Cookies Cheesecake','cookies-cheesecake','Light continental cheesecake with cookie pieces.','A light, set continental cheesecake with cookie pieces through it.',NULL,NULL,0,1,1,0,NULL,5,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(35,5,'Tangy Lemon Cheesecake','tangy-lemon-cheesecake','Light continental cheesecake with a tangy lemon finish.','A light, set continental cheesecake with a tangy lemon finish. Refreshing after a big meal.','products/tangy-lemon-cheesecake.webp',NULL,0,1,1,0,NULL,6,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(36,5,'Blueberry Cheesecake','blueberry-cheesecake','Light continental cheesecake with blueberry.','A light, set continental cheesecake with blueberry on a biscuit base.','products/blueberry-cheesecake.webp',NULL,0,1,1,0,NULL,7,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(37,5,'Pistachio Cheesecake','pistachio-cheesecake','Light continental cheesecake with pistachio.','A light, set continental cheesecake with pistachio. Creamy and nutty.',NULL,NULL,0,1,1,0,NULL,8,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(38,6,'Carrot Cake','carrot-cake','Spiced carrot cake with cream cheese icing.','Moist spiced carrot cake with cream cheese icing. Great for morning tea.','products/carrot-cake.webp',NULL,0,1,1,0,NULL,1,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(39,6,'Profiterole Tower','profiterole-tower','A tower of cream-filled profiteroles. A real centrepiece.','A tower of cream-filled profiteroles, finished with chocolate. A real centrepiece for parties and dessert tables.','products/profiterole-tower.webp',NULL,1,1,1,0,NULL,2,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(40,6,'Pavlova','pavlova','Crisp meringue with soft centre, cream and fruit.','Crisp meringue with a soft centre, topped with fresh cream and fruit.','products/pavlova.webp',NULL,0,1,1,0,NULL,3,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(41,6,'Orange & Poppy Seed Cake','orange-poppy-seed-cake','Orange and poppy seed tea cake.','A moist orange and poppy seed tea cake. Lovely with a cup of tea or coffee.','products/orange-poppy-seed-cake.webp',NULL,0,1,1,0,NULL,4,NULL,NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `author` varchar(120) NOT NULL,
  `source` varchar(40) NOT NULL DEFAULT 'Google',
  `body` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES
(1,'Shelphy W.','Google','The best quality cakes ever. Not overly sweet. Easy to order by phone. Very reliable staff. Reasonable price.',1,1,'2026-10-18 13:35:43'),
(2,'Hansi','Google','The tiramisu and red velvet cake were really amazing and beyond expectations.',1,2,'2026-10-18 13:35:43'),
(3,'Liam','Google','The staff are friendly and welcoming.',1,3,'2026-10-18 13:35:43'),
(4,'Anil','Google','The best cake shop in the town.',1,4,'2026-10-18 13:35:43');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `schema_migrations` (
  `migration` varchar(190) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES
('001_customer_accounts.sql','2026-10-18 13:35:43'),
('002_pages_and_faqs.sql','2026-10-18 13:35:43'),
('003_hero_image_setting.sql','2026-10-18 13:35:43'),
('004_teal_hero_cake.sql','2026-10-18 13:35:43'),
('005_admin_panel.sql','2026-10-18 13:35:43'),
('006_delivery_announcement.sql','2026-10-18 13:35:43'),
('007_delivery_and_sizes.sql','2026-10-18 13:35:43'),
('008_coupons_popup_delivery_windows.sql','2026-10-18 13:35:43'),
('009_homepage_sections_and_mail.sql','2026-10-18 13:35:43'),
('010_messages_reports_permissions.sql','2026-10-18 13:35:43'),
('011_daily_email_settings.sql','2026-10-18 13:35:43');
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
('announcement_text','Delivery all over Canberra','2026-10-18 13:35:43'),
('bank_account_name','','2026-10-18 13:35:43'),
('bank_account_number','','2026-10-18 13:35:43'),
('bank_bsb','','2026-10-18 13:35:43'),
('coupons_enabled','1','2026-10-18 13:35:43'),
('cron_last_run','','2026-10-18 13:35:43'),
('custom_lead_days','3','2026-10-18 13:35:43'),
('daily_email_enabled','1','2026-10-18 13:35:43'),
('daily_email_last_sent','','2026-10-18 13:35:43'),
('daily_email_minutes_before','60','2026-10-18 13:35:43'),
('daily_email_to','','2026-10-18 13:35:43'),
('delivery_enabled','1','2026-10-18 13:35:43'),
('delivery_fee','15.00','2026-10-18 13:35:43'),
('delivery_free_over','0','2026-10-18 13:35:43'),
('delivery_min_order','0','2026-10-18 13:35:43'),
('delivery_note','All over Canberra and Queanbeyan','2026-10-18 13:35:43'),
('delivery_postcodes','2600-2620, 2900-2914','2026-10-18 13:35:43'),
('delivery_windows','[\"9am to 12pm\",\"12pm to 3pm\",\"3pm to 6pm\"]','2026-10-18 13:35:43'),
('email_accent_note','Thank you for choosing a local Canberra cake shop.','2026-10-18 13:35:43'),
('google_rating','4.3','2026-10-18 13:35:43'),
('google_review_count','290+','2026-10-18 13:35:43'),
('google_reviews_url','https://www.google.com/maps/search/?api=1&query=The+Continental+Cake+Shop+Canberra+Greenway','2026-10-18 13:35:43'),
('hero_btn1_text','Order Online','2026-10-18 13:35:43'),
('hero_btn1_url','shop','2026-10-18 13:35:43'),
('hero_btn2_text','Request a Custom Cake','2026-10-18 13:35:43'),
('hero_btn2_url','custom-cakes','2026-10-18 13:35:43'),
('hero_eyebrow','Baked in Greenway, Canberra since 2015','2026-10-18 13:35:43'),
('hero_image','gallery/hero-cake-teal.webp','2026-10-18 13:35:43'),
('hero_show_shop_card','1','2026-10-18 13:35:43'),
('hero_subtitle','Tortes, mud cakes, velvet cakes and cheesecakes made in our shop with 100% Halal ingredients. Order online, choose your pickup time, and we will have it ready.','2026-10-18 13:35:43'),
('hero_title','Fresh cakes for every celebration','2026-10-18 13:35:43'),
('home_categories_text','Every cake is made in our Greenway shop.','2026-10-18 13:35:43'),
('home_categories_title','Shop by category','2026-10-18 13:35:43'),
('home_custom_btn_text','Get a Quote','2026-10-18 13:35:43'),
('home_custom_steps','[[\"Share your idea\",\"Upload a photo or describe the cake you want.\"],[\"Get your quote\",\"We reply with a price and help with size and flavour.\"],[\"Pickup or delivery\",\"Collect your cake from Greenway or we deliver it on the day.\"]]','2026-10-18 13:35:43'),
('home_custom_text','<p>Bring us a photo or tell us your idea. We will help you choose the size, flavour and decorations, then send you a quote. Please allow at least {custom_lead_days} days for custom designs.</p>','2026-10-18 13:35:43'),
('home_custom_title','Made to order, just the way you pictured it','2026-10-18 13:35:43'),
('home_featured_count','8','2026-10-18 13:35:43'),
('home_featured_text','The cakes Canberra keeps coming back for.','2026-10-18 13:35:43'),
('home_featured_title','Customer favourites','2026-10-18 13:35:43'),
('home_reviews_text','Reviews left by our customers on Google.','2026-10-18 13:35:43'),
('home_reviews_title','What our customers say','2026-10-18 13:35:43'),
('home_sections','[{\"key\":\"hero\",\"on\":1},{\"key\":\"usp\",\"on\":1},{\"key\":\"categories\",\"on\":1},{\"key\":\"featured\",\"on\":1},{\"key\":\"custom\",\"on\":1},{\"key\":\"text\",\"on\":0},{\"key\":\"reviews\",\"on\":1},{\"key\":\"visit\",\"on\":1}]','2026-10-18 13:35:43'),
('home_text_body','<p>Write anything you like here, such as holiday opening hours or a new range of cakes.</p>','2026-10-18 13:35:43'),
('home_text_style','tint','2026-10-18 13:35:43'),
('home_text_title','A note from our bakers','2026-10-18 13:35:43'),
('home_usp_items','[\"100% Halal ingredients\",\"Gluten-free, eggless and vegan options\",\"Free message on every cake\",\"Pickup in Greenway or delivery across Canberra\"]','2026-10-18 13:35:43'),
('home_visit_title','Visit us in Greenway','2026-10-18 13:35:43'),
('last_slot_buffer_minutes','30','2026-10-18 13:35:43'),
('mail_driver','','2026-10-18 13:35:43'),
('mail_from_address','','2026-10-18 13:35:43'),
('mail_from_name','','2026-10-18 13:35:43'),
('max_advance_days','60','2026-10-18 13:35:43'),
('max_deliveries_per_day','0','2026-10-18 13:35:43'),
('max_orders_per_slot','0','2026-10-18 13:35:43'),
('min_lead_hours','24','2026-10-18 13:35:43'),
('opening_hours','{\"mon\":[\"08:00\",\"19:00\"],\"tue\":[\"08:00\",\"19:00\"],\"wed\":[\"08:00\",\"19:00\"],\"thu\":[\"08:00\",\"19:00\"],\"fri\":[\"08:00\",\"19:00\"],\"sat\":[\"09:00\",\"18:00\"],\"sun\":[\"09:00\",\"18:00\"]}','2026-10-18 13:35:43'),
('order_notification_email','continentalcakeshopcanberra@gmail.com','2026-10-18 13:35:43'),
('payid_account_name','The Continental Cake Shop','2026-10-18 13:35:43'),
('payid_identifier','','2026-10-18 13:35:43'),
('payment_bank_enabled','1','2026-10-18 13:35:43'),
('payment_bank_label','Bank transfer','2026-10-18 13:35:43'),
('payment_bank_note','Transfer the total to our bank account, then upload a screenshot or PDF of the receipt.','2026-10-18 13:35:43'),
('payment_payid_enabled','1','2026-10-18 13:35:43'),
('payment_payid_label','PayID','2026-10-18 13:35:43'),
('payment_payid_note','Send the total to our PayID from your banking app, then upload a screenshot of the payment.','2026-10-18 13:35:43'),
('payment_stripe_enabled','0','2026-10-18 13:35:43'),
('payment_stripe_label','Credit or debit card','2026-10-18 13:35:43'),
('payment_stripe_note','Pay securely with Visa, Mastercard, Amex, Apple Pay or Google Pay through Stripe.','2026-10-18 13:35:43'),
('pickup_enabled','1','2026-10-18 13:35:43'),
('popup_button_text','Order Online','2026-10-18 13:35:43'),
('popup_button_url','shop','2026-10-18 13:35:43'),
('popup_coupon_id','','2026-10-18 13:35:43'),
('popup_delay_seconds','3','2026-10-18 13:35:43'),
('popup_enabled','0','2026-10-18 13:35:43'),
('popup_frequency','once','2026-10-18 13:35:43'),
('popup_image','','2026-10-18 13:35:43'),
('popup_pages','all','2026-10-18 13:35:43'),
('popup_text','Order your next cake online and save.','2026-10-18 13:35:43'),
('popup_title','Sweet deal for you','2026-10-18 13:35:43'),
('popup_version','1','2026-10-18 13:35:43'),
('send_customer_emails','1','2026-10-18 13:35:43'),
('seo_home_description','Order fresh tortes, mud cakes, velvet cakes and cheesecakes online from The Continental Cake Shop in Greenway, Canberra. 100% Halal ingredients, custom cakes and easy pickup.','2026-10-18 13:35:43'),
('seo_home_title','The Continental Cake Shop Canberra | Halal Cakes in Greenway, ACT','2026-10-18 13:35:43'),
('slot_interval_minutes','30','2026-10-18 13:35:43'),
('smtp_encryption','','2026-10-18 13:35:43'),
('smtp_host','','2026-10-18 13:35:43'),
('smtp_password_encrypted','','2026-10-18 13:35:43'),
('smtp_port','','2026-10-18 13:35:43'),
('smtp_username','','2026-10-18 13:35:43'),
('social_facebook','','2026-10-18 13:35:43'),
('social_instagram','','2026-10-18 13:35:43'),
('social_pinterest','https://pin.it/Klvu8wY','2026-10-18 13:35:43'),
('store_country','AU','2026-10-18 13:35:43'),
('store_email','continentalcakeshopcanberra@gmail.com','2026-10-18 13:35:43'),
('store_founded','2015','2026-10-18 13:35:43'),
('store_name','The Continental Cake Shop Canberra','2026-10-18 13:35:43'),
('store_phone','02 6293 9578','2026-10-18 13:35:43'),
('store_phone_e164','+61262939578','2026-10-18 13:35:43'),
('store_postcode','2900','2026-10-18 13:35:43'),
('store_short_name','The Continental Cake Shop','2026-10-18 13:35:43'),
('store_state','ACT','2026-10-18 13:35:43'),
('store_street','Shop 4, 38 Reed St N','2026-10-18 13:35:43'),
('store_suburb','Greenway','2026-10-18 13:35:43'),
('stripe_publishable_key','','2026-10-18 13:35:43'),
('stripe_secret_key','','2026-10-18 13:35:43'),
('stripe_webhook_secret','','2026-10-18 13:35:43');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `thread_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `thread_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(10) unsigned NOT NULL,
  `sender` enum('customer','shop') NOT NULL,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_thread_messages` (`thread_id`,`created_at`),
  KEY `fk_thread_messages_admin` (`admin_id`),
  CONSTRAINT `fk_thread_messages_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_thread_messages_thread` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `thread_messages` WRITE;
/*!40000 ALTER TABLE `thread_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `thread_messages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `threads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `threads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token` char(32) NOT NULL,
  `subject` varchar(160) NOT NULL,
  `customer_name` varchar(120) NOT NULL,
  `customer_email` varchar(190) NOT NULL,
  `customer_phone` varchar(40) DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  `custom_request_id` int(10) unsigned DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `unread_admin` tinyint(1) NOT NULL DEFAULT 0,
  `unread_customer` tinyint(1) NOT NULL DEFAULT 0,
  `last_message_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  UNIQUE KEY `uq_threads_order` (`order_id`),
  UNIQUE KEY `uq_threads_custom` (`custom_request_id`),
  KEY `idx_threads_inbox` (`status`,`last_message_at`),
  KEY `idx_threads_email` (`customer_email`),
  KEY `fk_threads_user` (`user_id`),
  CONSTRAINT `fk_threads_custom` FOREIGN KEY (`custom_request_id`) REFERENCES `custom_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_threads_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_threads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `threads` WRITE;
/*!40000 ALTER TABLE `threads` DISABLE KEYS */;
/*!40000 ALTER TABLE `threads` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `failed_logins` int(10) unsigned NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

