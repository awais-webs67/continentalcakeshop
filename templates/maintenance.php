<?php
// The page customers see while maintenance mode is on (src/maintenance.php). It stands alone
// (its own small styles), so it shows correctly even while the rest of the website changes.
$shops = active_branches();
$message = trim(setting('maintenance_message'));
$message = $message !== '' ? $message : 'We are making a few improvements to our website and will be back very soon. You can still order: call or email us and we will take your order.';
$name = setting('store_short_name', setting('store_name'));
?><!DOCTYPE html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Back soon | <?= e(setting('store_name')) ?></title>
<link rel="icon" href="<?= e(asset_url_plain('img/logo.svg')) ?>" type="image/svg+xml">
<style>
    :root { --brand: #1E9B82; --brand-dark: #146B5A; --soft: #F1FCF9; --ink: #17191C; --text: #3D4146; --muted: #6B7076; --line: #E2E8E6; }
    * { box-sizing: border-box; }
    html, body { margin: 0; }
    body { min-height: 100vh; display: flex; flex-direction: column; font: 16px/1.6 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: var(--text); background: linear-gradient(180deg, var(--soft), #fff 60%); }
    main { flex: 1; width: 100%; max-width: 760px; margin: 0 auto; padding: 48px 16px 32px; text-align: center; }
    .logo { display: block; width: 96px; height: 96px; margin: 0 auto; }
    .badge { display: inline-flex; align-items: center; gap: 8px; margin: 18px 0 6px; padding: 6px 14px; border-radius: 99px; background: #fff; border: 1px solid var(--line); font-size: 14px; font-weight: 600; color: var(--brand-dark); }
    .badge::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: #E0A100; }
    h1 { margin: 10px 0 12px; font-size: clamp(1.7rem, 5vw, 2.4rem); line-height: 1.2; color: var(--ink); }
    .lead { max-width: 560px; margin: 0 auto 28px; font-size: 17px; overflow-wrap: anywhere; }
    .shops { display: grid; gap: 14px; text-align: left; }
    @media (min-width: 640px) { .shops--2 { grid-template-columns: 1fr 1fr; } }
    .shop { padding: 18px; border: 1px solid var(--line); border-radius: 16px; background: #fff; box-shadow: 0 6px 20px -10px rgba(16, 24, 22, .18); min-width: 0; }
    .shop h2 { margin: 0 0 4px; font-size: 18px; color: var(--ink); }
    .shop p { margin: 0 0 14px; font-size: 14px; color: var(--muted); overflow-wrap: anywhere; }
    .actions { display: grid; gap: 8px; }
    .btn { display: flex; align-items: center; justify-content: center; gap: 8px; min-height: 46px; padding: 10px 16px; border-radius: 99px; font-weight: 600; font-size: 15px; text-decoration: none; overflow-wrap: anywhere; text-align: center; }
    .btn--call { background: var(--brand); color: #fff; }
    .btn--call:hover { background: var(--brand-dark); }
    .btn--mail { border: 1.5px solid var(--ink); color: var(--ink); background: #fff; }
    .btn--mail:hover { border-color: var(--brand); color: var(--brand-dark); }
    .btn svg { flex: none; }
    .address { margin: 2px 0 0; text-align: center; font-size: 13px; color: var(--muted); overflow-wrap: anywhere; }
    .address a { color: inherit; }
    .hours { margin: 26px 0 0; font-size: 14px; color: var(--muted); }
    footer { padding: 18px 16px 24px; text-align: center; font-size: 13px; color: var(--muted); }
</style>
</head>
<body>
<main>
    <?php render('partials/logo'); ?>
    <div class="badge">Back very soon</div>
    <h1>Our website is having a quick update</h1>
    <p class="lead"><?= e($message) ?></p>
    <div class="shops <?= count($shops) > 1 ? 'shops--2' : '' ?>">
        <?php foreach ($shops as $shop): $phone = branch_phone($shop); $mail = branch_email_shown($shop); ?>
            <section class="shop">
                <h2><?= count($shops) > 1 ? e($shop['name']) . ' shop' : e($name) ?></h2>
                <p><?= e(branch_address_line($shop)) ?></p>
                <div class="actions">
                    <?php if ($phone !== ''): ?>
                        <a class="btn btn--call" href="tel:<?= e(phone_e164($phone)) ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg> Call <?= e($phone) ?></a>
                    <?php endif; ?>
                    <?php if ($mail !== ''): ?>
                        <a class="btn btn--mail" href="mailto:<?= e($mail) ?>?subject=<?= rawurlencode('Cake order') ?>" aria-label="Email us at <?= e($mail) ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg> Email us</a>
                        <p class="address"><a href="mailto:<?= e($mail) ?>"><?= str_replace('@', '<wbr>@', e($mail)) ?></a></p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
    <?php if (today_hours_label() !== ''): ?><p class="hours"><?= e(today_hours_label()) ?></p><?php endif; ?>
</main>
<footer>&copy; <?= date('Y') ?> <?= e(setting('store_name')) ?></footer>
</body>
</html>
