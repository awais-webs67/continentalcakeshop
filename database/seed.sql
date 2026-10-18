-- Starter data taken from the shop's current menu (continentalcakeshop.com.au, AUD).
-- Servings per size are left empty until the shop confirms them in the admin panel.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Settings
-- ---------------------------------------------------------------------------
INSERT INTO settings (setting_key, setting_value) VALUES
('store_name',            'The Continental Cake Shop Canberra'),
('store_short_name',      'The Continental Cake Shop'),
('store_phone',           '02 6293 9578'),
('store_phone_e164',      '+61262939578'),
('store_email',           'continentalcakeshopcanberra@gmail.com'),
('store_street',          'Shop 4, 38 Reed St N'),
('store_suburb',          'Greenway'),
('store_state',           'ACT'),
('store_postcode',        '2900'),
('store_country',         'AU'),
('store_founded',         '2015'),
('opening_hours',         '{"mon":["08:00","19:00"],"tue":["08:00","19:00"],"wed":["08:00","19:00"],"thu":["08:00","19:00"],"fri":["08:00","19:00"],"sat":["09:00","18:00"],"sun":["09:00","18:00"]}'),

('announcement_text',     '100% Halal ingredients. Order online and pick up from our Greenway shop.'),
('hero_eyebrow',          'Baked in Greenway, Canberra since 2015'),
('hero_title',            'Fresh cakes for every celebration'),
('hero_subtitle',         'Tortes, mud cakes, velvet cakes and cheesecakes made in our shop with 100% Halal ingredients. Order online, choose your pickup time, and we will have it ready.'),
('google_rating',         '4.3'),
('google_review_count',   '290+'),
('google_reviews_url',    'https://www.google.com/maps/search/?api=1&query=The+Continental+Cake+Shop+Canberra+Greenway'),

-- Pickup and booking rules
('min_lead_hours',        '24'),
('custom_lead_days',      '3'),
('max_advance_days',      '60'),
('slot_interval_minutes', '30'),
('last_slot_buffer_minutes', '30'),

-- Fulfilment
('pickup_enabled',        '1'),
('delivery_enabled',      '0'),
('delivery_fee',          '15.00'),
('delivery_note',         'Delivery across the ACT and Queanbeyan.'),

-- Payments. A method only shows at checkout when it is switched on AND its details are filled in.
('payment_stripe_enabled',  '0'),
('stripe_publishable_key',  ''),
('stripe_secret_key',       ''),
('stripe_webhook_secret',   ''),
('payment_payid_enabled',   '1'),
('payid_identifier',        ''),
('payid_account_name',      'The Continental Cake Shop'),
('payment_bank_enabled',    '1'),
('bank_account_name',       ''),
('bank_bsb',                ''),
('bank_account_number',     ''),

-- Notifications
('order_notification_email', 'continentalcakeshopcanberra@gmail.com'),
('send_customer_emails',     '1'),

-- Social
('social_facebook',       ''),
('social_instagram',      ''),
('social_pinterest',      'https://pin.it/Klvu8wY'),

-- SEO
('seo_home_title',        'The Continental Cake Shop Canberra | Halal Cakes in Greenway, ACT'),
('seo_home_description',  'Order fresh tortes, mud cakes, velvet cakes and cheesecakes online from The Continental Cake Shop in Greenway, Canberra. 100% Halal ingredients, custom cakes and easy pickup.');

-- ---------------------------------------------------------------------------
-- Categories
-- ---------------------------------------------------------------------------
INSERT INTO categories (id, name, slug, description, sort_order, meta_title, meta_description) VALUES
(1, 'Tortes', 'tortes', 'Light layered sponge tortes filled with cream, fruit and our house fillings. A favourite for birthdays and family get-togethers.', 1,
    'Tortes in Canberra | The Continental Cake Shop', 'Order layered sponge tortes online in Canberra: Black Forest, Lotus Biscoff, Raffaello, Tiramisu and more. Pick up in Greenway.'),
