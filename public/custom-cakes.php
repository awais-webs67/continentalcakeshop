<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

const OCCASIONS = ['Birthday', 'Kids birthday', 'Wedding', 'Engagement', 'Baby shower', 'Gender reveal', 'Anniversary', 'Corporate event', 'Other'];

$leadDays = (int) setting('custom_lead_days', '3');
$minDate = (new DateTimeImmutable('today'))->modify("+{$leadDays} days")->format('Y-m-d');

if (is_post()) {
    verify_csrf();
    $data = [
        'name'       => str_limit(input('name'), 120),
        'email'      => str_limit(input('email'), 190),
        'phone'      => str_limit(input('phone'), 40),
        'occasion'   => input('occasion'),
        'event_date' => input('event_date'),
        'servings'   => str_limit(input('servings'), 40),
        'flavour'    => str_limit(input('flavour'), 120),
        'budget'     => str_limit(input('budget'), 40),
        'details'    => str_limit(input('details'), 3000),
    ];

    $errors = [];
    if ($data['name'] === '') {
        $errors['name'] = 'Please enter your name.';
    }
    if (!valid_email($data['email'])) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (!valid_au_phone($data['phone'])) {
        $errors['phone'] = 'Please enter an Australian phone number.';
    }
    if (!in_array($data['occasion'], OCCASIONS, true)) {
        $errors['occasion'] = 'Please choose the occasion.';
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['event_date']);
    if (!$date || $date->format('Y-m-d') < $minDate) {
        $errors['event_date'] = "Please choose a date at least {$leadDays} days from today.";
    }
    if (mb_strlen($data['details']) < 10) {
        $errors['details'] = 'Please tell us a little about the cake you would like.';
    }

    $image = null;
    if (!$errors && has_upload('reference_image')) {
        [$image, $uploadError] = store_private_upload('reference_image', 'custom', UPLOAD_IMAGE_TYPES);
        if ($uploadError) {
            $errors['reference_image'] = $uploadError;
        }
    }

    if ($errors) {
        remember_input($data);
        $_SESSION['form_errors'] = $errors;
        flash('error', 'Please check the highlighted fields.');
        redirect('custom-cakes#quote');
    }

    db_run(
        'INSERT INTO custom_requests (name, email, phone, occasion, event_date, servings, flavour, budget, details, reference_image)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$data['name'], $data['email'], $data['phone'], $data['occasion'], $data['event_date'],
         $data['servings'] ?: null, $data['flavour'] ?: null, $data['budget'] ?: null, $data['details'], $image]
    );

    $notify = setting('order_notification_email');
    if ($notify !== '') {
        send_mail(
            $notify,
            'Custom cake request: ' . $data['occasion'] . ' on ' . $data['event_date'],
            "Name: {$data['name']}\nPhone: {$data['phone']}\nEmail: {$data['email']}\nOccasion: {$data['occasion']}\n"
            . "Date: {$data['event_date']}\nServings: {$data['servings']}\nFlavour: {$data['flavour']}\nBudget: {$data['budget']}\n\n{$data['details']}"
            . ($image ? "\n\nA reference photo was uploaded." : ''),
            $data['email']
        );
    }

    forget_input();
    flash('success', 'Thank you! We have received your request and will contact you soon with a quote.');
    redirect('custom-cakes#quote');
}

$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);
$fieldError = static fn (string $f): string => isset($errors[$f]) ? '<p class="field-error" id="' . e($f) . '-error">' . e($errors[$f]) . '</p>' : '';
$invalid = static fn (string $f): string => isset($errors[$f]) ? 'aria-invalid="true" aria-describedby="' . e($f) . '-error"' : '';
$preset = old('occasion', input('occasion'));
const GALLERY_PER_PAGE = 12;
$galleryTotal = count_gallery();
$galleryPages = max(1, (int) ceil($galleryTotal / GALLERY_PER_PAGE));
$galleryPage = min($galleryPages, max(1, (int) input('page', '1')));
$gallery = get_gallery(GALLERY_PER_PAGE, ($galleryPage - 1) * GALLERY_PER_PAGE);

