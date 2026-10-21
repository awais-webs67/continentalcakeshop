-- Nightly database backups in storage/backups (Admin > System check).

-- @data-start
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('backup_enabled',   '1'),
('backup_keep_days', '14'),
('backup_last_run',  '');
-- @data-end