(2, 'Velvet Cakes', 'velvet-cakes', 'Soft velvet sponge layered with smooth cream cheese frosting, in four flavours.', 2,
    'Red Velvet & Velvet Cakes Canberra | The Continental Cake Shop', 'Red velvet, pistachio, blueberry and orange mango velvet cakes, made fresh in Greenway, Canberra. Order online for pickup.'),
(3, 'Mud Cakes', 'mud-cakes', 'Dense, moist mud cakes that hold up well for decorating and travel well to parties.', 3,
    'Mud Cakes Canberra | The Continental Cake Shop', 'Chocolate, white gold, marble and caramel mud cakes made fresh in Canberra. Order online and pick up in Greenway.'),
(4, 'Baked Cheesecakes', 'baked-cheesecakes', 'Classic oven-baked cheesecakes on a biscuit base. Available in full and half sizes.', 4,
    'Baked Cheesecakes Canberra | The Continental Cake Shop', 'American, blueberry, marble, tropical and hazelnut baked cheesecakes. Full and half sizes, ready for pickup in Greenway.'),
(5, 'Continental Cheesecakes', 'continental-cheesecakes', 'Our light, set continental-style cheesecakes in eight flavours. Full and half sizes.', 5,
    'Continental Cheesecakes Canberra | The Continental Cake Shop', 'Light continental cheesecakes in eight flavours including pistachio, passionfruit and tangy lemon. Order online in Canberra.'),
(6, 'Tea Cakes & Desserts', 'tea-cakes-desserts', 'Carrot cake, pavlova, profiterole towers and more for morning tea and dessert tables.', 6,
    'Tea Cakes & Desserts Canberra | The Continental Cake Shop', 'Carrot cake, pavlova and profiterole towers from The Continental Cake Shop in Greenway, Canberra.');

