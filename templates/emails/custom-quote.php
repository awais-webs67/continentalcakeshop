<?php /** @var array $request @var string $message */ ?>
<p style="margin:0 0 12px">Hi <?= e(email_first_name($request['name'])) ?>,</p>
<p style="margin:0 0 12px"><?= nl2br(e($message)) ?></p>
<?php if ($request['quoted_price']): ?>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;background:#F1FCF9;border-radius:12px">
        <tr><td style="padding:16px 18px">
            <span style="font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#1E9B82;font-weight:bold">Your quote</span><br>
            <span style="font-size:28px;font-weight:bold;color:#1B1B1F"><?= e(money($request['quoted_price'])) ?></span><br>
            <span style="font-size:14px;color:#66666E"><?= e($request['occasion']) ?> cake for <?= e(date('l j F Y', strtotime($request['event_date']))) ?></span>
            <?php if ($request['deposit_amount'] !== null && quote_payable($request)): ?>
                <br><span style="font-size:14px;color:#1B1B1F">Deposit to confirm: <strong><?= e(money(quote_deposit($request))) ?></strong></span>
            <?php endif; ?>
        </td></tr>
    </table>
<?php endif; ?>
<?php if (quote_payable($request) && !$request['order_id']): ?>
    <?= email_button(quote_url($request), $request['deposit_amount'] !== null ? 'Accept and pay the deposit' : 'Accept and pay') ?>
<?php endif; ?>
<p style="margin:0;font-size:14px;color:#66666E">Reply to this email or call us on <?= e(setting('store_phone')) ?> to confirm or ask a question.</p>
