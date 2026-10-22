<?php /** @var array $user @var string $link @var int $minutes */ ?>
<p style="margin:0 0 12px">Hi <?= e(email_first_name($user['name'])) ?>,</p>
<p style="margin:0 0 12px">We received a request to reset your password. The button below works once, for <?= (int) $minutes ?> minutes.</p>
<?= email_button($link, 'Choose a new password') ?>
<p style="margin:0;font-size:14px;color:#66666E">If you did not ask for this, you can ignore this email and your password will stay the same.</p>
