<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if (is_logged_in()) {
    redirect('account');
}

if (is_post()) {
    verify_csrf();
    if (is_spam_submission('register', 5)) {
        flash('error', 'Sorry, that did not go through. Please wait a moment and try again, or call us.');
        redirect('register');
    }
    $input = [
        'name'  => str_limit(input('name'), 120),
        'email' => str_limit(input('email'), 190),
        'phone' => str_limit(input('phone'), 40),
    ];
    [$userId, $errors] = register_user($input['name'], $input['email'], $input['phone'], (string) ($_POST['password'] ?? ''));
    if (!$userId) {
        fail_form($errors, $input, 'register');
    }
    login_user($userId);
    send_email_verification(db_one('SELECT * FROM users WHERE id = ?', [$userId]));
    forget_input();
    flash('success', 'Your account is ready. Your orders will now be saved here.');
    redirect(take_after_login());
}

if (input('next') === 'checkout') {
    $_SESSION['after_login'] = '/checkout';
}

$errors = take_form_errors();
$page = ['title' => 'Create an Account | ' . setting('store_short_name'), 'noindex' => true];
render('header', compact('page'));
?>

<section class="section auth-section">
    <div class="container auth-layout">
        <form class="form-card auth-card" method="post" action="<?= e(url('register')) ?>" novalidate>
            <?= csrf_field() ?>
            <?= spam_trap_fields() ?>
            <h1 class="form-card__title">Create an account</h1>
            <p class="form-card__lead">Save your order history and check out faster next time.</p>
            <div class="field">
                <label class="field-label" for="name">Full name</label>
                <input class="input" id="name" name="name" type="text" autocomplete="name" value="<?= e(old('name')) ?>" required <?= invalid_attr($errors, 'name') ?>>
                <?= field_error($errors, 'name') ?>
            </div>
            <div class="field">
                <label class="field-label" for="email">Email</label>
                <input class="input" id="email" name="email" type="email" autocomplete="email" value="<?= e(old('email')) ?>" required <?= invalid_attr($errors, 'email') ?>>
                <?= field_error($errors, 'email') ?>
            </div>
            <div class="field">
                <label class="field-label" for="phone">Mobile number <span class="field-hint">Optional</span></label>
                <input class="input" id="phone" name="phone" type="tel" autocomplete="tel" value="<?= e(old('phone')) ?>" <?= invalid_attr($errors, 'phone') ?>>
                <?= field_error($errors, 'phone') ?>
            </div>
            <div class="field">
                <label class="field-label" for="password">Password</label>
                <input class="input" id="password" name="password" type="password" autocomplete="new-password" minlength="<?= PASSWORD_MIN_LENGTH ?>" required <?= invalid_attr($errors, 'password') ?>>
                <?= field_error($errors, 'password') ?: '<p class="field-help">At least ' . PASSWORD_MIN_LENGTH . ' characters.</p>' ?>
            </div>
            <button class="btn btn--primary btn--lg btn--block" type="submit">Create Account</button>
            <p class="auth-switch">Already have an account? <a href="<?= e(url('login')) ?>">Log in</a></p>
        </form>
        <?php render('partials/auth-aside'); ?>
    </div>
</section>

<?php forget_input(); render('footer'); ?>
