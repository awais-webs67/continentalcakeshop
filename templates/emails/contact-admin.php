<?php /** @var array $data */ ?>
<p style="margin:0 0 12px"><strong><?= e($data['name']) ?></strong> sent a message through the website.</p>
<p style="margin:0 0 12px">Email: <a href="mailto:<?= e($data['email']) ?>" style="color:#58DCBF"><?= e($data['email']) ?></a><?php if ($data['phone']): ?><br>Phone: <a href="tel:<?= e($data['phone']) ?>" style="color:#58DCBF"><?= e($data['phone']) ?></a><?php endif; ?></p>
<p style="margin:0 0 12px;padding:14px 16px;background:#F1FCF9;border-radius:12px"><?= nl2br(e($data['message'])) ?></p>
<?= email_button(email_admin_url('messages'), 'Open messages') ?>
