<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if (is_post()) {
    verify_csrf();
    $email = str_limit(input('email'), 190);
    if (valid_email($email)) {
        send_password_reset($email);
    }
    flash('success', 'If that email has an account, we have sent a link to reset your password.');
    redirect('login');
}

$page = ['title' => 'Reset Password | ' . setting('store_short_name'), 'noindex' => true];
render('header', compact('page'));
?>

<section class="section auth-section">
    <div class="container container--narrow">
        <form class="form-card auth-card auth-card--single" method="post" action="<?= e(url('forgot-password')) ?>" novalidate>
            <?= csrf_field() ?>
            <h1 class="form-card__title">Forgot your password?</h1>
            <p class="form-card__lead">Enter your email and we will send you a link to choose a new one.</p>
            <div class="field">
                <label class="field-label" for="email">Email</label>
                <input class="input" id="email" name="email" type="email" autocomplete="email" required autofocus>
            </div>
            <button class="btn btn--primary btn--lg btn--block" type="submit">Send Reset Link</button>
            <p class="auth-switch"><a href="<?= e(url('login')) ?>">Back to log in</a></p>
        </form>
    </div>
</section>

<?php render('footer'); ?>