$crumbs = [['Home', url()], ['Custom Cakes', url('custom-cakes')]];
$page = [
    'title'       => 'Custom Cakes Canberra | Birthday, Wedding & Baby Shower Cakes',
    'description' => 'Custom birthday, wedding, engagement and baby shower cakes made in Greenway, Canberra. Send us your idea or a photo and get a quote. 100% Halal.',
    'canonical'   => url('custom-cakes'),
    'schema'      => [breadcrumb_schema($crumbs)],
];
render('header', compact('page'));
?>

<section class="page-hero">
    <div class="container">
        <?php render('partials/breadcrumbs', compact('crumbs')); ?>
        <h1 class="page-title">Custom cakes</h1>
        <p class="page-lead">Birthdays, weddings, engagements, baby showers and gender reveals. Show us a photo or describe your idea and we will make it, with your choice of size, shape, filling and decorations.</p>
    </div>
</section>

<section class="section section--compact" id="quote">
    <div class="container custom-layout">
        <form class="form-card" method="post" action="<?= e(url('custom-cakes')) ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <h2 class="form-card__title">Request a quote</h2>
            <p class="form-card__lead">Fill in what you know. We will contact you to go over the details and confirm the price.</p>

            <div class="field-row">
                <div class="field">
                    <label class="field-label" for="name">Full name</label>
                    <input class="input" id="name" name="name" type="text" autocomplete="name" value="<?= e(old('name')) ?>" required <?= $invalid('name') ?>>
                    <?= $fieldError('name') ?>
                </div>
                <div class="field">
                    <label class="field-label" for="phone">Mobile number</label>
                    <input class="input" id="phone" name="phone" type="tel" autocomplete="tel" value="<?= e(old('phone')) ?>" required <?= $invalid('phone') ?>>
                    <?= $fieldError('phone') ?>
                </div>
            </div>
            <div class="field">
                <label class="field-label" for="email">Email</label>
                <input class="input" id="email" name="email" type="email" autocomplete="email" value="<?= e(old('email')) ?>" required <?= $invalid('email') ?>>
                <?= $fieldError('email') ?>
            </div>
            <div class="field-row">
                <div class="field">
                    <label class="field-label" for="occasion">Occasion</label>
                    <select class="input" id="occasion" name="occasion" required <?= $invalid('occasion') ?>>
                        <option value="">Choose one</option>
                        <?php foreach (OCCASIONS as $occasion): ?>
                            <option <?= strcasecmp($preset, $occasion) === 0 ? 'selected' : '' ?>><?= e($occasion) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $fieldError('occasion') ?>
                </div>
                <div class="field">
                    <label class="field-label" for="event_date">Date needed</label>
                    <input class="input" id="event_date" name="event_date" type="date" min="<?= e($minDate) ?>" value="<?= e(old('event_date')) ?>" required <?= $invalid('event_date') ?>>
                    <?= $fieldError('event_date') ?>
                </div>
            </div>
            <div class="field-row field-row--3">
                <div class="field">
                    <label class="field-label" for="servings">Number of guests</label>
                    <input class="input" id="servings" name="servings" type="text" inputmode="numeric" placeholder="e.g. 30" value="<?= e(old('servings')) ?>">
                </div>
                <div class="field">
                    <label class="field-label" for="flavour">Flavour</label>
                    <input class="input" id="flavour" name="flavour" type="text" placeholder="e.g. Red velvet" value="<?= e(old('flavour')) ?>">
                </div>
                <div class="field">
                    <label class="field-label" for="budget">Budget <span class="field-hint">Optional</span></label>
                    <input class="input" id="budget" name="budget" type="text" placeholder="e.g. $150" value="<?= e(old('budget')) ?>">
                </div>
            </div>
            <div class="field">
                <label class="field-label" for="details">Describe your cake</label>
                <textarea class="input" id="details" name="details" rows="5" placeholder="Theme, colours, number of tiers, writing on the cake, dietary needs (gluten-free, eggless, vegan)..." required <?= $invalid('details') ?>><?= e(old('details')) ?></textarea>
                <?= $fieldError('details') ?>
            </div>
            <div class="field">
                <label class="field-label" for="reference_image">Reference photo <span class="field-hint">Optional</span></label>
                <label class="file-drop" data-file-drop>
                    <input id="reference_image" name="reference_image" type="file" accept="image/jpeg,image/png,image/webp,image/heic" <?= $invalid('reference_image') ?>>
                    <?= icon('upload', '', 24) ?>
                    <span data-file-name>Tap to upload a photo of a cake you like (max 5 MB)</span>
                </label>
                <?= $fieldError('reference_image') ?>
            </div>
            <button class="btn btn--primary btn--lg" type="submit">Send Request <?= icon('arrow', '', 18) ?></button>
        </form>

        <aside class="custom-aside">
            <div class="info-card">
                <h2>How it works</h2>
                <ol class="steps steps--compact">
                    <li><span class="steps__num">1</span><div><strong>Send your idea</strong><span>Use the form, call us or visit the shop.</span></div></li>
                    <li><span class="steps__num">2</span><div><strong>We send a quote</strong><span>We reply with a price and options.</span></div></li>
                    <li><span class="steps__num">3</span><div><strong>Collect your cake</strong><span>From our Greenway shop on your chosen day.</span></div></li>
                </ol>
            </div>
            <div class="info-card">
                <h2>Good to know</h2>
                <ul class="tick-list">
                    <li><?= icon('check', '', 18) ?> Please order at least <?= $leadDays ?> days ahead</li>
                    <li><?= icon('check', '', 18) ?> 100% Halal ingredients</li>
                    <li><?= icon('check', '', 18) ?> Gluten-free, eggless and vegan options</li>
                    <li><?= icon('check', '', 18) ?> Edible images and custom shapes available</li>
                </ul>
            </div>
            <div class="info-card info-card--brand">
                <h2>Prefer to talk?</h2>
                <p>Call us and we will help you plan your cake.</p>
                <a class="btn btn--light btn--block" href="tel:<?= e(setting('store_phone_e164')) ?>"><?= icon('phone', '', 18) ?> <?= e(setting('store_phone')) ?></a>
                <?php if (setting('social_pinterest')): ?>
                    <a class="info-card__link" href="<?= e(setting('social_pinterest')) ?>" target="_blank" rel="noopener">See our cake gallery on Pinterest <?= icon('arrow', '', 16) ?></a>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</section>

