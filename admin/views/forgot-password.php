<h1 class="auth-box__title">Forgot your password?</h1>
<p class="auth-box__lead">Enter your admin email and we will send you a link to choose a new password.</p>
<form method="post" action="<?= e(admin_url('forgot-password')) ?>" class="form-stack">
    <?= csrf_field() ?>
    <?= spam_trap_fields() ?>
    <label class="field"><span>Email</span><input class="input" type="email" name="email" autocomplete="username" required autofocus></label>
    <button class="btn btn--primary btn--block" type="submit">Send reset link</button>
</form>
<p class="auth-box__alt"><a href="<?= e(admin_url('login')) ?>">Back to log in</a></p>
