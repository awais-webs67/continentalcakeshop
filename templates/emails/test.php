<?php /** @var array $admin */ ?>
<p style="margin:0 0 12px">Hi <?= e(strtok($admin['name'], ' ')) ?>,</p>
<p style="margin:0 0 12px">This is a test email from your website's admin panel. If you can read it, order confirmations, order updates and custom cake quotes will reach your customers in the same design.</p>
<p style="margin:0;font-size:14px;color:#66666E">Sent <?= e(date('l j F Y, g:ia')) ?> using the "<?= e((string) mail_config('driver', 'mail')) ?>" mail setting.</p>
