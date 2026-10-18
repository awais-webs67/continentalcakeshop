<?php
/** @var string $content @var string $title @var string $preheader */
$brand = '#1A8670';
?>
<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<title><?= e($title) ?></title>
</head>
<body style="margin:0;padding:0;background:#EEF4F2;-webkit-text-size-adjust:100%">
<span style="display:none;max-height:0;overflow:hidden;opacity:0;color:#EEF4F2"><?= e($preheader) ?></span>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#EEF4F2">
<tr><td align="center" style="padding:28px 12px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px">
        <tr><td align="center" style="background:<?= $brand ?>;border-radius:18px 18px 0 0;padding:26px 24px 22px">
            <a href="<?= e(url()) ?>" style="text-decoration:none">
                <img src="<?= e(url('assets/img/logo-email.png')) ?>" width="96" height="96" alt="<?= e(setting('store_name')) ?>" style="display:block;border:0;margin:0 auto">
            </a>
        </td></tr>
        <tr><td style="background:#FFFFFF;padding:34px 34px 30px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#2E2E33">
            <h1 style="margin:0 0 14px;font-family:Arial,Helvetica,sans-serif;font-size:24px;line-height:1.25;color:#1B1B1F"><?= e($title) ?></h1>
            <?= $content ?>
            <?php if (!empty($reply_url)): ?>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:26px 0 0;border-top:1px solid #E3EAE8">
                <tr><td style="padding:20px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#66666E">
                    <strong style="color:#1B1B1F">Questions or changes?</strong> Message us and we will reply here and by email.
                    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:12px 0 0"><tr><td style="border-radius:999px;border:2px solid <?= $brand ?>">
                        <a href="<?= e($reply_url) ?>" style="display:inline-block;padding:10px 22px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;color:<?= $brand ?>;text-decoration:none">Reply to us</a>
                    </td></tr></table>
                </td></tr>
            </table>
            <?php endif; ?>
        </td></tr>
        <tr><td style="background:#F7FBFA;border-top:1px solid #E3EAE8;border-radius:0 0 18px 18px;padding:22px 34px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;color:#66666E;text-align:center">
            <strong style="color:#1B1B1F"><?= e(setting('store_name')) ?></strong><br>
            <?= e(store_address_line()) ?><br>
            <?php if (setting('store_abn') !== ''): ?>ABN <?= e(setting('store_abn')) ?><br><?php endif; ?>
            <a href="tel:<?= e(setting('store_phone_e164')) ?>" style="color:<?= $brand ?>;text-decoration:none"><?= e(setting('store_phone')) ?></a>
            &nbsp;&middot;&nbsp;
            <a href="<?= e(url()) ?>" style="color:<?= $brand ?>;text-decoration:none"><?= e(preg_replace('#^https?://#', '', rtrim(url(), '/'))) ?></a>
            <?php if (setting('email_accent_note')): ?>
                <br><span style="color:#8A8A92"><?= e(setting('email_accent_note')) ?></span>
            <?php endif; ?>
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
