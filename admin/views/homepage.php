<?php /** @var array $sections */
$v = static fn (string $key): string => e(setting($key));
$usp = array_pad(setting_list('home_usp_items'), 4, '');
$steps = array_pad(setting_list('home_custom_steps'), 3, ['', '']);
?>
<div class="page-head">
    <div><h1>Homepage</h1><p>Switch sections on or off, change their order with the arrows and edit the text in each one.</p></div>
    <div class="page-head__actions"><a class="btn btn--secondary" href="<?= e(url()) ?>" target="_blank"><?= icon('arrow', '', 18) ?> View homepage</a></div>
</div>

<form method="post" enctype="multipart/form-data" action="<?= e(admin_url('homepage')) ?>" data-sections-form>
    <?= csrf_field() ?>
    <input type="hidden" name="_open" value="" data-open-section>

    <section class="card">
        <div class="card__head"><h2>Top bar</h2><span class="hint">The thin strip above the menu on every page</span></div>
        <div class="card__body">
            <label class="field"><span>Text next to the delivery icon <small>empty = only hours and phone</small></span><input class="input" name="announcement_text" maxlength="200" value="<?= $v('announcement_text') ?>"></label>
        </div>
    </section>

    <div class="home-sections" data-sections>
    <?php foreach ($sections as $key => $enabled): [$label, $hint] = HOME_SECTIONS[$key]; ?>
        <details class="card home-section<?= $enabled ? '' : ' is-off' ?>" id="<?= e($key) ?>" data-section>
            <summary class="home-section__head">
                <input type="hidden" name="order[]" value="<?= e($key) ?>">
                <span class="home-section__move">
                    <button type="button" class="icon-btn" data-move="-1" aria-label="Move <?= e($label) ?> up">&#8593;</button>
                    <button type="button" class="icon-btn" data-move="1" aria-label="Move <?= e($label) ?> down">&#8595;</button>
                </span>
                <span class="home-section__title"><strong><?= e($label) ?></strong><small><?= e($hint) ?></small></span>
                <label class="switch" data-stop><input type="checkbox" name="on[<?= e($key) ?>]" value="1" <?= checked($enabled) ?> data-section-toggle><span class="switch__track"></span><span class="sr-only">Show <?= e($label) ?></span></label>
                <span class="home-section__edit">Edit <?= icon('chevron', '', 16) ?></span>
            </summary>
            <div class="card__body form-stack">
            <?php if ($key === 'hero'): ?>
                <div class="grid-main" style="gap:20px">
                    <div class="form-stack">
                        <label class="field"><span>Small line above the title</span><input class="input" name="hero_eyebrow" value="<?= $v('hero_eyebrow') ?>"></label>
                        <label class="field"><span>Main title</span><input class="input" name="hero_title" value="<?= $v('hero_title') ?>" required></label>
                        <label class="field"><span>Text under the title</span><textarea class="input" name="hero_subtitle" rows="3"><?= $v('hero_subtitle') ?></textarea></label>
                        <div class="form-grid">
                            <label class="field"><span>First button <small>empty = hide</small></span><input class="input" name="hero_btn1_text" value="<?= $v('hero_btn1_text') ?>"></label>
                            <label class="field"><span>First button link</span><input class="input" name="hero_btn1_url" value="<?= $v('hero_btn1_url') ?>" placeholder="shop"></label>
                            <label class="field"><span>Second button <small>empty = hide</small></span><input class="input" name="hero_btn2_text" value="<?= $v('hero_btn2_text') ?>"></label>
                            <label class="field"><span>Second button link</span><input class="input" name="hero_btn2_url" value="<?= $v('hero_btn2_url') ?>" placeholder="custom-cakes"></label>
                        </div>
                        <label class="switch"><input type="checkbox" name="hero_show_shop_card" value="1" <?= checked(setting_bool('hero_show_shop_card')) ?>><span class="switch__track"></span><span>Show the shop photo card with today's hours</span></label>
                    </div>
                    <div class="form-stack">
                        <span class="label">Main photo</span>
                        <img id="hero-preview" src="<?= e(product_image_url(setting('hero_image'))) ?>" alt="" style="width:100%;aspect-ratio:3/4;object-fit:cover;border-radius:12px;border:1px solid var(--line)">
                        <input class="input" type="file" name="hero_upload" accept="image/jpeg,image/png,image/webp" data-image-input="hero-preview">
                        <p class="hint">Portrait photos work best, or choose one in <a href="<?= e(admin_url('gallery')) ?>">Gallery</a>.</p>
                    </div>
                </div>
            <?php elseif ($key === 'usp'): ?>
                <div class="form-grid">
                    <?php foreach ($usp as $i => $item): ?>
                        <label class="field"><span>Point <?= $i + 1 ?></span><input class="input" name="usp_items[]" maxlength="80" value="<?= e((string) $item) ?>"></label>
                    <?php endforeach; ?>
                </div>
                <p class="hint">Empty points are not shown.</p>
            <?php elseif ($key === 'categories'): ?>
                <label class="field"><span>Heading</span><input class="input" name="home_categories_title" value="<?= $v('home_categories_title') ?>"></label>
                <label class="field"><span>Text</span><input class="input" name="home_categories_text" value="<?= $v('home_categories_text') ?>"></label>
                <p class="hint">The tiles come from <a href="<?= e(admin_url('categories')) ?>">Categories</a>.</p>
            <?php elseif ($key === 'featured'): ?>
                <label class="field"><span>Heading</span><input class="input" name="home_featured_title" value="<?= $v('home_featured_title') ?>"></label>
                <label class="field"><span>Text</span><input class="input" name="home_featured_text" value="<?= $v('home_featured_text') ?>"></label>
                <label class="field" style="max-width:220px"><span>How many cakes</span><input class="input" type="number" min="1" max="24" name="home_featured_count" value="<?= $v('home_featured_count') ?>"></label>
                <p class="hint">Choose the cakes with "Feature on the homepage" in <a href="<?= e(admin_url('products')) ?>">Products</a>.</p>
            <?php elseif ($key === 'custom'): ?>
                <label class="field"><span>Heading</span><input class="input" name="home_custom_title" value="<?= $v('home_custom_title') ?>"></label>
                <div class="field"><span class="label">Text</span><textarea class="input" name="home_custom_text" data-rich-editor="simple"><?= $v('home_custom_text') ?></textarea></div>
                <span class="label">Steps</span>
                <?php foreach ($steps as $i => $step): ?>
                    <div class="form-grid">
                        <label class="field"><span>Step <?= $i + 1 ?> title</span><input class="input" name="steps[<?= $i ?>][title]" value="<?= e((string) ($step[0] ?? '')) ?>"></label>
                        <label class="field"><span>Step <?= $i + 1 ?> text</span><input class="input" name="steps[<?= $i ?>][text]" value="<?= e((string) ($step[1] ?? '')) ?>"></label>
                    </div>
                <?php endforeach; ?>
                <label class="field" style="max-width:320px"><span>Button text</span><input class="input" name="home_custom_btn_text" value="<?= $v('home_custom_btn_text') ?>"></label>
                <p class="hint">The six photos come from the <a href="<?= e(admin_url('gallery')) ?>">Gallery</a>.</p>
            <?php elseif ($key === 'text'): ?>
                <label class="field"><span>Heading <small>optional</small></span><input class="input" name="home_text_title" value="<?= $v('home_text_title') ?>"></label>
                <div class="field"><span class="label">Text</span><textarea class="input" name="home_text_body" data-rich-editor><?= $v('home_text_body') ?></textarea></div>
                <label class="field" style="max-width:320px"><span>Background</span>
                    <select class="input" name="home_text_style">
                        <option value="tint" <?= selected(setting('home_text_style') !== 'plain') ?>>Light green</option>
                        <option value="plain" <?= selected(setting('home_text_style') === 'plain') ?>>White</option>
                    </select></label>
            <?php elseif ($key === 'reviews'): ?>
                <label class="field"><span>Heading</span><input class="input" name="home_reviews_title" value="<?= $v('home_reviews_title') ?>"></label>
                <label class="field"><span>Text</span><input class="input" name="home_reviews_text" value="<?= $v('home_reviews_text') ?>"></label>
                <div class="form-grid form-grid--3">
                    <label class="field"><span>Google rating</span><input class="input" name="google_rating" value="<?= $v('google_rating') ?>" placeholder="4.3"></label>
                    <label class="field"><span>Number of reviews</span><input class="input" name="google_review_count" value="<?= $v('google_review_count') ?>" placeholder="290+"></label>
                    <label class="field"><span>Google reviews link</span><input class="input" type="url" name="google_reviews_url" value="<?= $v('google_reviews_url') ?>"></label>
                </div>
                <p class="hint">The review cards come from <a href="<?= e(admin_url('reviews')) ?>">Reviews</a>.</p>
            <?php elseif ($key === 'visit'): ?>
                <label class="field"><span>Heading</span><input class="input" name="home_visit_title" value="<?= $v('home_visit_title') ?>"></label>
                <p class="hint">The address and opening hours come from <a href="<?= e(admin_url('settings')) ?>">Settings</a>.</p>
            <?php endif; ?>
            </div>
        </details>
    <?php endforeach; ?>
    </div>

    <div class="card" style="margin-top:16px"><div class="form-actions form-actions--sticky">
        <button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> Save homepage</button>
        <span class="hint">Sections that are switched off are kept, so you can turn them back on later.</span>
    </div></div>
</form>
