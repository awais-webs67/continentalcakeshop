<?php /** @var array $page @var array $errors */ ?>
<?php $landing = ($page['type'] ?? 'policy') === 'landing'; ?>
<a class="crumb" href="<?= e(admin_url('pages')) ?>"><?= icon('arrow', '', 14) ?> Pages</a>
<div class="page-head">
    <div><h1><?= e($page['title']) ?></h1><p><a href="<?= e(url($page['slug'])) ?>" target="_blank">View on website</a> &middot; last updated <?= e(date('j M Y', strtotime($page['updated_at']))) ?></p></div>
</div>
<?php if ($errors): ?><div class="alert alert--error">Please fix the highlighted fields.</div><?php endif; ?>
<form method="post" action="<?= e(admin_url('pages/edit', ['id' => $page['id']])) ?>">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $page['id'] ?>">
    <div class="grid-main">
        <div class="stack">
            <section class="card">
                <div class="card__head"><h2>Content</h2></div>
                <div class="card__body form-stack">
                    <label class="field"><span>Title</span><input class="input" name="title" value="<?= e($page['title']) ?>" required></label>
                    <?= field_error($errors, 'title') ?>
                    <label class="field"><span>Intro line <small>shown under the title</small></span><input class="input" name="summary" maxlength="255" value="<?= e((string) $page['summary']) ?>"></label>
                    <div class="field">
                        <span>Page text</span>
                        <textarea class="input" name="body" rows="22" data-rich-editor><?= e($page['body']) ?></textarea>
                        <?= field_error($errors, 'body') ?>
                    </div>
                </div>
            </section>
        </div>
        <div class="stack">
            <section class="card">
                <div class="card__head"><h2>Publish</h2></div>
                <div class="card__body form-stack">
                    <label class="switch"><input type="checkbox" name="is_active" value="1" <?= checked((bool) $page['is_active']) ?>><span class="switch__track"></span><span>Show on the website</span></label>
                    <?php if ($landing): ?>
                        <label class="field"><span>Web address</span><input class="input" name="slug" maxlength="80" value="<?= e($page['slug']) ?>"></label>
                        <?= field_error($errors, 'slug') ?>
                        <label class="field"><span>"Get a quote" fills in</span>
                            <select class="input" name="occasion"><option value="">No occasion</option><?php foreach (CAKE_OCCASIONS as $o): ?><option value="<?= e($o) ?>" <?= selected(($page['occasion'] ?? '') === $o) ?>><?= e($o) ?></option><?php endforeach; ?></select></label>
                        <label class="switch"><input type="checkbox" name="show_gallery" value="1" <?= checked((bool) ($page['show_gallery'] ?? 1)) ?>><span class="switch__track"></span><span>Show cake photos from the gallery</span></label>
                        <label class="field"><span>Order in the footer</span><input class="input" type="number" name="sort_order" value="<?= (int) ($page['sort_order'] ?? 0) ?>"></label>
                    <?php endif; ?>
                    <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> Save page</button>
                </div>
            </section>
            <section class="card">
                <div class="card__head"><h2>Shortcuts</h2></div>
                <div class="card__body">
                    <p class="hint" style="margin-bottom:10px">Type these in the text and the website fills in the current details from Settings:</p>
                    <ul class="check-list token-list">
                        <?php foreach (['{store_name}' => 'Shop name', '{store_phone}' => 'Phone (clickable)', '{store_email}' => 'Email (clickable)', '{store_address}' => 'Shop address', '{min_lead_hours}' => 'Hours of notice', '{custom_lead_days}' => 'Days for custom cakes', '{url:contact}' => 'Link to a page'] as $token => $label): ?>
                            <li><code><?= e($token) ?></code><span class="muted"><?= e($label) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
            <section class="card">
                <div class="card__head"><h2>Search engines</h2></div>
                <div class="card__body form-stack">
                    <div class="serp">
                        <span class="serp__url"><?= e(url($page['slug'])) ?></span>
                        <span class="serp__title"><?= e((string) ($page['meta_title'] ?: $page['title'] . ' | ' . setting('store_short_name'))) ?></span>
                        <span class="serp__desc"><?= e((string) ($page['meta_description'] ?: $page['summary'])) ?></span>
                    </div>
                    <label class="field"><span>Page title <small>30 to 60 characters, start with the search words</small></span><input class="input" name="meta_title" maxlength="160" value="<?= e((string) $page['meta_title']) ?>" data-count></label>
                    <label class="field"><span>Description <small>120 to 160 characters</small></span><textarea class="input" name="meta_description" rows="3" maxlength="255" data-count><?= e((string) $page['meta_description']) ?></textarea></label>
                </div>
            </section>
        </div>
    </div>
</form>
<?php if ($landing): ?>
    <form method="post" style="margin-top:16px" data-confirm="Delete this page? Its address will show the not found page.">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $page['id'] ?>"><input type="hidden" name="action" value="delete">
        <button class="btn btn--danger btn--sm" type="submit"><?= icon('trash', '', 16) ?> Delete page</button>
    </form>
<?php endif; ?>
