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

('announcement_text',     'Delivery all over Canberra'),
('hero_image',            'gallery/hero-cake-teal.webp'),
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
('email_accent_note',       'Thank you for choosing a local Canberra cake shop.'),
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

-- ---------------------------------------------------------------------------
-- Photos (taken from the shop's own website). Products without a photo show a placeholder.
-- ---------------------------------------------------------------------------
UPDATE products SET image = 'products/american-baked-cheesecake.webp' WHERE slug = 'american-baked-cheesecake';
UPDATE products SET image = 'products/black-forest-torte.webp' WHERE slug = 'black-forest-torte';
UPDATE products SET image = 'products/blueberry-baked-cheesecake.webp' WHERE slug = 'blueberry-baked-cheesecake';
UPDATE products SET image = 'products/blueberry-cheesecake.webp' WHERE slug = 'blueberry-cheesecake';
UPDATE products SET image = 'products/caramel-cheesecake.webp' WHERE slug = 'caramel-cheesecake';
UPDATE products SET image = 'products/caramel-mud-cake.webp' WHERE slug = 'caramel-mud-cake';
UPDATE products SET image = 'products/caramel-torte.webp' WHERE slug = 'caramel-torte';
UPDATE products SET image = 'products/carrot-cake.webp' WHERE slug = 'carrot-cake';
UPDATE products SET image = 'products/choc-chip-cheesecake.webp' WHERE slug = 'choc-chip-cheesecake';
UPDATE products SET image = 'products/chocolate-mud-cake.webp' WHERE slug = 'chocolate-mud-cake';
UPDATE products SET image = 'products/continental-torte.webp' WHERE slug = 'continental-torte';
UPDATE products SET image = 'products/cookies-n-cream-torte.webp' WHERE slug = 'cookies-n-cream-torte';
UPDATE products SET image = 'products/double-choc-strawberry-torte.webp' WHERE slug = 'double-choc-strawberry-torte';
UPDATE products SET image = 'products/hazelnut-baked-cheesecake.webp' WHERE slug = 'hazelnut-baked-cheesecake';
UPDATE products SET image = 'products/hazelnut-torte.webp' WHERE slug = 'hazelnut-torte';
UPDATE products SET image = 'products/honey-bee-torte.webp' WHERE slug = 'honey-bee-torte';
UPDATE products SET image = 'products/marble-baked-cheesecake.webp' WHERE slug = 'marble-baked-cheesecake';
UPDATE products SET image = 'products/marble-mud-cake.webp' WHERE slug = 'marble-mud-cake';
UPDATE products SET image = 'products/mousse-torte.webp' WHERE slug = 'mousse-torte';
UPDATE products SET image = 'products/orange-poppy-seed-cake.webp' WHERE slug = 'orange-poppy-seed-cake';
UPDATE products SET image = 'products/passionfruit-cheesecake.webp' WHERE slug = 'passionfruit-cheesecake';
UPDATE products SET image = 'products/pavlova.webp' WHERE slug = 'pavlova';
UPDATE products SET image = 'products/profiterole-tower.webp' WHERE slug = 'profiterole-tower';
UPDATE products SET image = 'products/rainbow-torte.webp' WHERE slug = 'rainbow-torte';
UPDATE products SET image = 'products/red-velvet-cake.webp' WHERE slug = 'red-velvet-cake';
UPDATE products SET image = 'products/strawberry-cheesecake.webp' WHERE slug = 'strawberry-cheesecake';
UPDATE products SET image = 'products/strawberry-torte.webp' WHERE slug = 'strawberry-torte';
UPDATE products SET image = 'products/tangy-lemon-cheesecake.webp' WHERE slug = 'tangy-lemon-cheesecake';
UPDATE products SET image = 'products/tiramisu-torte.webp' WHERE slug = 'tiramisu-torte';
UPDATE products SET image = 'products/treasure-cake.webp' WHERE slug = 'treasure-cake';
UPDATE products SET image = 'products/tropical-baked-cheesecake.webp' WHERE slug = 'tropical-baked-cheesecake';
UPDATE products SET image = 'products/vanilla-torte.webp' WHERE slug = 'vanilla-torte';
UPDATE products SET image = 'products/white-gold-mud-cake.webp' WHERE slug = 'white-gold-mud-cake';
UPDATE categories SET image = 'categories/tortes.webp' WHERE slug = 'tortes';
UPDATE categories SET image = 'categories/velvet-cakes.webp' WHERE slug = 'velvet-cakes';
UPDATE categories SET image = 'categories/mud-cakes.webp' WHERE slug = 'mud-cakes';
UPDATE categories SET image = 'categories/baked-cheesecakes.webp' WHERE slug = 'baked-cheesecakes';
UPDATE categories SET image = 'categories/continental-cheesecakes.webp' WHERE slug = 'continental-cheesecakes';
UPDATE categories SET image = 'categories/tea-cakes-desserts.webp' WHERE slug = 'tea-cakes-desserts';

-- Homepage favourites: cakes customers mention in reviews, plus popular cakes with photos.
UPDATE products SET is_featured = 0, badge = NULL;
UPDATE products SET is_featured = 1 WHERE slug IN ('tiramisu-torte','red-velvet-cake','black-forest-torte','double-choc-strawberry-torte','strawberry-cheesecake','chocolate-mud-cake','hazelnut-torte','profiterole-tower');
UPDATE products SET badge = 'Customer favourite' WHERE slug IN ('tiramisu-torte','red-velvet-cake');

-- Custom cake gallery (photos from the shop's website)
INSERT INTO gallery_images (image, caption, sort_order) VALUES
('gallery/custom-01.webp', 'Pink and cream drip birthday cake with macarons', 1),
('gallery/custom-02.webp', 'Black Happy Birthday cake with fresh fruit and macarons', 2),
('gallery/custom-03.webp', 'Two-tier Spider-Man birthday cake', 3),
('gallery/custom-04.webp', 'Three-tier wedding cake with fresh roses', 4),
('gallery/custom-05.webp', 'Blippi themed birthday cake', 5),
('gallery/custom-06.webp', 'Blue starry birthday cake with name topper', 6),
('gallery/custom-07.webp', 'Three-tier ivory wedding cake with piped detail', 7),
('gallery/custom-08.webp', 'Two-tier white cake with cream roses', 8),
('gallery/custom-09.webp', 'Pink vintage heart cake', 9),
('gallery/custom-10.webp', 'Chocolate drip cake with flowers and macarons', 10),
('gallery/custom-11.webp', 'Soccer field cake', 11),
('gallery/custom-12.webp', 'Blue vintage heart cake', 12),
('gallery/custom-13.webp', 'Teal drip cake with pink rosettes', 13),
('gallery/custom-14.webp', 'Cream drip birthday cake with yellow flowers', 14),
('gallery/custom-15.webp', 'Bluey edible image cake', 15),
('gallery/custom-16.webp', 'Soccer themed 11th birthday cake', 16),
('gallery/custom-17.webp', 'White drip cake with red roses', 17),
('gallery/custom-18.webp', 'Two-tier Super Mario cake', 18),
('gallery/custom-19.webp', 'Unicorn cake with rainbow mane', 19),
('gallery/custom-20.webp', 'Textured white cake with gold heart topper', 20),
('gallery/custom-21.webp', 'Spider-Man web cake', 21),
('gallery/custom-22.webp', 'Two-tier green rosette cake', 22),
('gallery/custom-23.webp', 'Coral rosette cake', 23),
('gallery/custom-24.webp', 'Two-tier first birthday cake', 24),
('gallery/custom-25.webp', 'Minnie Mouse edible image cake', 25),
('gallery/custom-26.webp', 'Graduation cake', 26),
('gallery/custom-27.webp', 'Three-tier white cake with fresh flowers', 27),
('gallery/custom-28.webp', 'Spider-Man edible image cake', 28),
('gallery/custom-29.webp', 'Two-tier pink rosette cake', 29);

-- Policy pages and FAQs. Review these with the shop before going live.
INSERT IGNORE INTO pages (slug, title, summary, body) VALUES
('privacy-policy', 'Privacy Policy', 'How we collect, use and protect your personal information.', '<p>{store_name} ("we", "us") respects your privacy. This policy explains what personal information we collect when you use our website or place an order, and how we handle it in line with the Australian Privacy Principles in the <em>Privacy Act 1988</em> (Cth).</p>
<h2>Information we collect</h2>
<ul>
<li><strong>Contact details:</strong> your name, email address and phone number when you place an order, request a custom cake quote, send us a message or create an account.</li>
<li><strong>Order details:</strong> the cakes you order, messages for your cake, pickup or delivery date and time, delivery address (if used) and any notes you add.</li>
<li><strong>Payment information:</strong> card payments are processed by Stripe. We never see or store your full card number. If you pay by PayID or bank transfer, we keep the payment screenshot or receipt you upload.</li>
<li><strong>Photos you send us:</strong> reference images uploaded with a custom cake request.</li>
<li><strong>Account details:</strong> if you create an account, we store your details and a securely encrypted version of your password.</li>
</ul>
<h2>How we use your information</h2>
<ul>
<li>To prepare your order and have it ready at the time you chose.</li>
<li>To contact you about your order, your custom cake quote or your enquiry.</li>
<li>To check payments and keep records required for tax and accounting.</li>
<li>To show your order history when you log in to your account.</li>
</ul>
<p>We do not sell your personal information, and we do not send marketing emails unless you ask us to.</p>
<h2>Who we share it with</h2>
<p>We only share information with the services we need to run the shop, such as our payment provider (Stripe), our website host and our email provider. They may only use it to provide their service to us.</p>
<h2>Cookies</h2>
<p>Our website uses a small session cookie so your cart and login work while you browse. We do not use advertising cookies.</p>
<h2>Keeping your information safe</h2>
<p>We store your information on secure servers and limit access to staff who need it. Payment screenshots and reference photos are kept in a private area that is not publicly accessible.</p>
<h2>Accessing or correcting your information</h2>
<p>You can update your details from your account page, or contact us to see, correct or delete the information we hold about you.</p>
<h2>Questions or complaints</h2>
<p>Please contact us on {store_phone} or {store_email}. If you are not happy with our response, you can contact the Office of the Australian Information Commissioner at <a href="https://www.oaic.gov.au" target="_blank" rel="noopener">oaic.gov.au</a>.</p>'),
('terms', 'Terms and Conditions', 'The terms that apply when you order from our website.', '<p>These terms apply when you place an order with {store_name} through this website. By placing an order you agree to them.</p>
<h2>Orders</h2>
<ul>
<li>Please order at least {min_lead_hours} hours before your pickup time for cakes from our menu, and at least {custom_lead_days} days ahead for custom cakes.</li>
<li>Your order is confirmed once payment has been received. For PayID and bank transfer, this is after we have checked your payment screenshot.</li>
<li>We will contact you if we cannot fulfil your order as placed, for example if an ingredient is unavailable.</li>
</ul>
<h2>Prices and payment</h2>
<ul>
<li>All prices are in Australian dollars.</li>
<li>Prices shown at checkout are the prices you pay. Custom cakes are priced by quote.</li>
</ul>
<h2>Pickup</h2>
<ul>
<li>Please collect your order from {store_address} at the time you selected. If you need to change the time, call us on {store_phone} as early as possible.</li>
<li>Once collected, please keep cream and cheesecake products refrigerated and transport them on a flat surface.</li>
</ul>
<h2>Our cakes</h2>
<ul>
<li>Every cake is handmade, so colours, decorations and shapes may differ slightly from photos on our website.</li>
<li>For custom cakes, we will match your design as closely as we can. Characters and printed images may be adapted.</li>
<li>Our products are made in a kitchen that handles common allergens. Please read our <a href="{url:allergens}">allergen information</a> before ordering.</li>
</ul>
<h2>Cancellations and refunds</h2>
<p>Please see our <a href="{url:refund-policy}">Refund and Cancellation Policy</a>.</p>
<h2>Your rights</h2>
<p>Nothing in these terms limits your rights under the Australian Consumer Law.</p>
<h2>Contact</h2>
<p>{store_name}, {store_address}. Phone {store_phone}, email {store_email}.</p>'),
('refund-policy', 'Refund and Cancellation Policy', 'What to do if you need to cancel or have a problem with your order.', '<p>We want you to love your cake. This policy explains what happens if you need to cancel or if something is not right with your order.</p>
<h2>Cancelling or changing an order</h2>
<p>Please contact us on {store_phone} as early as possible if you need to cancel or change your order. Because every cake is made to order, we may not be able to offer a refund once we have started preparing your cake, buying special ingredients or making decorations.</p>
<h2>Custom cakes</h2>
<p>Custom cakes take extra preparation. Any cancellation terms for your custom cake will be explained when we send your quote.</p>
<h2>If something is wrong</h2>
<p>If your order is not what you ordered, is damaged when you collect it, or there is a problem with quality, please let us know straight away, ideally before you leave the shop or on the same day, and keep the cake if you can. We will work with you to fix it with a replacement, a credit or a refund.</p>
<h2>Refunds</h2>
<p>Approved refunds are returned to the original payment method. Card refunds usually appear within 5 to 10 business days, depending on your bank.</p>
<h2>Your rights</h2>
<p>Our products come with guarantees that cannot be excluded under the Australian Consumer Law. This policy does not limit those rights.</p>'),
('allergens', 'Allergen Information', 'Important information for customers with food allergies or intolerances.', '<p>Your safety matters to us. Please read this before ordering if you or your guests have a food allergy or intolerance.</p>
<h2>Our kitchen</h2>
<p>Our cakes are made in a kitchen that handles common allergens, including milk, eggs, wheat (gluten), soy and tree nuts such as hazelnuts, almonds and pistachios. Because we use shared equipment and work areas, <strong>we cannot guarantee that any product is completely free from traces of allergens</strong>.</p>
<h2>Gluten-free, eggless and vegan cakes</h2>
<p>We can make gluten-free, eggless and vegan options on request. Please call us on {store_phone} before ordering so we can confirm what is possible for your cake. These cakes are made in the same kitchen, so they may still contain traces of other allergens.</p>
<h2>Halal</h2>
<p>All our products are made with 100% Halal ingredients.</p>
<h2>Ask us</h2>
<p>If you have any questions about ingredients, please call us or ask in store before you order. Add any allergy details to the notes at checkout so our team can see them.</p>');

INSERT IGNORE INTO faqs (category, question, answer, sort_order) VALUES
('Ordering', 'How far in advance should I order?', '<p>Please order at least {min_lead_hours} hours ahead for cakes from our menu, and at least {custom_lead_days} days ahead for custom cakes. For weddings and big events, the earlier the better.</p>', 1),
('Ordering', 'I need a cake today. Can you help?', '<p>Please call us on {store_phone} and we will let you know what we can do.</p>', 2),
('Ordering', 'Can I add a message on my cake?', '<p>Yes, a message is free on every cake. Add it on the cake''s page before adding it to your cart.</p>', 3),
('Ordering', 'Can I change the size after adding a cake to my cart?', '<p>Yes. Open your cart and choose a different size from the dropdown next to the cake.</p>', 4),
('Pickup and delivery', 'Where do I collect my order?', '<p>From our shop at {store_address}. You choose the pickup date and time at checkout.</p>', 5),
('Pickup and delivery', 'Can I change my pickup time?', '<p>Please call us on {store_phone} as early as possible and we will do our best to help.</p>', 6),
('Pickup and delivery', 'Do you deliver?', '<p>We deliver across the ACT, Queanbeyan and surrounding areas. Call us on {store_phone} to arrange delivery.</p>', 7),
('Payment', 'How can I pay?', '<p>You can pay online at checkout. The payment options shown at checkout are the ones currently available.</p>', 8),
('Payment', 'When is my order confirmed?', '<p>Card payments are confirmed straight away. For PayID and bank transfer, we confirm your order once we have checked your payment screenshot.</p>', 9),
('Dietary needs', 'Are your cakes Halal?', '<p>Yes, all our products are made with 100% Halal ingredients.</p>', 10),
('Dietary needs', 'Do you make gluten-free, eggless or vegan cakes?', '<p>Yes, on request. Please call us before you order so we can confirm the options for your cake.</p>', 11),
('Dietary needs', 'I have a food allergy. Is it safe to order?', '<p>Our kitchen handles common allergens, so we cannot guarantee any cake is free from traces. Please read our <a href="{url:allergens}">allergen information</a> and call us before ordering.</p>', 12),
('Custom cakes', 'How do I order a custom cake?', '<p>Fill in our <a href="{url:custom-cakes}">custom cake form</a>, call us, or visit the shop. We will reply with a quote and help you choose the size, flavour and decorations.</p>', 13),
('Custom cakes', 'Can you make a cake from a photo?', '<p>Yes. Upload a photo with your request and we will match the design as closely as we can.</p>', 14),
('Cancellations', 'Can I cancel my order?', '<p>Please contact us as early as possible. See our <a href="{url:refund-policy}">Refund and Cancellation Policy</a> for details.</p>', 15);

-- A fresh install already contains every migration.
-- ---------------------------------------------------------------------------
-- Sizes, delivery settings and descriptions (same as migration 007)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('delivery_postcodes',       '2600-2620, 2900-2914'),
('delivery_min_order',       '0'),
('max_orders_per_slot',      '0'),
('max_deliveries_per_day',   '0');

UPDATE settings SET setting_value = '1' WHERE setting_key = 'delivery_enabled';
UPDATE settings SET setting_value = 'All over Canberra and Queanbeyan' WHERE setting_key = 'delivery_note' AND setting_value = 'Delivery across the ACT and Queanbeyan.';

-- Standard cakes come in two sizes: Midi serves 8, Large serves 14.
UPDATE product_sizes s JOIN products p ON p.id = s.product_id SET s.label = 'Midi' WHERE p.slug = 'rainbow-torte' AND s.label = 'Large';
UPDATE product_sizes s JOIN products p ON p.id = s.product_id SET s.label = 'Large' WHERE p.slug = 'rainbow-torte' AND s.label = 'XL';
UPDATE product_sizes s JOIN products p ON p.id = s.product_id SET s.label = 'Midi' WHERE p.slug = 'pavlova' AND s.label = 'Standard';
DELETE s FROM product_sizes s JOIN products p ON p.id = s.product_id WHERE p.slug = 'pavlova' AND s.label LIKE '% p/r';
UPDATE product_sizes SET label = 'Midi' WHERE label = 'Half';
UPDATE product_sizes SET label = 'Large' WHERE label = 'Full';

-- Cakes that only had one size get the other one too.
INSERT INTO product_sizes (product_id, label, price, sort_order)
SELECT s.product_id, 'Midi', s.price - 10.00, 1 FROM product_sizes s
WHERE s.label = 'Large' AND NOT EXISTS (SELECT 1 FROM product_sizes m WHERE m.product_id = s.product_id AND m.label = 'Midi');
INSERT INTO product_sizes (product_id, label, price, sort_order)
SELECT s.product_id, 'Large', s.price + 10.00, 2 FROM product_sizes s
WHERE s.label = 'Midi' AND NOT EXISTS (SELECT 1 FROM product_sizes l WHERE l.product_id = s.product_id AND l.label = 'Large');

INSERT INTO product_sizes (product_id, label, price, sort_order)
SELECT p.id, 'Midi', 34.95, 1 FROM products p
WHERE p.slug = 'orange-poppy-seed-cake' AND NOT EXISTS (SELECT 1 FROM product_sizes s WHERE s.product_id = p.id);
INSERT INTO product_sizes (product_id, label, price, sort_order)
SELECT p.id, 'Large', 44.95, 2 FROM products p
WHERE p.slug = 'orange-poppy-seed-cake' AND NOT EXISTS (SELECT 1 FROM product_sizes s WHERE s.product_id = p.id AND s.label = 'Large');
UPDATE products SET is_active = 1 WHERE slug = 'orange-poppy-seed-cake';

UPDATE product_sizes SET servings = 'Serves 8', sort_order = 1 WHERE label = 'Midi';
UPDATE product_sizes SET servings = 'Serves 14', sort_order = 2 WHERE label = 'Large';

UPDATE products SET image = 'products/zebra-torte.webp' WHERE slug = 'zebra-torte' AND image IS NULL;

-- Product descriptions, shown under "About this cake" and read by search engines.
UPDATE products SET description = 'A classic Black Forest torte: layers of chocolate sponge, vanilla cream and sour cherries, finished with chocolate on top. Light, not too sweet and always popular for birthdays and family celebrations.' WHERE slug = 'black-forest-torte';
UPDATE products SET description = 'Soft sponge layered with a light, airy mousse filling and finished with cream. A gentle, creamy torte that suits any occasion.' WHERE slug = 'mousse-torte';
UPDATE products SET description = 'Vanilla sponge layered with fresh cream and strawberries. A fresh, fruity torte that is a favourite for birthdays and morning teas.' WHERE slug = 'strawberry-torte';
UPDATE products SET description = 'A Hungarian-style sponge torte layered with chocolate buttercream and topped with caramel. Rich, smooth and a great choice for caramel lovers.' WHERE slug = 'caramel-torte';
UPDATE products SET description = 'Chocolate and vanilla sponge layered together with cream, covered in chocolate crumbs and finished with cream rosettes. Two favourite flavours in one cake.' WHERE slug = 'zebra-torte';
UPDATE products SET description = 'Sponge layers filled with a rich hazelnut cream. A nutty, chocolatey torte for anyone who loves hazelnut.' WHERE slug = 'hazelnut-torte';
UPDATE products SET description = 'Chocolate sponge with chocolate cream and fresh strawberries. Chocolate and berries in every slice.' WHERE slug = 'double-choc-strawberry-torte';
UPDATE products SET description = 'Sponge layered with a cookies and cream filling and decorated with chocolate biscuits. A big hit at kids'' parties.' WHERE slug = 'cookies-n-cream-torte';
UPDATE products SET description = 'Our classic vanilla sponge layered with fresh cream. Simple, light and easy to personalise with a message on top.' WHERE slug = 'vanilla-torte';
UPDATE products SET description = 'Coffee-soaked sponge layered with a creamy tiramisu filling and dusted with cocoa. One of our most loved cakes.' WHERE slug = 'tiramisu-torte';
UPDATE products SET description = 'Sponge layered with Biscoff cream and crushed Lotus biscuits, finished with a caramelised biscuit topping.' WHERE slug = 'lotus-biscoff-torte';
UPDATE products SET description = 'Bright rainbow sponge layers with cream. A colourful surprise when you cut it, perfect for kids'' birthdays.' WHERE slug = 'rainbow-torte';
UPDATE products SET description = 'Our honey-bee (bee sting) torte: three layers of honey flavoured sponge with fresh continental cream and vanilla custard.' WHERE slug = 'honey-bee-torte';
UPDATE products SET description = 'Our signature house torte, made the continental way with soft sponge and cream. A shop favourite since 2015.' WHERE slug = 'continental-torte';
UPDATE products SET description = 'A coconut and almond cream torte inspired by Raffaello, finished with coconut. Light, creamy and elegant.' WHERE slug = 'raffaello-torte';
UPDATE products SET description = 'Soft red velvet sponge layered with smooth cream cheese frosting. A customer favourite for birthdays and anniversaries.' WHERE slug = 'red-velvet-cake';
UPDATE products SET description = 'Pistachio velvet sponge layered with cream cheese frosting. A soft, nutty twist on the classic velvet cake.' WHERE slug = 'pistachio-velvet-cake';
UPDATE products SET description = 'Blueberry velvet sponge layered with cream cheese frosting, with a gentle berry flavour.' WHERE slug = 'blueberry-velvet-cake';
UPDATE products SET description = 'Orange and mango velvet sponge layered with cream cheese frosting. Fresh, fruity and a little different.' WHERE slug = 'orange-mango-velvet-cake';
UPDATE products SET description = 'A rich, moist chocolate mud cake. Dense enough to decorate and carry, which makes it a great base for birthday cakes.' WHERE slug = 'chocolate-mud-cake';
UPDATE products SET description = 'White chocolate mud cake with a soft caramel note. Moist, rich and very popular for celebrations.' WHERE slug = 'white-gold-mud-cake';
UPDATE products SET description = 'Chocolate and vanilla mud cake swirled together for a marbled look in every slice.' WHERE slug = 'marble-mud-cake';
UPDATE products SET description = 'A moist caramel mud cake with a deep, buttery flavour.' WHERE slug = 'caramel-mud-cake';
UPDATE products SET description = 'Our signature Treasure cake: a rich mud cake decorated with chocolates and treats on top.' WHERE slug = 'treasure-cake';
UPDATE products SET description = 'A creamy New York style cheesecake baked on a biscuit base. Smooth, rich and classic.' WHERE slug = 'american-baked-cheesecake';
UPDATE products SET description = 'Creamy baked cheesecake on a biscuit base with blueberries.' WHERE slug = 'blueberry-baked-cheesecake';
UPDATE products SET description = 'Baked cheesecake swirled with chocolate on a biscuit base.' WHERE slug = 'marble-baked-cheesecake';
UPDATE products SET description = 'Baked cheesecake on a biscuit base with a tropical fruit topping.' WHERE slug = 'tropical-baked-cheesecake';
UPDATE products SET description = 'Baked cheesecake with hazelnut on a biscuit base. Rich and nutty.' WHERE slug = 'hazelnut-baked-cheesecake';
UPDATE products SET description = 'A light, set continental cheesecake with chocolate chips on a biscuit base.' WHERE slug = 'choc-chip-cheesecake';
UPDATE products SET description = 'A light, set continental cheesecake with caramel on a biscuit base.' WHERE slug = 'caramel-cheesecake';
UPDATE products SET description = 'A light, set continental cheesecake with strawberry on a biscuit base. Fresh and fruity.' WHERE slug = 'strawberry-cheesecake';
UPDATE products SET description = 'A light, set continental cheesecake with a passionfruit topping. Sweet and a little tangy.' WHERE slug = 'passionfruit-cheesecake';
UPDATE products SET description = 'A light, set continental cheesecake with cookie pieces through it.' WHERE slug = 'cookies-cheesecake';
UPDATE products SET description = 'A light, set continental cheesecake with a tangy lemon finish. Refreshing after a big meal.' WHERE slug = 'tangy-lemon-cheesecake';
UPDATE products SET description = 'A light, set continental cheesecake with blueberry on a biscuit base.' WHERE slug = 'blueberry-cheesecake';
UPDATE products SET description = 'A light, set continental cheesecake with pistachio. Creamy and nutty.' WHERE slug = 'pistachio-cheesecake';
UPDATE products SET description = 'Moist spiced carrot cake with cream cheese icing. Great for morning tea.' WHERE slug = 'carrot-cake';
UPDATE products SET description = 'A tower of cream-filled profiteroles, finished with chocolate. A real centrepiece for parties and dessert tables.' WHERE slug = 'profiterole-tower';
UPDATE products SET description = 'Crisp meringue with a soft centre, topped with fresh cream and fruit.' WHERE slug = 'pavlova';
UPDATE products SET description = 'A moist orange and poppy seed tea cake. Lovely with a cup of tea or coffee.' WHERE slug = 'orange-poppy-seed-cake';

-- ---------------------------------------------------------------------------
-- Coupons, popup, delivery windows and payment texts (same as migration 008)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('delivery_windows',         '["9am to 12pm","12pm to 3pm","3pm to 6pm"]'),
('delivery_free_over',       '0'),
('coupons_enabled',          '1'),
('payment_stripe_label',     'Credit or debit card'),
('payment_stripe_note',      'Pay securely with Visa, Mastercard, Amex, Apple Pay or Google Pay through Stripe.'),
('payment_payid_label',      'PayID'),
('payment_payid_note',       'Send the total to our PayID from your banking app, then upload a screenshot of the payment.'),
('payment_bank_label',       'Bank transfer'),
('payment_bank_note',        'Transfer the total to our bank account, then upload a screenshot or PDF of the receipt.'),
('popup_enabled',            '0'),
('popup_title',              'Sweet deal for you'),
('popup_text',               'Order your next cake online and save.'),
('popup_coupon_id',          ''),
('popup_image',              ''),
('popup_button_text',        'Order Online'),
('popup_button_url',         'shop'),
('popup_delay_seconds',      '3'),
('popup_pages',              'all'),
('popup_frequency',          'once'),
('popup_version',            '1');

INSERT IGNORE INTO schema_migrations (migration) VALUES
('001_customer_accounts.sql'), ('002_pages_and_faqs.sql'), ('003_hero_image_setting.sql'), ('004_teal_hero_cake.sql'), ('005_admin_panel.sql'), ('006_delivery_announcement.sql'), ('007_delivery_and_sizes.sql'), ('008_coupons_popup_delivery_windows.sql');
