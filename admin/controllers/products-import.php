<?php
require_admin();

// Step 1: download the template (or current products), fill it in Excel, upload it.
// Step 2: check the preview. Step 3: import.

$download = (string) ($_GET['download'] ?? '');
if ($download === 'template' || $download === 'current') {
    $blank = array_fill_keys(array_keys(IMPORT_COLUMNS), '');
    $rows = $download === 'current' ? products_as_import_rows() : [
        array_merge($blank, ['name' => 'Chocolate Ganache Torte', 'category' => 'Tortes', 'short_description' => 'Chocolate sponge with rich ganache.',
            'description' => 'Layers of chocolate sponge and ganache, finished with chocolate curls.', 'size1_name' => 'Midi', 'size1_serves' => 'Serves 8',
            'size1_price' => '44.95', 'size2_name' => 'Full', 'size2_serves' => 'Serves 14', 'size2_price' => '54.95', 'visible' => 'yes', 'featured' => 'no',
            'price_on_request' => 'no', 'allow_message' => 'yes', 'sort_order' => '20', 'focus_keyword' => 'chocolate ganache cake canberra',
            'image_alt' => 'Chocolate ganache torte with chocolate curls', 'options' => 'Eggless +5.00; Extra ganache +3.50']),
        array_merge($blank, ['name' => 'Wedding Showpiece Cake', 'category' => 'Tea Cakes & Desserts', 'short_description' => 'Made to order for weddings.',
            'visible' => 'yes', 'featured' => 'no', 'price_on_request' => 'yes', 'allow_message' => 'no', 'badge' => 'Special', 'notice_hours' => '72',
            'sort_order' => '30', 'focus_keyword' => 'wedding cakes canberra']),
    ];
    $rows = array_map(static fn ($r) => array_merge($blank, $r), $rows);
    send_csv($download === 'current' ? 'continental-cakes-products-' . date('Y-m-d') . '.csv' : 'product-import-template.csv', array_keys(IMPORT_COLUMNS), array_map('array_values', $rows));
}

$categories = array_change_key_case(array_column(db_all('SELECT id, LOWER(name) AS k FROM categories'), 'id', 'k'));
foreach (db_all('SELECT id, slug FROM categories') as $c) {
    $categories[strtolower($c['slug'])] ??= (int) $c['id'];
}

if (is_post()) {
    if (input('action') === 'import' && !empty($_SESSION['product_import'])) {
        $import = $_SESSION['product_import'];
        unset($_SESSION['product_import']);
        $done = ['create' => 0, 'update' => 0];
        $notes = [];
        foreach ($import['plans'] as $i => $plan) {
            try {
                $note = apply_import_row($plan, $import['replace_images'], $categories);
                $done[$plan['action']]++;
                if ($note !== '') {
                    $notes[] = 'Row ' . ($i + 2) . ': ' . $note;
                }
            } catch (Throwable $e) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }
                $notes[] = 'Row ' . ($i + 2) . ' (' . $plan['data']['name'] . '): ' . $e->getMessage();
            }
        }
        flash('success', "Import finished: {$done['create']} new and {$done['update']} updated products.");
        if ($notes) {
            flash('error', implode(' ', array_slice($notes, 0, 8)) . (count($notes) > 8 ? ' And ' . (count($notes) - 8) . ' more.' : ''));
        }
        redirect(admin_url('products'));
    }

    // Upload: read and check every row, then show the preview.
    $file = $_FILES['csv'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        flash('error', 'Please choose the CSV file to upload.');
        redirect(admin_url('products/import'));
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        flash('error', 'The file is larger than 2 MB. Please split it into smaller files.');
        redirect(admin_url('products/import'));
    }
    [$rows, $error] = read_import_csv($file['tmp_name']);
    if ($error) {
        flash('error', $error);
        redirect(admin_url('products/import'));
    }
    $plans = [];
    $problems = [];
    $slugs = [];
    foreach ($rows as $i => $row) {
        [$plan, $errors] = plan_import_row($row, $categories, input('create_categories') === '1');
        if (isset($slugs[$plan['data']['slug']])) {
            $errors[] = 'Same web address as row ' . $slugs[$plan['data']['slug']] . '.';
        }
        $slugs[$plan['data']['slug']] = $i + 2;
        if ($errors) {
            $problems[$i + 2] = ['name' => $plan['data']['name'] ?: '(no name)', 'errors' => $errors];
        } else {
            $plans[$i] = $plan;
        }
    }
    $_SESSION['product_import'] = ['plans' => $plans, 'replace_images' => input('replace_images') === '1'];
    admin_view('products-import', ['title' => 'Import products', 'plans' => $plans, 'problems' => $problems, 'total' => count($rows)]);
}

unset($_SESSION['product_import']);
admin_view('products-import', ['title' => 'Import products', 'plans' => null, 'problems' => [], 'total' => 0]);
