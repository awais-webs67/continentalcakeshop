<?php /** @var string $token @var string $email @var ?string $error */ ?>
<h1 class="auth-box__title">Choose a new password</h1>
<p class="auth-box__lead">For <?= e($email) ?></p>
<?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= e(admin_url('reset-password')) ?>" class="form-stack">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <label class="field"><span>New password <small>at least 10 characters</small></span><input class="input" type="password" name="password" minlength="10" autocomplete="new-password" required autofocus></label>
    <label class="field"><span>Repeat new password</span><input class="input" type="password" name="confirm" autocomplete="new-password" required></label>
    <button class="btn btn--primary btn--block" type="submit">Save new password</button>
</form>
