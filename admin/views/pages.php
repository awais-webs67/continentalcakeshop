<?php /** @var array $pages */ ?>
<div class="page-head">
    <div><h1>Policy pages</h1><p>Terms, privacy, refunds and other information pages linked in the footer.</p></div>
</div>
<section class="card">
    <?php if (!$pages): ?>
        <div class="empty"><?= icon('info', '', 32) ?><p>No pages found. Run the database migrations to add them.</p></div>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Page</th><th>Status</th><th>Last updated</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($pages as $p): ?>
            <tr>
                <td><a class="row-link" href="<?= e(admin_url('pages/edit', ['id' => $p['id']])) ?>"><?= e($p['title']) ?></a><div class="muted">/<?= e($p['slug']) ?></div></td>
                <td><?= $p['is_active'] ? status_badge('active', 'Published') : status_badge('inactive', 'Hidden') ?></td>
                <td class="muted"><?= e(date('j M Y', strtotime($p['updated_at']))) ?></td>
                <td class="num"><a class="link-btn" href="<?= e(admin_url('pages/edit', ['id' => $p['id']])) ?>">Edit</a> &nbsp;<a class="link-btn" href="<?= e(url($p['slug'])) ?>" target="_blank">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>
