<?php
/** @var string $content @var string $title @var string $preheader */
// Every email: a slim header (logo and shop name), a wide white card, a one-line footer.
// Tables and inline styles so it looks the same in Gmail, Outlook and Apple Mail; the <style>
// block only tightens the spacing on phones.
$brand = '#1E9B82';
$site = preg_replace('#^https?://#', '', rtrim(url(), '/'));
?>
<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light">
<title><?= e($title) ?></title>
<style>
    @media only screen and (max-width: 620px) {
        .wrap { padding: 12px 8px !important; }
        .card { padding: 22px 18px !important; }
        .head { padding: 14px 18px !important; }
        .foot { padding: 16px 18px !important; }
        h1 { font-size: 21px !important; }
        .stack, .stack td { display: block !important; width: 100% !important; }
    }
</style>
</head>
<body style="margin:0;padding:0;background:#F4F4F5;-webkit-text-size-adjust:100%">
<span style="display:none;max-height:0;overflow:hidden;opacity:0;color:#F4F4F5"><?= e($preheader) ?></span>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F4F5">
<tr><td align="center" class="wrap" style="padding:28px 16px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;border-collapse:separate">
        <tr><td class="head" style="background:#FFFFFF;border:1px solid #E4E4E7;border-bottom:0;border-top:4px solid <?= $brand ?>;border-radius:14px 14px 0 0;padding:16px 32px">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                <td width="44" valign="middle"><a href="<?= e(url()) ?>"><img src="<?= e(url('assets/img/logo-email.png')) ?>" width="40" height="40" alt="" style="display:block;border:0;border-radius:50%"></a></td>
                <td valign="middle" style="padding-left:10px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#111113"><a href="<?= e(url()) ?>" style="color:#111113;text-decoration:none"><?= e(setting('store_name')) ?></a></td>
                <td align="right" valign="middle" style="font-family:Arial,Helvetica,sans-serif;font-size:13px;white-space:nowrap"><a href="tel:<?= e(setting('store_phone_e164')) ?>" style="color:#71717A;text-decoration:none"><?= e(setting('store_phone')) ?></a></td>
            </tr></table>
        </td></tr>
        <tr><td class="card" style="background:#FFFFFF;border:1px solid #E4E4E7;border-top:1px solid #EFEFF1;padding:30px 32px 28px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#27272A">
            <h1 style="margin:0 0 14px;font-family:Arial,Helvetica,sans-serif;font-size:23px;line-height:1.25;color:#111113"><?= e($title) ?></h1>
            <?= $content ?>
            <?php if (!empty($reply_url)): ?>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0 0;border-top:1px solid #EFEFF1">
                <tr><td style="padding:18px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#71717A">
                    <strong style="color:#111113">Questions or changes?</strong> Message us and we will reply by email.
                    <a href="<?= e($reply_url) ?>" style="display:inline-block;margin-left:6px;color:<?= $brand ?>;font-weight:bold;text-decoration:none">Reply to us &rarr;</a>
                </td></tr>
            </table>
            <?php endif; ?>
        </td></tr>
        <tr><td class="foot" style="background:#FAFAFA;border:1px solid #E4E4E7;border-top:0;border-radius:0 0 14px 14px;padding:16px 32px;font-family:Arial,Helvetica,sans-serif;font-size:12.5px;line-height:1.6;color:#71717A;text-align:center">
            <?= e(setting('store_name')) ?> &middot; <?= e(store_address_line()) ?><?php if (setting('store_abn') !== ''): ?> &middot; ABN <?= e(setting('store_abn')) ?><?php endif; ?><br>
            <a href="<?= e(url()) ?>" style="color:<?= $brand ?>;text-decoration:none"><?= e($site) ?></a>
            <?php if (setting('email_accent_note')): ?> &middot; <span><?= e(setting('email_accent_note')) ?></span><?php endif; ?>
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
