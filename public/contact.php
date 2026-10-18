<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if (is_post()) {
    verify_csrf();
    $data = [
        'name'    => str_limit(input('name'), 120),
        'email'   => str_limit(input('email'), 190),
        'phone'   => str_limit(input('phone'), 40),
        'message' => str_limit(input('message'), 3000),
    ];
    $errors = [];
    if ($data['name'] === '') {
        $errors['name'] = 'Please enter your name.';
    }
    if (!valid_email($data['email'])) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (mb_strlen($data['message']) < 5) {
        $errors['message'] = 'Please enter your message.';
    }
    // Honeypot field: real visitors never see or fill it.
    if (input('website') !== '') {
        redirect('contact');
    }

    if ($errors) {
        remember_input($data);
        $_SESSION['form_errors'] = $errors;
        flash('error', 'Please check the highlighted fields.');
        redirect('contact');
    }

    db_run('INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)',
        [$data['name'], $data['email'], $data['phone'] ?: null, $data['message']]);
    $notify = setting('order_notification_email');
    if ($notify !== '') {
        send_mail($notify, 'Website message from ' . $data['name'],
            "Name: {$data['name']}\nEmail: {$data['email']}\nPhone: {$data['phone']}\n\n{$data['message']}", $data['email']);
    }
    forget_input();
    flash('success', 'Thanks for your message. We will get back to you soon.');
    redirect('contact');
}

$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);
$fieldError = static fn (string $f): string => isset($errors[$f]) ? '<p class="field-error" id="' . e($f) . '-error">' . e($errors[$f]) . '</p>' : '';
$invalid = static fn (string $f): string => isset($errors[$f]) ? 'aria-invalid="true" aria-describedby="' . e($f) . '-error"' : '';

$quickFaqs = array_slice(get_faqs(), 0, 4);
$crumbs = [['Home', url()], ['Contact', url('contact')]];
$page = [
    'title'       => 'Contact Us | ' . setting('store_name'),
    'description' => 'Visit The Continental Cake Shop at ' . store_address_line() . ' or call ' . setting('store_phone') . '. Opening hours, directions and contact form.',
    'canonical'   => url('contact'),
    'schema'      => [breadcrumb_schema($crumbs)],
];
render('header', compact('page'));
?>

<section class="page-hero">
    <div class="container">
        <?php render('partials/breadcrumbs', compact('crumbs')); ?>
        <h1 class="page-title">Contact us</h1>
        <p class="page-lead">Questions about an order, a custom cake or dietary needs? Call, email or send us a message.</p>
    </div>
</section>

<section class="section section--compact">
    <div class="container contact-layout">
        <div class="contact-details">
            <a class="contact-tile" href="tel:<?= e(setting('store_phone_e164')) ?>"><?= icon('phone', '', 24) ?><span><strong>Call</strong><?= e(setting('store_phone')) ?></span></a>
            <a class="contact-tile" href="mailto:<?= e(setting('store_email')) ?>"><?= icon('mail', '', 24) ?><span><strong>Email</strong><?= e(setting('store_email')) ?></span></a>
            <a class="contact-tile" href="<?= e(store_maps_url()) ?>" target="_blank" rel="noopener"><?= icon('pin', '', 24) ?><span><strong>Visit</strong><?= e(store_address_line()) ?></span></a>
            <?php if ($quickFaqs): ?>
                <div class="info-card">
                    <h2>Quick answers</h2>
                    <?php foreach ($quickFaqs as $faq): ?>
                        <details class="faq-item faq-item--compact">
                            <summary><?= e($faq['question']) ?> <?= icon('plus', 'faq-item__icon', 16) ?></summary>
                            <div class="faq-item__answer prose"><?= render_content($faq['answer']) ?></div>
                        </details>
                    <?php endforeach; ?>
                    <a class="link-arrow info-card__more" href="<?= e(url('faq')) ?>">All questions <?= icon('arrow', '', 16) ?></a>
                </div>
            <?php endif; ?>
            <div class="info-card">
                <h2>Opening hours</h2>
                <ul class="footer-hours footer-hours--dark">
                    <?php foreach (opening_hours_grouped() as $row): ?>
                        <li><span><?= e($row['days']) ?></span><span><?= e($row['hours']) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <form class="form-card" method="post" action="<?= e(url('contact')) ?>" novalidate>
            <?= csrf_field() ?>
            <h2 class="form-card__title">Send a message</h2>
            <div class="hp-field" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>
            <div class="field">
                <label class="field-label" for="name">Full name</label>
                <input class="input" id="name" name="name" type="text" autocomplete="name" value="<?= e(old('name')) ?>" required <?= $invalid('name') ?>>
                <?= $fieldError('name') ?>
            </div>
            <div class="field-row">
                <div class="field">
                    <label class="field-label" for="email">Email</label>
                    <input class="input" id="email" name="email" type="email" autocomplete="email" value="<?= e(old('email')) ?>" required <?= $invalid('email') ?>>
                    <?= $fieldError('email') ?>
                </div>
                <div class="field">
                    <label class="field-label" for="phone">Phone <span class="field-hint">Optional</span></label>
                    <input class="input" id="phone" name="phone" type="tel" autocomplete="tel" value="<?= e(old('phone')) ?>">
                </div>
            </div>
            <div class="field">
                <label class="field-label" for="message">Message</label>
                <textarea class="input" id="message" name="message" rows="5" required <?= $invalid('message') ?>><?= e(old('message')) ?></textarea>
                <?= $fieldError('message') ?>
            </div>
            <button class="btn btn--primary btn--lg" type="submit">Send Message <?= icon('arrow', '', 18) ?></button>
        </form>
    </div>
    <div class="container">
        <div class="visit__map visit__map--wide">
            <iframe title="Map showing <?= e(setting('store_name')) ?>" src="https://www.google.com/maps?q=<?= e(rawurlencode(setting('store_name') . ', ' . store_address_line())) ?>&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
</section>

<?php render('footer'); ?>
