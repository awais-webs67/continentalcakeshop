-- The second way to pay later is called "Pay on call" (it was "Pay over the phone"), unless the shop renamed it.
UPDATE `settings` SET `setting_value` = 'Pay on call' WHERE `setting_key` = 'payment_call_label' AND `setting_value` = 'Pay over the phone';
