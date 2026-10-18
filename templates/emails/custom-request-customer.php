<?php /** @var array $request */ ?>
<p style="margin:0 0 12px">Hi <?= e(strtok($request['name'], ' ')) ?>,</p>
<p style="margin:0 0 12px">Thank you for your custom cake request. Our team will look at your idea and get back to you with a quote soon.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;background:#F1FAF8;border-radius:12px">
    <tr><td style="padding:16px 18px;font-size:14px;line-height:1.7">
        <strong>Occasion:</strong> <?= e($request['occasion']) ?><br>
        <strong>Date needed:</strong> <?= e(date('l j F Y', strtotime($request['event_date']))) ?><br>
        <?php if ($request['servings']): ?><strong>Guests:</strong> <?= e($request['servings']) ?><br><?php endif; ?>
        <?php if ($request['flavour']): ?><strong>Flavour:</strong> <?= e($request['flavour']) ?><br><?php endif; ?>
        <strong>Your idea:</strong> <?= nl2br(e($request['details'])) ?>
    </td></tr>
</table>
<p style="margin:0;font-size:14px;color:#66666E">If your date is soon, please call us on <?= e(setting('store_phone')) ?>.</p>
