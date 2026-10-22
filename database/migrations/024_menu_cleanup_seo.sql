-- Menu clean-up and search fixes:
--  * removes the cakes that have no photo on the shop's website (they showed "Photo coming soon");
--    a cake is only removed while it still has no photo, so one the shop has added a photo to stays
--  * each cake's "Ordering" heading names the cake and Canberra, e.g. "Black Forest Torte Canberra
--    pickup and delivery", so the focus keyword appears in the page text
--  * the shop's map position (38 Reed St N, Greenway), used by Google for the local listing

-- @data-start
DELETE FROM products WHERE image IS NULL AND slug IN ('lotus-biscoff-torte', 'raffaello-torte', 'pistachio-velvet-cake', 'blueberry-velvet-cake', 'orange-mango-velvet-cake', 'cookies-cheesecake', 'pistachio-cheesecake');
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Black Forest Torte Canberra pickup and delivery</h3>') WHERE slug = 'black-forest-torte';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Mousse Torte Canberra pickup and delivery</h3>') WHERE slug = 'mousse-torte';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Strawberry Torte Canberra pickup and delivery</h3>') WHERE slug = 'strawberry-torte';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Caramel Torte Canberra pickup and delivery</h3>') WHERE slug = 'caramel-torte';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Zebra Torte Canberra pickup and delivery</h3>') WHERE slug = 'zebra-torte';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Hazelnut Torte Canberra pickup and delivery</h3>') WHERE slug = 'hazelnut-torte';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Double Choc Strawberry Torte Canberra pickup and delivery</h3>') WHERE slug = 'double-choc-strawberry-torte';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Cookies ''n'' Cream Torte Canberra pickup and delivery</h3>') WHERE slug = 'cookies-n-cream-torte';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Vanilla Torte Canberra pickup and delivery</h3>') WHERE slug = 'vanilla-torte';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Tiramisu Torte Canberra pickup and delivery</h3>') WHERE slug = 'tiramisu-torte';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Rainbow Torte Canberra pickup and delivery</h3>') WHERE slug = 'rainbow-torte';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Honey-Bee Torte Canberra pickup and delivery</h3>') WHERE slug = 'honey-bee-torte';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Continental Torte Canberra pickup and delivery</h3>') WHERE slug = 'continental-torte';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Red Velvet Cake Canberra pickup and delivery</h3>') WHERE slug = 'red-velvet-cake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Chocolate Mud Cake Canberra pickup and delivery</h3>') WHERE slug = 'chocolate-mud-cake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>White Gold Mud Cake Canberra pickup and delivery</h3>') WHERE slug = 'white-gold-mud-cake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Marble Mud Cake Canberra pickup and delivery</h3>') WHERE slug = 'marble-mud-cake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Caramel Mud Cake Canberra pickup and delivery</h3>') WHERE slug = 'caramel-mud-cake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Treasure Cake Canberra pickup and delivery</h3>') WHERE slug = 'treasure-cake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>American Baked Cheesecake Canberra pickup and delivery</h3>') WHERE slug = 'american-baked-cheesecake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Blueberry Baked Cheesecake Canberra pickup and delivery</h3>') WHERE slug = 'blueberry-baked-cheesecake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Marble Baked Cheesecake Canberra pickup and delivery</h3>') WHERE slug = 'marble-baked-cheesecake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Tropical Baked Cheesecake Canberra pickup and delivery</h3>') WHERE slug = 'tropical-baked-cheesecake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Hazelnut Baked Cheesecake Canberra pickup and delivery</h3>') WHERE slug = 'hazelnut-baked-cheesecake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Choc Chip Cheesecake Canberra pickup and delivery</h3>') WHERE slug = 'choc-chip-cheesecake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Caramel Cheesecake Canberra pickup and delivery</h3>') WHERE slug = 'caramel-cheesecake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Strawberry Cheesecake Canberra pickup and delivery</h3>') WHERE slug = 'strawberry-cheesecake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Passionfruit Cheesecake Canberra pickup and delivery</h3>') WHERE slug = 'passionfruit-cheesecake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Tangy Lemon Cheesecake Canberra pickup and delivery</h3>') WHERE slug = 'tangy-lemon-cheesecake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Blueberry Cheesecake Canberra pickup and delivery</h3>') WHERE slug = 'blueberry-cheesecake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Carrot Cake Canberra pickup and delivery</h3>') WHERE slug = 'carrot-cake';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Profiterole Tower Canberra pickup and delivery</h3>') WHERE slug = 'profiterole-tower';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Pavlova Canberra pickup and delivery</h3>') WHERE slug = 'pavlova';
UPDATE products SET description = REPLACE(description, '<h3>Ordering</h3>', '<h3>Orange &amp; Poppy Seed Cake Canberra pickup and delivery</h3>') WHERE slug = 'orange-poppy-seed-cake';
INSERT INTO settings (setting_key, setting_value) VALUES ('store_latitude', '-35.418923'), ('store_longitude', '149.065858')
ON DUPLICATE KEY UPDATE setting_value = IF(setting_value = '', VALUES(setting_value), setting_value);
-- @data-end
