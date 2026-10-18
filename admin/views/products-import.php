<?php /** @var ?array $plans @var array $problems @var int $total */ ?>
<a class="crumb" href="<?= e(admin_url('products')) ?>"><?= icon('arrow', '', 14) ?> Products</a>
<div class="page-head">
    <div><h1>Import products</h1><p>Add many cakes at once, or update prices, from an Excel or Google Sheets file saved as CSV.</p></div>
</div>

<?php if ($plans === null): ?>
<div class="steps-row">
    <section class="card step-card">
        <span class="step-card__num">1</span>
        <h2>Download the template</h2>
        <p class="hint">Open it in Excel or Google Sheets. Each row is one cake, with up to three sizes.</p>
        <div class="step-card__actions">
            <a class="btn btn--primary" href="<?= e(admin_url('products/import', ['download' => 'template'])) ?>"><?= icon('download', '', 18) ?> Empty template</a>
            <a class="btn btn--secondary" href="<?= e(admin_url('products/import', ['download' => 'current'])) ?>"><?= icon('download', '', 18) ?> Current products</a>
        </div>
        <p class="hint">Use "Current products" to change many prices at once: edit them and upload the file again.</p>
    </section>
    <section class="card step-card">
        <span class="step-card__num">2</span>
        <h2>Upload the file</h2>
        <form class="form-stack" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input class="input" type="file" name="csv" accept=".csv,text/csv" required>
            <label class="inline-check"><input type="checkbox" name="create_categories" value="1"> Create categories that do not exist yet</label>
            <label class="inline-check"><input type="checkbox" name="replace_images" value="1"> Replace existing photos when image_url is filled in</label>
            <button class="btn btn--primary" type="submit"><?= icon('upload', '', 18) ?> Check the file</button>
        </form>
        <p class="hint">Save from Excel with File &gt; Save As &gt; "CSV UTF-8". Nothing is saved until you confirm on the next screen.</p>
    </section>
</div>
<section class="card" style="margin-top:16px">
    <div class="card__head"><h2>Columns</h2><span class="hint">Cakes with the same web address (slug) as an existing cake are updated, others are added</span></div>
    <div class="table-wrap"><table class="table table--stack">
        <thead><tr><th>Column</th><th>What to put in it</th></tr></thead>
        <tbody>
        <?php foreach (IMPORT_COLUMNS as $key => $help): ?>
            <tr><td data-label="Column"><code><?= e($key) ?></code></td><td data-label="Meaning" class="muted"><?= e($help) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>

<?php else: ?>
<section class="card">
    <div class="card__head"><h2>Preview</h2><span class="hint"><?= $total ?> rows read</span></div>
    <div class="card__body">
        <?php $new = count(array_filter($plans, static fn ($p) => $p['action'] === 'create')); ?>
        <div class="stats" style="margin-bottom:0">
            <div class="stat"><span class="stat__label"><?= icon('plus', '', 16) ?> New cakes</span><span class="stat__value"><?= $new ?></span></div>
            <div class="stat"><span class="stat__label"><?= icon('pen', '', 16) ?> Updates</span><span class="stat__value"><?= count($plans) - $new ?></span></div>
            <div class="stat <?= $problems ? 'stat--alert' : '' ?>"><span class="stat__label"><?= icon('info', '', 16) ?> Rows with problems</span><span class="stat__value"><?= count($problems) ?></span><span class="stat__note"><?= $problems ? 'These are skipped' : 'All good' ?></span></div>
        </div>
    </div>
    <?php if ($problems): ?>
        <div class="card__body" style="border-top:1px solid var(--line)">
            <h3 class="label">Fix these rows and upload again, or import the rest now</h3>
            <ul class="check-list">
                <?php foreach ($problems as $line => $p): ?><li><span><strong>Row <?= (int) $line ?>: <?= e($p['name']) ?></strong><br><span class="hint"><?= e(implode(' ', $p['errors'])) ?></span></span><?= status_badge('cancelled', 'Skipped') ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($plans): ?>
    <div class="table-wrap"><table class="table table--stack">
        <thead><tr><th>Cake</th><th>Category</th><th>Sizes</th><th>Visible</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($plans as $p): ?>
            <tr>
                <td data-label="Cake"><strong><?= e($p['data']['name']) ?></strong><div class="muted">/cakes/<?= e($p['data']['slug']) ?></div></td>
                <td data-label="Category"><?= e($p['category']) ?></td>
                <td data-label="Sizes"><?= $p['data']['is_enquiry'] ? 'Price on request' : e(implode(', ', array_map(static fn ($s) => $s['label'] . ' ' . money($s['price']), $p['sizes']))) ?></td>
                <td data-label="Visible"><?= $p['data']['is_active'] ? 'Yes' : 'No' ?></td>
                <td data-label="Action"><?= $p['action'] === 'create' ? status_badge('active', 'New') : status_badge('confirmed', 'Update') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <form class="form-actions" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="import">
        <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> Import <?= count($plans) ?> product<?= count($plans) === 1 ? '' : 's' ?></button>
        <a class="btn btn--ghost" href="<?= e(admin_url('products/import')) ?>">Start again</a>
    </form>
    <?php endif; ?>
</section>
<?php endif; ?>
