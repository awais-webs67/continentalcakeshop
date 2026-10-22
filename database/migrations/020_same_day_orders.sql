-- Cakes from the menu are made in the shop and ready to go, so customers can order for today:
-- the notice drops from 24 hours to 1 hour. Only changed when the shop never edited it.
-- (Admin > Settings > Ordering > Notice needed for orders.)

-- @data-start
UPDATE settings SET setting_value = '1' WHERE setting_key = 'min_lead_hours' AND setting_value = '24';
-- @data-end
