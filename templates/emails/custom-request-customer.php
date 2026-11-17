<?php /** @var array $request */ ?>
<p style="margin:0 0 12px">Hi <?= e(email_first_name($request['name'])) ?>,</p>
<p style="margin:0 0 12px">Thank you for your custom cake request. Our team will look at your idea and get back to you with a quote soon.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;background:#F1FCF9;border-radius:12px">
    <tr><td style="padding:16px 18px;font-size:14px;line-height:1.7">
        <strong>Occasion:</strong> <?= e($request['occasion']) ?><br>
        <strong>Date needed:</strong> <?= e(date('l j F Y', strtotime($request['event_date']))) ?><br>
        <?php if ($request['servings']): ?><strong>Serves:</strong> <?= e($request['servings']) ?><br><?php endif; ?>
        <strong>Pickup or delivery:</strong> <?= ($request['fulfilment'] ?? '') === 'delivery' ? 'Delivery' : 'Pickup' ?><br>
        <?php if ($request['flavour']): ?><strong>Flavour:</strong> <?= e($request['flavour']) ?><br><?php endif; ?>
        <strong>Your idea:</strong> as described in your request
    </td></tr>
</table>
<?php if (!empty($request['gallery_photo_url'])): ?>
<p style="margin:0 0 8px;font-size:14px"><strong>The cake you chose:</strong></p>
<p style="margin:0 0 18px"><img src="<?= e($request['gallery_photo_url']) ?>" alt="The cake you chose from our gallery" width="200" style="display:block;width:200px;max-width:100%;height:auto;border-radius:12px"></p>
<?php endif; ?>
<p style="margin:0;font-size:14px;color:#71717A">If your date is soon, please call us on <?= e(setting('store_phone')) ?>.</p>
