-- Search result text that was too long (home page) or too short (policy pages).
-- Only changed when the shop has not edited it yet.

-- @data-start
UPDATE settings SET setting_value = 'Order fresh Halal cakes online from The Continental Cake Shop in Greenway, Canberra: tortes, mud cakes, velvet cakes, cheesecakes and custom cakes.'
WHERE setting_key = 'seo_home_description' AND setting_value = 'Order fresh tortes, mud cakes, velvet cakes and cheesecakes online from The Continental Cake Shop in Greenway, Canberra. 100% Halal ingredients, custom cakes and easy pickup.';
UPDATE pages SET meta_description = 'How The Continental Cake Shop in Greenway, Canberra collects, uses and protects your personal information when you order or contact us.'
WHERE slug = 'privacy-policy' AND (meta_description IS NULL OR meta_description = '');
UPDATE pages SET meta_description = 'The terms that apply when you order cakes online from The Continental Cake Shop in Greenway, Canberra, including pickup, payment and custom cakes.'
WHERE slug = 'terms' AND (meta_description IS NULL OR meta_description = '');
UPDATE pages SET meta_description = 'How to cancel or change a cake order with The Continental Cake Shop in Greenway, Canberra, and what to do if there is a problem with your order.'
WHERE slug = 'refund-policy' AND (meta_description IS NULL OR meta_description = '');
-- @data-end
