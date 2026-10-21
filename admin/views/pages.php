<?php /** @var array $pages */
$groups = ['landing' => [], 'policy' => []];
foreach ($pages as $p) {
    $groups[$p['type'] === 'landing' ? 'landing' : 'policy'][] = $p;
}
$table = static function (array $rows): void { ?>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Page</th><th>Status</th><th>Last updated</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $p): ?>
            <tr>
                <td><a class="row-link" href="<?= e(admin_url('pages/edit', ['id' => $p['id']])) ?>"><?= e($p['title']) ?></a><div class="muted">/<?= e($p['slug']) ?></div></td>
                <td><?= $p['is_active'] ? status_badge('active', 'Published') : status_badge('inactive', 'Hidden') ?></td>
                <td class="muted"><?= e(date('j M Y', strtotime($p['updated_at']))) ?></td>
                <td class="num"><a class="link-btn" href="<?= e(admin_url('pages/edit', ['id' => $p['id']])) ?>">Edit</a> &nbsp;<a class="link-btn" href="<?= e(url($p['slug'])) ?>" target="_blank">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php };
?>
<div class="page-head">
    <div><h1>Pages</h1><p>Landing pages bring visitors from Google searches such as "birthday cakes canberra". Policy pages are linked in the footer.</p></div>
</div>

<section class="card">
    <div class="card__head"><h2>Landing pages</h2><span class="hint">Shown in the footer and the sitemap</span></div>
    <?php if ($groups['landing']): ?><?php $table($groups['landing']); ?><?php else: ?><div class="empty"><p>No landing pages yet.</p></div><?php endif; ?>
    <form class="card__body form-grid" method="post" style="border-top:1px solid var(--line)">
        <?= csrf_field() ?><input type="hidden" name="action" value="create">
        <label class="field"><span>New page title</span><input class="input" name="title" maxlength="160" placeholder="e.g. Cupcakes in Canberra" required></label>
        <label class="field"><span>Web address <small>optional</small></span><input class="input" name="slug" maxlength="80" placeholder="e.g. cupcakes-canberra"></label>
        <div><button class="btn btn--primary btn--sm" type="submit"><?= icon('plus', '', 16) ?> Add a landing page</button></div>
    </form>
</section>

<section class="card" style="margin-top:16px">
    <div class="card__head"><h2>Policy pages</h2></div>
    <?php if ($groups['policy']): ?><?php $table($groups['policy']); ?><?php else: ?><div class="empty"><p>No pages found.</p></div><?php endif; ?>
</section>
