<?php /** @var array $request */ ?>
<p style="margin:0 0 12px"><strong><?= e($request['name']) ?></strong> asked for a custom cake.</p>
<p style="margin:0 0 12px">Phone: <a href="tel:<?= e($request['phone']) ?>" style="color:#0F6E5F"><?= e($request['phone']) ?></a><br>
Email: <a href="mailto:<?= e($request['email']) ?>" style="color:#0F6E5F"><?= e($request['email']) ?></a></p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;background:#F1FAF8;border-radius:12px">
    <tr><td style="padding:16px 18px;font-size:14px;line-height:1.7">
        <strong>Occasion:</strong> <?= e($request['occasion']) ?><br>
        <strong>Date needed:</strong> <?= e(date('l j F Y', strtotime($request['event_date']))) ?><br>
        <strong>Guests:</strong> <?= e($request['servings'] ?: 'Not given') ?><br>
        <strong>Flavour:</strong> <?= e($request['flavour'] ?: 'Not given') ?><br>
        <strong>Budget:</strong> <?= e($request['budget'] ?: 'Not given') ?><br>
        <strong>Details:</strong> <?= nl2br(e($request['details'])) ?>
        <?php if ($request['reference_image']): ?><br><strong>Reference photo:</strong> attached in the admin panel<?php endif; ?>
    </td></tr>
</table>
<?= email_button(email_admin_url('custom-requests/view?id=' . $request['id']), 'Open in admin panel') ?>
