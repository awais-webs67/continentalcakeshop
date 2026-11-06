-- Each shop (branch) now holds everything about itself: its address, phone, the email shown on
-- the website and its email account. Settings keeps only what the whole business shares.
-- This copies the main shop's details from Settings into its branch row, once.

-- The main shop's address, phone and map position (Settings > Shop details until now).
UPDATE `branches` b SET
  b.`street`    = COALESCE(NULLIF((SELECT s.`setting_value` FROM `settings` s WHERE s.`setting_key` = 'store_street'), ''), b.`street`),
  b.`suburb`    = COALESCE(NULLIF((SELECT s.`setting_value` FROM `settings` s WHERE s.`setting_key` = 'store_suburb'), ''), b.`suburb`),
  b.`state`     = COALESCE(NULLIF((SELECT s.`setting_value` FROM `settings` s WHERE s.`setting_key` = 'store_state'), ''), b.`state`),
  b.`postcode`  = COALESCE(NULLIF((SELECT s.`setting_value` FROM `settings` s WHERE s.`setting_key` = 'store_postcode'), ''), b.`postcode`),
  b.`phone`     = COALESCE(NULLIF((SELECT s.`setting_value` FROM `settings` s WHERE s.`setting_key` = 'store_phone'), ''), b.`phone`),
  b.`latitude`  = COALESCE(NULLIF((SELECT s.`setting_value` FROM `settings` s WHERE s.`setting_key` = 'store_latitude'), ''), b.`latitude`),
  b.`longitude` = COALESCE(NULLIF((SELECT s.`setting_value` FROM `settings` s WHERE s.`setting_key` = 'store_longitude'), ''), b.`longitude`)
WHERE b.`is_main` = 1;

-- Its order alerts address (Settings > Emails until now), unless it already has one.
UPDATE `branches` b SET b.`email` = (SELECT s.`setting_value` FROM `settings` s WHERE s.`setting_key` = 'order_notification_email')
WHERE b.`is_main` = 1 AND (b.`email` IS NULL OR b.`email` = '')
  AND (SELECT s.`setting_value` FROM `settings` s WHERE s.`setting_key` = 'order_notification_email') LIKE '%@%';
UPDATE `settings` SET `setting_value` = '' WHERE `setting_key` = 'order_notification_email';

-- The email shown on the website for the main shop (Settings > Shop details until now).
INSERT INTO `branch_settings` (`branch_id`, `setting_key`, `setting_value`)
SELECT b.`id`, 'public_email', s.`setting_value` FROM `branches` b JOIN `settings` s ON s.`setting_key` = 'store_email'
WHERE b.`is_main` = 1 AND s.`setting_value` LIKE '%@%'
ON DUPLICATE KEY UPDATE `setting_value` = `branch_settings`.`setting_value`;

-- The main shop's email account is the one in Settings (it also sends emails that belong to no
-- shop, like password links). If it was given its "own" account in the meantime, that becomes it.
INSERT INTO `settings` (`setting_key`, `setting_value`)
SELECT bs.`setting_key`, bs.`setting_value` FROM `branch_settings` bs JOIN `branches` b ON b.`id` = bs.`branch_id` AND b.`is_main` = 1
WHERE bs.`setting_key` IN ('mail_provider', 'mail_driver', 'mail_from_address', 'mail_from_name', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password_encrypted', 'imap_host', 'imap_port')
  AND EXISTS (SELECT 1 FROM `branch_settings` o WHERE o.`branch_id` = b.`id` AND o.`setting_key` = 'email_own' AND o.`setting_value` = '1')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

DELETE bs FROM `branch_settings` bs JOIN `branches` b ON b.`id` = bs.`branch_id` AND b.`is_main` = 1
WHERE bs.`setting_key` IN ('email_own', 'mail_provider', 'mail_driver', 'mail_from_address', 'mail_from_name', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password_encrypted', 'imap_host', 'imap_port');
