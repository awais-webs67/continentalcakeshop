<?php /** @var array $page @var array $errors */ ?>
<a class="crumb" href="<?= e(admin_url('pages')) ?>"><?= icon('arrow', '', 14) ?> Policy pages</a>
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
                    <label class="field"><span>Page title</span><input class="input" name="meta_title" maxlength="160" value="<?= e((string) $page['meta_title']) ?>"></label>
                    <label class="field"><span>Description</span><textarea class="input" name="meta_description" rows="3" maxlength="255"><?= e((string) $page['meta_description']) ?></textarea></label>
                </div>
            </section>
        </div>
    </div>
</form>
