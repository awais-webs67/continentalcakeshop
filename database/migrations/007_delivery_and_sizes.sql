-- Delivery at checkout, the Midi (serves 8) and Large (serves 14) sizes, enquiry-only
-- products, booking limits and fuller product descriptions.

-- ---------------------------------------------------------------------------
-- Structure
-- ---------------------------------------------------------------------------

-- Delivery orders have no time until the shop sets one.
ALTER TABLE orders MODIFY pickup_time TIME NULL;
ALTER TABLE orders ADD COLUMN delivery_suburb VARCHAR(80) NULL AFTER delivery_address;
ALTER TABLE orders ADD COLUMN delivery_postcode VARCHAR(4) NULL AFTER delivery_suburb;
ALTER TABLE orders ADD COLUMN delivery_window VARCHAR(20) NULL AFTER delivery_postcode;

-- Specials that are priced on request: the product page shows an enquiry button instead of a cart.
ALTER TABLE products ADD COLUMN is_enquiry TINYINT(1) NOT NULL DEFAULT 0 AFTER allow_message;

-- Custom cake requests: whether the customer wants pickup or delivery.
ALTER TABLE custom_requests ADD COLUMN fulfilment VARCHAR(20) NULL AFTER event_date;

-- ---------------------------------------------------------------------------
-- Data (also part of seed.sql)
-- ---------------------------------------------------------------------------

-- @data-start
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
-- @data-end
