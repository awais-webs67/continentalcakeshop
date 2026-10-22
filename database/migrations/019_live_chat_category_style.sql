-- Live updates: the admin panel and the customer's chat page check for new messages every few
-- seconds. These times show who has the conversation open right now, so no email is sent for a
-- message the other side is already reading live.
ALTER TABLE threads ADD COLUMN admin_seen_at DATETIME NULL AFTER last_message_at;
ALTER TABLE threads ADD COLUMN customer_seen_at DATETIME NULL AFTER admin_seen_at;

-- @data-start
-- Homepage categories: 'tiles' (photo grid) or 'circles' (round photos that scroll past).
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('home_categories_style', 'tiles');
-- @data-end