-- ---------------------------------------------------------------------------
-- Products
-- ---------------------------------------------------------------------------
INSERT INTO products (id, category_id, name, slug, short_description, badge, is_featured, sort_order) VALUES
-- Tortes
(1,  1, 'Black Forest Torte',           'black-forest-torte',           'Chocolate sponge layered with fresh cream and cherries.', NULL, 1, 1),
(2,  1, 'Mousse Torte',                 'mousse-torte',                 'Sponge layers with a light, airy mousse filling.', NULL, 0, 2),
(3,  1, 'Strawberry Torte',             'strawberry-torte',             'Vanilla sponge with fresh cream and strawberries.', NULL, 0, 3),
(4,  1, 'Caramel Torte',                'caramel-torte',                'Sponge layers with smooth caramel cream.', NULL, 0, 4),
(5,  1, 'Zebra Torte',                  'zebra-torte',                  'Chocolate and vanilla sponge layered together with cream.', NULL, 0, 5),
(6,  1, 'Hazelnut Torte',               'hazelnut-torte',               'Sponge layered with rich hazelnut cream.', NULL, 0, 6),
(7,  1, 'Double Choc Strawberry Torte', 'double-choc-strawberry-torte', 'Chocolate sponge with chocolate cream and strawberries.', NULL, 0, 7),
(8,  1, 'Cookies ''n'' Cream Torte',    'cookies-n-cream-torte',        'Sponge layers with cookies and cream filling.', NULL, 0, 8),
(9,  1, 'Vanilla Torte',                'vanilla-torte',                'Classic vanilla sponge with fresh cream.', NULL, 0, 9),
(10, 1, 'Tiramisu Torte',               'tiramisu-torte',               'Coffee-soaked sponge with a creamy tiramisu filling.', 'Customer favourite', 1, 10),
(11, 1, 'Lotus Biscoff Torte',          'lotus-biscoff-torte',          'Sponge layers with Biscoff cream and crushed Lotus biscuits.', 'Bestseller', 1, 11),
(12, 1, 'Rainbow Torte',                'rainbow-torte',                'Colourful rainbow sponge layers with cream. Great for kids'' parties.', NULL, 0, 12),
(13, 1, 'Honey-Bee Torte',              'honey-bee-torte',              'Sponge layers with a honey cream filling.', NULL, 0, 13),
(14, 1, 'Continental Torte',            'continental-torte',            'Our signature house torte.', NULL, 0, 14),
(15, 1, 'Raffaello Torte',              'raffaello-torte',              'Coconut and almond cream torte inspired by Raffaello.', NULL, 1, 15),
-- Velvet
(16, 2, 'Red Velvet Cake',              'red-velvet-cake',              'Red velvet sponge with cream cheese frosting.', 'Customer favourite', 1, 1),
(17, 2, 'Pistachio Velvet Cake',        'pistachio-velvet-cake',        'Pistachio velvet sponge with cream cheese frosting.', NULL, 1, 2),
(18, 2, 'Blueberry Velvet Cake',        'blueberry-velvet-cake',        'Blueberry velvet sponge with cream cheese frosting.', NULL, 0, 3),
(19, 2, 'Orange Mango Velvet Cake',     'orange-mango-velvet-cake',     'Orange and mango velvet sponge with cream cheese frosting.', NULL, 0, 4),
-- Mud
(20, 3, 'Chocolate Mud Cake',           'chocolate-mud-cake',           'Rich, moist chocolate mud cake.', NULL, 1, 1),
(21, 3, 'White Gold Mud Cake',          'white-gold-mud-cake',          'White chocolate mud cake with a caramel note.', NULL, 0, 2),
(22, 3, 'Marble Mud Cake',              'marble-mud-cake',              'Chocolate and vanilla mud cake swirled together.', NULL, 0, 3),
(23, 3, 'Caramel Mud Cake',             'caramel-mud-cake',             'Moist caramel mud cake.', NULL, 0, 4),
(24, 3, 'Treasure Cake',                'treasure-cake',                'Our signature Treasure mud cake.', NULL, 0, 5),
-- Baked cheesecakes
(25, 4, 'American Baked Cheesecake',    'american-baked-cheesecake',    'Creamy New York style baked cheesecake.', NULL, 0, 1),
(26, 4, 'Blueberry Baked Cheesecake',   'blueberry-baked-cheesecake',   'Baked cheesecake with blueberries.', NULL, 0, 2),
(27, 4, 'Marble Baked Cheesecake',      'marble-baked-cheesecake',      'Baked cheesecake swirled with chocolate.', NULL, 0, 3),
(28, 4, 'Tropical Baked Cheesecake',    'tropical-baked-cheesecake',    'Baked cheesecake with tropical fruit.', NULL, 0, 4),
(29, 4, 'Hazelnut Baked Cheesecake',    'hazelnut-baked-cheesecake',    'Baked cheesecake with hazelnut.', NULL, 0, 5),
-- Continental cheesecakes
(30, 5, 'Choc Chip Cheesecake',         'choc-chip-cheesecake',         'Light continental cheesecake with chocolate chips.', NULL, 0, 1),
(31, 5, 'Caramel Cheesecake',           'caramel-cheesecake',           'Light continental cheesecake with caramel.', NULL, 0, 2),
(32, 5, 'Strawberry Cheesecake',        'strawberry-cheesecake',        'Light continental cheesecake with strawberry.', NULL, 0, 3),
(33, 5, 'Passionfruit Cheesecake',      'passionfruit-cheesecake',      'Light continental cheesecake with passionfruit.', NULL, 0, 4),
(34, 5, 'Cookies Cheesecake',           'cookies-cheesecake',           'Light continental cheesecake with cookie pieces.', NULL, 0, 5),
(35, 5, 'Tangy Lemon Cheesecake',       'tangy-lemon-cheesecake',       'Light continental cheesecake with a tangy lemon finish.', NULL, 0, 6),
(36, 5, 'Blueberry Cheesecake',         'blueberry-cheesecake',         'Light continental cheesecake with blueberry.', NULL, 0, 7),
(37, 5, 'Pistachio Cheesecake',         'pistachio-cheesecake',         'Light continental cheesecake with pistachio.', NULL, 0, 8),
-- Tea cakes & desserts
(38, 6, 'Carrot Cake',                  'carrot-cake',                  'Spiced carrot cake with cream cheese icing.', NULL, 0, 1),
(39, 6, 'Profiterole Tower',            'profiterole-tower',            'A tower of cream-filled profiteroles. A real centrepiece.', NULL, 1, 2),
(40, 6, 'Pavlova',                      'pavlova',                      'Crisp meringue with soft centre, cream and fruit.', NULL, 0, 3),
(41, 6, 'Orange & Poppy Seed Cake',     'orange-poppy-seed-cake',       'Orange and poppy seed tea cake.', NULL, 0, 4);

