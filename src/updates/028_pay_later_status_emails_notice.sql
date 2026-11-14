-- Two ways to pay later (in the shop at pickup, or by card when the shop calls), off until switched on;
-- a switch for the status update emails customers get (on, as before); and a separate notice time
-- for deliveries, in hours or days (the pickup notice stays as it was).
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('payment_shop_enabled', '0'),
  ('payment_shop_label', 'Pay in the shop'),
  ('payment_shop_note', 'Pay by cash or card when you pick up your order.'),
  ('payment_call_enabled', '0'),
  ('payment_call_label', 'Pay on call'),
  ('payment_call_note', 'We will call you to take payment by card before we make your order.'),
  ('status_emails_enabled', '1'),
  ('pickup_lead_unit', 'hours'),
  ('delivery_lead_unit', 'hours');

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`)
SELECT 'delivery_lead_hours', `setting_value` FROM `settings` WHERE `setting_key` = 'min_lead_hours';
