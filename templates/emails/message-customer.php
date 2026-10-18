<?php /** @var array $thread @var string $body */ ?>
<p style="margin:0 0 12px">Hi <?= e(strtok($thread['customer_name'], ' ')) ?>,</p>
<p style="margin:0 0 6px;font-size:13px;color:#66666E">About: <?= e($thread['subject']) ?></p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 4px"><tr><td style="padding:16px 18px;background:#F1FCF9;border-left:4px solid #1A8670;border-radius:0 12px 12px 0;font-size:15px;line-height:1.6;color:#1B1B1F">
    <?= nl2br(e($body)) ?>
</td></tr></table>
<p style="margin:12px 0 0;font-size:14px;color:#66666E">You can answer with the button below, or simply reply to this email.</p>
