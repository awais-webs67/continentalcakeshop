<?php /** @var array $checks @var array $oldPages @var array $missing @var array $redirects @var array $targets */
$passed = count(array_filter($checks, static fn ($c) => $c[1]));
?>
<div class="page-head">
    <div><h1>SEO</h1><p>Everything that helps the new website keep, and improve, the Google rankings of the old one.</p></div>
    <div class="page-head__actions"><a class="btn btn--secondary" href="<?= e(url('sitemap.xml')) ?>" target="_blank">Sitemap</a><a class="btn btn--secondary" href="<?= e(admin_url('settings', ['tab' => 'seo'])) ?>">SEO settings</a></div>
</div>

<div class="grid-main">
    <div class="stack">
        <section class="card">
            <div class="card__head"><h2>Launch checklist</h2><span class="seo-score seo-score--<?= $passed === count($checks) ? 'good' : ($passed >= count($checks) / 2 ? 'ok' : 'poor') ?>"><?= $passed ?>/<?= count($checks) ?></span></div>
            <div class="card__body">
                <ul class="seo-checks">
                    <?php foreach ($checks as [$label, $ok, $tip]): ?>
                        <li class="<?= $ok ? 'is-ok' : 'is-todo' ?>"><?= icon($ok ? 'check' : 'info', '', 16) ?><span><strong><?= e($label) ?></strong><?php if (!$ok): ?><small><?= e($tip) ?></small><?php endif; ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <?php $noKeyword = (int) db_value("SELECT COUNT(*) FROM products WHERE is_active = 1 AND (focus_keyword IS NULL OR focus_keyword = '')"); ?>
                <?php if ($noKeyword > 0): ?>
                    <form method="post" class="note-box" style="margin-top:14px" data-confirm="Give <?= $noKeyword ?> cakes a focus keyword like &quot;red velvet cake canberra&quot;? You can change each one later.">
                        <?= csrf_field() ?><input type="hidden" name="action" value="suggest_keywords">
                        <p style="margin:0 0 8px"><strong><?= $noKeyword ?> cakes have no focus keyword.</strong> Start with the cake name plus "canberra", then improve each cake from its checklist.</p>
                        <button class="btn btn--secondary btn--sm" type="submit">Suggest keywords</button>
                    </form>
                <?php endif; ?>
                <p class="label" style="margin:18px 0 8px">On launch day</p>
                <ol class="hint" style="margin:0;padding-left:18px;line-height:1.7">
                    <li>Keep the same address, www.continentalcakeshop.com.au, so Google keeps the rankings.</li>
                    <li>In Google Search Console, submit <code><?= e(url('sitemap.xml')) ?></code> and use URL Inspection on the homepage and main pages.</li>
                    <li>In your Google Business Profile, check the website link and add "Order online" pointing to <?= e(url('shop')) ?>.</li>
                    <li>Open the old addresses below to see each one land on the right page.</li>
                    <li>For the first weeks, check "Addresses not found" here and redirect anything that shows up.</li>
                </ol>
            </div>
        </section>

        <section class="card">
            <div class="card__head"><h2>Addresses not found</h2><span class="hint">Last 90 days</span></div>
            <?php if (!$missing): ?>
                <div class="empty"><?= icon('check', '', 32) ?><p>Nothing yet. When someone opens an address that does not exist, it shows here so you can send it to the right page.</p></div>
            <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Address</th><th>Times</th><th>Send to</th><th class="num"></th></tr></thead>
                <tbody>
                <?php foreach ($missing as $m): ?>
                    <tr>
                        <td><code><?= e($m['path']) ?></code><div class="muted">Last <?= e(time_ago($m['last_seen'])) ?><?= $m['referrer'] ? ' &middot; from ' . e(parse_url($m['referrer'], PHP_URL_HOST) ?: $m['referrer']) : '' ?></div></td>
                        <td><?= (int) $m['hits'] ?></td>
                        <td>
                            <form method="post" class="input-action"><?= csrf_field() ?><input type="hidden" name="action" value="redirect"><input type="hidden" name="from" value="<?= e($m['path']) ?>">
                                <select class="input" name="to" required>
                                    <option value="">Choose a page</option>
                                    <?php foreach ($targets as [$path, $label]): ?><option value="/<?= e($path) ?>"><?= e($label) ?></option><?php endforeach; ?>
                                </select>
                                <button class="btn btn--primary btn--sm" type="submit">Redirect</button>
                            </form>
                        </td>
                        <td class="num"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="ignore"><input type="hidden" name="path" value="<?= e($m['path']) ?>"><button class="link-btn" type="submit">Ignore</button></form></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </section>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card__head"><h2>Old website addresses</h2><span class="hint"><?= count(array_filter($oldPages, static fn ($p) => $p['ok'])) ?>/<?= count($oldPages) ?> OK</span></div>
            <div class="card__body">
                <table class="money-table">
                    <?php foreach ($oldPages as $p): ?>
                        <tr><td><a href="<?= e(url(ltrim($p['from'], '/'))) ?>" target="_blank"><?= e($p['from']) ?></a><div class="muted"><?= e(str_replace(rtrim(url(), '/'), '', (string) $p['to']) ?: '/') ?></div></td><td><?= $p['ok'] ? status_badge('active', 'OK') : status_badge('cancelled', 'Broken') ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </section>
        <section class="card">
            <div class="card__head"><h2>Your redirects</h2><span class="hint"><?= count($redirects) ?></span></div>
            <div class="card__body">
                <?php if (!$redirects): ?><p class="hint" style="margin:0">Added automatically when you change a cake, category or page address, or from the list on the left.</p><?php endif; ?>
                <table class="money-table">
                    <?php foreach ($redirects as $line): [$from, $to] = array_pad(preg_split('/\s+/', $line), 2, ''); ?>
                        <tr><td><?= e($from) ?><div class="muted">&rarr; <?= e($to) ?></div></td>
                            <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove_redirect"><input type="hidden" name="line" value="<?= e($line) ?>"><button class="link-btn link-btn--danger" type="submit">Remove</button></form></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </section>
    </div>
</div>
