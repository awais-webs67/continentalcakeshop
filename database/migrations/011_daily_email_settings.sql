-- The morning email before the shop opens (sent by cron/run.php).

-- @data-start
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('daily_email_enabled',        '1'),
('daily_email_to',             ''),
('daily_email_minutes_before', '60'),
('daily_email_last_sent',      ''),
('cron_last_run',              '');
-- @data-end
