<?php
/**
 * One shop, in tabs: Overview, Shop details, Email, Payments, Website page, Logins.
 * $branch null = adding a new shop (only Shop details).
 *
 * @var ?array $branch @var string $tab @var array $stats @var array $admin
 */
$isNew = $branch === null;
$b = $branch ?? ['id' => 0, 'slug' => '', 'name' => '', 'street' => '', 'suburb' => '', 'state' => 'ACT', 'postcode' => '', 'phone' => '', 'email' => '', 'latitude' => '', 'longitude' => '', 'maps_url' => '', 'reviews_url' => '', 'intro' => '', 'photo' => null, 'is_main' => 0, 'is_active' => 0];
$id = (int) $b['id'];
$f = static fn (string $field, string $fallback = ''): string => e(old($field, (string) ($b[$field] ?? $fallback)));
// A shop's own setting, the typed value after an error, or a fallback.
$bs = static fn (string $key, string $fallback = ''): string => e(old($key, (string) ($id ? (branch_setting($id, $key) ?? $fallback) : $fallback)));
$tabUrl = static fn (string $t): string => admin_url('branches', ['id' => $id, 'tab' => $t]);
$multi = count(branches()) > 1;
?>
<a class="crumb" href="<?= e(admin_url('branches')) ?>"><?= icon('arrow', '', 14) ?> All branches</a>
<div class="page-head branch-head">
    <div>
        <h1><?= $isNew ? 'Add a branch' : e($b['name']) ?>
            <?php if (!$isNew): ?><?= $b['is_active'] ? status_badge('active', $b['is_main'] ? 'Main shop' : 'Live') : status_badge('inactive', 'Hidden') ?><?php endif; ?></h1>
        <p><?= $isNew ? 'Start with the shop\'s name and address. Email, payments and its page come next.' : e(branch_address_line($b)) ?></p>
    </div>
    <?php if (!$isNew): ?>
    <div class="page-head__actions">
        <form method="post" action="<?= e(admin_url('branch-view')) ?>"><?= csrf_field() ?><input type="hidden" name="branch" value="<?= $id ?>"><input type="hidden" name="_back" value="<?= e(admin_url('orders')) ?>"><button class="btn btn--secondary" type="submit"><?= icon('bag', '', 16) ?> Its orders</button></form>
        <?php if ($b['is_active']): ?><a class="btn btn--secondary" href="<?= e(branch_url($b)) ?>" target="_blank" rel="noopener">Its page <?= icon('arrow', '', 15) ?></a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (!$isNew): ?>
<nav class="tabs branch-tabs card" aria-label="<?= e($b['name']) ?> settings">
    <?php foreach (BRANCH_TABS as $key => $label): ?>
        <a href="<?= e($tabUrl($key)) ?>" class="<?= $tab === $key ? 'is-active' : '' ?>"<?= $tab === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>

<?php if ($tab === 'overview'): ?>
    <div class="branch-overview">
        <section class="card">
            <div class="card__head"><h2>Today and this month</h2></div>
            <div class="card__body">
                <div class="branch-card__stats branch-card__stats--flush">
                    <div><span>Due today</span><strong><?= (int) ($stats['due_today'] ?? 0) ?></strong></div>
                    <div><span>New orders</span><strong><?= (int) ($stats['new_orders'] ?? 0) ?></strong></div>
                    <div><span>Open requests</span><strong><?= (int) ($stats['requests'] ?? 0) ?></strong></div>
                    <div><span>This month</span><strong><?= e(money((float) ($stats['month_sales'] ?? 0))) ?></strong></div>
                </div>
            </div>
        </section>
        <section class="card">
            <div class="card__head"><h2>Set up</h2></div>
            <ul class="setup-list">
                <?php foreach (branch_checklist($b) as $row): [$label, $ok, $goTo] = $row; $note = $row[3] ?? ''; ?>
                    <li>
                        <span class="setup-list__mark <?= $ok ? 'is-ok' : 'is-todo' ?>"><?= icon($ok ? 'check' : 'info', '', 15) ?></span>
                        <span class="setup-list__text"><strong><?= e($label) ?></strong><?php if ($note !== ''): ?><small><?= e($note) ?></small><?php endif; ?></span>
                        <a class="link-btn" href="<?= e($tabUrl($goTo)) ?>"><?= $ok ? 'Change' : 'Set up' ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
    <?php if (!$b['is_main']): ?>
    <section class="card">
        <div class="card__body branch-visibility">
            <div><strong><?= $b['is_active'] ? 'Customers can choose this shop' : 'Hidden from customers' ?></strong><p class="hint" style="margin:0"><?= $b['is_active'] ? 'It shows at checkout, on the forms and has its page for Google.' : 'Switch it on when the address, phone and email are ready.' ?></p></div>
            <form method="post" <?= $b['is_active'] ? 'data-confirm="Hide ' . e($b['name']) . ' from customers? Its orders stay in the panel."' : '' ?>><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="_back" value="<?= e($tabUrl('overview')) ?>"><button class="btn btn--sm <?= $b['is_active'] ? 'btn--secondary' : 'btn--primary' ?>" type="submit"><?= $b['is_active'] ? 'Hide from customers' : 'Show to customers' ?></button></form>
        </div>
    </section>
    <?php endif; ?>

