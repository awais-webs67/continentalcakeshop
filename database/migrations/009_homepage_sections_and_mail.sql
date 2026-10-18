-- Homepage sections the admin can switch on or off, reorder and edit, and email settings
-- that can be managed in the admin panel. Existing values are never overwritten.

-- @data-start
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('home_sections',          '[{"key":"hero","on":1},{"key":"usp","on":1},{"key":"categories","on":1},{"key":"featured","on":1},{"key":"custom","on":1},{"key":"text","on":0},{"key":"reviews","on":1},{"key":"visit","on":1}]'),
('hero_btn1_text',         'Order Online'),
('hero_btn1_url',          'shop'),
('hero_btn2_text',         'Request a Custom Cake'),
('hero_btn2_url',          'custom-cakes'),
('hero_show_shop_card',    '1'),
('home_usp_items',         '["100% Halal ingredients","Gluten-free, eggless and vegan options","Free message on every cake","Pickup in Greenway or delivery across Canberra"]'),
('home_categories_title',  'Shop by category'),
('home_categories_text',   'Every cake is made in our Greenway shop.'),
('home_featured_title',    'Customer favourites'),
('home_featured_text',     'The cakes Canberra keeps coming back for.'),
('home_featured_count',    '8'),
('home_custom_title',      'Made to order, just the way you pictured it'),
('home_custom_text',       '<p>Bring us a photo or tell us your idea. We will help you choose the size, flavour and decorations, then send you a quote. Please allow at least {custom_lead_days} days for custom designs.</p>'),
('home_custom_steps',      '[["Share your idea","Upload a photo or describe the cake you want."],["Get your quote","We reply with a price and help with size and flavour."],["Pickup or delivery","Collect your cake from Greenway or we deliver it on the day."]]'),
('home_custom_btn_text',   'Get a Quote'),
('home_text_title',        'A note from our bakers'),
('home_text_body',         '<p>Write anything you like here, such as holiday opening hours or a new range of cakes.</p>'),
('home_text_style',        'tint'),
('home_reviews_title',     'What our customers say'),
('home_reviews_text',      'Reviews left by our customers on Google.'),
('home_visit_title',       'Visit us in Greenway'),
('mail_driver',            ''),
('mail_from_address',      ''),
('mail_from_name',         ''),
('smtp_host',              ''),
('smtp_port',              ''),
('smtp_encryption',        ''),
('smtp_username',          ''),
('smtp_password_encrypted', '');
-- @data-end
