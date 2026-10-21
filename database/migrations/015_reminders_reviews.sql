-- Reminder email the day before pickup or delivery, and a Google review request after an order is completed.

-- @data-start
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('reminder_email_enabled', '1'),
('reminder_send_hour',     '10'),
('review_email_enabled',   '1'),
('review_delay_hours',     '20');
-- @data-end