<?php elseif ($tab === 'details'): ?>
    <form method="post" class="branch-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="<?= $isNew ? 'create' : 'details' ?>"><input type="hidden" name="id" value="<?= $id ?>">
        <div class="branch-form__main">
            <section class="card">
                <div class="card__head"><h2>Name and address</h2></div>
                <div class="card__body form-stack">
                    <label class="field"><span>Shop name <small>what customers see, e.g. Wright</small></span><input class="input" name="name" maxlength="80" value="<?= $f('name') ?>" required></label>
                    <label class="field"><span>Street</span><input class="input" name="street" maxlength="120" value="<?= $f('street') ?>" placeholder="e.g. Shop 2, 10 Cooyong Crescent"></label>
                    <div class="form-grid form-grid--3">
                        <label class="field"><span>Suburb</span><input class="input" name="suburb" maxlength="80" value="<?= $f('suburb') ?>" required></label>
                        <label class="field"><span>State</span><input class="input" name="state" maxlength="10" value="<?= $f('state', 'ACT') ?>"></label>
                        <label class="field"><span>Postcode</span><input class="input" name="postcode" maxlength="10" inputmode="numeric" value="<?= $f('postcode') ?>"></label>
                    </div>
                </div>
            </section>
            <?php
            $publicEmail = old('public_email', (string) ($id ? branch_setting($id, 'public_email') : ''));
            $showEmail = old('show_email', $id ? (branch_setting($id, 'show_email') ?? '1') : '1') === '1';
            ?>
            <section class="card">
                <div class="card__head"><h2>Phone and email for customers</h2></div>
                <div class="card__body form-stack">
                    <p class="hint" style="margin:0">Shown for <?= $isNew ? 'this shop' : e($b['name']) ?> in the website footer, on the contact page and on its own page<?= $b['is_main'] ? ', and on invoices and emails' : ', invoices and its emails' ?>.</p>
                    <div class="form-grid">
                        <label class="field"><span>Phone</span><input class="input" type="tel" name="phone" maxlength="40" value="<?= $f('phone') ?>" placeholder="02 6100 0000"></label>
                        <label class="field"><span>Email</span><input class="input" type="email" name="public_email" maxlength="190" value="<?= e($publicEmail) ?>" placeholder="<?= e(($id ? branch_account_email($id) : '') ?: 'e.g. wright@continentalcakes.com.au') ?>"></label>
                    </div>
                    <label class="switch"><input type="checkbox" name="show_email" value="1" <?= checked($showEmail) ?>><span class="switch__track"></span><span><strong>Show the email on the website</strong><small>Switch off to show only the phone. Customers can still write through the contact form.</small></span></label>
                    <p class="hint" style="margin:0">Use the address of this shop's email account (Email tab), so customers' emails arrive where the shop reads them<?= $id ? ' and show in Messages when its inbox is on' : '' ?>.</p>
                </div>
            </section>
            <section class="card">
                <div class="card__head"><h2>On the map</h2></div>
                <div class="card__body form-stack">
                    <div class="form-grid">
                        <label class="field"><span>Latitude</span><input class="input" name="latitude" maxlength="12" inputmode="decimal" value="<?= $f('latitude') ?>" placeholder="-35.3222"></label>
                        <label class="field"><span>Longitude</span><input class="input" name="longitude" maxlength="12" inputmode="decimal" value="<?= $f('longitude') ?>" placeholder="149.0347"></label>
                    </div>
                    <p class="hint" style="margin:0">Tells Google exactly where the shop is. In Google Maps, right-click the shop and click the two numbers at the top to copy them.</p>
                    <label class="field"><span>Google Maps link <small>optional, from the shop's Google listing: Share &gt; Copy link</small></span><input class="input" type="url" name="maps_url" maxlength="500" value="<?= $f('maps_url') ?>" placeholder="https://maps.app.goo.gl/..."></label>
                    <label class="field" style="max-width:360px"><span>Its page address <small>/locations/...</small></span><input class="input" name="slug" maxlength="60" value="<?= $f('slug') ?>" placeholder="wright"></label>
                </div>
            </section>
        </div>
        <aside class="branch-form__side">
            <section class="card">
                <div class="card__body form-stack">
                    <?php if (!$b['is_main']): ?>
                        <label class="switch"><input type="checkbox" name="is_active" value="1" <?= checked(old('is_active', (string) $b['is_active']) === '1') ?>><span class="switch__track"></span><span><strong>Show to customers</strong><small>At checkout, on the forms and its page for Google. Needs the street and phone.</small></span></label>
                    <?php endif; ?>
                    <button class="btn btn--primary btn--block" type="submit"><?= icon('check', '', 18) ?> <?= $isNew ? 'Add branch' : 'Save' ?></button>
                </div>
            </section>
        </aside>
    </form>

