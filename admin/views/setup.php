<h1 class="auth-box__title">Create the owner account</h1>
<p class="auth-box__lead">This page only appears once, before any admin account exists.</p>
<form method="post" action="<?= e(admin_url('setup')) ?>" class="form-stack">
    <?= csrf_field() ?>
    <label class="field"><span>Your name</span><input class="input" name="name" value="<?= e(input('name')) ?>" required></label>
    <?= field_error($errors, 'name') ?>
    <label class="field"><span>Email</span><input class="input" type="email" name="email" value="<?= e(input('email')) ?>" autocomplete="username" required></label>
    <?= field_error($errors, 'email') ?>
    <label class="field"><span>Password</span><input class="input" type="password" name="password" autocomplete="new-password" minlength="10" required></label>
    <?= field_error($errors, 'password') ?: '<p class="hint">At least 10 characters.</p>' ?>
    <button class="btn btn--primary btn--block" type="submit">Create account</button>
</form>
