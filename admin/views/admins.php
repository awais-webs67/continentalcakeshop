<?php /** @var array $admins @var array $admin */ ?>
<div class="page-head">
    <div><h1>Staff accounts</h1><p>People who can log in to this admin panel. Choose what each staff member can open. Only owners can change staff accounts and run the system check.</p></div>
</div>
<?php if (!empty($sharedEmails)): ?>
    <div class="alert alert--warning">
        <strong>Same email used twice.</strong>
        <?php foreach ($sharedEmails as $row): ?>
            <?= e($row['email']) ?> is a staff login and also a <a href="<?= e(admin_url('customers/view', ['id' => $row['user_id']])) ?>">customer account</a>.
        <?php endforeach; ?>
        Staff and customers now need different emails. Give the staff login a different email in My account (or ask the customer to use another email) so it is always clear which account is which.
    </div>
<?php endif; ?>
<div class="grid-main">
    <section class="card">
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Name</th><th>Role</th><th>Last login</th><th class="num">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($admins as $a): $self = (int) $a['id'] === (int) $admin['id']; ?>
                <tr>
                    <td><strong><?= e($a['name']) ?></strong><?= $self ? ' <span class="muted">(you)</span>' : '' ?><div class="muted"><?= e($a['email']) ?></div></td>
                    <td><?= status_badge($a['role'], ADMIN_ROLES[$a['role']]) ?><?= $a['is_active'] ? '' : ' ' . status_badge('inactive', 'Disabled') ?>
                        <div class="muted"><?= $a['role'] === 'owner' ? 'Everything' : e(implode(', ', array_map(static fn ($k) => ADMIN_SECTIONS[$k][0], admin_permissions($a))) ?: 'Dashboard only') ?></div></td>
                    <td class="muted"><?= $a['last_login_at'] ? e(time_ago($a['last_login_at'])) : 'Never' ?></td>
                    <td class="num">
                        <?php if ($self): ?>
                            <a class="link-btn" href="<?= e(admin_url('account')) ?>">My account</a>
                        <?php else: ?>
                            <details class="row-menu">
                                <summary class="link-btn">Manage</summary>
                                <div class="row-menu__panel">
                                    <form method="post" class="form-stack" style="gap:8px">
                                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="action" value="role">
                                        <label class="field"><span>Role</span><select class="input" name="role" data-auto-submit><?php foreach (ADMIN_ROLES as $key => $label): ?><option value="<?= e($key) ?>" <?= selected($a['role'] === $key) ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
                                    </form>
                                    <?php if ($a['role'] === 'staff'): ?>
                                    <form method="post" class="form-stack" style="gap:6px">
                                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="action" value="permissions">
                                        <span class="label">Can open</span>
                                        <?php $allowed = admin_permissions($a); foreach (ADMIN_SECTIONS as $key => [$label]): ?>
                                            <label class="inline-check"><input type="checkbox" name="permissions[]" value="<?= e($key) ?>" <?= checked(in_array($key, $allowed, true)) ?>> <?= e($label) ?></label>
                                        <?php endforeach; ?>
                                        <button class="btn btn--secondary btn--sm" type="submit">Save access</button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="post" class="form-stack" style="gap:8px">
                                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="action" value="password">
                                        <label class="field"><span>New password</span><input class="input" type="password" name="password" minlength="10" autocomplete="new-password" required></label>
                                        <button class="btn btn--secondary btn--sm" type="submit">Set password</button>
                                    </form>
                                    <form method="post" <?= $a['is_active'] ? 'data-confirm="Stop ' . e($a['name']) . ' from logging in?"' : '' ?>>
                                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="action" value="toggle">
                                        <button class="btn btn--sm <?= $a['is_active'] ? 'btn--danger' : 'btn--success' ?>" type="submit"><?= $a['is_active'] ? 'Disable login' : 'Enable login' ?></button>
                                    </form>
                                </div>
                            </details>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </section>
    <section class="card">
        <div class="card__head"><h2>Add a staff member</h2></div>
        <form class="card__body form-stack" method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="create">
            <label class="field"><span>Name</span><input class="input" name="name" required></label>
            <label class="field"><span>Email</span><input class="input" type="email" name="email" required autocomplete="off"></label>
            <label class="field"><span>Password <small>at least 10 characters</small></span><input class="input" type="password" name="password" minlength="10" required autocomplete="new-password"></label>
            <label class="field"><span>Role</span><select class="input" name="role" data-role-select><?php foreach (ADMIN_ROLES as $key => $label): ?><option value="<?= e($key) ?>" <?= selected($key === 'staff') ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
            <fieldset class="field" data-staff-permissions style="border:0;padding:0;margin:0">
                <span class="label">Staff can open</span>
                <?php foreach (ADMIN_SECTIONS as $key => [$label]): ?>
                    <label class="inline-check"><input type="checkbox" name="permissions[]" value="<?= e($key) ?>" <?= checked(in_array($key, ['orders', 'custom', 'messages', 'customers'], true)) ?>> <?= e($label) ?></label>
                <?php endforeach; ?>
                <p class="hint">Owners can open everything, including staff accounts and the system check.</p>
            </fieldset>
            <button class="btn btn--primary" type="submit"><?= icon('plus', '', 18) ?> Add account</button>
        </form>
    </section>
</div>
