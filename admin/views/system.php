<?php /** @var array $checks @var array $pending @var array $recentMail @var string $appUrl */
$problems = 0;
foreach ($checks as $rows) { foreach ($rows as $row) { $problems += $row[1] ? 0 : 1; } }
?>
<div class="page-head">
    <div><h1>System check</h1><p>Use this after moving the website to new hosting, or if something stops working.</p></div>
</div>
<?php if ($pending): ?>
    <div class="alert alert--warning" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;justify-content:space-between">
        <span><strong>The database needs an update.</strong> <?= count($pending) ?> change<?= count($pending) === 1 ? '' : 's' ?> waiting: <?= e(implode(', ', $pending)) ?></span>
        <form method="post" data-confirm="Update the database now? Make a backup in cPanel first if this is the live site."><?= csrf_field() ?><input type="hidden" name="action" value="migrate"><button class="btn btn--primary btn--sm" type="submit">Update database</button></form>
    </div>
<?php endif; ?>
<div class="stats">
    <div class="stat <?= $problems ? 'stat--alert' : '' ?>"><span class="stat__label"><?= icon($problems ? 'info' : 'check', '', 16) ?> Checks</span><span class="stat__value"><?= $problems ? $problems . ' to fix' : 'All good' ?></span></div>
    <div class="stat"><span class="stat__label"><?= icon('store', '', 16) ?> Database</span><span class="stat__value" style="font-size:18px"><?= $pending ? 'Update needed' : 'Up to date' ?></span><span class="stat__note"><?= count(migration_files()) ?> migrations</span></div>
    <div class="stat"><span class="stat__label"><?= icon('mail', '', 16) ?> Mail driver</span><span class="stat__value" style="font-size:18px"><?= e(strtoupper((string) mail_config('driver', 'mail'))) ?></span></div>
    <div class="stat"><span class="stat__label"><?= icon('card', '', 16) ?> Payment methods</span><span class="stat__value" style="font-size:18px"><?= count(available_payment_methods()) ?> live</span></div>
</div>
<div class="grid-2">
    <?php foreach ($checks as $group => $rows): ?>
        <section class="card">
            <div class="card__head"><h2><?= e($group) ?></h2></div>
            <div class="card__body"><ul class="check-list">
                <?php foreach ($rows as [$label, $ok, $fix]): ?>
                    <li><span><strong><?= e($label) ?></strong><?php if (!$ok): ?><br><span class="hint"><?= e($fix) ?></span><?php endif; ?></span><?= $ok ? status_badge('active', 'OK') : status_badge('pending', 'Check') ?></li>
                <?php endforeach; ?>
            </ul></div>
        </section>
    <?php endforeach; ?>
    <section class="card">
        <div class="card__head"><h2>Useful links</h2></div>
        <div class="card__body"><ul class="check-list">
            <li><span>Website address</span><a href="<?= e($appUrl) ?>" target="_blank"><?= e($appUrl) ?></a></li>
            <li><span>Stripe webhook</span><code><?= e(url('stripe-webhook')) ?></code></li>
            <li><span>Sitemap for Google</span><a href="<?= e(url('sitemap.xml')) ?>" target="_blank">sitemap.xml</a></li>
            <li><span>Send a test email</span><a href="<?= e(admin_url('settings', ['tab' => 'emails'])) ?>">Settings, Emails</a></li>
        </ul></div>
    </section>
    <?php if ($recentMail): ?>
    <section class="card">
        <div class="card__head"><h2>Recently saved emails</h2><span class="hint">storage/logs/mail</span></div>
        <div class="card__body"><ul class="check-list">
            <?php foreach ($recentMail as $file): ?>
                <li><span><?= e(basename($file)) ?></span><span class="muted"><?= e(time_ago(date('Y-m-d H:i:s', filemtime($file)))) ?></span></li>
            <?php endforeach; ?>
        </ul></div>
    </section>
    <?php endif; ?>
</div>
