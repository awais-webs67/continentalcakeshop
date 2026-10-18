<h1 class="auth-box__title">Admin log in</h1>
<p class="auth-box__lead">Manage orders, cakes and your website.</p>
<form method="post" action="<?= e(admin_url('login')) ?>" class="form-stack">
    <?= csrf_field() ?>
    <label class="field"><span>Email</span><input class="input" type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="username" required autofocus></label>
    <label class="field"><span>Password</span><input class="input" type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn btn--primary btn--block" type="submit">Log in</button>
</form>
<p class="auth-box__alt"><a href="<?= e(admin_url('forgot-password')) ?>">Forgot your password?</a></p>
<?php forget_input(); ?>
