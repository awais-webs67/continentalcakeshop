<?php /** @var array $thread @var string $message */ ?>
<p style="margin:0 0 12px">Hi <?= e(email_first_name($thread['customer_name'])) ?>,</p>
<p style="margin:0 0 12px">Thanks for getting in touch. We have your message and will reply as soon as we can, usually within one business day. You can see the conversation and add to it with the button below.</p>
<p style="margin:12px 0 0;font-size:14px;color:#66666E">Need us sooner? Call <?= e(setting('store_phone')) ?>.</p>
