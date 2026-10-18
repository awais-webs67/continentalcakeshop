<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if (is_logged_in()) {
    redirect('account');
}

if (is_post()) {
    verify_csrf();
    $email = str_limit(input('email'), 190);
    [$user, $error] = attempt_login($email, (string) ($_POST['password'] ?? ''));
    if (!$user) {
        flash('error', $error);
        fail_form([], ['email' => $email], 'login');
    }
    login_user((int) $user['id']);
    forget_input();
    flash('success', 'Welcome back, ' . strtok($user['name'], ' ') . '!');
    redirect(take_after_login());
}

if (input('next') === 'checkout') {
    $_SESSION['after_login'] = '/checkout';
}

$page = ['title' => 'Log In | ' . setting('store_short_name'), 'noindex' => true];
render('header', compact('page'));
?>

<section class="section auth-section">
    <div class="container auth-layout">
        <form class="form-card auth-card" method="post" action="<?= e(url('login')) ?>" novalidate>
            <?= csrf_field() ?>
            <h1 class="form-card__title">Log in</h1>
            <p class="form-card__lead">Welcome back. Log in to see your orders.</p>
            <div class="field">
                <label class="field-label" for="email">Email</label>
                <input class="input" id="email" name="email" type="email" autocomplete="email" value="<?= e(old('email')) ?>" required autofocus>
            </div>
            <div class="field">
                <div class="field-label-row">
                    <label class="field-label" for="password">Password</label>
                    <a href="<?= e(url('forgot-password')) ?>">Forgot password?</a>
                </div>
                <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn--primary btn--lg btn--block" type="submit">Log In</button>
            <p class="auth-switch">New here? <a href="<?= e(url('register')) ?>">Create an account</a></p>
        </form>
        <?php render('partials/auth-aside'); ?>
    </div>
</section>

<?php forget_input(); render('footer'); ?>
