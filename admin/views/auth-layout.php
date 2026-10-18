<?php /** @var string $content @var string $title */ ?>
<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Admin') ?> | <?= e(setting('store_short_name')) ?></title>
<link rel="icon" href="<?= e(asset('img/logo.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin admin--auth">
<main class="auth-shell">
    <div class="auth-box">
        <img class="auth-box__logo" src="<?= e(asset('img/logo.svg')) ?>" alt="<?= e(setting('store_name')) ?>" width="84" height="84">
        <?php foreach (take_flashes() as $flash): ?>
            <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>
        <?= $content ?>
    </div>
    <a class="auth-shell__back" href="<?= e(url()) ?>">Back to the website</a>
</main>
</body>
</html>