<?php elseif ($tab === 'email'): ?>
    <?php
    $isMainShop = (bool) $b['is_main'];
    $main = main_branch();
    // The account's saved values: the main shop's in settings (or config.php before the first save), another shop's own.
    $mailKeys = ['mail_from_address' => 'from_address', 'mail_from_name' => 'from_name', 'smtp_host' => 'smtp.host', 'smtp_port' => 'smtp.port', 'smtp_encryption' => 'smtp.encryption'];
    $saved = static function (string $key) use ($isMainShop, $id, $mailKeys): string {
        if (!$isMainShop) {
            return (string) branch_setting($id, $key);
        }
        return isset($mailKeys[$key]) ? (string) with_branch(null, static fn () => mail_config($mailKeys[$key], '')) : setting_raw($key);
    };
    $acct = static fn (string $key, string $fallback = ''): string => e(old($key, $saved($key) !== '' ? $saved($key) : $fallback));
    if ($isMainShop && $saved('mail_provider') === '') {
        // Before the first save here: work out what is used now.
        $host = $saved('smtp_host');
        $guess = ['mail' => 'server', 'log' => 'log'][with_branch(null, static fn () => mail_config('driver', 'mail'))] ?? 'other';
        foreach (EMAIL_PROVIDERS as $key => $p) {
            if ($p[1] !== '' && strcasecmp($p[1], $host) === 0) {
                $guess = $key;
            }
        }
    }
    $provider = old('mail_provider', $saved('mail_provider') ?: ($guess ?? 'gmail'));
    $ownEmail = $isMainShop || old('email_mode', branch_uses_own($id, 'email') ? 'own' : 'main') === 'own';
    $hasPassword = $saved('smtp_password_encrypted') !== '' || ($isMainShop && (string) config('mail.smtp.password', '') !== '');
    $mainFrom = branch_account_email(main_branch_id());
    $defaultAlerts = $ownEmail && $saved('mail_from_address') !== '' ? $saved('mail_from_address') : branch_notify_email($id);
    $inboxOn = branch_setting($id, 'inbox_enabled') === '1';
    $inboxChecked = (string) branch_setting($id, 'inbox_checked_at');
    $inboxError = (string) branch_setting($id, 'inbox_error');
    $effective = static fn (string $key, string $default) => with_branch($id, static fn () => setting($key, $default));
    ?>
    <?php $spamProblems = array_filter(email_delivery_checks($id, false), static fn ($row) => !$row[1]); ?>
    <?php if ($spamProblems): ?>
        <div class="alert alert--warning spam-alert">
            <strong><?= icon('alert', '', 16) ?> Emails from <?= e($b['name']) ?> may land in spam</strong>
            <ul><?php foreach ($spamProblems as [, , $fix]): ?><li><?= e($fix) ?></li><?php endforeach; ?></ul>
            <a href="<?= e(admin_url('system')) ?>">Full check in System check (also the domain's SPF, DKIM and DMARC)</a>
        </div>
    <?php endif; ?>
    <div class="branch-form">
        <form method="post" class="branch-form__main" id="branch-email-form" data-reveal-form>
            <?= csrf_field() ?><input type="hidden" name="action" value="email"><input type="hidden" name="id" value="<?= $id ?>">

            <section class="card">
                <div class="card__head"><h2>Email account</h2></div>
                <div class="card__body form-stack">
                    <p class="hint" style="margin:0">Every email of <?= e($b['name']) ?> is sent from this account: order confirmations, updates, quotes, reminders, review requests and replies from Messages. Customers' replies come back to it.<?= $isMainShop ? ' As the main shop, it also sends the emails that belong to no shop: password links and the owner\'s morning email.' : '' ?></p>
                    <?php if (!$isMainShop): ?>
                    <div class="choice-cards choice-cards--2">
                        <label class="choice-card"><input type="radio" name="email_mode" value="own" <?= checked($ownEmail) ?>><span><strong>Its own email account</strong><small>Gmail, Hostinger or another mailbox for <?= e($b['name']) ?></small></span></label>
                        <label class="choice-card"><input type="radio" name="email_mode" value="main" <?= checked(!$ownEmail) ?>><span><strong>Not yet: use <?= e($main['name'] ?? 'the main shop') ?>'s account</strong><small><?= e($mainFrom ?: 'Set it up in ' . ($main['name'] ?? 'the main shop') . ' > Email') ?>, signed "<?= e(setting_raw('store_short_name') . ' ' . $b['name']) ?>"</small></span></label>
                    </div>
                    <?php endif; ?>
                    <div class="form-stack own-account" <?= $isMainShop ? '' : 'data-show-if="email_mode:own"' ?> <?= $ownEmail ? '' : 'hidden' ?>>
                        <div class="form-grid">
                            <label class="field"><span>Email provider</span>
                                <select class="input" name="mail_provider" data-provider>
                                    <?php foreach (email_providers() as $key => $p): ?><option value="<?= e($key) ?>" data-help="<?= e($p[6]) ?>" <?= selected($provider === $key) ?>><?= e($p[0]) ?></option><?php endforeach; ?>
                                </select></label>
                            <label class="field"><span>Email address</span><input class="input" type="email" name="mail_from_address" value="<?= $acct('mail_from_address') ?>" placeholder="<?= e(strtolower($b['name'] ?: 'shop')) ?>@gmail.com" autocomplete="off"></label>
                        </div>
                        <div class="form-grid">
                            <label class="field" data-show-if="mail_provider:!server|log" <?= in_array($provider, ['server', 'log'], true) ? 'hidden' : '' ?>><span>Password <?= $hasPassword ? '<small>saved, leave empty to keep it</small>' : '' ?></span><input class="input" type="password" name="mail_password" autocomplete="new-password" placeholder="<?= $hasPassword ? '••••••••' : '' ?>"></label>
                            <label class="field"><span>Sender name <small>optional</small></span><input class="input" name="mail_from_name" maxlength="120" value="<?= $acct('mail_from_name') ?>" placeholder="<?= e(setting_raw('store_short_name') . ($multi ? ' ' . $b['name'] : '')) ?>"></label>
                        </div>
                        <p class="hint provider-help" data-provider-help style="margin:0"><?= e(email_providers()[$provider][6] ?? '') ?></p>
                        <div class="form-stack" data-show-if="mail_provider:other" <?= $provider === 'other' ? '' : 'hidden' ?>>
                            <div class="form-grid form-grid--3">
                                <label class="field"><span>Sending server (SMTP)</span><input class="input" name="smtp_host" value="<?= $acct('smtp_host') ?>" placeholder="mail.yourdomain.com.au"></label>
                                <label class="field"><span>Port</span><input class="input" type="number" name="smtp_port" value="<?= $acct('smtp_port', '465') ?>"></label>
                                <label class="field"><span>Security</span><select class="input" name="smtp_encryption"><?php $enc = $saved('smtp_encryption') ?: 'ssl'; foreach (['ssl' => 'SSL (465)', 'tls' => 'TLS (587)', 'none' => 'None'] as $k => $l): ?><option value="<?= $k ?>" <?= selected($enc === $k) ?>><?= $l ?></option><?php endforeach; ?></select></label>
                            </div>
                            <div class="form-grid">
                                <label class="field"><span>Inbox server (IMAP)</span><input class="input" name="imap_host" value="<?= $acct('imap_host') ?>" placeholder="mail.yourdomain.com.au"></label>
                                <label class="field"><span>Port</span><input class="input" type="number" name="imap_port" value="<?= $acct('imap_port', '993') ?>"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><h2>Inbox in Messages</h2></div>
                <div class="card__body form-stack">
                    <?php if (!$isMainShop): ?><p class="hint" data-show-if="email_mode:main" <?= $ownEmail ? 'hidden' : '' ?> style="margin:0">While <?= e($b['name']) ?> uses <?= e($main['name'] ?? 'the main shop') ?>'s account, emails to that account show under <?= e($main['name'] ?? 'the main shop') ?>. Give <?= e($b['name']) ?> its own account to have its own inbox here.</p><?php endif; ?>
                    <p class="hint" data-show-if="mail_provider:server|log" <?= in_array($provider, ['server', 'log'], true) && $ownEmail ? '' : 'hidden' ?> style="margin:0">The website server has no inbox. Choose Gmail, Hostinger or another email account to read its emails here.</p>
                    <div data-show-if="mail_provider:!server|log" <?= in_array($provider, ['server', 'log'], true) ? 'hidden' : '' ?>>
                    <label class="switch" <?= $isMainShop ? '' : 'data-show-if="email_mode:own"' ?> <?= $ownEmail ? '' : 'hidden' ?>><input type="checkbox" name="inbox_enabled" value="1" <?= checked($inboxOn) ?>><span class="switch__track"></span><span><strong>Show <?= e($b['name']) ?>'s emails in Messages</strong><small>New emails customers send to this account appear in Admin &gt; Messages for <?= e($b['name']) ?>, and you can answer them there. They stay in your email app too. Emails from before switching on are not copied.</small></span></label>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><h2>Order alerts</h2></div>
                <div class="card__body form-stack">
                    <label class="field"><span>Send new order alerts to <small>empty = <?= e($defaultAlerts ?: 'the account above') ?></small></span><input class="input" type="email" name="alerts_email" value="<?= $f('email') ?>" placeholder="<?= e($defaultAlerts) ?>"></label>
                    <p class="hint" style="margin:0">New orders, custom cake requests and messages for <?= e($b['name']) ?>. Usually leave it empty so alerts arrive in the shop's own inbox; type another address only if someone else should get them.</p>
                </div>
            </section>

            <section class="card">
                <div class="card__head"><h2>Automatic emails</h2></div>
                <div class="card__body form-stack">
                    <?php if ($multi): ?>
                        <label class="switch"><input type="checkbox" name="daily_branch_enabled" value="1" <?= checked(($id ? branch_setting($id, 'daily_branch_enabled') : null) !== '0') ?>><span class="switch__track"></span><span><strong>Morning plan for this shop</strong><small>Today's pickups and deliveries at <?= e($b['name']) ?>, before opening. The owner's plan for all shops is in Settings &gt; Emails.</small></span></label>
                        <label class="field" style="max-width:420px"><span>Send the morning plan to <small>empty = the order alerts address</small></span><input class="input" type="email" name="daily_branch_to" value="<?= $bs('daily_branch_to') ?>" placeholder="<?= e(branch_notify_email($id)) ?>"></label>
                    <?php endif; ?>
                    <label class="switch"><input type="checkbox" name="reminder_email_enabled" value="1" <?= checked($effective('reminder_email_enabled', '1') === '1') ?>><span class="switch__track"></span><span><strong>Reminder the day before</strong><small>Tells the customer the time and this shop's address the day before pickup or delivery</small></span></label>
                    <label class="switch"><input type="checkbox" name="review_email_enabled" value="1" <?= checked($effective('review_email_enabled', '1') === '1') ?>><span class="switch__track"></span><span><strong>Ask for a Google review</strong><small>After an order is completed, with this shop's review link. Each customer at most once every 4 months.</small></span></label>
                    <div class="form-grid">
                        <label class="field"><span>This shop's Google review link</span><input class="input" type="url" name="reviews_url" maxlength="500" value="<?= $f('reviews_url') ?>" placeholder="https://g.page/r/.../review"></label>
                        <label class="field"><span>Ask <small>hours after "Completed"</small></span><input class="input" type="number" min="1" max="240" name="review_delay_hours" value="<?= e((string) $effective('review_delay_hours', '20')) ?>"></label>
                    </div>
                </div>
            </section>
        </form>

        <aside class="branch-form__side">
            <section class="card">
                <div class="card__body form-stack">
                    <button class="btn btn--primary btn--block" type="submit" form="branch-email-form"><?= icon('check', '', 18) ?> Save email settings</button>
                </div>
            </section>
            <section class="card">
                <div class="card__head"><h2>Check it works</h2></div>
                <div class="card__body form-stack">
                    <form method="post" class="form-stack" style="gap:6px">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="test_email">
                        <button class="btn btn--secondary btn--block" type="submit"><?= icon('mail', '', 16) ?> Send a test email</button>
                        <p class="hint" style="margin:0">From <?= e(with_branch($id, static fn () => mail_from()[0]) ?: 'the account') ?> to <?= e(branch_notify_email($id) ?: 'the alerts address') ?>.</p>
                    </form>
                    <?php if ($inboxOn): ?>
                    <form method="post" class="form-stack" style="gap:6px">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="check_inbox">
                        <button class="btn btn--secondary btn--block" type="submit"><?= icon('mail', '', 16) ?> Check the inbox now</button>
                        <p class="hint" style="margin:0"><?= $inboxError !== '' ? status_badge('pending', 'Problem') . ' ' . e($inboxError) : ($inboxChecked !== '' ? 'Last checked ' . e(time_ago($inboxChecked)) . '. New emails are read every 15 minutes.' : 'Not checked yet. Save, then press the button.') ?></p>
                    </form>
                    <?php endif; ?>
                </div>
            </section>
        </aside>
    </div>

<?php elseif ($tab === 'payments'): ?>
    <?php
    $ownPay = old('payments_mode', branch_uses_own($id, 'payments') ? 'own' : 'shared') === 'own';
    $shared = array_map(static fn ($m) => $m['label'], available_payment_methods());
    $bsOn = static fn (string $key): bool => old($key, (string) (branch_setting($id, $key) ?? '0')) === '1';
    $hasStripe = (string) branch_setting($id, 'stripe_secret_key') !== '';
    $hasWebhook = (string) branch_setting($id, 'stripe_webhook_secret') !== '';
    ?>
    <form method="post" class="branch-form" data-reveal-form>
        <?= csrf_field() ?><input type="hidden" name="action" value="payments"><input type="hidden" name="id" value="<?= $id ?>">
        <div class="branch-form__main">
            <section class="card">
                <div class="card__head"><h2>Where customers pay</h2></div>
                <div class="card__body form-stack">
                    <div class="choice-cards choice-cards--2">
                        <label class="choice-card"><input type="radio" name="payments_mode" value="shared" <?= checked(!$ownPay) ?>><span><strong>Shared payment details</strong><small><?= $shared ? e(implode(', ', $shared)) . ', from Settings &gt; Payments' : 'Set in Settings > Payments' ?></small></span></label>
                        <label class="choice-card"><input type="radio" name="payments_mode" value="own" <?= checked($ownPay) ?>><span><strong>This shop's own details</strong><small>Its own PayID, bank account or Stripe account</small></span></label>
                    </div>
                    <p class="hint" style="margin:0">Customers of <?= e($b['name']) ?> see these details at checkout, in quotes and payment links. The names and notes of each way to pay stay the same as in Settings &gt; Payments.</p>
                </div>
            </section>
            <div class="form-stack" data-show-if="payments_mode:own" <?= $ownPay ? '' : 'hidden' ?> style="gap:20px">
                <section class="card">
                    <div class="card__head"><h2>PayID</h2><label class="switch"><input type="checkbox" name="payment_payid_enabled" value="1" <?= checked($bsOn('payment_payid_enabled')) ?>><span class="switch__track"></span><span class="sr-only">PayID on</span></label></div>
                    <div class="card__body form-grid">
                        <label class="field"><span>PayID <small>email, phone or ABN</small></span><input class="input" name="payid_identifier" value="<?= $bs('payid_identifier') ?>"></label>
                        <label class="field"><span>Account name</span><input class="input" name="payid_account_name" value="<?= $bs('payid_account_name') ?>"></label>
                    </div>
                </section>
                <section class="card">
                    <div class="card__head"><h2>Bank transfer</h2><label class="switch"><input type="checkbox" name="payment_bank_enabled" value="1" <?= checked($bsOn('payment_bank_enabled')) ?>><span class="switch__track"></span><span class="sr-only">Bank transfer on</span></label></div>
                    <div class="card__body form-grid form-grid--3">
                        <label class="field"><span>Account name</span><input class="input" name="bank_account_name" value="<?= $bs('bank_account_name') ?>"></label>
                        <label class="field"><span>BSB</span><input class="input" name="bank_bsb" value="<?= $bs('bank_bsb') ?>" inputmode="numeric" placeholder="000-000"></label>
                        <label class="field"><span>Account number</span><input class="input" name="bank_account_number" value="<?= $bs('bank_account_number') ?>" inputmode="numeric"></label>
                    </div>
                </section>
                <section class="card">
                    <div class="card__head"><h2>Card payments (Stripe)</h2><label class="switch"><input type="checkbox" name="payment_stripe_enabled" value="1" <?= checked($bsOn('payment_stripe_enabled')) ?>><span class="switch__track"></span><span class="sr-only">Card payments on</span></label></div>
                    <div class="card__body form-stack">
                        <label class="field"><span>Publishable key</span><input class="input" name="stripe_publishable_key" value="<?= $bs('stripe_publishable_key') ?>" placeholder="pk_live_..."></label>
                        <div class="form-grid">
                            <label class="field"><span>Secret key <?= $hasStripe ? '<small>saved, leave empty to keep it</small>' : '' ?></span><input class="input" type="password" name="stripe_secret_key" autocomplete="off" placeholder="<?= $hasStripe ? '••••••••' : 'sk_live_...' ?>"></label>
                            <label class="field"><span>Webhook signing secret <?= $hasWebhook ? '<small>saved</small>' : '' ?></span><input class="input" type="password" name="stripe_webhook_secret" autocomplete="off" placeholder="<?= $hasWebhook ? '••••••••' : 'whsec_...' ?>"></label>
                        </div>
                        <p class="hint" style="margin:0">In this shop's Stripe account add the webhook <strong><?= e(url('stripe-webhook')) ?></strong> (the same address as the shared one) and paste its signing secret here.</p>
                    </div>
                </section>
            </div>
        </div>
        <aside class="branch-form__side">
            <section class="card"><div class="card__body"><button class="btn btn--primary btn--block" type="submit"><?= icon('check', '', 18) ?> Save payments</button></div></section>
        </aside>
    </form>

<?php elseif ($tab === 'page'): ?>
    <form method="post" enctype="multipart/form-data" class="branch-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="page"><input type="hidden" name="id" value="<?= $id ?>">
        <div class="branch-form__main">
            <section class="card">
                <div class="card__head"><h2>About this shop</h2><span class="hint"><?= e(preg_replace('#^https?://#', '', branch_url($b))) ?></span></div>
                <div class="card__body form-stack">
                    <p class="hint" style="margin:0">Its own page is what Google shows for "cake shop <?= e($b['suburb']) ?>", and where its Google listing should link. A few lines about parking, nearby suburbs and what is special here help it rank.</p>
                    <div class="field"><span class="label">Text</span><textarea class="input" name="intro" data-rich-editor="simple"><?= $f('intro') ?></textarea></div>
                </div>
            </section>
            <section class="card">
                <div class="card__head"><h2>Photo of the shop front</h2><span class="hint">optional</span></div>
                <div class="card__body">
                    <div class="branch-photo">
                        <?php if (!empty($b['photo'])): ?><img src="<?= e(product_image_url($b['photo'])) ?>" alt="" width="160" height="120"><?php else: ?><span class="branch-photo__empty"><?= icon('store', '', 26) ?></span><?php endif; ?>
                        <div class="form-stack" style="gap:8px">
                            <input class="input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" aria-label="Photo of the shop front">
                            <p class="hint" style="margin:0">Shown next to the map on this shop's page, and used by Google for that page. Without one, the page shows the map only.</p>
                            <?php if (!empty($b['photo'])): ?><label class="inline-check"><input type="checkbox" name="remove_photo" value="1"> Remove this photo</label><?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <aside class="branch-form__side">
            <section class="card"><div class="card__body"><button class="btn btn--primary btn--block" type="submit"><?= icon('check', '', 18) ?> Save page</button></div></section>
        </aside>
    </form>

<?php elseif ($tab === 'logins'): ?>
    <?php $logins = $stats['logins'] ?? []; ?>
    <div class="grid-main">
        <section class="card">
            <div class="card__head"><h2>Logins for <?= e($b['name']) ?></h2><a class="link-btn" href="<?= e(admin_url('admins')) ?>">All staff accounts</a></div>
            <?php if (!$logins): ?>
                <div class="empty"><?= icon('user', '', 30) ?><p>No logins for this shop yet. Owners see every shop.</p></div>
            <?php else: ?>
                <ul class="staff-list">
                    <?php foreach ($logins as $l): $type = admin_account_type($l); ?>
                        <li class="staff-item<?= $l['is_active'] ? '' : ' is-off' ?>">
                            <div class="staff-item__row">
                                <span class="staff-item__avatar" style="background:<?= e(avatar_colour($l['name'])) ?>" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($l['name'], 0, 1))) ?></span>
                                <div class="staff-item__who">
                                    <strong><?= e($l['name']) ?></strong><span class="muted"><?= e($l['email']) ?></span>
                                    <div class="staff-access"><?= status_badge($type === 'manager' ? 'active' : 'staff', $type === 'manager' ? 'Branch manager' : 'Staff') ?><?= $l['is_active'] ? '' : status_badge('inactive', 'Disabled') ?><span class="muted staff-item__seen"><?= $l['last_login_at'] ? 'Last login ' . e(time_ago($l['last_login_at'])) : 'Never logged in' ?></span></div>
                                </div>
                                <a class="btn btn--secondary btn--sm staff-item__btn" href="<?= e(admin_url('admins')) ?>">Manage</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <section class="card">
            <div class="card__head"><h2>Add a login</h2></div>
            <form class="card__body form-stack" method="post" action="<?= e(admin_url('admins')) ?>">
                <?= csrf_field() ?><input type="hidden" name="action" value="create"><input type="hidden" name="branch_id" value="<?= $id ?>"><input type="hidden" name="_back" value="<?= e($tabUrl('logins')) ?>">
                <label class="field"><span>Name</span><input class="input" name="name" required></label>
                <label class="field"><span>Email <small>their own, to log in</small></span><input class="input" type="email" name="email" required autocomplete="off"></label>
                <label class="field"><span>Password <small>at least 10 characters</small></span><input class="input" type="password" name="password" minlength="10" required autocomplete="new-password"></label>
                <label class="field"><span>Type</span>
                    <select class="input" name="type">
                        <option value="manager">Branch manager: the whole panel for <?= e($b['name']) ?></option>
                        <option value="staff">Staff: orders, requests, messages and customers</option>
                    </select></label>
                <?php foreach (['orders', 'custom', 'messages', 'customers'] as $perm): ?><input type="hidden" name="permissions[]" value="<?= e($perm) ?>"><?php endforeach; ?>
                <p class="hint" style="margin:0">They only ever see <?= e($b['name']) ?>. They log in at <?= e(admin_url('login')) ?>.</p>
                <button class="btn btn--primary" type="submit"><?= icon('plus', '', 18) ?> Add login</button>
            </form>
        </section>
    </div>
<?php endif; ?>
