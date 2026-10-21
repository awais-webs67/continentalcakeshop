-- SEO for moving from the old website: landing pages for the searches the old site ranked for
-- (birthday, wedding and baby shower cakes in Canberra), a log of addresses that were not found,
-- and a homepage title that leads with "Cake Shop Canberra" like the old site.

ALTER TABLE pages ADD COLUMN type VARCHAR(20) NOT NULL DEFAULT 'policy' COMMENT 'policy or landing' AFTER slug;
ALTER TABLE pages ADD COLUMN occasion VARCHAR(60) NULL COMMENT 'Landing pages: occasion filled in on the quote form' AFTER summary;
ALTER TABLE pages ADD COLUMN show_gallery TINYINT(1) NOT NULL DEFAULT 1 AFTER occasion;
ALTER TABLE pages ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER show_gallery;

-- Addresses visitors or Google asked for that do not exist, so staff can redirect them.
CREATE TABLE IF NOT EXISTS not_found_log (
    path       VARCHAR(255) NOT NULL PRIMARY KEY,
    hits       INT UNSIGNED NOT NULL DEFAULT 1,
    referrer   VARCHAR(255) NULL,
    first_seen DATETIME NOT NULL,
    last_seen  DATETIME NOT NULL,
    KEY idx_not_found_last (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @data-start
-- Only changed when the shop has not edited them yet.
UPDATE settings SET setting_value = 'Cake Shop Canberra | The Continental Cake Shop, Greenway'
WHERE setting_key = 'seo_home_title' AND setting_value = 'The Continental Cake Shop Canberra | Halal Cakes in Greenway, ACT';
UPDATE settings SET setting_value = 'Cake shop in Greenway, Canberra since 2015'
WHERE setting_key = 'hero_eyebrow' AND setting_value = 'Baked in Greenway, Canberra since 2015';

INSERT IGNORE INTO pages (slug, type, title, summary, occasion, show_gallery, sort_order, body, is_active, meta_title, meta_description) VALUES
('birthday-cakes-canberra', 'landing', 'Birthday cakes in Canberra',
 'Fresh birthday cakes from our shop in Greenway, ready to order online or made to your own design.', 'Birthday', 1, 1,
 '<h2>A birthday cake made fresh in Greenway</h2><p>At {store_name} we bake birthday cakes in our own kitchen in Greenway, Tuggeranong, with 100% Halal ingredients. Choose a favourite from the shop, such as a black forest torte, a red velvet cake or a chocolate mud cake, and add a free message on top. Or tell us your idea and we will design a cake just for the birthday person.</p><h2>Ready-made birthday cakes to order online</h2><p>Our standard cakes come in two sizes: a Midi that serves about 8 people and a Large that serves about 14. Order online, choose your pickup day and time, and the cake will be ready when you arrive. Please order at least {min_lead_hours} hours ahead.</p><ul><li>Tortes, mud cakes, velvet cakes and cheesecakes</li><li>Free message on the cake</li><li>Candles, a cake knife and toppers can be added in the cart</li></ul><p><a href="{url:shop}">See all our cakes</a></p><h2>Custom birthday cakes for kids and adults</h2><p>Unicorns, football, number cakes, favourite colours or a photo you love: send us your idea with a reference photo and we will reply with a quote. Custom cakes need about {custom_lead_days} days notice, more for busy weekends.</p><p>Questions? Call us on {store_phone} or visit us at {store_address}.</p>',
 1, 'Birthday Cakes Canberra | The Continental Cake Shop',
 'Birthday cakes in Canberra made fresh in Greenway with 100% Halal ingredients. Order a favourite online or get a quote for a custom kids or adults cake.'),
('wedding-cakes-canberra', 'landing', 'Wedding and engagement cakes in Canberra',
 'Wedding, engagement and anniversary cakes designed with you and made fresh in our Greenway kitchen.', 'Wedding', 1, 2,
 '<h2>Your wedding cake, made in Canberra</h2><p>From an elegant single tier for an engagement party to a tall tiered cake for the wedding day, {store_name} designs and bakes celebration cakes in Greenway, Tuggeranong. Every cake is made with 100% Halal ingredients.</p><h2>How it works</h2><ol><li>Send us your date, number of guests, flavours and a photo of the style you like.</li><li>We reply with a quote and any questions.</li><li>Accept the quote and pay a deposit online to book your date.</li></ol><p>Popular flavours include vanilla, chocolate, red velvet and our continental cream cakes. We can match your colours, flowers and theme.</p><h2>Book early</h2><p>Wedding cakes need at least {custom_lead_days} days notice, and spring and summer weekends fill up quickly, so please ask as early as you can. Call {store_phone} or send a request below.</p>',
 1, 'Wedding Cakes Canberra | The Continental Cake Shop',
 'Wedding and engagement cakes in Canberra, designed with you and made fresh in Greenway with 100% Halal ingredients. Send your idea and get a quote today.'),
('baby-shower-cakes-canberra', 'landing', 'Baby shower and gender reveal cakes in Canberra',
 'Sweet cakes for baby showers, gender reveals, christenings and first birthdays, made fresh in Greenway.', 'Baby shower', 1, 3,
 '<h2>Cakes to welcome a new baby</h2><p>{store_name} makes baby shower cakes, gender reveal cakes with a pink or blue surprise inside, christening cakes and first birthday cakes in our Greenway kitchen, with 100% Halal ingredients.</p><h2>Make it yours</h2><ul><li>Soft pastel colours, teddy bears, clouds, florals or your own theme</li><li>A name or a message on the cake</li><li>Sizes for a small family lunch or a big party</li></ul><p>Send us your idea and a reference photo, and we will reply with a quote. Please allow at least {custom_lead_days} days notice.</p><h2>Need a cake sooner?</h2><p>Our ready-made cakes can be ordered online with {min_lead_hours} hours notice, and we will write a message of your choice on top. <a href="{url:shop}">Browse our cakes</a> or call us on {store_phone}.</p>',
 1, 'Baby Shower Cakes Canberra | Continental Cake Shop',
 'Baby shower, gender reveal and christening cakes in Canberra, made fresh in Greenway with 100% Halal ingredients. Get a quote for a custom cake or order online.'),
('halal-cakes-canberra', 'landing', 'Halal cakes in Canberra',
 'Every cake we bake is made with 100% Halal ingredients in our shop in Greenway, Tuggeranong.', 'Birthday', 1, 4,
 '<h2>100% Halal ingredients in every cake</h2><p>{store_name} is an independent cake shop in Greenway, Canberra. Every torte, mud cake, velvet cake and cheesecake we make uses 100% Halal ingredients, so you can share them with everyone at your celebration.</p><h2>Cakes for every occasion</h2><p>Birthdays, Eid, weddings, engagements, aqiqah and baby showers: choose a cake from our shop to order online, or ask us to design a custom cake for your event.</p><ul><li><a href="{url:shop}">Order a cake online</a> with {min_lead_hours} hours notice</li><li><a href="{url:custom-cakes}">Request a custom cake</a> with {custom_lead_days} days notice</li><li>Pick up from {store_address}</li></ul><h2>Dietary needs</h2><p>Some cakes can be made eggless, so look for the options on each cake page or call us on {store_phone}. Our kitchen handles milk, eggs, wheat, soy and nuts. <a href="{url:allergens}">Read our allergen information</a>.</p>',
 1, 'Halal Cakes Canberra | The Continental Cake Shop',
 'Looking for Halal cakes in Canberra? Every cake at The Continental Cake Shop in Greenway is made with 100% Halal ingredients. Order online or get a quote.');
-- @data-end
