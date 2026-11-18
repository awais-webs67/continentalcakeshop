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
DROP TABLE IF EXISTS `addons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `addons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `max_qty` int(10) unsigned NOT NULL DEFAULT 10,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `addons` WRITE;
/*!40000 ALTER TABLE `addons` DISABLE KEYS */;
INSERT INTO `addons` VALUES
(1,'Birthday candles','Pack of 12 candles',3.00,5,1,0,'2026-10-21 10:26:07','2026-10-21 10:26:07'),
(2,'Number candle','One gold number candle. Tell us the number in the order notes',4.00,4,2,0,'2026-10-21 10:26:07','2026-10-21 10:26:07'),
(3,'Cake knife','Plastic cake knife',2.00,3,3,0,'2026-10-21 10:26:07','2026-10-21 10:26:07'),
(4,'Happy Birthday topper','Gold acrylic topper',8.00,2,4,0,'2026-10-21 10:26:07','2026-10-21 10:26:07'),
(5,'Gift card','Handwritten card. Write your message in the order notes',5.00,3,5,0,'2026-10-21 10:26:07','2026-10-21 10:26:07');
/*!40000 ALTER TABLE `addons` ENABLE KEYS */;
UNLOCK TABLES;
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
  `auth_version` int(10) unsigned NOT NULL DEFAULT 0,
  `role` enum('owner','staff') NOT NULL DEFAULT 'staff',
  `branch_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL = every branch',
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
DROP TABLE IF EXISTS `branch_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_settings` (
  `branch_id` int(10) unsigned NOT NULL,
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`branch_id`,`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
INSERT INTO `branch_settings` VALUES (1,'public_email','continentalcakeshopcanberra@gmail.com');
DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES
(1,'greenway','Greenway','Shop 4, 38 Reed St N','Greenway','ACT','2900','02 6293 9578','continentalcakeshopcanberra@gmail.com','-35.418923','149.065858','','https://www.google.com/maps/search/?api=1&query=The+Continental+Cake+Shop+Canberra+Greenway',NULL,NULL,1,1,1,'2026-11-06 09:00:00','2026-11-06 09:00:00'),
(2,'wright','Wright','','Wright','ACT','2611','','','-35.3222','149.0347','','',NULL,NULL,0,0,2,'2026-11-06 09:00:00','2026-11-06 09:00:00');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `description` text DEFAULT NULL,
  `guide` mediumtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `image_alt` varchar(160) DEFAULT NULL,
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
(1,'Tortes','tortes','Light layered sponge tortes filled with cream, fruit and our house fillings. A favourite for birthdays and family get-togethers.','<h2>What Is a European Sponge Torte — and Why Canberra Families Choose Them</h2>
<p>A torte at {store_name} is a multi-layered European-style sponge cake brushed lightly for moisture and filled with whipped continental cream, fruit compotes, roasted nuts, espresso or airy mousse. Unlike heavy fondant or dense butter cakes, our tortes slice cleanly with a gentle, cloud-light crumb that is balanced rather than overly sweet—making them our most requested birthday and family dinner cakes in Greenway since March 2015.</p>
<p>Every torte in our Tuggeranong kitchen is baked from scratch using <strong>100% Halal ingredients</strong>, with zero alcohol or liqueurs (including our espresso-soaked <a href=\"{url:cakes/tiramisu-torte}\">Tiramisu Torte</a> and sour-cherry <a href=\"{url:cakes/black-forest-torte}\">Black Forest Torte</a>), no animal shortening, and only Halal-suitable gelatine and vanilla.</p>
<h3>Compare Our Most Popular Canberra Tortes</h3>
<table>
<thead><tr><th scope=\"col\">Torte Flavour</th><th scope=\"col\">Sponge &amp; Filling Profile</th><th scope=\"col\">Available Sizes</th><th scope=\"col\">Best For</th></tr></thead>
<tbody>
<tr><td><a href=\"{url:cakes/black-forest-torte}\"><strong>Black Forest Torte</strong></a></td><td>Dark cocoa sponge, whipped vanilla cream, tart cherries &amp; chocolate shavings</td><td>Midi (8) · Full (14)</td><td>Classic birthdays &amp; cherry-chocolate fans</td></tr>
<tr><td><a href=\"{url:cakes/tiramisu-torte}\"><strong>Tiramisu Torte</strong></a></td><td>Alcohol-free espresso-brushed sponge, creamy mascarpone-style filling &amp; cocoa</td><td>Full (14)</td><td>Coffee lovers &amp; dinner parties</td></tr>
<tr><td><a href=\"{url:cakes/continental-torte}\"><strong>Continental Torte</strong></a></td><td>Signature house sponge layered with smooth continental cream &amp; custard notes</td><td>Midi (8) · Full (14)</td><td>Multi-generational family celebrations</td></tr>
<tr><td><a href=\"{url:cakes/rainbow-torte}\"><strong>Rainbow Torte</strong></a></td><td>Vibrant six-colour sponge layers with fresh cream rosettes</td><td>Large (14) · XL (20)</td><td>Kids\' birthdays &amp; school celebrations</td></tr>
<tr><td><a href=\"{url:cakes/honey-bee-torte}\"><strong>Honey-Bee Torte</strong></a></td><td>Three honey-baked sponge layers with continental cream &amp; vanilla custard</td><td>Midi (8) · Full (14)</td><td>Afternoon teas &amp; gentle sweetness</td></tr>
</tbody>
</table>
<h3>How Many Guests Do Midi, Full &amp; XL Tortes Serve?</h3>
<p>Choose a <strong>Midi torte (serves ~8)</strong> for intimate family dinners, a <strong>Full torte (serves ~14)</strong> for birthday parties and office morning teas, or our <strong>XL Rainbow Torte (serves ~20)</strong> for larger crowds. Every torte includes a <strong>free piped message</strong> on top—just add your wording before checkout.</p>
<h3>Ordering, Dietary Options &amp; Fridge Storage</h3>
<p>Order online with at least {min_lead_hours} hours notice for free pickup at {store_address} or scheduled delivery across Canberra and Queanbeyan. Eggless, vegan and gluten-free torte variations can be arranged on request—call {store_phone} before ordering and review our <a href=\"{url:allergens}\">allergen guide</a>. Because our tortes are filled with fresh dairy cream, transport the box flat on your car floor and store refrigerated at 2–5°C for up to 3 days.</p>','categories/tortes.webp',NULL,1,1,'Tortes in Canberra | The Continental Cake Shop','Order layered sponge tortes online in Canberra: Black Forest, Lotus Biscoff, Raffaello, Tiramisu and more. Pick up in Greenway.','2026-10-18 13:35:43','2026-10-18 13:35:43'),
(2,'Velvet Cakes','velvet-cakes','Soft velvet sponge layered with smooth cream cheese frosting, in four flavours.','<h2>Fresh Red Velvet &amp; Velvet Cakes Baked in Greenway, Canberra</h2>
<p>Our velvet cakes pair a tender, fine-crumb cocoa-kissed sponge with generous layers of smooth, tangy cream cheese frosting finished by hand in our Greenway kitchen. That subtle tang cuts through the sweetness so every slice feels balanced and velvety—making <a href=\"{url:cakes/red-velvet-cake}\">Red Velvet Cake</a> one of Canberra\'s favourite centrepieces for engagements, anniversaries, Eid tables and milestone birthdays.</p>
<p>Like everything baked at {store_name} since March 2015, our velvet cakes are made with <strong>100% Halal ingredients</strong>—zero alcohol, no animal fats, and Halal-suitable vanilla and food colourings—so every guest at your celebration can enjoy a slice with confidence.</p>
<h3>Velvet Cake Sizes, Servings &amp; Ordering Details</h3>
<table>
<thead><tr><th scope=\"col\">Size</th><th scope=\"col\">Approximate Guests</th><th scope=\"col\">Frosting &amp; Finish</th><th scope=\"col\">Minimum Notice</th></tr></thead>
<tbody>
<tr><td><strong>Midi Velvet Cake</strong></td><td>Serves ~8 dessert portions</td><td>Hand-smoothed cream cheese frosting + free piped message</td><td>{min_lead_hours} hours online</td></tr>
<tr><td><strong>Full Velvet Cake</strong></td><td>Serves ~14 dessert portions</td><td>Tall layered velvet sponge, cream cheese icing + free message</td><td>{min_lead_hours} hours online</td></tr>
<tr><td><strong>Custom Tiered Velvet Cake</strong></td><td>14 to 120+ guests</td><td>Bespoke <a href=\"{url:custom-cakes}\">wedding or engagement design</a>, florals or vintage piping</td><td>{custom_lead_days} days (2–8 wks for tiered)</td></tr>
</tbody>
</table>
<h3>When to Choose Velvet Cake Over a Sponge Torte or Mud Cake</h3>
<ul>
<li><strong>Choose a Velvet Cake</strong> when you want a plush, medium-density crumb paired with tangy cream cheese frosting that holds clean slices for photos and candle-blowing.</li>
<li><strong>Choose a <a href=\"{url:shop/tortes}\">European Torte</a></strong> if your guests prefer airy whipped cream and fresh fruit layers.</li>
<li><strong>Choose a <a href=\"{url:shop/mud-cakes}\">Mud Cake</a></strong> when you want deep fudgy chocolate richness or a sculpted multi-tier cake that travels long distances in warm weather.</li>
</ul>
<h3>Storage, Transport &amp; Dietary Requests in Canberra</h3>
<p>Order online for timed pickup in Greenway or courier delivery across Tuggeranong, Woden, Molonglo Valley, Belconnen, Gungahlin, Inner Canberra and Queanbeyan. Keep cream-cheese frosted velvet cakes chilled at 2–5°C and take the cake out of the fridge 20 minutes before cutting so the crumb softens to peak tenderness. For eggless options or custom colour themes, call {store_phone} or check our <a href=\"{url:allergens}\">allergen information</a>.</p>','categories/velvet-cakes.webp',NULL,2,1,'Red Velvet & Velvet Cakes Canberra | Continental Cake Shop','Red velvet, pistachio, blueberry and orange mango velvet cakes, made fresh in Greenway, Canberra. Order online for pickup.','2026-10-18 13:35:43','2026-10-18 13:35:43'),
(3,'Mud Cakes','mud-cakes','Dense, moist mud cakes that hold up well for decorating and travel well to parties.','<h2>Rich 100% Halal Mud Cakes in Canberra — Built for Flavour &amp; Celebrations</h2>
<p>Baked slow and low in our Greenway ovens, our mud cakes have a moist, fudgy crumb and glossy ganache finish that chocolate and caramel lovers come back for year after year. Because mud cake batter is naturally dense and sturdy, it holds sharp edges, travels reliably in the car across Canberra and Queanbeyan, and serves generous finger or dessert slices without crumbling.</p>
<p>Every mud cake at {store_name} is made from scratch using <strong>100% Halal ingredients</strong>—real cocoa, white and dark couverture-style chocolate notes, butter/vegetable blends with zero animal shortening, and zero alcohol. Free custom writing is piped onto every Midi and Full mud cake at no extra charge.</p>
<h3>Compare Our Five Signature Canberra Mud Cakes</h3>
<table>
<thead><tr><th scope=\"col\">Mud Cake</th><th scope=\"col\">Flavour Profile</th><th scope=\"col\">Sizes &amp; Servings</th><th scope=\"col\">Ideal Occasion</th></tr></thead>
<tbody>
<tr><td><a href=\"{url:cakes/chocolate-mud-cake}\"><strong>Chocolate Mud Cake</strong></a></td><td>Deep, fudgy dark cocoa crumb coated in smooth chocolate ganache</td><td>Midi (8) · Full (14)</td><td>All-ages birthdays, chocoholics &amp; party travel</td></tr>
<tr><td><a href=\"{url:cakes/white-gold-mud-cake}\"><strong>White Gold Mud Cake</strong></a></td><td>Creamy white chocolate mud crumb with a warm caramelised note</td><td>Midi (8) · Full (14)</td><td>Engagements, milestone birthdays &amp; white-chocolate fans</td></tr>
<tr><td><a href=\"{url:cakes/marble-mud-cake}\"><strong>Marble Mud Cake</strong></a></td><td>Swirled ribbons of vanilla and dark chocolate mud batter</td><td>Midi (8) · Full (14)</td><td>Mixed-crowd offices, kids\' parties &amp; family gatherings</td></tr>
<tr><td><a href=\"{url:cakes/caramel-mud-cake}\"><strong>Caramel Mud Cake</strong></a></td><td>Buttery golden caramel mud crumb with smooth caramel glaze</td><td>Midi (8) · Full (14)</td><td>Autumn/winter dinners &amp; salted/sweet caramel lovers</td></tr>
<tr><td><a href=\"{url:cakes/treasure-cake}\"><strong>Treasure Cake</strong></a></td><td>Signature loaded mud cake crowned with assorted chocolates &amp; treats</td><td>Midi (8) · Full (14)</td><td>Showstopper teen &amp; adult birthday centrepiece</td></tr>
</tbody>
</table>
<h3>Gluten-Free Mud Cakes, Serving Tips &amp; Custom Tiers</h3>
<p>Mud cakes are also our number-one structural base for <a href=\"{url:wedding-cakes-canberra}\">tiered wedding cakes</a> and themed <a href=\"{url:custom-cakes}\">custom birthday cakes</a>. <strong>Gluten-free mud cakes</strong> can be baked on request—call {store_phone} before ordering to confirm availability, and review our <a href=\"{url:allergens}\">allergen page</a> regarding shared kitchen traces. For the fudgiest texture, store refrigerated and stand your mud cake at room temperature for 30–45 minutes before slicing with a warm knife wiped clean between cuts.</p>','categories/mud-cakes.webp',NULL,3,1,'Mud Cakes Canberra | The Continental Cake Shop','Rich 100% Halal chocolate, white gold, marble and caramel mud cakes baked fresh in Greenway. Order online for Canberra pickup or delivery.','2026-10-18 13:35:43','2026-10-18 13:35:43'),
(4,'Baked Cheesecakes','baked-cheesecakes','Classic oven-baked cheesecakes on a biscuit base. Available in full and half sizes.','<h2>Classic Oven-Baked Cheesecakes in Canberra — Full &amp; Half Sizes</h2>
<p>Our oven-baked cheesecakes follow the timeless New York and European patisserie tradition: rich cream cheese blended smooth with eggs and vanilla over a buttery crushed-biscuit crust, baked gently until velvety from edge to centre. Whether you need a <strong>Half cheesecake (serves ~6)</strong> for a weeknight family dessert or a <strong>Full cheesecake (serves ~12)</strong> for an office morning tea or birthday spread, every cheesecake is baked fresh in our Greenway shop.</p>
<p>All five flavours use <strong>100% Halal ingredients</strong> with zero alcohol and no animal shortening, making them effortless to share across workplaces, schools and family tables throughout Canberra and Queanbeyan.</p>
<h3>Compare Our Oven-Baked Cheesecake Range</h3>
<table>
<thead><tr><th scope=\"col\">Baked Cheesecake</th><th scope=\"col\">Topping &amp; Flavour Notes</th><th scope=\"col\">Sizes</th><th scope=\"col\">Pairing Suggestion</th></tr></thead>
<tbody>
<tr><td><a href=\"{url:cakes/american-baked-cheesecake}\"><strong>American Baked Cheesecake</strong></a></td><td>Classic golden New York style vanilla cream cheese on biscuit base</td><td>Half (6) · Full (12)</td><td>Espresso, fresh berries or enjoyed plain</td></tr>
<tr><td><a href=\"{url:cakes/blueberry-baked-cheesecake}\"><strong>Blueberry Baked Cheesecake</strong></a></td><td>Velvety baked cream cheese crowned with glossy whole-berry compote</td><td>Half (6) · Full (12)</td><td>Afternoon tea &amp; Sunday family lunches</td></tr>
<tr><td><a href=\"{url:cakes/marble-baked-cheesecake}\"><strong>Marble Baked Cheesecake</strong></a></td><td>Creamy vanilla cheesecake rippled with rich dark chocolate swirls</td><td>Half (6) · Full (12)</td><td>Dinner parties &amp; chocolate-cheesecake fans</td></tr>
<tr><td><a href=\"{url:cakes/tropical-baked-cheesecake}\"><strong>Tropical Baked Cheesecake</strong></a></td><td>Dense baked cheesecake brightened with sunny tropical fruit glaze</td><td>Half (6) · Full (12)</td><td>Summer barbecues &amp; festive grazing tables</td></tr>
<tr><td><a href=\"{url:cakes/hazelnut-baked-cheesecake}\"><strong>Hazelnut Baked Cheesecake</strong></a></td><td>Roasted hazelnut &amp; cocoa notes folded through baked cream cheese</td><td>Half (6) · Full (12)</td><td>After-dinner coffee &amp; nut-chocolate lovers</td></tr>
</tbody>
</table>
<h3>Baked Cheesecake vs. Continental Cheesecake — Which Should You Pick?</h3>
<p>Choose an <strong>oven-baked cheesecake</strong> when you love a dense, rich, custard-smooth texture that pairs beautifully with hot coffee. Prefer something chilled, airy and mousse-light after a heavy meal? Explore our <a href=\"{url:shop/continental-cheesecakes}\">Continental Cheesecakes</a>. Order online at least {min_lead_hours} hours ahead for pickup at {store_address} or delivery across Canberra. Keep refrigerated at 2–5°C and slice with a hot, dry blade for café-clean wedges.</p>','categories/baked-cheesecakes.webp',NULL,4,1,'Baked Cheesecakes Canberra | The Continental Cake Shop','American, blueberry, marble, tropical and hazelnut baked cheesecakes. Full and half sizes, ready for pickup in Greenway.','2026-10-18 13:35:43','2026-10-18 13:35:43'),
(5,'Continental Cheesecakes','continental-cheesecakes','Our light, set continental-style cheesecakes in eight flavours. Full and half sizes.','<h2>Light, Chilled Continental Cheesecakes in Canberra — Full &amp; Half Sizes</h2>
<p>A Canberra staple for summer entertaining, Iftar gatherings and post-roast family desserts, our <strong>continental cheesecakes</strong> are chilled, mousse-light cream cheese cakes set atop a crisp biscuit crumb without heavy oven baking. Each bite is cool, delicate and refreshing—ideal for guests who find mud cakes or New York baked cheesecakes too rich after a big meal.</p>
<p>Crucially for Halal households across Canberra, every continental cheesecake at {store_name} is set exclusively with <strong>100% Halal-suitable ingredients</strong> (zero pork gelatine, zero alcohol and no animal shortening), prepared fresh inside our dedicated Halal Greenway kitchen.</p>
<h3>Compare Our Six Continental Cheesecake Flavours</h3>
<table>
<thead><tr><th scope=\"col\">Continental Cheesecake</th><th scope=\"col\">Flavour &amp; Finish</th><th scope=\"col\">Sizes &amp; Servings</th><th scope=\"col\">Best Occasion</th></tr></thead>
<tbody>
<tr><td><a href=\"{url:cakes/strawberry-cheesecake}\"><strong>Strawberry Cheesecake</strong></a></td><td>Airy set cream cheese finished with sweet strawberry glaze</td><td>Half (6) · Full (12)</td><td>Family birthdays, picnics &amp; kids\' favourites</td></tr>
<tr><td><a href=\"{url:cakes/passionfruit-cheesecake}\"><strong>Passionfruit Cheesecake</strong></a></td><td>Light mousse-style cheesecake with tangy passionfruit seed topping</td><td>Half (6) · Full (12)</td><td>Summer lunches, barbecues &amp; citrus/fruit lovers</td></tr>
<tr><td><a href=\"{url:cakes/tangy-lemon-cheesecake}\"><strong>Tangy Lemon Cheesecake</strong></a></td><td>Crisp, zesty lemon finish over cool whipped cream cheese</td><td>Half (6) · Full (12)</td><td>Refreshing palate cleanser after rich banquets</td></tr>
<tr><td><a href=\"{url:cakes/blueberry-cheesecake}\"><strong>Blueberry Cheesecake</strong></a></td><td>Smooth continental cream cheese topped with vibrant blueberry compote</td><td>Half (6) · Full (12)</td><td>Morning teas &amp; weekend family visits</td></tr>
<tr><td><a href=\"{url:cakes/caramel-cheesecake}\"><strong>Caramel Cheesecake</strong></a></td><td>Silky caramel crown over light continental cheesecake &amp; biscuit base</td><td>Half (6) · Full (12)</td><td>Sweet-tooth dessert tables &amp; office shout</td></tr>
<tr><td><a href=\"{url:cakes/choc-chip-cheesecake}\"><strong>Choc Chip Cheesecake</strong></a></td><td>Whipped continental filling speckled with crisp chocolate chips</td><td>Half (6) · Full (12)</td><td>Kids\' dinners &amp; casual weekend treats</td></tr>
</tbody>
</table>
<h3>Ordering, Pickup, Canberra Delivery &amp; Chilled Storage</h3>
<p>Available in <strong>Half (serves ~6, from $22.95)</strong> and <strong>Full (serves ~12, from $39.95)</strong> with just {min_lead_hours} hours online notice. Pick up from {store_address} or book delivery across Canberra and Queanbeyan at checkout. Keep chilled continental cheesecakes in the refrigerator at 2–5°C right until serving, and check our <a href=\"{url:allergens}\">allergen guide</a> or call {store_phone} if ordering for guests with food sensitivities.</p>','categories/continental-cheesecakes.webp',NULL,5,1,'Continental Cheesecakes Canberra | The Continental Cake Shop','Light continental cheesecakes in eight flavours including pistachio, passionfruit and tangy lemon. Order online in Canberra.','2026-10-18 13:35:43','2026-10-18 13:35:43'),
(6,'Tea Cakes & Desserts','tea-cakes-desserts','Carrot cake, pavlova, profiterole towers and more for morning tea and dessert tables.','<h2>Fresh Tea Cakes, Pavlova &amp; Profiterole Towers in Canberra</h2>
<p>Beyond layered tortes and cheesecakes, our Greenway pastry team bakes classic Australian morning-tea cakes and showstopping French-inspired dessert centrepieces—all made with <strong>100% Halal ingredients</strong>, zero alcohol and fresh local dairy. Whether you are hosting an office morning tea in Woden or Barton, bringing a gift to a Tuggeranong family lunch, or styling an engagement grazing table, these four favourites arrive ready to serve.</p>
<h3>Compare Our Tea Cakes &amp; Dessert Centrepieces</h3>
<table>
<thead><tr><th scope=\"col\">Dessert / Tea Cake</th><th scope=\"col\">Style &amp; Texture</th><th scope=\"col\">Sizes &amp; Servings</th><th scope=\"col\">Best Occasion</th></tr></thead>
<tbody>
<tr><td><a href=\"{url:cakes/profiterole-tower}\"><strong>Profiterole Tower</strong></a></td><td>Golden choux pastry puffs filled with fresh cream &amp; drizzled with chocolate</td><td>20, 25, 30 or 35 pieces (serves ~10–18)</td><td>Engagements, milestone birthdays &amp; corporate events</td></tr>
<tr><td><a href=\"{url:cakes/pavlova}\"><strong>Pavlova</strong></a></td><td>Crisp baked meringue shell, pillowy marshmallow centre, whipped cream &amp; fresh fruit</td><td>One size (serves ~10)</td><td>Australian summer lunches, Eid, Christmas &amp; gluten-friendly tables*</td></tr>
<tr><td><a href=\"{url:cakes/carrot-cake}\"><strong>Carrot Cake</strong></a></td><td>Moist warmly spiced grated-carrot sponge topped with smooth cream cheese icing</td><td>Midi (8) · Full (14)</td><td>Office morning teas, birthdays &amp; afternoon coffee</td></tr>
<tr><td><a href=\"{url:cakes/orange-poppy-seed-cake}\"><strong>Orange &amp; Poppy Seed Cake</strong></a></td><td>Fragrant citrus tea cake speckled with crunchy blue poppy seeds &amp; icing</td><td>Midi (8) · Full (14)</td><td>Morning tea platters, brunch &amp; thank-you gifts</td></tr>
</tbody>
</table>
<h3>Tips for Serving Profiterole Towers, Pavlova &amp; Tea Cakes</h3>
<ul>
<li><strong>Profiterole Towers:</strong> Guests can lift individual cream-filled choux puffs straight from the tower—no knife or plates needed at stand-up cocktail parties and office functions.</li>
<li><strong>Pavlova &amp; Cream-Topped Cakes:</strong> Store refrigerated at 2–5°C and serve on the day of pickup or within 24–48 hours while the meringue shell stays crisp.</li>
<li><strong>Allergen Note (*):</strong> While pavlova meringue is naturally made without wheat flour, our Greenway kitchen handles wheat, dairy, eggs, soy and tree nuts—please read our <a href=\"{url:allergens}\">allergen information</a> or call {store_phone} before ordering.</li>
</ul>
<p>Order online at least {min_lead_hours} hours ahead for pickup in Greenway or delivery across Canberra and Queanbeyan, or <a href=\"{url:custom-cakes}\">enquire about a larger custom profiterole tower or dessert spread</a>.</p>','categories/tea-cakes-desserts.webp',NULL,6,1,'Tea Cakes & Desserts Canberra | The Continental Cake Shop','Fresh 100% Halal carrot cake, orange poppy seed cake, pavlova and profiterole towers baked in Greenway for Canberra pickup or delivery.','2026-10-18 13:35:43','2026-10-18 13:35:43');
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
  `branch_id` int(10) unsigned NOT NULL DEFAULT 1,
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
  `gallery_image` varchar(255) DEFAULT NULL COMMENT 'Copy of the chosen gallery photo, inside storage/uploads',
  `status` enum('new','quoted','confirmed','closed') NOT NULL DEFAULT 'new',
  `quoted_price` decimal(10,2) DEFAULT NULL,
  `deposit_amount` decimal(10,2) DEFAULT NULL COMMENT 'Paid to accept the quote; NULL = full price',
  `quote_token` char(32) DEFAULT NULL,
  `quote_sent_at` datetime DEFAULT NULL,
  `order_id` int(10) unsigned DEFAULT NULL COMMENT 'Order made when the customer accepted the quote',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_custom_quote_token` (`quote_token`),
  KEY `idx_custom_status` (`status`,`event_date`),
  KEY `idx_custom_branch` (`branch_id`,`status`)
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES
(1,'Ordering','How far in advance should I order?','<p>Order menu cakes online at least <strong>{min_lead_hours} hours</strong> ahead. For custom birthday and celebration cakes, please allow <strong>3 to 7 days</strong> (minimum {custom_lead_days} days), and for two- or three-tier wedding cakes we recommend booking <strong>2 to 8+ weeks</strong> ahead—especially for spring and autumn weekends.</p>',1,1),
(2,'Ordering','I need a cake today. Can you help?','<p>Please call us on {store_phone} and we will let you know what we can do.</p>',1,2),
(3,'Ordering','Can I add a message on my cake?','<p>Yes, a message is free on every cake. Add it on the cake\'s page before adding it to your cart.</p>',1,3),
(4,'Ordering','Can I change the size after adding a cake to my cart?','<p>Yes. Open your cart and choose a different size from the dropdown next to the cake.</p>',1,4),
(5,'Pickup and delivery','Where do I collect my order?','<p>From our shop at {store_address}. You choose the pickup date and time at checkout.</p>',1,5),
(6,'Pickup and delivery','Can I change my pickup time?','<p>Please call us on {store_phone} as early as possible and we will do our best to help.</p>',1,6),
(7,'Pickup and delivery','Do you deliver?','<p>Yes — we deliver fresh cakes across all Canberra districts (Tuggeranong, Woden, Weston Creek, Molonglo Valley, Inner South, Inner North, Belconnen and Gungahlin — postcodes 2600–2620 and 2900–2914) and Queanbeyan. Simply choose <strong>Delivery</strong> at online checkout, enter your address and preferred time window, and our Greenway team will email your confirmed delivery window. For outer-region venues, call {store_phone}.</p>',1,7),
(8,'Payment','How can I pay?','<p>You can pay online at checkout. The payment options shown at checkout are the ones currently available.</p>',1,8),
(9,'Payment','When is my order confirmed?','<p>Card payments are confirmed straight away. For PayID and bank transfer, we confirm your order once we have checked your payment screenshot.</p>',1,9),
(10,'Dietary needs','Are your cakes Halal?','<p>Yes — <strong>100% of the cakes, tortes, mud cakes, velvet cakes, cheesecakes and profiterole towers</strong> baked in our Greenway kitchen use 100% Halal ingredients, with zero alcohol or liqueurs, no animal shortening, and only Halal-suitable gelatine and vanilla flavourings.</p>',1,10),
(11,'Dietary needs','Do you make gluten-free, eggless or vegan cakes?','<p>Yes, on request. Eligible cakes offer an <strong>Eggless</strong> checkbox directly on the product page, and we also bake gluten-free mud cakes and vegan celebration cakes by arrangement—please call {store_phone} before ordering and read our <a href=\"{url:allergens}\">allergen information</a> regarding shared-kitchen traces.</p>',1,11),
(12,'Dietary needs','I have a food allergy. Is it safe to order?','<p>Our kitchen handles common allergens, so we cannot guarantee any cake is free from traces. Please read our <a href=\"{url:allergens}\">allergen information</a> and call us before ordering.</p>',1,12),
(13,'Custom cakes','How do I order a custom cake?','<p>Fill in our <a href=\"{url:custom-cakes}\">custom cake form</a>, call us, or visit the shop. We will reply with a quote and help you choose the size, flavour and decorations.</p>',1,13),
(14,'Custom cakes','Can you make a cake from a photo?','<p>Yes. Upload a photo with your request and we will match the design as closely as we can.</p>',1,14),
(15,'Cancellations','Can I cancel my order?','<p>Please contact us as early as possible. See our <a href=\"{url:refund-policy}\">Refund and Cancellation Policy</a> for details.</p>',1,15),
(16,'Ordering','How many people do Midi, Half and Full cakes serve?','<p>Our <strong>Half cheesecakes serve about 6</strong> (from $22.95), <strong>Midi celebration cakes serve about 8</strong> (from $34.95), <strong>Full cheesecakes serve about 12</strong> (from $39.95), and <strong>Full tortes, mud cakes and velvet cakes serve about 14</strong> (from $44.95). For larger crowds, our <a href=\"{url:cakes/rainbow-torte}\">XL Rainbow Torte</a> serves ~20, <a href=\"{url:cakes/profiterole-tower}\">Profiterole Towers</a> serve 10–18, and <a href=\"{url:custom-cakes}\">custom tiered cakes</a> serve up to 120+ guests.</p>',1,16),
(17,'Pickup and delivery','How should I transport and store my cake in Canberra?','<p>Place the cake box flat in your car\'s front passenger footwell (never on a sloped seat) with air-conditioning running. Refrigerate fresh cream tortes, cheesecakes, carrot cake, red velvet and pavlova at <strong>2–5°C</strong> and enjoy within <strong>2 to 3 days</strong>. Let dense mud cakes rest at room temperature for 30 minutes before slicing.</p>',1,17),
(18,'Custom cakes','Do you make tiered wedding cakes and deliver to Canberra venues?','<p>Yes — we bake custom 1-tier, 2-tier and 3-tier 100% Halal <a href=\"{url:wedding-cakes-canberra}\">wedding, engagement and Nikah cakes</a> in Greenway and offer scheduled courier delivery to Canberra and Queanbeyan reception venues. Send your date, guest count and inspiration photo on our <a href=\"{url:custom-cakes}\">custom cake form</a> to receive an itemised quote, reserved online with a 50% deposit.</p>',1,18);
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
DROP TABLE IF EXISTS `not_found_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `not_found_log` (
  `path` varchar(255) NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT 1,
  `referrer` varchar(255) DEFAULT NULL,
  `first_seen` datetime NOT NULL,
  `last_seen` datetime NOT NULL,
  PRIMARY KEY (`path`),
  KEY `idx_not_found_last` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `not_found_log` WRITE;
/*!40000 ALTER TABLE `not_found_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `not_found_log` ENABLE KEYS */;
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
  `addon_id` int(10) unsigned DEFAULT NULL,
  `product_name` varchar(160) NOT NULL,
  `size_label` varchar(60) NOT NULL,
  `options` varchar(255) DEFAULT NULL COMMENT 'Chosen product options, e.g. Eggless',
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
  `branch_id` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'The shop that makes and hands over the order',
  `order_number` varchar(30) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `channel` varchar(20) NOT NULL DEFAULT 'online' COMMENT 'online, phone, in_store or quote',
  `custom_request_id` int(10) unsigned DEFAULT NULL,
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
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_refunded` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(20) NOT NULL,
  `payment_status` enum('pending','awaiting_verification','partially_paid','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `order_status` enum('new','confirmed','in_progress','ready','completed','cancelled') NOT NULL DEFAULT 'new',
  `payment_reference` varchar(190) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL COMMENT 'Path inside storage/uploads',
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `reminder_sent_at` datetime DEFAULT NULL,
  `review_requested_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_orders_pickup` (`pickup_date`,`pickup_time`),
  KEY `idx_orders_status` (`order_status`,`payment_status`),
  KEY `idx_orders_stripe` (`stripe_session_id`),
  KEY `idx_orders_user` (`user_id`,`created_at`),
  KEY `idx_orders_custom` (`custom_request_id`),
  KEY `idx_orders_branch` (`branch_id`,`pickup_date`),
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
  `type` varchar(20) NOT NULL DEFAULT 'policy' COMMENT 'policy or landing',
  `title` varchar(160) NOT NULL,
  `summary` varchar(255) DEFAULT NULL,
  `occasion` varchar(60) DEFAULT NULL COMMENT 'Landing pages: occasion filled in on the quote form',
  `show_gallery` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `body` mediumtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `meta_title` varchar(160) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES
(1,'privacy-policy','policy','Privacy Policy','How we collect, use and protect your personal information.',NULL,1,0,'<p>{store_name} (\"we\", \"us\") respects your privacy. This policy explains what personal information we collect when you use our website, place an order or contact us, and how we handle it in line with the Australian Privacy Principles in the <em>Privacy Act 1988</em> (Cth).</p>\n<h2>Information we collect</h2>\n<ul>\n<li><strong>Contact details:</strong> your name, email address and phone number when you place an order, request a custom cake quote, send us a message or create an account.</li>\n<li><strong>Order details:</strong> the cakes you order, messages for your cake, pickup or delivery date and time, delivery address (if used) and any notes you add.</li>\n<li><strong>Payment information:</strong> card payments are processed by Stripe. We never see or store your full card number. If you pay by PayID or bank transfer, we keep the payment screenshot or receipt you upload.</li>\n<li><strong>Photos and messages you send us:</strong> reference images with a custom cake request, and the messages and emails you send us.</li>\n<li><strong>Account details:</strong> if you create an account, we store your details and a securely encrypted version of your password.</li>\n<li><strong>Website use:</strong> like most websites, our server records technical details such as your IP address and browser, to keep the site secure and working.</li>\n</ul>\n<h2>How we use your information</h2>\n<ul>\n<li>To prepare your order and have it ready at the time you chose, or deliver it.</li>\n<li>To contact you about your order, your custom cake quote or your enquiry. This includes emails about your order, a reminder the day before, and after pickup an invitation to review us, which you can ignore.</li>\n<li>To check payments and keep the records required for tax and accounting.</li>\n<li>To show your orders and messages when you log in to your account.</li>\n<li>To improve our website and our service.</li>\n</ul>\n<p>We do not sell your personal information. We do not send marketing emails unless you ask us to, and you can ask us to stop at any time.</p>\n<h2>Who we share it with</h2>\n<p>We only share information with the services we need to run the shop: our payment provider (Stripe), our website host, our email provider and, if you choose delivery, the person delivering your cake. They may only use it to provide their service to us. We will also share information when the law requires it.</p>\n<h2>Information stored overseas</h2>\n<p>Some of these services, such as Stripe and Google, may store information outside Australia, including in the United States. We use well-known providers that protect personal information.</p>\n<h2>Cookies and analytics</h2>\n<p>Our website uses a small session cookie so your cart and login work while you browse. We may use Google Analytics to see how visitors use the website, for example which pages are popular. It uses cookies but does not tell us who you are. You can block or delete cookies in your browser settings. We do not use advertising cookies.</p>\n<h2>How long we keep it</h2>\n<p>We keep order and payment records for at least 5 years, as Australian tax law requires. Other information is kept only as long as we need it, and you can ask us to delete your account at any time.</p>\n<h2>Keeping your information safe</h2>\n<p>We store your information on secure servers and limit access to staff who need it. Payment screenshots and reference photos are kept in a private area that is not publicly accessible. No website can be completely secure, but we take reasonable steps to protect your information, and we will tell you if a serious data breach affects you.</p>\n<h2>Children</h2>\n<p>Our website is meant for adults placing orders. We do not knowingly collect information from children under 16.</p>\n<h2>Accessing or correcting your information</h2>\n<p>You can update your details from your account page, or contact us to see, correct or delete the information we hold about you. We reply within 30 days.</p>\n<h2>Questions or complaints</h2>\n<p>Please contact us on {store_phone} or {store_email}. We will reply within 30 days. If you are not happy with our response, you can contact the Office of the Australian Information Commissioner at <a href=\"https://www.oaic.gov.au\" target=\"_blank\" rel=\"noopener\">oaic.gov.au</a> or on 1300 363 992.</p>\n<h2>Changes to this policy</h2>\n<p>We may update this policy from time to time. The latest version is always on this page.</p>',1,NULL,'How The Continental Cake Shop in Greenway, Canberra collects, uses and protects your personal information when you order or contact us.','2026-10-18 13:35:43'),
(2,'terms','policy','Terms and Conditions','The terms that apply when you order from our website.',NULL,1,0,'<p>These terms apply when you place an order with {store_name}, on this website, by phone or in the shop. By placing an order you agree to them.</p>\n<h2>Orders</h2>\n<ul>\n<li>Please order at least {min_lead_hours} hours before your pickup or delivery time for cakes from our menu, and at least {custom_lead_days} days ahead for custom cakes.</li>\n<li>You receive an order number and a confirmation email after you order. Please check the date, time and the message for the cake.</li>\n<li>Card orders are confirmed when the payment goes through. PayID and bank transfer orders are confirmed once we have checked your payment.</li>\n<li>Orders you pay for in the shop or on a phone call are booked when you place them and paid before or at pickup.</li>\n<li>We will contact you if we cannot make your order as placed, for example if an ingredient is unavailable, and refund anything you have paid for it.</li>\n</ul>\n<h2>Prices and payment</h2>\n<ul>\n<li>All prices are in Australian dollars and include GST where it applies.</li>\n<li>The price shown at checkout, including any delivery fee, is the price you pay. Custom cakes are priced by quote.</li>\n<li>We accept the ways to pay shown at checkout. Card payments are processed securely by Stripe; we never see your full card number.</li>\n</ul>\n<h2>Pickup</h2>\n<ul>\n<li>Please collect your order from {store_address} at the time you chose. If you need to change it, call {store_phone} as early as you can.</li>\n<li>Orders not collected on the day are kept until closing time.</li>\n</ul>\n<h2>Delivery</h2>\n<ul>\n<li>We deliver to the areas and on the days shown at checkout. The delivery fee is shown before you pay.</li>\n<li>Someone needs to be at the address to receive the cake. See our <a href=\"{url:refund-policy}\">Refund and Cancellation Policy</a> for what happens if no one is there.</li>\n</ul>\n<h2>Looking after your cake</h2>\n<ul>\n<li>Carry the cake flat, for example on the floor of the car, not on a seat, and keep it out of the sun.</li>\n<li>Keep cream cakes, cheesecakes and mousse cakes in the fridge, and enjoy them within 2 to 3 days.</li>\n<li>Once the cake has been handed over, it is your responsibility to transport and store it correctly.</li>\n</ul>\n<h2>Our cakes</h2>\n<ul>\n<li>Every cake is handmade, so colours, decorations and shapes may differ slightly from the photos on our website.</li>\n<li>For custom cakes, we match your design as closely as we can. Characters and printed images may be adapted.</li>\n<li>Our products are made in a kitchen that handles common allergens. Please read our <a href=\"{url:allergens}\">allergen information</a> before ordering, and tell us about any allergy when you order.</li>\n</ul>\n<h2>Cancellations and refunds</h2>\n<p>Please see our <a href=\"{url:refund-policy}\">Refund and Cancellation Policy</a>.</p>\n<h2>Photos of your cake</h2>\n<p>We sometimes share photos of cakes we have made on our website and social media. We never show your name or personal details. Tell us when you order if you would rather we did not share a photo of your cake.</p>\n<h2>Your rights</h2>\n<p>Nothing in these terms limits your rights under the Australian Consumer Law. These terms are governed by the law of the Australian Capital Territory.</p>\n<h2>Contact</h2>\n<p>{store_name}, {store_address}. Phone {store_phone}, email {store_email}.</p>',1,NULL,'The terms that apply when you order cakes online from The Continental Cake Shop in Greenway, Canberra, including pickup, payment and custom cakes.','2026-10-18 13:35:43'),
(3,'refund-policy','policy','Refund and Cancellation Policy','What to do if you need to cancel or have a problem with your order.',NULL,1,0,'<p>We want you to love your cake. Because every cake is fresh food, made to order for you, this policy explains when we can offer a refund, a credit or a replacement, and what to do if something is not right.</p>\n<h2>Change of mind</h2>\n<p>Cakes are perishable and made especially for your order, so we do not offer refunds or exchanges for a change of mind, or if you choose the wrong cake, size or date.</p>\n<h2>Cancelling or changing an order</h2>\n<ul>\n<li><strong>48 hours or more before pickup or delivery:</strong> call us on {store_phone} and we will change the date, time or cake, or cancel the order with a full refund.</li>\n<li><strong>Less than 48 hours before:</strong> your cake may already be in preparation. We will always try to move it to another day, but we may not be able to offer a refund.</li>\n<li>Changes are only confirmed once we have replied to you. A message or email sent outside our opening hours is read the next day we are open.</li>\n</ul>\n<h2>Custom cakes</h2>\n<ul>\n<li>A custom cake is booked once your quote is accepted and the deposit is paid.</li>\n<li>Cancel <strong>7 days or more</strong> before the event and we refund your deposit, less anything we have already spent on special ingredients or decorations.</li>\n<li>Cancel <strong>less than 7 days</strong> before and the deposit cannot be refunded, as your cake has been planned and its decorations made or ordered.</li>\n<li>Design changes are welcome up to 7 days before. Later changes depend on what we can still do, and may change the price.</li>\n</ul>\n<h2>If something is wrong with your order</h2>\n<ul>\n<li>Please check your cake when you collect it or when it is delivered, before you leave the shop or sign for it.</li>\n<li>If the cake is not what you ordered, is damaged, or there is a problem with its quality, tell us <strong>on the same day, and within 24 hours at the latest</strong>. Call {store_phone} or email {store_email} with your order number and photos of the cake.</li>\n<li>Please keep the cake, or what is left of it, until we have spoken with you.</li>\n<li>When the problem is ours, we will offer a replacement, a store credit or a refund, whichever suits you.</li>\n</ul>\n<h2>What we cannot cover</h2>\n<ul>\n<li>Damage after the cake has been handed over, for example while it is carried or driven, or when it is left out of the fridge or in the heat.</li>\n<li>Small differences from photos. Every cake is handmade, so colours, decorations and shapes may vary a little.</li>\n<li>Personal taste, when the cake was made as ordered.</li>\n<li>Orders not collected on the day. We keep them until closing time; after that they cannot be refunded.</li>\n</ul>\n<h2>Delivery</h2>\n<ul>\n<li>Someone needs to be at the address to receive the cake at the time we agree. We call you if no one answers.</li>\n<li>If no one is there, we take the cake back to the shop. You can collect it the same day, or we can deliver again for another delivery fee.</li>\n<li>Please tell the driver straight away if the cake arrives damaged, and send us a photo.</li>\n</ul>\n<h2>How refunds are paid</h2>\n<p>Approved refunds go back to the way you paid. Card refunds usually appear within 5 to 10 business days, depending on your bank. PayID and bank transfer refunds are sent to your account, and cash payments are refunded in the shop.</p>\n<h2>Your rights</h2>\n<p>Our cakes come with guarantees that cannot be excluded under the Australian Consumer Law. Nothing in this policy limits those rights.</p>',1,NULL,'How to cancel or change a cake order with The Continental Cake Shop in Greenway, Canberra, and what to do if there is a problem with your order.','2026-10-18 13:35:43'),
(4,'allergens','policy','Allergen Information','Important information for customers with food allergies or intolerances.',NULL,1,0,'<p>Your safety and dietary confidence matter to us. Please read this guide before ordering if you or your guests have a food allergy, intolerance or religious dietary requirement.</p>\n<h2>Our Greenway Kitchen &amp; Cross-Contact Notice</h2>\n<p>Every cake at {store_name} is baked by hand in our kitchen at {store_address}, which handles common allergens daily—including <strong>milk (dairy)</strong>, <strong>eggs</strong>, <strong>wheat (gluten)</strong>, <strong>soy</strong> and <strong>tree nuts</strong> (hazelnuts, almonds, walnuts and pistachios). Because we share ovens, mixers and preparation benches, <strong>we cannot guarantee that any product is 100% free from traces of allergens</strong> for individuals with severe or anaphylactic allergies.</p>\n<h2>Dietary, Allergen &amp; Halal Quick-Reference Guide</h2>\n<table>\n<thead><tr><th scope=\"col\">Dietary / Ingredient Question</th><th scope=\"col\">Status in Our Kitchen</th><th scope=\"col\">How to Order Safely</th></tr></thead>\n<tbody>\n<tr><td><strong>100% Halal Ingredients</strong></td><td>Yes — 100% of products across the entire shop</td><td>Standard on every menu &amp; custom cake (zero alcohol, no animal shortening, Halal-suitable gelatine)</td></tr>\n<tr><td><strong>Alcohol / Liqueurs</strong></td><td>0% alcohol used anywhere in our kitchen</td><td>Even our <a href=\"{url:cakes/tiramisu-torte}\">Tiramisu</a> &amp; <a href=\"{url:cakes/black-forest-torte}\">Black Forest</a> tortes are completely alcohol-free</td></tr>\n<tr><td><strong>Eggless Cakes</strong></td><td>Available on eligible cakes &amp; custom orders</td><td>Tick <em>Eggless</em> on eligible product pages or call {store_phone} before ordering</td></tr>\n<tr><td><strong>Gluten-Free Options</strong></td><td>Made on request (including selected <a href=\"{url:shop/mud-cakes}\">mud cakes</a>)</td><td>Call {store_phone} prior to ordering; prepared in a kitchen that also handles wheat flour</td></tr>\n<tr><td><strong>Vegan Options</strong></td><td>Available on request for custom orders</td><td>Call {store_phone} or note on our <a href=\"{url:custom-cakes}\">custom cake quote form</a></td></tr>\n<tr><td><strong>Tree Nuts, Dairy &amp; Soy</strong></td><td>Present in recipes &amp; shared kitchen equipment</td><td>Ask our team about nut-free recipes; trace cross-contact cannot be ruled out</td></tr>\n</tbody>\n</table>\n<h2>Ask Our Bakers Before You Order</h2>\n<p>If you have questions about a specific sponge, glaze, biscuit base or edible topper, please call us on {store_phone} or ask in store before placing your order, and include any allergy notes in the checkout comments so our kitchen team sees them clearly on your baking sheet.</p>',1,NULL,'Allergen, 100% Halal, gluten-free, eggless and vegan cake information from our Greenway kitchen in Canberra. Check ingredients before ordering.','2026-10-18 13:35:43'),
(5,'birthday-cakes-canberra','landing','Birthday cakes in Canberra','Fresh birthday cakes from our shop in Greenway, ready to order online or made to your own design.','Birthday',1,1,'<h2>Fresh Birthday Cakes in Canberra — Ready in {min_lead_hours} Hours or Custom-Designed</h2>\n<p>At {store_name} in Greenway, Tuggeranong, we bake fresh birthday cakes every day from scratch using <strong>100% Halal ingredients</strong>—starting from <strong>$34.95 for a Midi cake (serves ~8)</strong> and <strong>$44.95 for a Full birthday cake (serves ~14)</strong>, with a <strong>free custom birthday message</strong> piped on top of every celebration cake. Order a menu favourite online with {min_lead_hours} hours notice, or send us a photo for a custom kids\' or milestone birthday cake.</p>\n<img src=\"{url:assets/img/cake-serving-guide.svg}\" alt=\"Canberra birthday cake size and serving guide: Half Cheesecake serves 6, Midi Birthday Cake serves 8, Full Cheesecake serves 12, Full Birthday Cake serves 14, XL Rainbow Torte serves 20\" width=\"960\" height=\"340\">\n<h2>Compare Ready-Made vs. Custom Birthday Cakes in Canberra</h2>\n<table>\n<thead><tr><th scope=\"col\">Birthday Cake Option</th><th scope=\"col\">Servings</th><th scope=\"col\">Price Guide</th><th scope=\"col\">Notice Needed</th><th scope=\"col\">Top Flavours</th></tr></thead>\n<tbody>\n<tr><td><strong>Midi Menu Birthday Cake</strong></td><td>~8 guests</td><td>From $34.95 – $59.95</td><td>{min_lead_hours} hours online</td><td><a href=\"{url:cakes/black-forest-torte}\">Black Forest</a>, <a href=\"{url:cakes/chocolate-mud-cake}\">Chocolate Mud</a>, <a href=\"{url:cakes/red-velvet-cake}\">Red Velvet</a></td></tr>\n<tr><td><strong>Full Menu Birthday Cake</strong></td><td>~14 guests</td><td>From $44.95 – $69.95</td><td>{min_lead_hours} hours online</td><td><a href=\"{url:cakes/tiramisu-torte}\">Tiramisu Torte</a>, <a href=\"{url:cakes/treasure-cake}\">Treasure Mud</a>, <a href=\"{url:cakes/white-gold-mud-cake}\">White Gold Mud</a></td></tr>\n<tr><td><strong>XL Kids\' Party Rainbow Torte</strong></td><td>~20 guests</td><td>$69.95 (Large 14: $59.95)</td><td>{min_lead_hours} hours online</td><td><a href=\"{url:cakes/rainbow-torte}\">Six-colour Rainbow Sponge Torte</a></td></tr>\n<tr><td><strong>Custom Kids\' &amp; Milestone Cake</strong></td><td>8 – 100+ guests</td><td>Priced by design quote</td><td>3 – 7 days (min {custom_lead_days} days)</td><td>Edible images (Bluey, Spider-Man, Mario), vintage hearts, number &amp; 2-tier cakes</td></tr>\n</tbody>\n</table>\n<h2>Kids\' Character Cakes, Vintage Heart Cakes &amp; Milestone Designs</h2>\n<p>Planning a 1st, 18th, 21st, 30th or 50th birthday in Canberra? Browse our <a href=\"{url:custom-cake-gallery}\">custom cake gallery</a> and tap <em>Order one like this</em> on any unicorn, soccer pitch, Spider-Man, Bluey, drip-and-macaron or piped vintage heart cake—or upload your own reference photo on our <a href=\"{url:custom-cakes}?occasion=Birthday#quote\">custom cake quote form</a>.</p>\n<h2>Same-Week Orders, Eggless Options &amp; Canberra Delivery</h2>\n<ul>\n<li><strong>Free personalised writing:</strong> Type <em>\"Happy 10th Birthday Leo\"</em> on any cake page before adding to cart.</li>\n<li><strong>Dietary-friendly celebrations:</strong> Every cake uses 100% Halal ingredients (zero alcohol), with eggless, vegan and gluten-free mud cake options available on request—see our <a href=\"{url:allergens}\">allergen guide</a>.</li>\n<li><strong>Greenway pickup or Canberra-wide delivery:</strong> Collect free from {store_address} or select scheduled delivery across Tuggeranong, Woden, Weston Creek, Molonglo Valley, Inner South, Inner North, Belconnen, Gungahlin and Queanbeyan at checkout. Need a birthday cake urgently today? Call {store_phone}.</li>\n</ul>',1,'Birthday Cakes Canberra | The Continental Cake Shop','Birthday cakes in Canberra made fresh in Greenway with 100% Halal ingredients. Order a favourite online or get a quote for a custom kids or adults cake.','2026-10-21 10:26:08'),
(6,'wedding-cakes-canberra','landing','Wedding and engagement cakes in Canberra','Wedding, engagement and anniversary cakes designed with you and made fresh in our Greenway kitchen.','Wedding',1,2,'<h2>Custom 100% Halal Wedding, Engagement &amp; Nikah Cakes in Canberra</h2>\n<p>{store_name} designs, bakes and finishes custom single-tier, two-tier and three-tier wedding and engagement cakes from scratch in our Greenway kitchen (established March 2015), with scheduled courier delivery to reception venues across Canberra, Tuggeranong, Yarralumla, Barton, Braddon, Belconnen, Gungahlin and Queanbeyan. Every tier is baked with <strong>100% Halal ingredients</strong>—zero alcohol, no animal shortening, and Halal-suitable fillings—with eggless, vegan and gluten-free tiers available on request.</p>\n<img src=\"{url:assets/img/wedding-tier-guide.svg}\" alt=\"Canberra wedding cake tier sizing chart comparing Single-Tier Engagement Cake (14 to 25 guests), Two-Tier Wedding Cake (35 to 60 guests), and Three-Tier Statement Wedding Cake (75 to 120+ guests)\" width=\"960\" height=\"360\">\n<h2>Wedding Cake Tiers, Guest Counts &amp; Recommended Booking Notice</h2>\n<table>\n<thead><tr><th scope=\"col\">Tier Configuration</th><th scope=\"col\">Approximate Guests (Coffee Portions)</th><th scope=\"col\">Recommended Notice</th><th scope=\"col\">Fulfilment &amp; Venue Options</th></tr></thead>\n<tbody>\n<tr><td><strong>Single-Tier Tall Cake</strong></td><td>14 – 25 guests</td><td>5 – 7 days (minimum {custom_lead_days} days)</td><td>Greenway pickup or Canberra delivery</td></tr>\n<tr><td><strong>Two-Tier Wedding / Engagement Cake</strong></td><td>35 – 60 guests</td><td>2 – 4 weeks</td><td>Scheduled Canberra &amp; Queanbeyan venue delivery</td></tr>\n<tr><td><strong>Three-Tier Statement Wedding Cake</strong></td><td>75 – 120+ guests</td><td>4 – 8+ weeks (book early for Oct–Apr peak season)</td><td>Scheduled Canberra &amp; winery/hotel venue delivery</td></tr>\n<tr><td><strong>Kitchen Cutting Cakes + Profiterole Towers</strong></td><td>20 – 100+ extra serves</td><td>1 – 2 weeks</td><td>Pair a display tier with <a href=\"{url:shop/mud-cakes}\">Full mud cakes</a> or <a href=\"{url:cakes/profiterole-tower}\">Profiterole Towers</a></td></tr>\n</tbody>\n</table>\n<h2>Most Popular Wedding Sponge Flavours &amp; Decorative Styles</h2>\n<p>Each tier in a multi-tier wedding cake can be baked in a different flavour so you can please both traditional and chocolate-loving guests:</p>\n<ul>\n<li><strong>Dense <a href=\"{url:shop/mud-cakes}\">Mud Cake Tiers</a> (Chocolate, White Gold, Marble or Caramel):</strong> Our top recommendation for tall sculpted, smooth-buttercream and multi-tier wedding cakes—moist, rich and structurally rock-solid during transport and reception display.</li>\n<li><strong><a href=\"{url:cakes/red-velvet-cake}\">Red Velvet</a> &amp; Spiced Carrot Tiers:</strong> Striking slice contrast layered with smooth cream cheese or vanilla buttercream.</li>\n<li><strong>Light <a href=\"{url:shop/tortes}\">European Sponge Tortes</a>:</strong> Ideal for single-tier engagement parties, Nikah lunches and bridal showers where guests prefer airy sponge and fresh cream.</li>\n<li><strong>Finishes:</strong> Smooth ivory buttercream, vintage Lambeth piping, textured palette-knife waves, semi-naked rustic finishes, gold leaf accents, macarons and fresh florals matched to your florist\'s palette.</li>\n</ul>\n<h2>How Quotes, Deposits &amp; Tasting Slices Work</h2>\n<ol>\n<li><strong>Share your wedding details:</strong> Send your reception date, venue suburb, approximate guest count, preferred flavours and 1–2 inspiration photos via our <a href=\"{url:custom-cakes}?occasion=Wedding#quote\">wedding quote form</a> (or pick a design from our <a href=\"{url:custom-cake-gallery}\">custom cake gallery</a>).</li>\n<li><strong>Sample flavours beforehand:</strong> Want to taste sponges with your partner before locking in tiers? Order a Midi cake or visit our counter at {store_address} to try our house mud cakes, velvet cakes and tortes.</li>\n<li><strong>Lock in your date online:</strong> We email an itemised quote; accept online with a 50% booking deposit to reserve your weekend baking and delivery slot, or call our Greenway team on {store_phone}.</li>\n</ol>',1,'Wedding Cakes Canberra | The Continental Cake Shop','Wedding and engagement cakes in Canberra, designed with you and made fresh in Greenway with 100% Halal ingredients. Send your idea and get a quote today.','2026-10-21 10:26:08'),
(7,'baby-shower-cakes-canberra','landing','Baby shower and gender reveal cakes in Canberra','Sweet cakes for baby showers, gender reveals, christenings and first birthdays, made fresh in Greenway.','Baby shower',1,3,'<h2>Baby Shower, Gender Reveal &amp; First Birthday Cakes in Canberra</h2>\n<p>{store_name} bakes custom baby shower cakes, pink-or-blue hidden sponge gender reveal cakes, Aqiqah, naming-day, christening and first birthday cakes from scratch in our Greenway kitchen using <strong>100% Halal ingredients</strong>. Because our entire kitchen is 100% alcohol-free and Halal, expectant mums, grandparents, children and extended family can all share the same centrepiece with complete peace of mind.</p>\n<h2>Compare Baby Shower &amp; Gender Reveal Cake Options</h2>\n<table>\n<thead><tr><th scope=\"col\">Celebration Style</th><th scope=\"col\">How It Is Made</th><th scope=\"col\">Typical Guests</th><th scope=\"col\">Recommended Notice</th></tr></thead>\n<tbody>\n<tr><td><strong>Hidden-Colour Gender Reveal Cake</strong></td><td>Neutral white/cream buttercream outside; tinted pink or blue sponge or cream revealed on the first cut</td><td>12 – 30 guests</td><td>4 – 7 days (minimum {custom_lead_days} days)</td></tr>\n<tr><td><strong>Pastel &amp; Teddy Baby Shower Cake</strong></td><td>Single-tier tall or vintage piped heart cake in sage, butter yellow, blush, sky blue or neutral florals</td><td>14 – 35 guests</td><td>5 – 7 days</td></tr>\n<tr><td><strong>Two-Tier Christening, Aqiqah or 1st Birthday</strong></td><td>Sturdy <a href=\"{url:shop/mud-cakes}\">mud cake</a> or <a href=\"{url:shop/velvet-cakes}\">velvet</a> tiers with custom name topper, rosettes or fondant details</td><td>35 – 60+ guests</td><td>2 – 3 weeks</td></tr>\n<tr><td><strong>Last-Minute Ready-Made Cake + Message</strong></td><td>Menu <a href=\"{url:shop/tortes}\">torte</a>, mud cake or <a href=\"{url:cakes/profiterole-tower}\">Profiterole Tower</a> with free piped baby message</td><td>8 – 20 guests</td><td>{min_lead_hours} hours online</td></tr>\n</tbody>\n</table>\n<h2>How Secret Gender Reveal Orders Work (Keep It a Surprise!)</h2>\n<p>Want the baby\'s gender to surprise both parents too? Have your ultrasound envelope dropped into our shop at {store_address}, or ask a trusted friend or sibling to email the result with your quote reference—our pastry team will bake the hidden pink or blue interior while keeping the outside completely neutral in white buttercream, gold leaf or pastel clouds.</p>\n<h2>Dietary Options, Greenway Pickup &amp; Canberra Delivery</h2>\n<ul>\n<li><strong>100% Halal, zero alcohol &amp; pregnancy-conscious handling:</strong> Baked fresh to order with pasteurised Australian dairy; keep cream and buttercream cakes refrigerated at 2–5°C until serving.</li>\n<li><strong>Eggless, vegan &amp; gluten-free requests:</strong> Call {store_phone} before ordering so we can tailor sponge and frosting options—and check our <a href=\"{url:allergens}\">allergen information</a>.</li>\n<li><strong>Easy ordering:</strong> <a href=\"{url:custom-cakes}?occasion=Baby%20shower#quote\">Send your theme and photo for a quote</a>, or <a href=\"{url:shop}\">order a ready-made cake online</a> with {min_lead_hours} hours notice for pickup in Greenway or delivery across Canberra and Queanbeyan.</li>\n</ul>',1,'Baby Shower Cakes Canberra | Continental Cake Shop','Baby shower, gender reveal and christening cakes in Canberra, made fresh in Greenway with 100% Halal ingredients. Get a quote for a custom cake or order online.','2026-10-21 10:26:08'),
(8,'halal-cakes-canberra','landing','Halal cakes in Canberra','Every cake we bake is made with 100% Halal ingredients in our shop in Greenway, Tuggeranong.','Birthday',1,4,'<h2>Are All Cakes at The Continental Cake Shop 100% Halal?</h2>\n<p>Yes — every cake, European sponge torte, mud cake, velvet cake, cheesecake and dessert baked at {store_name} in Greenway, Tuggeranong is made exclusively with <strong>100% Halal ingredients</strong>. Because our entire Canberra kitchen operates to a strict Halal-only ingredient standard since opening in March 2015 (rather than baking a small separate Halal line alongside non-Halal products), there is zero cross-contact with alcohol, liqueurs, pork-derived gelatine or animal shortening.</p>\n<h3>Our 100% Halal Kitchen Ingredient Standards</h3>\n<table>\n<thead><tr><th scope=\"col\">Ingredient Category</th><th scope=\"col\">Our Greenway Kitchen Standard</th><th scope=\"col\">Applies To</th></tr></thead>\n<tbody>\n<tr><td><strong>Gelatine &amp; Setting Agents</strong></td><td>100% Halal-suitable gelatine &amp; plant-based setting agents only (zero porcine gelatine)</td><td>All <a href=\"{url:shop/continental-cheesecakes}\">Continental Cheesecakes</a> &amp; mousse tortes</td></tr>\n<tr><td><strong>Alcohol &amp; Liqueurs</strong></td><td>Strictly 0% alcohol — espresso &amp; fruit syrups used instead of coffee/cherry liqueurs</td><td><a href=\"{url:cakes/tiramisu-torte}\">Tiramisu Torte</a>, <a href=\"{url:cakes/black-forest-torte}\">Black Forest Torte</a> &amp; entire menu</td></tr>\n<tr><td><strong>Fats, Emulsifiers &amp; Dairy</strong></td><td>Fresh Australian dairy cream, butter &amp; Halal-suitable vegetable oils (zero lard/tallow)</td><td>All sponges, <a href=\"{url:shop/mud-cakes}\">Mud Cakes</a>, choux pastry &amp; frostings</td></tr>\n<tr><td><strong>Vanilla, Colours &amp; Toppings</strong></td><td>Halal-suitable vanilla flavourings, cocoa, chocolates &amp; edible image inks</td><td>All ready-made &amp; <a href=\"{url:custom-cakes}\">Custom Celebration Cakes</a></td></tr>\n</tbody>\n</table>\n<h2>Halal Cakes for Eid, Nikah, Weddings, Birthdays &amp; Office Events</h2>\n<p>Whether you need a last-minute cake for Iftar or an office farewell in Woden, Barton or Civic, or a tiered centrepiece for a Nikah, Walima, Aqiqah or milestone birthday, you can order two ways:</p>\n<ul>\n<li><strong>Ready-to-order menu cakes ({min_lead_hours} hours notice):</strong> Choose from 34+ <a href=\"{url:shop/tortes}\">tortes</a>, <a href=\"{url:shop/mud-cakes}\">mud cakes</a>, <a href=\"{url:shop/velvet-cakes}\">velvet cakes</a>, <a href=\"{url:shop/baked-cheesecakes}\">baked cheesecakes</a>, <a href=\"{url:shop/continental-cheesecakes}\">continental cheesecakes</a> and <a href=\"{url:cakes/profiterole-tower}\">profiterole towers</a> from $22.95, with free custom piped writing included on celebration cakes.</li>\n<li><strong>Custom &amp; tiered celebration cakes (at least {custom_lead_days} days notice; 2–8+ weeks for <a href=\"{url:wedding-cakes-canberra}\">wedding cakes</a>):</strong> Send a reference photo via our <a href=\"{url:custom-cakes}\">custom cake quote form</a> for custom buttercream, vintage piping, edible images or multi-tier designs.</li>\n</ul>\n<img src=\"{url:assets/img/cake-serving-guide.svg}\" alt=\"Halal cake sizes and serving guide in Canberra: Half Cheesecake serves 6, Midi serves 8, Full Cheesecake serves 12, Full Cake serves 14, XL and Towers serve 15 to 20\" width=\"960\" height=\"340\">\n<h2>Eggless, Vegan, Gluten-Free &amp; Allergen Transparency</h2>\n<p>Many Canberra celebrations include guests with extra dietary requirements. Select <strong>Eggless</strong> directly on eligible cake pages, or call {store_phone} before ordering to arrange <strong>gluten-free mud cakes</strong> or <strong>vegan celebration cakes</strong>. Our Greenway kitchen handles dairy, eggs, wheat (gluten), soy and tree nuts on shared equipment—please review our <a href=\"{url:allergens}\">Allergen Information</a> before ordering for guests with severe allergies, and collect from {store_address} or book Canberra &amp; Queanbeyan delivery at checkout.</p>',1,'Halal Cakes Canberra | The Continental Cake Shop','Looking for Halal cakes in Canberra? Every cake at The Continental Cake Shop in Greenway is made with 100% Halal ingredients. Order online or get a quote.','2026-10-21 10:26:08');
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
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `kind` enum('payment','refund') NOT NULL DEFAULT 'payment',
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(20) NOT NULL,
  `reference` varchar(190) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payments_reference` (`reference`),
  KEY `idx_payments_order` (`order_id`,`created_at`),
  KEY `fk_payments_admin` (`admin_id`),
  CONSTRAINT `fk_payments_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `image` varchar(255) NOT NULL COMMENT 'Path inside public/uploads',
  `alt` varchar(160) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_images` (`product_id`,`sort_order`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `product_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `name` varchar(80) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Added to the size price',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_options_product` (`product_id`,`is_active`,`sort_order`),
  CONSTRAINT `fk_options_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `product_options` WRITE;
/*!40000 ALTER TABLE `product_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_options` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `product_seo_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_seo_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `fields` mediumtext NOT NULL COMMENT 'JSON: the SEO fields as they were before the change',
  `replaced_by` varchar(20) NOT NULL DEFAULT 'edit' COMMENT 'edit, ai or restore: what replaced this version',
  `admin_name` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_seo_history_product` (`product_id`),
  CONSTRAINT `fk_seo_history_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `product_sizes` WRITE;
/*!40000 ALTER TABLE `product_sizes` DISABLE KEYS */;
INSERT INTO `product_sizes` VALUES
(1,1,'Midi','Serves 8',44.95,1,1),
(2,1,'Full','Serves 14',54.95,2,1),
(3,2,'Full','Serves 14',64.95,1,1),
(4,3,'Midi','Serves 8',39.95,1,1),
(5,3,'Full','Serves 14',49.95,2,1),
(6,4,'Full','Serves 14',49.95,1,1),
(7,5,'Midi','Serves 8',49.95,1,1),
(8,5,'Full','Serves 14',59.95,2,1),
(9,6,'Full','Serves 14',64.95,1,1),
(10,7,'Midi','Serves 8',44.95,1,1),
(11,7,'Full','Serves 14',54.95,2,1),
(12,8,'Full','Serves 14',64.95,1,1),
(13,9,'Midi','Serves 8',39.95,1,1),
(14,9,'Full','Serves 14',49.95,2,1),
(15,10,'Full','Serves 14',54.95,1,1),
(18,12,'Large','Serves 14',59.95,1,1),
(19,12,'XL','Serves 20',69.95,2,1),
(20,13,'Midi','Serves 8',39.95,1,1),
(21,13,'Full','Serves 14',49.95,2,1),
(22,14,'Midi','Serves 8',39.95,1,1),
(23,14,'Full','Serves 14',49.95,2,1),
(26,16,'Midi','Serves 8',59.95,1,1),
(27,16,'Full','Serves 14',69.95,2,1),
(34,20,'Midi','Serves 8',39.95,1,1),
(35,20,'Full','Serves 14',49.95,2,1),
(36,21,'Midi','Serves 8',39.95,1,1),
(37,21,'Full','Serves 14',49.95,2,1),
(38,22,'Midi','Serves 8',39.95,1,1),
(39,22,'Full','Serves 14',49.95,2,1),
(40,23,'Midi','Serves 8',39.95,1,1),
(41,23,'Full','Serves 14',49.95,2,1),
(42,24,'Midi','Serves 8',59.95,1,1),
(43,24,'Full','Serves 14',69.95,2,1),
(44,25,'Half','Serves 6',22.95,1,1),
(45,25,'Full','Serves 12',39.95,2,1),
(46,26,'Half','Serves 6',22.95,1,1),
(47,26,'Full','Serves 12',39.95,2,1),
(48,27,'Half','Serves 6',22.95,1,1),
(49,27,'Full','Serves 12',39.95,2,1),
(50,28,'Half','Serves 6',22.95,1,1),
(51,28,'Full','Serves 12',39.95,2,1),
(52,29,'Half','Serves 6',24.95,1,1),
(53,29,'Full','Serves 12',44.95,2,1),
(54,30,'Half','Serves 6',22.95,1,1),
(55,30,'Full','Serves 12',39.95,2,1),
(56,31,'Half','Serves 6',22.95,1,1),
(57,31,'Full','Serves 12',39.95,2,1),
(58,32,'Half','Serves 6',22.95,1,1),
(59,32,'Full','Serves 12',39.95,2,1),
(60,33,'Half','Serves 6',22.95,1,1),
(61,33,'Full','Serves 12',39.95,2,1),
(64,35,'Half','Serves 6',22.95,1,1),
(65,35,'Full','Serves 12',39.95,2,1),
(66,36,'Half','Serves 6',22.95,1,1),
(67,36,'Full','Serves 12',39.95,2,1),
(70,38,'Midi','Serves 8',34.95,1,1),
(71,38,'Full','Serves 14',44.95,2,1),
(86,40,'One size','Serves 10',39.95,1,1),
(87,41,'Midi','Serves 8',34.95,1,1),
(88,41,'Full','Serves 14',44.95,2,1),
(89,39,'20 pieces','Serves about 10',55.00,1,1),
(90,39,'25 pieces','Serves about 12',65.00,2,1),
(91,39,'30 pieces','Serves about 15',75.00,3,1),
(92,39,'35 pieces','Serves about 18',85.00,4,1);
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
  `guide` mediumtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `image_alt` varchar(160) DEFAULT NULL COMMENT 'Describes the main photo for Google and screen readers',
  `badge` varchar(40) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `allow_message` tinyint(1) NOT NULL DEFAULT 1,
  `is_enquiry` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Priced on request: shows an enquiry button instead of add to cart',
  `sold_out_until` date DEFAULT NULL COMMENT 'Last day the cake is sold out; 9999-12-31 = until switched back',
  `lead_time_hours` int(10) unsigned DEFAULT NULL COMMENT 'Overrides the global minimum notice when set',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `meta_title` varchar(160) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `focus_keyword` varchar(80) DEFAULT NULL COMMENT 'Words people search for, e.g. black forest cake canberra',
  `search_words` varchar(255) DEFAULT NULL COMMENT 'Other words people search for, comma separated (site search, Google data)',
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
(1,1,'Black Forest Torte','black-forest-torte','Chocolate sponge layered with fresh cream and cherries.','<p>Our <strong>Black Forest Torte Canberra</strong> customers return for year after year layers tender dark chocolate sponge with lightly sweetened whipped vanilla cream and juicy sour cherries, finished with curls of dark chocolate around the crown and cream rosettes. Unlike traditional European versions made with kirsch, our Greenway recipe is <strong>100% alcohol-free and 100% Halal</strong>, using cherry compote syrup so children and every guest at the table can share a slice.</p>\n<p>The balance of bittersweet cocoa crumb, tart cherries and cloud-light dairy cream keeps every forkful refreshing rather than heavy. Choose the <strong>Midi (serves 8)</strong> for a family birthday dinner or the <strong>Full (serves 14)</strong> for larger celebrations, with a free piped birthday or anniversary message included on top.</p>\n<h3>Serving, Dietary Notes &amp; Canberra Ordering</h3>\n<ul>\n<li><strong>Storage:</strong> Keep refrigerated at 2–5°C and serve chilled within 2–3 days.</li>\n<li><strong>Dietary requests:</strong> Ask our bakers on {store_phone} about eggless, vegan or gluten-free torte options.</li>\n<li><strong>Pickup &amp; delivery:</strong> Order online with {min_lead_hours} hours notice for free pickup at {store_address} or delivery across Canberra and Queanbeyan.</li>\n</ul>','products/black-forest-torte.webp','Black Forest Torte, chocolate sponge layered with fresh cream and cherries',NULL,1,1,1,0,NULL,NULL,1,NULL,NULL,'black forest torte canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(2,1,'Mousse Torte','mousse-torte','Sponge layers with a light, airy mousse filling.','<p>Silky, feather-light and gently sweet, our <strong>Mousse Torte Canberra</strong> centrepiece pairs delicate sponge layers with an aerated chocolate-and-cream mousse filling and a smooth piped cream finish. Because the mousse is whipped for lift rather than dense ganache weight, it slices effortlessly after a rich roast or banquet dinner without feeling heavy.</p>\n<p>Baked fresh in our Greenway patisserie kitchen using <strong>100% Halal ingredients</strong>—including Halal-suitable setting agents, zero alcohol and fresh Australian cream—this Full-size torte generously <strong>serves 14 guests</strong> and includes complimentary piped lettering on top for birthdays, farewells or family gatherings.</p>\n<h3>Serving Suggestions &amp; Local Fulfilment</h3>\n<ul>\n<li><strong>Clean slicing tip:</strong> Keep chilled at 2–5°C right until serving and dip your knife in warm water between cuts to keep the mousse layers sharp.</li>\n<li><strong>Pairings:</strong> Lovely alongside espresso, peppermint tea or fresh berries from our <a href=\"{url:shop/tortes}\">European torte range</a>.</li>\n<li><strong>How to order:</strong> Book online at least {min_lead_hours} hours ahead for Greenway pickup at {store_address} or courier delivery across Canberra and Queanbeyan, or call {store_phone} for dietary enquiries.</li>\n</ul>','products/mousse-torte.webp','Mousse Torte, sponge layers with a light, airy mousse filling',NULL,0,1,1,0,NULL,NULL,2,NULL,NULL,'mousse torte canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(3,1,'Strawberry Torte','strawberry-torte','Vanilla sponge with fresh cream and strawberries.','<p>Bright, fruity and unmistakably fresh, our <strong>Strawberry Torte Canberra</strong> favourite layers soft golden vanilla sponge with clouds of whipped continental cream and sweet strawberries. Every bite contrasts airy house-baked sponge against cool dairy cream and berry tang, making it a go-to choice for spring birthdays, bridal showers, Mother\'s Day lunches and sunny Canberra courtyard gatherings.</p>\n<p>Prepared daily from scratch in our Greenway shop with <strong>100% Halal ingredients</strong> (zero alcohol, no animal shortening, Halal-suitable vanilla and glaze), it comes in both <strong>Midi (serves 8)</strong> and <strong>Full (serves 14)</strong> sizes—with your custom celebration message piped free on top.</p>\n<h3>Storage, Dietary Options &amp; Pickup or Delivery</h3>\n<ul>\n<li><strong>Freshness:</strong> Because we use real whipped cream and strawberries, store flat in the refrigerator at 2–5°C and enjoy within 48–72 hours.</li>\n<li><strong>Eggless or custom requests:</strong> Call our Tuggeranong bakers on {store_phone} before ordering to discuss eggless sponge or custom berry decorations.</li>\n<li><strong>Ordering:</strong> Reserve online with {min_lead_hours} hours notice for pickup at {store_address} or scheduled delivery throughout Canberra and Queanbeyan.</li>\n</ul>','products/strawberry-torte.webp','Strawberry Torte, vanilla sponge with fresh cream and strawberries',NULL,0,1,1,0,NULL,NULL,3,NULL,NULL,'strawberry torte canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(4,1,'Caramel Torte','caramel-torte','Sponge layers with smooth caramel cream.','<p>Inspired by classic Central European patisserie, our <strong>Caramel Torte Canberra</strong> speciality layers light Hungarian-style sponge with velvety chocolate buttercream and crowns the cake with a glossy golden caramel glaze. Where fresh-cream fruit tortes lean bright and summery, this Full-size torte delivers deeper roasted-sugar caramel warmth balanced by smooth cocoa cream.</p>\n<p>Handcrafted in Greenway since 2015 using <strong>100% Halal ingredients</strong> with zero alcohol, the Full cake <strong>serves 14 guests</strong> comfortably (or up to 18 smaller coffee fingers) and includes free piped writing for milestone birthdays, Father\'s Day dinners, office shouts and anniversaries.</p>\n<h3>Serving &amp; Canberra Delivery Details</h3>\n<ul>\n<li><strong>Texture tip:</strong> Keep refrigerated at 2–5°C, then stand out of the fridge for 15 minutes before cutting so the chocolate buttercream softens to a silky finish.</li>\n<li><strong>Compare caramel styles:</strong> Prefer a denser fudgy crumb? Try our <a href=\"{url:cakes/caramel-mud-cake}\">Caramel Mud Cake</a> or chilled <a href=\"{url:cakes/caramel-cheesecake}\">Caramel Cheesecake</a>.</li>\n<li><strong>Fulfilment:</strong> Order online {min_lead_hours} hours ahead for pickup at {store_address} or delivery across Canberra and Queanbeyan; call {store_phone} for allergen advice.</li>\n</ul>','products/caramel-torte.webp','Caramel Torte, sponge layers with smooth caramel cream',NULL,0,1,1,0,NULL,NULL,4,NULL,NULL,'caramel torte canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(5,1,'Zebra Torte','zebra-torte','Chocolate and vanilla sponge layered together with cream.','<p>Why choose between vanilla and chocolate when your guests can have both in every wedge? Our <strong>Zebra Torte Canberra</strong> crowd-pleaser alternates layers of tender chocolate sponge and golden vanilla sponge with fresh whipped cream, coats the sides in fine chocolate crumbs, and finishes the top with piped cream rosettes.</p>\n<p>When sliced at the table, the striking dark-and-light striped interior makes an instant talking point for family birthdays, school celebrations and office morning teas. Baked fresh in Greenway using <strong>100% Halal ingredients</strong> and zero alcohol, it is available in <strong>Midi (serves 8)</strong> and <strong>Full (serves 14)</strong> sizes with complimentary message writing on top.</p>\n<h3>Good to Know Before Ordering</h3>\n<ul>\n<li><strong>All-ages appeal:</strong> Mild sweetness and dual sponge flavours satisfy picky kids and adults alike without heavy fondant.</li>\n<li><strong>Care &amp; transport:</strong> Carry flat in your car footwell, refrigerate at 2–5°C, and enjoy within 2–3 days.</li>\n<li><strong>Canberra pickup &amp; delivery:</strong> Order online at least {min_lead_hours} hours ahead for free Greenway pickup ({store_address}) or delivery across Canberra and Queanbeyan, or phone {store_phone}.</li>\n</ul>','products/zebra-torte.webp','Zebra Torte, chocolate and vanilla sponge layered together with cream',NULL,0,1,1,0,NULL,NULL,5,NULL,NULL,'zebra torte canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(6,1,'Hazelnut Torte','hazelnut-torte','Sponge layered with rich hazelnut cream.','<p>Crafted for lovers of roasted praline and European nut patisserie, our <strong>Hazelnut Torte Canberra</strong> favourite layers tender house sponge with smooth, aromatic hazelnut cream and nutty chocolate accents. Rich yet buoyant on the fork, it captures the comforting flavour of toasted hazelnuts and cream without overwhelming sweetness—pairing effortlessly with flat whites, Turkish coffee or black tea.</p>\n<p>Baked to order in our Greenway kitchen with <strong>100% Halal ingredients</strong> (zero liqueurs or alcohol), this Full-size celebration torte <strong>serves 14 guests</strong> and comes with your custom message piped free across the top.</p>\n<h3>Allergen, Storage &amp; Ordering Information</h3>\n<ul>\n<li><strong>Tree nut notice:</strong> Contains hazelnuts and dairy, and is baked in our shared Greenway kitchen—see our <a href=\"{url:allergens}\">allergen guide</a> if guests have nut allergies.</li>\n<li><strong>Storage:</strong> Store chilled at 2–5°C and slice with a clean warm knife within 2–3 days of collection.</li>\n<li><strong>Order online:</strong> Place your order {min_lead_hours} hours ahead for timed pickup at {store_address} or delivery anywhere in Canberra and Queanbeyan, or call {store_phone}.</li>\n</ul>','products/hazelnut-torte.webp','Hazelnut Torte, sponge layered with rich hazelnut cream',NULL,1,1,1,0,NULL,NULL,6,NULL,NULL,'hazelnut torte canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(7,1,'Double Choc Strawberry Torte','double-choc-strawberry-torte','Chocolate sponge with chocolate cream and strawberries.','<p>Pairing dark cocoa richness with juicy red berries, our <strong>Double Choc Strawberry Torte Canberra</strong> bestseller stacks moist chocolate sponge layers with whipped chocolate cream and fresh strawberries. Where a mud cake is dense and fudgy, this double-chocolate torte stays airy and spoon-soft, letting the bright acidity of fresh strawberries lift the cocoa cream in every bite.</p>\n<p>Made fresh daily in our Tuggeranong kitchen using <strong>100% Halal ingredients</strong> and zero alcohol, it is offered in <strong>Midi (serves 8)</strong> for intimate dinners and <strong>Full (serves 14)</strong> for birthday parties, anniversaries, Eid desserts and Valentine\'s celebrations—complete with free custom piped lettering.</p>\n<h3>Storage, Dietary Requests &amp; Fulfilment</h3>\n<ul>\n<li><strong>Chilled freshness:</strong> Keep refrigerated at 2–5°C to protect the fresh strawberries and chocolate dairy cream; best enjoyed within 48–72 hours.</li>\n<li><strong>Custom &amp; eggless options:</strong> Phone {store_phone} before ordering if you require eggless sponge or a custom berry-and-drip presentation.</li>\n<li><strong>Pickup or Canberra delivery:</strong> Book online {min_lead_hours} hours ahead from {store_address} or select scheduled Canberra and Queanbeyan delivery at checkout.</li>\n</ul>','products/double-choc-strawberry-torte.webp','Double Choc Strawberry Torte, chocolate sponge with chocolate cream and strawberries',NULL,1,1,1,0,NULL,NULL,7,NULL,NULL,'double choc strawberry torte canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(8,1,'Cookies \'n\' Cream Torte','cookies-n-cream-torte','Sponge layers with cookies and cream filling.','<p>Crunchy chocolate biscuit pieces folded through pillowy cream between soft sponge layers make our <strong>Cookies \'n\' Cream Torte Canberra</strong> party cake an unmistakable hit with kids, teens and Oreo-loving adults. Crowned with piped cream swirls and crisp chocolate sandwich biscuits, it delivers nostalgic cookies-and-milk flavour in a light bakery-fresh torte format.</p>\n<p>Baked from scratch in Greenway with <strong>100% Halal ingredients</strong>—zero alcohol and no animal shortening—this generous Full torte <strong>serves 14 party guests</strong> and includes a free piped birthday name or message on top.</p>\n<h3>Party Planning, Storage &amp; Canberra Delivery</h3>\n<ul>\n<li><strong>Great for kids\' &amp; teen birthdays:</strong> Easier to slice and lighter after party food than heavy fondant cakes; add candles or a number topper in your cart.</li>\n<li><strong>Fridge care:</strong> Store chilled at 2–5°C in its flat bakery box and enjoy within 2–3 days.</li>\n<li><strong>Easy online booking:</strong> Order at least {min_lead_hours} hours in advance for pickup at {store_address} or delivery across Canberra and Queanbeyan. Questions? Call {store_phone}.</li>\n</ul>','products/cookies-n-cream-torte.webp','Cookies \'n\' Cream Torte, sponge layers with cookies and cream filling',NULL,0,1,1,0,NULL,NULL,8,NULL,NULL,'cookies n cream torte canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(9,1,'Vanilla Torte','vanilla-torte','Classic vanilla sponge with fresh cream.','<p>Clean, timeless and endlessly versatile, our <strong>Vanilla Torte Canberra</strong> classic pairs fragrant golden vanilla sponge layers with freshly whipped continental cream. Because its flavour profile is gentle and pure—free from heavy spices, nuts in the batter or dark cocoa—it is our safest crowd-pleaser for mixed-age family gatherings, toddlers\' birthdays, office farewells and school celebrations.</p>\n<p>Every Vanilla Torte is baked in house at our Greenway shop using <strong>100% Halal ingredients</strong> (Halal-suitable vanilla, zero alcohol and fresh Australian dairy). Pick <strong>Midi (serves 8)</strong> or <strong>Full (serves 14)</strong>, and type a complimentary birthday or congratulations message before adding to cart.</p>\n<h3>Customisation, Storage &amp; Local Ordering</h3>\n<ul>\n<li><strong>Easy to dress up:</strong> Delicious served with fresh seasonal berries on the side, or <a href=\"{url:custom-cakes}\">request a custom decorated vanilla sponge</a> with pastel piping or an edible image.</li>\n<li><strong>Storage:</strong> Refrigerate at 2–5°C and transport flat on your car floor; best within 2–3 days.</li>\n<li><strong>Pickup &amp; delivery:</strong> Ready in {min_lead_hours} hours for Greenway pickup ({store_address}) or delivery across Canberra and Queanbeyan; call {store_phone} for eggless options.</li>\n</ul>','products/vanilla-torte.webp','Vanilla Torte, classic vanilla sponge with fresh cream',NULL,0,1,1,0,NULL,NULL,9,NULL,NULL,'vanilla torte canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(10,1,'Tiramisu Torte','tiramisu-torte','Coffee-soaked sponge with a creamy tiramisu filling.','<p>One of our highest-rated customer favourites since 2015, our <strong>Tiramisu Torte Canberra</strong> speciality soaks tender sponge layers in bold espresso coffee, layers them with luscious mascarpone-style tiramisu cream, and finishes the crown with a generous dusting of fine dark cocoa. Crucially, we craft it with <strong>zero Marsala, zero coffee liqueur and 100% Halal ingredients</strong>—delivering authentic Italian café flavour that everyone at your table can enjoy.</p>\n<p>Balanced between aromatic roasted espresso bitterness and cool velvety cream, this Full-size torte <strong>serves 14 guests</strong> and includes optional free piped lettering on top for birthdays, graduations and dinner parties.</p>\n<h3>Why Canberra Coffee Lovers Rate It Highly</h3>\n<ul>\n<li><strong>Not overly sweet:</strong> Real coffee infusion keeps the finish grown-up, clean and light after an evening meal.</li>\n<li><strong>Storage:</strong> Keep refrigerated at 2–5°C until the moment you slice; flavours meld beautifully over the first 24–48 hours.</li>\n<li><strong>Fast online ordering:</strong> Reserve at least {min_lead_hours} hours ahead for free pickup at {store_address} or scheduled courier delivery across Canberra and Queanbeyan, or call {store_phone}.</li>\n</ul>','products/tiramisu-torte.webp','Tiramisu Torte, coffee-soaked sponge with a creamy tiramisu filling','Customer favourite',1,1,1,0,NULL,NULL,10,NULL,NULL,'tiramisu torte canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(12,1,'Rainbow Torte','rainbow-torte','Colourful rainbow sponge layers with cream. Great for kids\' parties.','<p>Cut the first wedge and reveal vibrant ribbons of bright rainbow sponge stacked high between smooth white cream—our <strong>Rainbow Torte Canberra</strong> party cake is built for gasps around the birthday table. Unlike dense buttercream rainbow cakes that can feel heavy for young children, our European-style sponge stays soft, colourful and light with fresh whipped cream rosettes.</p>\n<p>Sized specifically for bigger party guest lists, it comes in <strong>Large (serves 14)</strong> and our generous <strong>XL party size (serves 20)</strong>—baked fresh in Greenway using <strong>100% Halal ingredients</strong>, Halal-suitable food colours and zero alcohol, with your child\'s name and age piped free on top.</p>\n<h3>Kids\' Party Sizing, Care &amp; Canberra Delivery</h3>\n<ul>\n<li><strong>Built for party crowds:</strong> The XL 20-serve torte comfortably feeds a whole classroom or family park gathering (keep chilled until cutting!).</li>\n<li><strong>Want a character topper too?</strong> Pair with candles at checkout or <a href=\"{url:custom-cakes}\">enquire about a custom Bluey, Unicorn or Mario design</a>.</li>\n<li><strong>Pickup &amp; delivery:</strong> Order online {min_lead_hours} hours ahead for pickup at {store_address} or delivery across Canberra and Queanbeyan; call {store_phone} with dietary questions.</li>\n</ul>','products/rainbow-torte.webp','Rainbow Torte with colourful rainbow sponge layers and cream',NULL,0,1,1,0,NULL,NULL,12,NULL,NULL,'rainbow torte canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(13,1,'Honey-Bee Torte','honey-bee-torte','Sponge layers with a honey cream filling.','<p>Modelled on the beloved European <em>Bienenstich</em> (bee-sting cake), our <strong>Honey-Bee Torte Canberra</strong> baker\'s favourite stacks three fragrant honey-infused sponge layers filled generously with fresh continental cream and smooth vanilla custard. Floral honey warmth in the crumb meets cool, creamy custard without heavy icing sugar—making it a standout choice for grandparents\' birthdays, afternoon teas and guests who prefer nuanced European flavours.</p>\n<p>Baked from scratch in our Greenway kitchen with <strong>100% Halal ingredients</strong> and zero alcohol, it is available in <strong>Midi (serves 8)</strong> and <strong>Full (serves 14)</strong> sizes, complete with free piped message writing.</p>\n<h3>Serving, Storage &amp; Ordering Notes</h3>\n<ul>\n<li><strong>Best served chilled:</strong> Store at 2–5°C in the refrigerator so the custard-and-cream layers hold neat, tall slices for 2–3 days.</li>\n<li><strong>Pairings:</strong> Exceptional alongside Earl Grey tea, chai or a mid-morning cappuccino.</li>\n<li><strong>Greenway pickup &amp; Canberra delivery:</strong> Book online with at least {min_lead_hours} hours notice from {store_address} or choose delivery across Canberra and Queanbeyan; call {store_phone} for dietary enquiries.</li>\n</ul>','products/honey-bee-torte.webp','Honey-Bee Torte, sponge layers with a honey cream filling',NULL,0,1,1,0,NULL,NULL,13,NULL,NULL,'honey-bee torte canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(14,1,'Continental Torte','continental-torte','Our signature house torte.','<p>Named after our bakery itself, the signature <strong>Continental Torte Canberra</strong> locals have celebrated with since March 2015 showcases traditional European sponge craft: delicate house-baked sponge layers brushed for tenderness and filled with our signature continental cream and smooth custard blend. Light on the palate, never cloying, and finished cleanly by hand, it is the quintessential all-occasion celebration cake.</p>\n<p>Every Continental Torte is baked fresh in our Greenway shop using <strong>100% Halal ingredients</strong>—zero alcohol, no animal shortening and Halal-suitable vanilla—in <strong>Midi (serves 8)</strong> and <strong>Full (serves 14)</strong> sizes, with a complimentary piped message on top.</p>\n<h3>When to Pick Our Signature House Torte</h3>\n<ul>\n<li><strong>Ideal for mixed tastes:</strong> When ordering for an office team, extended family or multi-generational birthday where you want universal appeal.</li>\n<li><strong>Care &amp; transport:</strong> Keep level in the car footwell and refrigerate at 2–5°C for up to 3 days.</li>\n<li><strong>Order online:</strong> Reserve {min_lead_hours} hours ahead for free pickup at {store_address} or delivery across Canberra and Queanbeyan, or phone {store_phone} about eggless options.</li>\n</ul>','products/continental-torte.webp','Continental Torte, our signature house torte',NULL,0,1,1,0,NULL,NULL,14,NULL,NULL,'continental torte canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(16,2,'Red Velvet Cake','red-velvet-cake','Red velvet sponge with cream cheese frosting.','<p>Velvety crimson cocoa-kissed sponge meets thick, tangy layers of smooth cream cheese frosting in our customer-favourite <strong>Red Velvet Cake Canberra</strong> centrepiece. Hand-frosted in our Greenway kitchen, the gentle buttermilk-style tang of the cream cheese icing balances the tender red crumb so every forkful tastes plush, rich and clean rather than sugary.</p>\n<p>Baked from scratch with <strong>100% Halal ingredients</strong> (Halal-suitable colours and vanilla, zero alcohol and no animal shortening), our Red Velvet Cake comes in <strong>Midi (serves 8)</strong> and <strong>Full (serves 14)</strong> sizes, with free custom piped writing included for birthdays, anniversaries, engagements and Eid celebrations.</p>\n<h3>Slicing, Storage &amp; Canberra Fulfilment</h3>\n<ul>\n<li><strong>Best serving temperature:</strong> Store refrigerated at 2–5°C, then rest on the bench for 20 minutes before slicing so the velvet crumb and cream cheese frosting soften together.</li>\n<li><strong>Planning a tiered wedding cake?</strong> Red velvet is also available in 2- and 3-tier <a href=\"{url:wedding-cakes-canberra}\">Canberra wedding cakes</a>.</li>\n<li><strong>Pickup or delivery:</strong> Order online {min_lead_hours} hours ahead from {store_address} or book courier delivery across Canberra and Queanbeyan; call {store_phone} for eggless enquiries.</li>\n</ul>','products/red-velvet-cake.webp','Red Velvet Cake, red velvet sponge with cream cheese frosting','Customer favourite',1,1,1,0,NULL,NULL,1,NULL,NULL,'red velvet cake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(20,3,'Chocolate Mud Cake','chocolate-mud-cake','Rich, moist chocolate mud cake.','<p>Deeply cocoa-rich, moist and gloriously fudgy, our <strong>Chocolate Mud Cake Canberra</strong> staple is slow-baked in our Greenway ovens and coated in smooth dark chocolate ganache. Unlike airy sponge cakes, true Australian mud cake has a dense, Melt-in-the-mouth crumb that slices into neat party portions and holds up effortlessly during car trips to parks, restaurants and community halls across the ACT.</p>\n<p>Crafted using <strong>100% Halal ingredients</strong> with zero alcohol and no animal shortening, it is available in <strong>Midi (serves 8)</strong> and <strong>Full (serves 14—or up to 20 finger slices)</strong>, including free piped birthday or celebration lettering on top.</p>\n<h3>Serving, Gluten-Free Options &amp; Ordering</h3>\n<ul>\n<li><strong>Baker\'s serving tip:</strong> Refrigerate for storage, then stand at room temperature for 30–45 minutes before cutting with a warm knife so the ganache turns silky and fudge-soft.</li>\n<li><strong>Gluten-free &amp; custom bases:</strong> Gluten-free mud cakes are available by advance request on {store_phone}, and this recipe is our top base for <a href=\"{url:custom-cakes}\">custom themed cakes</a>.</li>\n<li><strong>Fulfilment:</strong> Order online {min_lead_hours} hours ahead for Greenway pickup ({store_address}) or Canberra &amp; Queanbeyan delivery.</li>\n</ul>','products/chocolate-mud-cake.webp','Rich, moist Chocolate Mud Cake',NULL,1,1,1,0,NULL,NULL,1,NULL,NULL,'chocolate mud cake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(21,3,'White Gold Mud Cake','white-gold-mud-cake','White chocolate mud cake with a caramel note.','<p>Smooth, buttery and laced with a warm caramelised white-chocolate note, our <strong>White Gold Mud Cake Canberra</strong> favourite offers all the dense, fudgy indulgence of a traditional mud cake in a golden ivory crumb. Finished with creamy white-chocolate ganache and piped detail, its elegant pale palette makes it a standout choice for engagements, 21st and 30th birthdays, christenings, Aqiqah and bridal showers.</p>\n<p>Every White Gold Mud Cake is baked from scratch in Greenway with <strong>100% Halal ingredients</strong>—zero alcohol and no animal fats—in <strong>Midi (serves 8)</strong> and <strong>Full (serves 14)</strong> sizes, with a complimentary custom message piped on top.</p>\n<h3>Transport, Storage &amp; Custom Styling</h3>\n<ul>\n<li><strong>Sturdy party travel:</strong> Firm mud structure travels cleanly across Canberra suburbs without sliding; bring to room temperature 30 minutes before serving.</li>\n<li><strong>Custom tier favourite:</strong> Frequently chosen for <a href=\"{url:wedding-cakes-canberra}\">tiered wedding cakes</a> and pastel <a href=\"{url:baby-shower-cakes-canberra}\">baby shower cakes</a>.</li>\n<li><strong>Order online:</strong> Book {min_lead_hours} hours ahead for free pickup at {store_address} or delivery across Canberra and Queanbeyan; call {store_phone} about gluten-free mud requests.</li>\n</ul>','products/white-gold-mud-cake.webp','White Gold Mud Cake, white chocolate mud cake with a caramel note',NULL,0,1,1,0,NULL,NULL,2,NULL,NULL,'white gold mud cake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(22,3,'Marble Mud Cake','marble-mud-cake','Chocolate and vanilla mud cake swirled together.','<p>Swirling ribbons of rich dark cocoa batter and golden vanilla mud batter together in the tin, our <strong>Marble Mud Cake Canberra</strong> crowd-pleaser gives every guest two classic mud flavours in a single artistic slice. Coated in glossy chocolate ganache, each cut reveals a unique marbled pattern while keeping the signature dense, moist crumb our Greenway mud cakes are known for.</p>\n<p>Baked fresh using <strong>100% Halal ingredients</strong> with zero alcohol and no animal shortening, it comes in <strong>Midi (serves 8)</strong> for family dinners and <strong>Full (serves 14)</strong> for offices, classrooms and birthday parties—complete with free custom piped writing on top.</p>\n<h3>Why Hosts Pick Marble Mud Cake</h3>\n<ul>\n<li><strong>Solves the chocolate-vs-vanilla debate:</strong> Ideal for workplace morning teas and kids\' parties where half the room wants vanilla and half wants chocolate.</li>\n<li><strong>Serving &amp; storage:</strong> Keeps well refrigerated for up to 4 days; stand at room temperature for 30 minutes before slicing.</li>\n<li><strong>Canberra pickup &amp; delivery:</strong> Order online {min_lead_hours} hours ahead from {store_address} or select scheduled Canberra and Queanbeyan delivery; phone {store_phone} for dietary options.</li>\n</ul>','products/marble-mud-cake.webp','Marble Mud Cake, chocolate and vanilla mud cake swirled together',NULL,0,1,1,0,NULL,NULL,3,NULL,NULL,'marble mud cake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(23,3,'Caramel Mud Cake','caramel-mud-cake','Moist caramel mud cake.','<p>Slow-baked for deep brown-butter and golden toffee richness, our <strong>Caramel Mud Cake Canberra</strong> indulgence pairs a moist, tender caramel mud crumb with a smooth caramel glaze finish. Less bitter than dark chocolate and warmer than plain vanilla, its buttery Dulce-style sweetness shines alongside hot coffee at autumn and winter celebrations, milestone birthdays and family Sunday roasts.</p>\n<p>Prepared from scratch in our Greenway kitchen with <strong>100% Halal ingredients</strong> (zero alcohol and zero animal shortening), choose <strong>Midi (serves 8)</strong> or <strong>Full (serves 14)</strong> and add your complimentary piped cake message before adding to cart.</p>\n<h3>Serving, Dietary &amp; Delivery Guide</h3>\n<ul>\n<li><strong>Slicing tip:</strong> Let chilled mud slices warm on the bench for 30 minutes before serving so the buttery caramel crumb opens up on the palate.</li>\n<li><strong>Gluten-free &amp; custom options:</strong> Call {store_phone} in advance to enquire about gluten-free mud baking or <a href=\"{url:custom-cakes}\">custom caramel drip cakes</a>.</li>\n<li><strong>Local fulfilment:</strong> Order online with {min_lead_hours} hours notice for pickup at {store_address} or delivery across Canberra and Queanbeyan.</li>\n</ul>','products/caramel-mud-cake.webp','Caramel Mud Cake, moist caramel mud cake',NULL,0,1,1,0,NULL,NULL,4,NULL,NULL,'caramel mud cake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(24,3,'Treasure Cake','treasure-cake','Our signature Treasure mud cake.','<p>Built as an instant party centrepiece, our signature <strong>Treasure Cake Canberra</strong> showstopper starts with our moist, fudgy house mud cake coated in glossy chocolate ganache and crowns the top with a generous bounty of assorted chocolates and sweet treats. There is no need to buy extra toppers—the loaded chocolate crown brings immediate theatre the moment you open the box.</p>\n<p>Handcrafted in Greenway using <strong>100% Halal ingredients</strong> (including 100% Halal confectionery toppings, zero alcohol and no animal fats), it is offered in <strong>Midi (serves 8)</strong> and <strong>Full (serves 14)</strong> sizes with a free piped birthday message nestled on top.</p>\n<h3>Ideal Occasions &amp; Canberra Ordering</h3>\n<ul>\n<li><strong>Favourite for teens &amp; chocoholics:</strong> Especially popular for 13th, 16th, 18th and 21st birthdays, graduation gifts and Father\'s Day celebrations.</li>\n<li><strong>Transport &amp; care:</strong> Dense mud base travels sturdily across Canberra; keep cool in the fridge and rest 30 minutes at room temperature before cutting between the chocolate toppings.</li>\n<li><strong>Pickup or delivery:</strong> Book online {min_lead_hours} hours ahead for pickup at {store_address} or delivery across Canberra and Queanbeyan; call {store_phone} for allergen details.</li>\n</ul>','products/treasure-cake.webp','Treasure Cake, our signature Treasure mud cake',NULL,0,1,1,0,NULL,NULL,5,NULL,NULL,'treasure cake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(25,4,'American Baked Cheesecake','american-baked-cheesecake','Creamy New York style baked cheesecake.','<p>Rich, velvety and baked until lightly golden over a buttery crushed-biscuit base, our <strong>American Baked Cheesecake Canberra</strong> classic honours authentic New York delicatessen style. Smooth full-bodied cream cheese, eggs and fragrant vanilla bake gently in our Greenway ovens for a dense, satin-smooth wedge with zero sourness or chalkiness.</p>\n<p>Made fresh using <strong>100% Halal ingredients</strong> (zero alcohol and no animal shortening), it comes in two practical sizes: <strong>Half (serves 6)</strong> for a cosy family dinner and <strong>Full (serves 12)</strong> for dinner parties, office morning teas and birthday dessert tables.</p>\n<h3>Serving Ideas &amp; Canberra Pickup or Delivery</h3>\n<ul>\n<li><strong>Dress it up or serve classic:</strong> Delicious plain alongside double espresso, or spoon fresh strawberries, raspberries or warm berry compote over each slice at home.</li>\n<li><strong>Clean wedge tip:</strong> Keep chilled at 2–5°C and wipe a thin knife with hot water between cuts.</li>\n<li><strong>Order online:</strong> Ready in {min_lead_hours} hours for free pickup at {store_address} or scheduled delivery across Canberra and Queanbeyan. Questions? Call {store_phone}.</li>\n</ul>','products/american-baked-cheesecake.webp','American Baked Cheesecake, creamy New York style baked cheesecake',NULL,0,1,1,0,NULL,NULL,1,NULL,NULL,'american baked cheesecake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(26,4,'Blueberry Baked Cheesecake','blueberry-baked-cheesecake','Baked cheesecake with blueberries.','<p>Combining dense New York-style oven-baked cream cheese richness with bursts of sweet-tart purple berries, our <strong>Blueberry Baked Cheesecake Canberra</strong> favourite sits on a crisp golden biscuit crumb and finishes with a glossy blueberry topping. The natural berry acidity cuts neatly through the creamy baked filling, making every forkful bright and indulgent.</p>\n<p>Baked in house at our Greenway shop with <strong>100% Halal ingredients</strong> and zero alcohol, choose a <strong>Half cheesecake (serves 6)</strong> for weekend family lunches or a <strong>Full cheesecake (serves 12)</strong> for workplace shouts and celebrations.</p>\n<h3>Baked vs. Chilled Blueberry &amp; Local Ordering</h3>\n<ul>\n<li><strong>Texture comparison:</strong> Prefer a lighter, mousse-set chilled filling instead of dense oven-baked richness? See our <a href=\"{url:cakes/blueberry-cheesecake}\">Blueberry Continental Cheesecake</a>.</li>\n<li><strong>Storage:</strong> Transport flat, refrigerate at 2–5°C, and enjoy within 3 days of pickup.</li>\n<li><strong>Fulfilment:</strong> Book online {min_lead_hours} hours ahead for Greenway pickup ({store_address}) or delivery across Canberra and Queanbeyan, or phone {store_phone}.</li>\n</ul>','products/blueberry-baked-cheesecake.webp','Blueberry Baked Cheesecake, baked cheesecake with blueberries',NULL,0,1,1,0,NULL,NULL,2,NULL,NULL,'blueberry baked cheesecake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(27,4,'Marble Baked Cheesecake','marble-baked-cheesecake','Baked cheesecake swirled with chocolate.','<p>Artfully swirled with dark chocolate ribbons through velvety vanilla cream cheese before slow oven baking, our <strong>Marble Baked Cheesecake Canberra</strong> dessert looks as striking on a cake stand as it tastes. Resting on a crunchy biscuit crust, each dense, custard-smooth wedge balances tangy baked cream cheese with mellow cocoa depth.</p>\n<p>Handcrafted in our Tuggeranong kitchen using <strong>100% Halal ingredients</strong>—zero alcohol and no animal shortening—it is available in <strong>Half (serves 6)</strong> and <strong>Full (serves 12)</strong> sizes to suit everything from a four-person dinner party to a twelve-guest celebration.</p>\n<h3>Pairing, Storage &amp; Canberra Delivery</h3>\n<ul>\n<li><strong>After-dinner winner:</strong> Pairs effortlessly with black coffee, hot chocolate or fresh cherries without needing extra cream on the side.</li>\n<li><strong>Care:</strong> Keep refrigerated at 2–5°C and slice chilled with a warm blade for crisp marble contrast.</li>\n<li><strong>How to order:</strong> Reserve online with {min_lead_hours} hours notice for free pickup at {store_address} or courier delivery across Canberra and Queanbeyan; call {store_phone} for allergen checks.</li>\n</ul>','products/marble-baked-cheesecake.webp','Marble Baked Cheesecake, baked cheesecake swirled with chocolate',NULL,0,1,1,0,NULL,NULL,3,NULL,NULL,'marble baked cheesecake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(28,4,'Tropical Baked Cheesecake','tropical-baked-cheesecake','Baked cheesecake with tropical fruit.','<p>Bring sunny fruit brightness to any Canberra table with our <strong>Tropical Baked Cheesecake Canberra</strong> crowd-pleaser: a rich, oven-baked cream cheese filling on a golden biscuit base crowned with a vibrant tropical fruit topping. Sweet mango, pineapple and passionfruit notes lift the creamy baked custard, bridging classic cheesecake indulgence with refreshing summer flavour.</p>\n<p>Baked fresh in Greenway using <strong>100% Halal ingredients</strong> and zero alcohol, pick a <strong>Half size (serves 6)</strong> for a small family treat or a <strong>Full size (serves 12)</strong> for barbecues, Eid lunches and office farewells.</p>\n<h3>Storage &amp; Canberra Ordering Details</h3>\n<ul>\n<li><strong>Storage &amp; shelf life:</strong> Keep chilled in the refrigerator at 2–5°C and enjoy within 2–3 days while the fruit glaze is glossy and vibrant.</li>\n<li><strong>Compare styles:</strong> Looking for an even lighter mousse-style citrus/fruit finish? Explore our <a href=\"{url:cakes/passionfruit-cheesecake}\">Passionfruit Continental Cheesecake</a>.</li>\n<li><strong>Pickup or delivery:</strong> Order online {min_lead_hours} hours ahead from {store_address} or book delivery across Canberra and Queanbeyan; call {store_phone} with any ingredient questions.</li>\n</ul>','products/tropical-baked-cheesecake.webp','Tropical Baked Cheesecake, baked cheesecake with tropical fruit',NULL,0,1,1,0,NULL,NULL,4,NULL,NULL,'tropical baked cheesecake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(29,4,'Hazelnut Baked Cheesecake','hazelnut-baked-cheesecake','Baked cheesecake with hazelnut.','<p>Rich roasted hazelnut meets velvety oven-baked cream cheese in our <strong>Hazelnut Baked Cheesecake Canberra</strong> dessert, baked gently on a crisp biscuit base in our Greenway kitchen. Nutty praline-chocolate notes weave through the dense, creamy cheesecake interior, making it a favourite winter warmer and sophisticated alternative to traditional birthday sponge.</p>\n<p>Prepared from scratch with <strong>100% Halal ingredients</strong>—zero alcohol and no animal shortening—it comes in <strong>Half (serves 6)</strong> and <strong>Full (serves 12)</strong> options to match intimate dinners and larger dessert spreads alike.</p>\n<h3>Allergen Notes, Slicing &amp; Fulfilment</h3>\n<ul>\n<li><strong>Allergen note:</strong> Contains tree nuts (hazelnuts), dairy, eggs and gluten—please review our <a href=\"{url:allergens}\">allergen guide</a> when catering for guests.</li>\n<li><strong>Serving tip:</strong> Refrigerate at 2–5°C; take out 10–15 minutes before serving alongside espresso for peak roasted-nut aroma.</li>\n<li><strong>Canberra pickup &amp; delivery:</strong> Book online at least {min_lead_hours} hours ahead for pickup at {store_address} or delivery across Canberra and Queanbeyan, or phone {store_phone}.</li>\n</ul>','products/hazelnut-baked-cheesecake.webp','Hazelnut Baked Cheesecake, baked cheesecake with hazelnut',NULL,0,1,1,0,NULL,NULL,5,NULL,NULL,'hazelnut baked cheesecake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(30,5,'Choc Chip Cheesecake','choc-chip-cheesecake','Light continental cheesecake with chocolate chips.','<p>Cool, airy and dotted throughout with crisp chocolate chips, our <strong>Choc Chip Cheesecake Canberra</strong> favourite is made in the light continental no-bake style on a buttery biscuit crumb. Every spoonful contrasts cloud-soft chilled cream cheese mousse against tiny snaps of dark chocolate—giving kids and chocolate fans a refreshing dessert that never feels heavy.</p>\n<p>Crucially, our Greenway kitchen sets every continental cheesecake exclusively with <strong>100% Halal-suitable ingredients</strong> (zero porcine gelatine, zero alcohol and no animal fats). Available in <strong>Half (serves 6)</strong> and <strong>Full (serves 12)</strong> sizes.</p>\n<h3>Chilled Care &amp; Local Canberra Ordering</h3>\n<ul>\n<li><strong>Keep cold until serving:</strong> Continental cheesecakes have a delicate mousse-like set—carry flat with car air-con on and store at 2–5°C for up to 3 days.</li>\n<li><strong>Great for mixed dessert tables:</strong> Pair a Half Choc Chip Cheesecake with a Half <a href=\"{url:cakes/strawberry-cheesecake}\">Strawberry Cheesecake</a> to offer two flavours for 12 guests!</li>\n<li><strong>Pickup or delivery:</strong> Order online {min_lead_hours} hours ahead from {store_address} or choose Canberra and Queanbeyan delivery at checkout; call {store_phone}.</li>\n</ul>','products/choc-chip-cheesecake.webp','Choc Chip Cheesecake, light continental cheesecake with chocolate chips',NULL,0,1,1,0,NULL,NULL,1,NULL,NULL,'choc chip cheesecake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(31,5,'Caramel Cheesecake','caramel-cheesecake','Light continental cheesecake with caramel.','<p>Smooth, chilled and crowned with a silky golden caramel mirror, our <strong>Caramel Cheesecake Canberra</strong> continental dessert pairs an airy, whipped cream cheese filling with a crisp biscuit base. Unlike oven-baked caramel cakes, this continental-style cheesecake stays cool and mousse-light on the spoon—ideal after a generous family roast, curry banquet or barbecue.</p>\n<p>Handcrafted in our Greenway shop using <strong>100% Halal ingredients</strong>—including Halal-suitable setting agents, zero alcohol and fresh Australian dairy—choose between <strong>Half (serves 6)</strong> and <strong>Full (serves 12)</strong> sizes.</p>\n<h3>Serving Suggestions &amp; Canberra Delivery</h3>\n<ul>\n<li><strong>Storage:</strong> Keep refrigerated at 2–5°C right up to serving time so the caramel glaze and mousse filling slice cleanly.</li>\n<li><strong>Mix-and-match tip:</strong> Hosting a grazing table? Combine Half sizes across Caramel, <a href=\"{url:cakes/passionfruit-cheesecake}\">Passionfruit</a> and <a href=\"{url:cakes/choc-chip-cheesecake}\">Choc Chip</a> for effortless variety.</li>\n<li><strong>Fast online booking:</strong> Order at least {min_lead_hours} hours ahead for free Greenway pickup ({store_address}) or delivery across Canberra and Queanbeyan; phone {store_phone}.</li>\n</ul>','products/caramel-cheesecake.webp','Caramel Cheesecake, light continental cheesecake with caramel',NULL,0,1,1,0,NULL,NULL,2,NULL,NULL,'caramel cheesecake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(32,5,'Strawberry Cheesecake','strawberry-cheesecake','Light continental cheesecake with strawberry.','<p>One of our bestselling chilled desserts, this <strong>Strawberry Cheesecake Canberra</strong> favourite tops a light, mousse-set continental cream cheese filling with sweet ruby strawberry glaze over a golden biscuit crumb. Cool, fruity and delicate, it delivers classic strawberries-and-cream appeal in a sliceable cheesecake that suits both children\'s dinners and adult afternoon teas.</p>\n<p>Prepared fresh in our Greenway kitchen with <strong>100% Halal ingredients</strong>—strictly zero pork gelatine, zero alcohol and no animal shortening—it is available in <strong>Half (serves 6)</strong> and <strong>Full (serves 12)</strong> sizes.</p>\n<h3>Why Canberra Families Love Our Continental Style</h3>\n<ul>\n<li><strong>Light after big meals:</strong> Refreshing whipped texture makes it effortless to enjoy after festive Eid lunches, Sunday roasts and summer barbecues.</li>\n<li><strong>Fridge care:</strong> Transport flat and keep chilled at 2–5°C until the moment you cut; best within 2–3 days.</li>\n<li><strong>Order online:</strong> Ready with {min_lead_hours} hours notice for pickup at {store_address} or courier delivery across Canberra and Queanbeyan. Call {store_phone} for allergen advice.</li>\n</ul>','products/strawberry-cheesecake.webp','Strawberry Cheesecake, light continental cheesecake with strawberry',NULL,1,1,1,0,NULL,NULL,3,NULL,NULL,'strawberry cheesecake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(33,5,'Passionfruit Cheesecake','passionfruit-cheesecake','Light continental cheesecake with passionfruit.','<p>Bright tropical tang meets cool, airy creaminess in our <strong>Passionfruit Cheesecake Canberra</strong> staple: a light continental-style set cheesecake on a crisp biscuit base, finished with golden passionfruit topping speckled with real seeds. That unmistakable Australian passionfruit zing cuts cleanly through the whipped cream cheese beneath, making it a warm-weather standout.</p>\n<p>Made in house at our Greenway patisserie using <strong>100% Halal ingredients</strong> (Halal-suitable setting agents, zero alcohol and zero animal shortening), choose <strong>Half (serves 6)</strong> for small gatherings or <strong>Full (serves 12)</strong> for parties and office morning teas.</p>\n<h3>Serving, Storage &amp; Local Fulfilment</h3>\n<ul>\n<li><strong>Summer &amp; barbecue hero:</strong> Pairs wonderfully with fresh mango, berries or iced tea on sunny Canberra afternoons.</li>\n<li><strong>Chilled handling:</strong> Store flat in your fridge at 2–5°C and serve straight from cold within 2–3 days.</li>\n<li><strong>Greenway pickup &amp; delivery:</strong> Book online {min_lead_hours} hours ahead for pickup at {store_address} or scheduled delivery across Canberra and Queanbeyan; call {store_phone}.</li>\n</ul>','products/passionfruit-cheesecake.webp','Passionfruit Cheesecake, light continental cheesecake with passionfruit',NULL,0,1,1,0,NULL,NULL,4,NULL,NULL,'passionfruit cheesecake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(35,5,'Tangy Lemon Cheesecake','tangy-lemon-cheesecake','Light continental cheesecake with a tangy lemon finish.','<p>Crisp, zesty and wonderfully refreshing, our <strong>Tangy Lemon Cheesecake Canberra</strong> continental cheesecake pairs a feather-light set cream cheese filling on a golden biscuit crust with a bright lemon finish. Designed for guests who prefer citrus snap over heavy chocolate or caramel, it acts as an effortless palate cleanser after rich savoury courses.</p>\n<p>Every Tangy Lemon Cheesecake is prepared fresh in Greenway using <strong>100% Halal ingredients</strong>—Halal-suitable gelatine/setting agents, zero alcohol and no animal fats—in convenient <strong>Half (serves 6)</strong> and <strong>Full (serves 12)</strong> sizes.</p>\n<h3>When to Choose Tangy Lemon Cheesecake</h3>\n<ul>\n<li><strong>Ideal for citrus fans:</strong> Our brightest, least-sweet cheesecake option—great for afternoon tea, seafood lunches and summer dinners.</li>\n<li><strong>Care &amp; slicing:</strong> Keep refrigerated at 2–5°C until serving and wipe your blade clean between wedges.</li>\n<li><strong>Pickup or Canberra delivery:</strong> Order online at least {min_lead_hours} hours ahead from {store_address} or select delivery across Canberra and Queanbeyan at checkout; phone {store_phone}.</li>\n</ul>','products/tangy-lemon-cheesecake.webp','Tangy Lemon Cheesecake, light continental cheesecake with a tangy lemon finish',NULL,0,1,1,0,NULL,NULL,6,NULL,NULL,'tangy lemon cheesecake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(36,5,'Blueberry Cheesecake','blueberry-cheesecake','Light continental cheesecake with blueberry.','<p>Cool, mousse-light and crowned with jewel-bright whole blueberry topping, our <strong>Blueberry Cheesecake Canberra</strong> continental cheesecake sits on a buttery biscuit crumb without heavy oven baking. Compared with our denser New York <a href=\"{url:cakes/blueberry-baked-cheesecake}\">Blueberry Baked Cheesecake</a>, this chilled continental style feels airy, gentle and refreshing on warm afternoons or after a hearty family meal.</p>\n<p>Handcrafted in our Greenway kitchen with <strong>100% Halal ingredients</strong>—zero porcine gelatine, zero alcohol and no animal shortening—it is offered in <strong>Half (serves 6)</strong> and <strong>Full (serves 12)</strong> sizes.</p>\n<h3>Storage, Pairing &amp; Canberra Ordering</h3>\n<ul>\n<li><strong>Storage:</strong> Carry flat in an air-conditioned car and keep chilled at 2–5°C in the refrigerator for up to 3 days.</li>\n<li><strong>Great for morning teas:</strong> Easy to portion cleanly on paper plates at workplace shouts and community catch-ups.</li>\n<li><strong>Book online:</strong> Place your order {min_lead_hours} hours ahead for free pickup at {store_address} or scheduled delivery across Canberra and Queanbeyan; call {store_phone}.</li>\n</ul>','products/blueberry-cheesecake.webp','Blueberry Cheesecake, light continental cheesecake with blueberry',NULL,0,1,1,0,NULL,NULL,7,NULL,NULL,'blueberry cheesecake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(38,6,'Carrot Cake','carrot-cake','Spiced carrot cake with cream cheese icing.','<p>Packed with freshly grated carrot, warm baking spices and a tender, moist crumb, our <strong>Carrot Cake Canberra</strong> morning-tea favourite is finished generously with smooth, tangy cream cheese icing. Neither dry nor overly spiced, it strikes that nostalgic homestyle balance that pairs effortlessly with flat whites, chai lattes and pot-brewed tea.</p>\n<p>Baked from scratch in our Greenway kitchen using <strong>100% Halal ingredients</strong> (zero alcohol and no animal shortening), our Carrot Cake comes in <strong>Midi (serves 8)</strong> and <strong>Full (serves 14)</strong> sizes, with a complimentary piped birthday or thank-you message available on top.</p>\n<h3>Allergen, Storage &amp; Ordering Guide</h3>\n<ul>\n<li><strong>Allergen note:</strong> Please check with our team on {store_phone} or read our <a href=\"{url:allergens}\">allergen information</a> regarding walnuts/tree nuts, gluten, dairy and eggs in our shared Greenway kitchen.</li>\n<li><strong>Storage:</strong> Because of the real cream cheese icing, store refrigerated at 2–5°C and stand at room temperature for 15–20 minutes before slicing.</li>\n<li><strong>Fulfilment:</strong> Order online {min_lead_hours} hours ahead for pickup at {store_address} or delivery across Canberra and Queanbeyan.</li>\n</ul>','products/carrot-cake.webp','Carrot Cake, spiced carrot cake with cream cheese icing',NULL,0,1,1,0,NULL,NULL,1,NULL,NULL,'carrot cake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(39,6,'Profiterole Tower','profiterole-tower','A tower of cream-filled profiteroles. A real centrepiece.','<p>A true French-patisserie showstopper built in Greenway, our <strong>Profiterole Tower Canberra</strong> centrepiece stacks golden choux pastry puffs filled with cool whipped continental cream and drizzles the entire cone in glossy chocolate. Dramatic on arrival yet effortless to serve—guests simply pluck individual cream puffs straight from the tower without needing cake knives or slicing plates.</p>\n<p>Hand-baked to order with <strong>100% Halal ingredients</strong> (zero alcohol and no animal shortening), choose from four crowd-ready sizes: <strong>20 pieces (serves ~10)</strong>, <strong>25 pieces (serves ~12)</strong>, <strong>30 pieces (serves ~15)</strong> or <strong>35 pieces (serves ~18)</strong>, with free celebration writing included.</p>\n<h3>Events, Transport &amp; Larger Custom Towers</h3>\n<ul>\n<li><strong>Favourite for stand-up events:</strong> Ideal for engagements, milestone birthdays, corporate launches, Eid tables and wedding dessert buffets.</li>\n<li><strong>Need 50+ pieces?</strong> <a href=\"{url:custom-cakes}\">Send a custom quote request</a> for taller wedding or corporate croquembouche-style towers.</li>\n<li><strong>Care &amp; ordering:</strong> Keep refrigerated at 2–5°C and serve on the day of collection for the crispest choux pastry. Order online {min_lead_hours} hours ahead from {store_address} or book Canberra &amp; Queanbeyan delivery; call {store_phone}.</li>\n</ul>','products/profiterole-tower.webp','Profiterole Tower of cream-filled profiteroles finished with chocolate',NULL,1,1,1,0,NULL,NULL,2,NULL,NULL,'profiterole tower canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(40,6,'Pavlova','pavlova','Crisp meringue with soft centre, cream and fruit.','<p>Crisp, snowy meringue on the outside, pillowy marshmallow softness in the centre, and crowned generously with freshly whipped cream and vibrant seasonal fruit—our <strong>Pavlova Canberra</strong> celebration dessert is baked fresh in our Greenway ovens for effortless entertaining. Sized generously to <strong>serve about 10 guests</strong>, it brings classic Australian summer and festive charm to family lunches, Christmas tables, Eid banquets and backyard barbecues.</p>\n<p>Prepared with <strong>100% Halal ingredients</strong>, zero alcohol and fresh local dairy, it arrives decorated and ready for the centre of your table.</p>\n<h3>Freshness, Ingredient Notes &amp; Canberra Delivery</h3>\n<ul>\n<li><strong>Best enjoyed fresh:</strong> Keep refrigerated at 2–5°C and serve on the day of pickup (or within 24–48 hours) while the outer meringue shell retains its delicate crunch beneath the cream.</li>\n<li><strong>Wheat &amp; allergen note:</strong> While meringue contains no wheat flour, our Greenway kitchen handles wheat, eggs, dairy, soy and nuts—please read our <a href=\"{url:allergens}\">allergen guide</a> if ordering for coeliac guests.</li>\n<li><strong>Ordering:</strong> Book online {min_lead_hours} hours ahead for pickup at {store_address} or careful courier delivery across Canberra and Queanbeyan; phone {store_phone}.</li>\n</ul>','products/pavlova.webp','Pavlova, crisp meringue with soft centre, cream and fruit',NULL,0,1,1,0,NULL,NULL,3,NULL,NULL,'pavlova canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43'),
(41,6,'Orange & Poppy Seed Cake','orange-poppy-seed-cake','Orange and poppy seed tea cake.','<p>Fragrant with bright citrus zest and speckled throughout with nutty blue poppy seeds, our <strong>Orange &amp; Poppy Seed Cake Canberra</strong> tea cake offers a moist, tender crumb finished cleanly for effortless slicing. Lighter than chocolate mud cake and brighter than spiced carrot cake, its sunny orange aroma makes it a staple for Canberra office morning teas, brunch tables, book clubs and thoughtful host gifts.</p>\n<p>Baked from scratch in our Greenway shop using <strong>100% Halal ingredients</strong>—zero alcohol and no animal shortening—it is available in <strong>Midi (serves 8)</strong> and <strong>Full (serves 14)</strong> sizes, with free custom piped writing included if you are gifting or celebrating a birthday.</p>\n<h3>Serving Suggestions &amp; Local Fulfilment</h3>\n<ul>\n<li><strong>Pairings:</strong> Wonderful slightly cool or at room temperature alongside Earl Grey, green tea, long blacks or a dollop of Greek yoghurt and berries.</li>\n<li><strong>Storage:</strong> Store refrigerated in a sealed box and bring to room temperature 20 minutes before serving; stays moist for up to 3 days.</li>\n<li><strong>Greenway pickup &amp; Canberra delivery:</strong> Order online at least {min_lead_hours} hours ahead from {store_address} or choose delivery across Canberra and Queanbeyan, or call {store_phone}.</li>\n</ul>','products/orange-poppy-seed-cake.webp','Orange & Poppy Seed Cake, orange and poppy seed tea cake',NULL,0,1,1,0,NULL,NULL,4,NULL,NULL,'orange and poppy seed cake canberra',NULL,'2026-10-18 13:35:43','2026-10-18 13:35:43');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rate_limits` (
  `bucket` char(40) NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT 0,
  `window_start` datetime NOT NULL,
  PRIMARY KEY (`bucket`),
  KEY `idx_rate_window` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `rate_limits` WRITE;
/*!40000 ALTER TABLE `rate_limits` DISABLE KEYS */;
/*!40000 ALTER TABLE `rate_limits` ENABLE KEYS */;
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
('011_daily_email_settings.sql','2026-10-18 13:35:43'),
('012_payments_addons_options.sql','2026-10-21 10:26:07'),
('013_sold_out_pause_whatsapp.sql','2026-10-21 10:26:07'),
('014_quote_payments.sql','2026-10-21 10:26:08'),
('015_reminders_reviews.sql','2026-10-21 10:26:08'),
('016_product_photos_seo.sql','2026-10-21 10:26:08'),
('017_seo_landing_pages.sql','2026-10-21 10:26:08'),
('018_backups.sql','2026-10-21 10:26:08'),
('019_live_chat_category_style.sql','2026-10-22 04:37:24'),
('020_same_day_orders.sql','2026-10-22 04:37:24'),
('021_security.sql','2026-10-22 04:37:24'),
('022_catalogue_refresh.sql','2026-10-22 07:28:45'),
('023_seo_descriptions.sql','2026-10-22 07:32:57'),
('024_menu_cleanup_seo.sql','2026-10-22 08:30:00'),
('025_branches.sql','2026-11-06 09:00:00'),
('026_branch_settings.sql','2026-11-06 12:00:00'),
('027_branch_contact.sql','2026-11-06 15:00:00'),
('028_pay_later_status_emails_notice.sql','2026-11-14 23:17:56'),
('029_pay_on_call_name.sql','2026-11-14 23:31:33'),
('030_product_search_words.sql','2026-11-16 02:11:44'),
('031_policy_pages.sql','2026-11-16 02:11:44'),
('032_ai_seo.sql','2026-11-16 05:14:46'),
('033_custom_request_gallery_photo.sql','2026-11-17 22:00:14'),
('034_seo_and_geo_upgrade.sql','2026-11-18 06:50:00');
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
('addons_enabled','0','2026-10-21 10:26:07'),
('addons_title','Add something extra','2026-10-21 10:26:07'),
('announcement_text','Delivery all over Canberra','2026-10-18 13:35:43'),
('backup_enabled','1','2026-10-21 10:26:08'),
('backup_keep_days','14','2026-10-21 10:26:08'),
('backup_last_run','','2026-10-21 10:26:08'),
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
('hero_eyebrow','Cake shop in Greenway, Canberra since 2015','2026-10-21 10:26:08'),
('hero_image','gallery/hero-cake-teal.webp','2026-10-18 13:35:43'),
('hero_show_shop_card','1','2026-10-18 13:35:43'),
('hero_subtitle','Tortes, mud cakes, velvet cakes and cheesecakes made in our shop with 100% Halal ingredients. Order online, choose your pickup time, and we will have it ready.','2026-10-18 13:35:43'),
('hero_title','Fresh cakes for every celebration','2026-10-18 13:35:43'),
('home_categories_style','tiles','2026-10-22 04:37:24'),
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
('min_lead_hours','1','2026-10-18 13:35:43'),
('opening_hours','{\"mon\":[\"08:00\",\"19:00\"],\"tue\":[\"08:00\",\"19:00\"],\"wed\":[\"08:00\",\"19:00\"],\"thu\":[\"08:00\",\"19:00\"],\"fri\":[\"08:00\",\"19:00\"],\"sat\":[\"09:00\",\"18:00\"],\"sun\":[\"09:00\",\"18:00\"]}','2026-10-18 13:35:43'),
('order_notification_email','','2026-10-18 13:35:43'),
('ordering_paused','0','2026-10-21 10:26:07'),
('ordering_paused_message','We are not taking online orders right now. Please call us and we will do our best to help.','2026-10-21 10:26:07'),
('ordering_paused_until','','2026-10-21 10:26:07'),
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
('quote_default_deposit_percent','50','2026-10-21 10:26:08'),
('reminder_email_enabled','1','2026-10-21 10:26:08'),
('reminder_send_hour','10','2026-10-21 10:26:08'),
('review_delay_hours','20','2026-10-21 10:26:08'),
('review_email_enabled','1','2026-10-21 10:26:08'),
('send_customer_emails','1','2026-10-18 13:35:43'),
('seo_home_description','Order fresh Halal cakes online from The Continental Cake Shop in Greenway, Canberra: tortes, mud cakes, velvet cakes, cheesecakes and custom cakes.','2026-10-18 13:35:43'),
('seo_home_title','Cake Shop Canberra | The Continental Cake Shop, Greenway','2026-10-21 10:26:08'),
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
('store_latitude','-35.418923','2026-10-22 08:21:46'),
('store_longitude','149.065858','2026-10-22 08:21:46'),
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
('stripe_webhook_secret','','2026-10-18 13:35:43'),
('whatsapp_message','Hi, I have a question about a cake.','2026-10-21 10:26:07'),
('whatsapp_number','','2026-10-21 10:26:07');
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
  `branch_id` int(10) unsigned NOT NULL DEFAULT 1,
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
  `admin_seen_at` datetime DEFAULT NULL,
  `customer_seen_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  UNIQUE KEY `uq_threads_order` (`order_id`),
  UNIQUE KEY `uq_threads_custom` (`custom_request_id`),
  KEY `idx_threads_inbox` (`status`,`last_message_at`),
  KEY `idx_threads_email` (`customer_email`),
  KEY `fk_threads_user` (`user_id`),
  KEY `idx_threads_branch` (`branch_id`,`status`),
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
  `email_verified_at` datetime DEFAULT NULL,
  `auth_version` int(10) unsigned NOT NULL DEFAULT 0,
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

