<?php /** @var array $admins @var array $admin */
$multi = count(branches()) > 1;
const ACCOUNT_TYPES = [
    'owner'   => ['Owner', 'Every shop and every page, including branches, staff, settings and system check.'],
    'manager' => ['Branch manager', 'The whole admin panel for one shop: its orders, pickups, requests, messages, sales, cakes and My shop.'],
    'staff'   => ['Staff', 'Only the pages you tick, for one shop or every shop.'],
];
// The account type, branch and pages fields, used in the add form and in each account's Manage panel.
$accessFields = static function (string $type, ?int $branchId, array $pages) use ($multi): void { ?>
    <label class="field"><span>Account type</span>
        <select class="input" name="type" data-access-type>
            <?php foreach (ACCOUNT_TYPES as $key => [$label]): if ($key === 'manager' && !$multi) continue; ?>
                <option value="<?= e($key) ?>" data-help="<?= e(ACCOUNT_TYPES[$key][1]) ?>" <?= selected($type === $key) ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select></label>
    <p class="hint" style="margin:0" data-access-help><?= e(ACCOUNT_TYPES[$type][1]) ?></p>
    <?php if ($multi): ?>
        <label class="field" data-access-branch <?= $type === 'owner' ? 'hidden' : '' ?>><span>Shop</span>
            <select class="input" name="branch_id">
                <option value="0" data-every>Every shop</option>
                <?php foreach (branches() as $b): ?><option value="<?= (int) $b['id'] ?>" <?= selected($branchId === (int) $b['id']) ?>><?= e($b['name']) ?></option><?php endforeach; ?>
            </select></label>
    <?php endif; ?>
    <fieldset class="access-pages" data-access-pages <?= $type === 'staff' ? '' : 'hidden' ?>>
        <legend class="label">Pages they can open</legend>
        <?php foreach (ADMIN_SECTIONS as $key => [$label]): ?>
            <label class="inline-check"><input type="checkbox" name="permissions[]" value="<?= e($key) ?>" <?= checked(in_array($key, $pages, true)) ?>> <?= e($label) ?></label>
        <?php endforeach; ?>
    </fieldset>
<?php };
?>
<div class="page-head">
    <div><h1>Staff accounts</h1><p>Everyone who can log in to this admin panel. <?= $multi ? 'A branch manager or staff login for one shop only ever sees that shop.' : 'Choose what each staff member can open.' ?></p></div>
</div>
<?php if (!empty($sharedEmails)): ?>
    <div class="alert alert--warning">
        <strong>Same email used twice.</strong>
        <?php foreach ($sharedEmails as $row): ?>
            <?= e($row['email']) ?> is a staff login and also a <a href="<?= e(admin_url('customers/view', ['id' => $row['user_id']])) ?>">customer account</a>.
        <?php endforeach; ?>
        Staff and customers need different emails. Give the staff login a different email in My account (or ask the customer to use another email).
    </div>
<?php endif; ?>
<div class="grid-main">
    <section class="card">
        <div class="table-wrap"><table class="table staff-table">
            <thead><tr><th>Name</th><th>Access</th><th>Last login</th><th class="num">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($admins as $a): $self = (int) $a['id'] === (int) $admin['id']; $type = admin_account_type($a); ?>
                <tr>
                    <td><strong><?= e($a['name']) ?></strong><?= $self ? ' <span class="muted">(you)</span>' : '' ?><div class="muted"><?= e($a['email']) ?></div></td>
                    <td>
                        <div class="staff-access">
                            <?= status_badge($type === 'owner' ? 'owner' : ($type === 'manager' ? 'active' : 'staff'), ACCOUNT_TYPES[$type][0]) ?>
                            <?php if ($multi): ?><span class="branch-tag"><?= icon('pin', '', 12) ?> <?= $type !== 'owner' && $a['branch_id'] ? e(branch_name((int) $a['branch_id'])) : 'Every shop' ?></span><?php endif; ?>
                            <?= $a['is_active'] ? '' : status_badge('inactive', 'Disabled') ?>
                        </div>
                        <?php if ($type === 'staff'): ?><div class="muted staff-access__pages"><?= e(implode(', ', array_map(static fn ($k) => ADMIN_SECTIONS[$k][0], admin_permissions($a))) ?: 'Dashboard only') ?></div><?php endif; ?>
                    </td>
                    <td class="muted"><?= $a['last_login_at'] ? e(time_ago($a['last_login_at'])) : 'Never' ?></td>
                    <td class="num">
                        <?php if ($self): ?>
                            <a class="link-btn" href="<?= e(admin_url('account')) ?>">My account</a>
                        <?php else: ?>
                            <details class="row-menu">
                                <summary class="link-btn">Manage</summary>
                                <div class="row-menu__panel row-menu__panel--wide">
                                    <form method="post" class="form-stack" style="gap:10px" data-access-form>
                                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="action" value="access">
                                        <?php $accessFields($type, $a['branch_id'] ? (int) $a['branch_id'] : null, admin_permissions($a)); ?>
                                        <button class="btn btn--secondary btn--sm" type="submit">Save access</button>
                                    </form>
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
    <section class="card" id="add-staff">
        <div class="card__head"><h2>Add a login</h2></div>
        <form class="card__body form-stack" method="post" data-access-form>
            <?= csrf_field() ?><input type="hidden" name="action" value="create">
            <label class="field"><span>Name</span><input class="input" name="name" required></label>
            <label class="field"><span>Email</span><input class="input" type="email" name="email" required autocomplete="off"></label>
            <label class="field"><span>Password <small>at least 10 characters</small></span><input class="input" type="password" name="password" minlength="10" required autocomplete="new-password"></label>
            <?php $accessFields($multi ? 'manager' : 'staff', null, ['orders', 'custom', 'messages', 'customers']); ?>
            <p class="hint" style="margin:0">They log in at <?= e(admin_url('login')) ?>. Give them the password in person.</p>
            <button class="btn btn--primary" type="submit"><?= icon('plus', '', 18) ?> Add login</button>
        </form>
    </section>
</div>
