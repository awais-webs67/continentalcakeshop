<?php /** @var array $request @var string $message */ ?>
<p style="margin:0 0 12px">Hi <?= e(strtok($request['name'], ' ')) ?>,</p>
<p style="margin:0 0 12px"><?= nl2br(e($message)) ?></p>
<?php if ($request['quoted_price']): ?>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;background:#F1FCF9;border-radius:12px">
        <tr><td style="padding:16px 18px">
            <span style="font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#1C8F77;font-weight:bold">Your quote</span><br>
            <span style="font-size:28px;font-weight:bold;color:#1B1B1F"><?= e(money($request['quoted_price'])) ?></span><br>
            <span style="font-size:14px;color:#66666E"><?= e($request['occasion']) ?> cake for <?= e(date('l j F Y', strtotime($request['event_date']))) ?></span>
        </td></tr>
    </table>
<?php endif; ?>
<p style="margin:0;font-size:14px;color:#66666E">Reply to this email or call us on <?= e(setting('store_phone')) ?> to confirm or ask a question.</p>
