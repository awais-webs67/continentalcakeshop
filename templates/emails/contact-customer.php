<?php /** @var array $thread @var string $message */ ?>
<p style="margin:0 0 12px">Hi <?= e(strtok($thread['customer_name'], ' ')) ?>,</p>
<p style="margin:0 0 12px">Thanks for getting in touch. We have your message and will reply as soon as we can, usually within one business day.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 4px"><tr><td style="padding:14px 16px;background:#F7FBFA;border-radius:12px;font-size:14px;color:#66666E">
    <?= nl2br(e($message)) ?>
</td></tr></table>
<p style="margin:12px 0 0;font-size:14px;color:#66666E">Need us sooner? Call <?= e(setting('store_phone')) ?>.</p>
