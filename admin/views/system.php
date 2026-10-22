<?php /** @var array $checks @var array $pending @var array $recentMail @var string $appUrl @var array $backups @var array $clearCounts */
$problems = 0;
foreach ($checks as $rows) { foreach ($rows as $row) { $problems += $row[1] ? 0 : 1; } }
?>
<div class="page-head">
    <div><h1>System check</h1><p>Use this after moving the website to new hosting, or if something stops working.</p></div>
</div>
<?php if ($pending): ?>
    <div class="alert alert--warning" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;justify-content:space-between">
        <span><strong>The database needs an update.</strong> It normally updates itself on the next visit. <?= count($pending) ?> change<?= count($pending) === 1 ? '' : 's' ?> waiting: <?= e(implode(', ', $pending)) ?></span>
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
    <section class="card">
        <div class="card__head"><h2>Backups</h2><span class="hint">Every night, kept <?= (int) setting('backup_keep_days', '14') ?> days</span></div>
        <div class="card__body">
            <form method="post" style="margin-bottom:12px"><?= csrf_field() ?><input type="hidden" name="action" value="backup"><button class="btn btn--secondary btn--sm" type="submit"><?= icon('upload', '', 16) ?> Back up now</button></form>
            <?php if (!$backups): ?>
                <p class="hint" style="margin:0">No backups yet.</p>
            <?php else: ?>
                <ul class="check-list">
                    <?php foreach (array_slice($backups, 0, 10) as $b): ?>
                        <li><span><?= e(date('D j M Y, g:ia', $b['time'])) ?><br><span class="muted"><?= e(number_format($b['size'] / 1024, 0)) ?> KB</span></span><a href="<?= e(admin_url('system', ['download' => $b['name']])) ?>">Download</a></li>
                    <?php endforeach; ?>
                </ul>
                <p class="hint" style="margin:10px 0 0">To restore, import the file in cPanel &gt; phpMyAdmin &gt; your database &gt; Import. Keep a copy on your computer too.</p>
            <?php endif; ?>
        </div>
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
<section class="card card--danger" id="clear-data">
    <div class="card__head"><h2>Clear test data</h2><span class="hint">Before going live</span></div>
    <div class="card__body">
        <p class="hint" style="margin-top:0">Removes the orders, accounts and messages made while testing. Your menu, prices, photos, pages, settings and staff logins stay. A backup is saved first, so you can undo it by importing that backup.</p>
        <form method="post" data-confirm="Delete the ticked data for good? A backup is made first."><?= csrf_field() ?>
            <input type="hidden" name="action" value="clear-data">
            <ul class="check-list clear-list">
                <?php foreach (CLEARABLE_DATA as $key => [$label, $about]): ?>
                    <li>
                        <label class="clear-check"><input type="checkbox" name="groups[]" value="<?= e($key) ?>" <?= $clearCounts[$key] ? '' : 'disabled' ?>>
                            <span><strong><?= e($label) ?></strong><br><span class="hint"><?= e($about) ?></span></span></label>
                        <span class="muted"><?= (int) $clearCounts[$key] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <label class="field" style="margin-top:14px"><span>Type DELETE to confirm</span><input class="input" name="confirm" autocomplete="off" spellcheck="false" placeholder="DELETE" style="max-width:220px"></label>
            <button class="btn btn--danger" type="submit" style="margin-top:10px"><?= icon('trash', '', 16) ?> Clear ticked data</button>
        </form>
    </div>
</section>
