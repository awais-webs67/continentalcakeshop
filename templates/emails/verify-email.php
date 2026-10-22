<?php /** @var array $user @var string $link */ ?>
<p style="margin:0 0 12px">Hi <?= e(email_first_name($user['name'])) ?>,</p>
<p style="margin:0 0 12px">Please confirm that this is your email address. Once it is confirmed, orders and messages you sent with this email before you had an account appear in your account too.</p>
<?= email_button($link, 'Confirm my email') ?>
<p style="margin:0;font-size:14px;color:#66666E">If you did not create an account with <?= e(setting('store_short_name')) ?>, you can ignore this email.</p>
