<?php /** @var array $me @var array $errors */ ?>
<div class="page-head">
    <div><h1>My account</h1><p>Your login for the admin panel.</p></div>
</div>
<section class="card" style="max-width:640px">
    <form class="card__body form-stack" method="post">
        <?= csrf_field() ?>
        <label class="field"><span>Name</span><input class="input" name="name" value="<?= e($me['name']) ?>" required></label>
        <?= field_error($errors, 'name') ?>
        <label class="field"><span>Email <small>used to log in</small></span><input class="input" type="email" name="email" value="<?= e($me['email']) ?>" required></label>
        <?= field_error($errors, 'email') ?>
        <div class="form-grid">
            <label class="field"><span>New password <small>leave empty to keep</small></span><input class="input" type="password" name="new_password" minlength="10" autocomplete="new-password"></label>
            <label class="field"><span>Repeat new password</span><input class="input" type="password" name="confirm_password" autocomplete="new-password"></label>
        </div>
        <?= field_error($errors, 'new_password') ?><?= field_error($errors, 'confirm_password') ?>
        <label class="field" style="border-top:1px solid var(--line);padding-top:14px"><span>Current password <small>needed to save changes</small></span><input class="input" type="password" name="current_password" required autocomplete="current-password"></label>
        <?= field_error($errors, 'current_password') ?>
        <div><button class="btn btn--primary" type="submit"><?= icon('check', '', 18) ?> Save changes</button></div>
    </form>
</section>
