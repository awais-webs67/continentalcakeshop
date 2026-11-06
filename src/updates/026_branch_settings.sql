-- Settings of one shop that can differ from the shared ones in Admin > Settings: its own email
-- account (sending, its inbox in Messages), its automatic emails (morning plan, reminders,
-- review requests) and, if it is ever wanted, its own payment details. A shop that has none
-- uses the shared settings, so nothing changes until a shop is given its own.

CREATE TABLE IF NOT EXISTS `branch_settings` (
  `branch_id` int(10) unsigned NOT NULL,
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`branch_id`, `setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