<?php if ($gallery): ?>
<section class="section" id="gallery">
    <div class="container">
        <div class="section-head">
            <div>
                <h2 class="section-title">Cakes we have made</h2>
                <p class="section-sub">A few of the custom cakes we have baked for Canberra families.</p>
            </div>
            <?php if (setting('social_pinterest')): ?>
                <a class="link-arrow" href="<?= e(setting('social_pinterest')) ?>" target="_blank" rel="noopener">More on Pinterest <?= icon('arrow', '', 18) ?></a>
            <?php endif; ?>
        </div>
        <div class="gallery-grid">
            <?php foreach ($gallery as $photo): ?>
                <figure><img src="<?= e(product_image_url($photo['image'])) ?>" alt="<?= e($photo['caption']) ?>" width="720" height="960" loading="lazy" decoding="async"></figure>
            <?php endforeach; ?>
        </div>
        <?php if ($galleryPages > 1): ?>
            <nav class="pagination" aria-label="Gallery pages">
                <?php if ($galleryPage > 1): ?>
                    <a class="pagination__step" href="<?= e(url('custom-cakes') . '?page=' . ($galleryPage - 1) . '#gallery') ?>" rel="prev">Previous</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $galleryPages; $i++): ?>
                    <a class="pagination__page <?= $i === $galleryPage ? 'is-current' : '' ?>" href="<?= e(url('custom-cakes') . ($i > 1 ? '?page=' . $i : '') . '#gallery') ?>" <?= $i === $galleryPage ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($galleryPage < $galleryPages): ?>
                    <a class="pagination__step" href="<?= e(url('custom-cakes') . '?page=' . ($galleryPage + 1) . '#gallery') ?>" rel="next">Next</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php render('footer'); ?>
