-- The top bar now shows the delivery message. Only replaces the old default text,
-- so a message the shop already changed in the admin panel is kept.
UPDATE settings
SET setting_value = 'Delivery all over Canberra'
WHERE setting_key = 'announcement_text'
  AND setting_value = '100% Halal ingredients. Order online and pick up from our Greenway shop.';