-- Orange & Poppy Seed has no published price yet, so it stays hidden until a size is added.
UPDATE products SET is_active = 0 WHERE id = 41;

-- ---------------------------------------------------------------------------
-- Sizes and prices (AUD)
-- ---------------------------------------------------------------------------
INSERT INTO product_sizes (product_id, label, price, sort_order) VALUES
(1,'Midi',44.95,1),(1,'Full',54.95,2),
(2,'Full',64.95,1),
(3,'Midi',39.95,1),(3,'Full',49.95,2),
(4,'Full',49.95,1),
(5,'Midi',49.95,1),(5,'Full',59.95,2),
(6,'Full',64.95,1),
(7,'Midi',44.95,1),(7,'Full',54.95,2),
(8,'Full',64.95,1),
(9,'Midi',39.95,1),(9,'Full',49.95,2),
(10,'Full',54.95,1),
(11,'Midi',59.95,1),(11,'Full',69.95,2),
(12,'Large',59.95,1),(12,'XL',69.95,2),
(13,'Midi',39.95,1),(13,'Full',49.95,2),
(14,'Midi',39.95,1),(14,'Full',49.95,2),
(15,'Midi',59.95,1),(15,'Full',69.95,2),
(16,'Midi',59.95,1),(16,'Full',69.95,2),
(17,'Midi',59.95,1),(17,'Full',69.95,2),
(18,'Midi',59.95,1),(18,'Full',69.95,2),
(19,'Midi',59.95,1),(19,'Full',69.95,2),
(20,'Midi',39.95,1),(20,'Full',49.95,2),
(21,'Midi',39.95,1),(21,'Full',49.95,2),
(22,'Midi',39.95,1),(22,'Full',49.95,2),
(23,'Midi',39.95,1),(23,'Full',49.95,2),
(24,'Midi',59.95,1),(24,'Full',69.95,2),
(25,'Half',22.95,1),(25,'Full',39.95,2),
(26,'Half',22.95,1),(26,'Full',39.95,2),
(27,'Half',22.95,1),(27,'Full',39.95,2),
(28,'Half',22.95,1),(28,'Full',39.95,2),
(29,'Half',24.95,1),(29,'Full',44.95,2),
(30,'Half',22.95,1),(30,'Full',39.95,2),
(31,'Half',22.95,1),(31,'Full',39.95,2),
(32,'Half',22.95,1),(32,'Full',39.95,2),
(33,'Half',22.95,1),(33,'Full',39.95,2),
(34,'Half',24.95,1),(34,'Full',44.95,2),
(35,'Half',22.95,1),(35,'Full',39.95,2),
(36,'Half',22.95,1),(36,'Full',39.95,2),
(37,'Half',24.95,1),(37,'Full',44.95,2),
(38,'Midi',34.95,1),(38,'Full',44.95,2),
(39,'Midi',34.95,1),(39,'Full',44.95,2),
(40,'Standard',39.95,1),(40,'20 p/r',55.00,2),(40,'25 p/r',65.00,3),(40,'30 p/r',75.00,4),(40,'35 p/r',85.00,5);

-- ---------------------------------------------------------------------------
-- Reviews (public Google reviews quoted on Region Canberra and Birdeye)
-- ---------------------------------------------------------------------------
INSERT INTO reviews (author, source, body, sort_order) VALUES
('Shelphy W.', 'Google', 'The best quality cakes ever. Not overly sweet. Easy to order by phone. Very reliable staff. Reasonable price.', 1),
('Hansi',      'Google', 'The tiramisu and red velvet cake were really amazing and beyond expectations.', 2),
('Liam',       'Google', 'The staff are friendly and welcoming.', 3),
('Anil',       'Google', 'The best cake shop in the town.', 4);
