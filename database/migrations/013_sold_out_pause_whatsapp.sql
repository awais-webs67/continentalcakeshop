-- Sold out cakes, pausing online orders (holidays, busy days) and the WhatsApp button.

ALTER TABLE products ADD COLUMN sold_out_until DATE NULL COMMENT 'Last day the cake is sold out; 9999-12-31 = until switched back' AFTER is_enquiry;

-- @data-start
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('ordering_paused',         '0'),
('ordering_paused_until',   ''),
('ordering_paused_message', 'We are not taking online orders right now. Please call us and we will do our best to help.'),
('whatsapp_number',         ''),
('whatsapp_message',        'Hi, I have a question about a cake.');
-- @data-end
