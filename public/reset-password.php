<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$token = input('token');
$reset = find_reset($token);

if ($reset && is_post()) {
    verify_csrf();
    $password = (string) ($_POST['password'] ?? '');
    if (mb_strlen($password) < PASSWORD_MIN_LENGTH) {
        fail_form(['password' => 'Please use at least ' . PASSWORD_MIN_LENGTH . ' characters.'], [], 'reset-password?token=' . $token);
    }
    db_run('UPDATE users SET password_hash = ?, failed_logins = 0, locked_until = NULL WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
    db_run('UPDATE password_resets SET used_at = NOW() WHERE id = ?', [$reset['id']]);
    login_user((int) $reset['user_id']);
    flash('success', 'Your password has been changed.');
    redirect('account');
}

$errors = take_form_errors();
$page = ['title' => 'Choose a New Password | ' . setting('store_short_name'), 'noindex' => true];
render('header', compact('page'));
?>

<section class="section auth-section">
    <div class="container container--narrow">
        <?php if (!$reset): ?>
            <div class="form-card auth-card auth-card--single">
                <h1 class="form-card__title">This link has expired</h1>
                <p class="form-card__lead">Reset links work once and only for <?= RESET_TOKEN_MINUTES ?> minutes. Please ask for a new one.</p>
                <a class="btn btn--primary btn--block" href="<?= e(url('forgot-password')) ?>">Send a New Link</a>
            </div>
        <?php else: ?>
            <form class="form-card auth-card auth-card--single" method="post" action="<?= e(url('reset-password') . '?token=' . $token) ?>" novalidate>
                <?= csrf_field() ?>
                <h1 class="form-card__title">Choose a new password</h1>
                <div class="field">
                    <label class="field-label" for="password">New password</label>
                    <input class="input" id="password" name="password" type="password" autocomplete="new-password" minlength="<?= PASSWORD_MIN_LENGTH ?>" required autofocus <?= invalid_attr($errors, 'password') ?>>
                    <?= field_error($errors, 'password') ?: '<p class="field-help">At least ' . PASSWORD_MIN_LENGTH . ' characters.</p>' ?>
                </div>
                <button class="btn btn--primary btn--lg btn--block" type="submit">Save Password</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php render('footer'); ?>
