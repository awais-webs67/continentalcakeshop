<?php /** @var array $order */ ?>
<p style="margin:0 0 12px">Hi <?= e(email_first_name($order['customer_name'])) ?>,</p>
<p style="margin:0 0 12px">Thank you for choosing <?= e(setting('store_short_name')) ?>. We hope your cake was a hit!</p>
<p style="margin:0 0 12px">We are a small local bakery, and a quick Google review from you helps other Canberra families find us. It only takes a minute.</p>
<?= email_button(setting('google_reviews_url'), 'Leave a Google review') ?>
<p style="margin:0;font-size:14px;color:#66666E">Something not quite right? Please reply to this email or call <?= e(setting('store_phone')) ?> and we will make it right.</p>
