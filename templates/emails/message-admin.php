<?php /** @var array $thread @var string $body */ ?>
<p style="margin:0 0 6px"><strong><?= e($thread['customer_name']) ?></strong> wrote about <strong><?= e($thread['subject']) ?></strong>:</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 4px"><tr><td style="padding:16px 18px;background:#F1FCF9;border-radius:12px;font-size:15px;line-height:1.6;color:#1B1B1F">
    <?= nl2br(e($body)) ?>
</td></tr></table>
<p style="margin:10px 0 0;font-size:14px;color:#66666E"><?= e($thread['customer_email']) ?><?= $thread['customer_phone'] ? ' &middot; ' . e($thread['customer_phone']) : '' ?></p>
<?= email_button(email_admin_url('messages/view?id=' . $thread['id']), 'Open and reply') ?>
