<?php
// AI SEO agent (owner only): the free AI services and their order, what the AI knows about the
// shop, writing SEO for many cakes at once, and sending the website to Google and Bing.
require_admin();

$tabs = ['ai' => 'AI services', 'knowledge' => 'Shop knowledge', 'bulk' => 'Many cakes', 'search' => 'Google & Bing'];
$tab = array_key_exists($_GET['tab'] ?? '', $tabs) ? $_GET['tab'] : 'ai';

if (is_post()) {
    $action = input('action');
    $list = ai_providers();
    $id = input('id');
    $index = array_search($id, array_column($list, 'id'), true);

    // Answers for the buttons that do not reload the page.
    if ($action === 'test') {
        @set_time_limit(90);
        $provider = ai_provider($id);
        json_out($provider ? ai_test($provider) : ['ok' => false, 'message' => 'Service not found.']);
    }
    if ($action === 'models') {
        // A saved service, or the one being added (its key is used for this request only).
        $provider = $id !== '' ? ai_provider($id) : null;
        if ($id === '') {
            $preset = AI_SERVICES[input('service')] ?? null;
            $address = rtrim(trim(input('base_url')) ?: (string) ($preset['url'] ?? ''), '/');
            if (!$preset || trim(input('api_key')) === '' || !preg_match('#^https://[^\s/]+\.[^\s]+$#', $address)) {
                json_out(['ok' => false, 'error' => 'Choose the service and paste its API key first' . (input('service') === 'custom' ? ', with its https address.' : '.')]);
            }
            $provider = ['url' => $address, 'key' => secret_encrypt(trim(input('api_key')))];
        }
        if (!$provider) {
            json_out(['ok' => false, 'error' => 'Service not found.']);
        }
        $raw = ai_models_raw($provider, $error);
        if (!$raw) {
            json_out(['ok' => false, 'error' => $error ?: 'This service did not list any models. Type the model name instead.']);
        }
        json_out(['ok' => true] + ai_model_choices($raw));
    }
    if ($action === 'profile_draft') {
        @set_time_limit(120);
        try {
            json_out(['ok' => true, 'text' => ai_profile_draft()]);
        } catch (Throwable $e) {
            json_out(['ok' => false, 'error' => $e->getMessage()], 502);
        }
    }

    if ($action === 'add_service') {
        $preset = AI_SERVICES[input('service')] ?? null;
        $key = trim(input('api_key'));
        $address = rtrim(trim(input('base_url')) ?: (string) ($preset['url'] ?? ''), '/');
        if (!$preset || $key === '') {
            flash('error', 'Choose a service and paste its API key.');
        } elseif (!preg_match('#^https://[^\s/]+\.[^\s]+$#', $address)) {
            flash('error', 'Type the service address (it starts with https:// and usually ends with /v1).');
        } else {
            $list[] = [
                'id' => bin2hex(random_bytes(4)), 'service' => input('service'),
                'name' => input('service') === 'custom' ? (str_limit(trim(input('name')), 40) ?: (string) parse_url($address, PHP_URL_HOST)) : $preset['name'],
                'url' => $address, 'model' => str_limit(trim(input('model')) ?: $preset['model'], 120), 'key' => secret_encrypt($key), 'enabled' => true,
            ];
            ai_providers_save($list);
            flash('success', $preset['name'] . ' added. Press Test to check it.');
        }
    } elseif ($index !== false && $action === 'update_service') {
        $list[$index]['model'] = str_limit(trim(input('model')), 120);
        if (trim(input('api_key')) !== '') {
            $list[$index]['key'] = secret_encrypt(trim(input('api_key')));
        }
        if ($list[$index]['service'] === 'custom' && preg_match('#^https://[^\s/]+\.[^\s]+$#', trim(input('base_url')))) {
            $list[$index]['url'] = rtrim(trim(input('base_url')), '/');
        }
        ai_providers_save($list);
        flash('success', $list[$index]['name'] . ' saved.');
    } elseif ($index !== false && $action === 'toggle') {
        $list[$index]['enabled'] = empty($list[$index]['enabled']);
        ai_providers_save($list);
    } elseif ($index !== false && $action === 'remove') {
        array_splice($list, $index, 1);
        ai_providers_save($list);
        flash('success', 'Service removed, with its key.');
    } elseif ($index !== false && in_array($action, ['up', 'down'], true)) {
        $other = $action === 'up' ? $index - 1 : $index + 1;
        if (isset($list[$other])) {
            [$list[$index], $list[$other]] = [$list[$other], $list[$index]];
            ai_providers_save($list);
        }
    } elseif ($action === 'save_profile') {
        setting_save('ai_shop_profile', str_limit(trim(input('ai_shop_profile')), 4000));
        flash('success', 'Saved. The AI uses it from the next cake it writes.');
    } elseif ($action === 'gsc_connect') {
        $file = $_FILES['gsc_key'] ?? null;
        $json = $file && ($file['error'] ?? 1) === UPLOAD_ERR_OK && $file['size'] < 20000 ? (string) file_get_contents($file['tmp_name']) : '';
        [$account, $error] = gsc_parse_account($json);
        if (!$account) {
            flash('error', $json === '' ? 'Choose the JSON key file you downloaded from Google Cloud.' : $error);
        } else {
            setting_save('gsc_service_account', secret_encrypt(json_encode($account, JSON_UNESCAPED_SLASHES)));
            setting_save('gsc_property', '');
            try {
                $property = gsc_property($account);
                flash('success', 'Connected to Google Search Console (' . $property . '). Press "Send everything now" to submit the sitemap.');
            } catch (Throwable $e) {
                flash('error', 'Key saved, but: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'gsc_property') {
        setting_save('gsc_property', str_limit(trim(input('gsc_property')), 190));
        flash('success', 'Search Console property saved.');
    } elseif ($action === 'gsc_remove') {
        setting_save('gsc_service_account', '');
        setting_save('gsc_property', '');
        flash('success', 'Google Search Console disconnected. Remove the service account from Search Console too if you no longer use it.');
    } elseif ($action === 'indexnow') {
        setting_save('indexnow_enabled', input('indexnow_enabled') === '1' ? '1' : '0');
        flash('success', 'Saved.');
    } elseif ($action === 'send_all') {
        @set_time_limit(90);
        [$ok, $report] = search_engines_sync(true);
        flash($ok ? 'success' : 'error', $report);
    }
    redirect(admin_url('ai-seo', ['tab' => $tab]));
}

$data = ['tabs' => $tabs, 'tab' => $tab, 'title' => 'AI SEO agent'];
if ($tab === 'bulk') {
    $data['products'] = db_all('SELECT p.*, c.name AS category_name, (SELECT COUNT(*) FROM product_images i WHERE i.product_id = p.id) AS photo_count
                                FROM products p JOIN categories c ON c.id = p.category_id ORDER BY p.is_active DESC, c.sort_order, p.sort_order, p.name');
}
if ($tab === 'search') {
    $data['gscStatus'] = gsc_account() ? gsc_sitemap_status() : null;
}
admin_view('ai-seo', $data);
