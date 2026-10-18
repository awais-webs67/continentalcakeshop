<?php
require_admin();

const POPUP_FIELDS = ['popup_title' => 80, 'popup_text' => 400, 'popup_button_text' => 40, 'popup_button_url' => 255];

if (is_post()) {
    $values = [];
    foreach (POPUP_FIELDS as $key => $max) {
        $values[$key] = str_limit(trim(input($key)), $max);
    }
    $values['popup_enabled'] = input('popup_enabled') === '1' ? '1' : '0';
    $values['popup_pages'] = input('popup_pages') === 'home' ? 'home' : 'all';
    $values['popup_frequency'] = input('popup_frequency') === 'session' ? 'session' : 'once';
    $values['popup_delay_seconds'] = (string) min(60, max(0, (int) input('popup_delay_seconds')));
    $couponId = post_int('popup_coupon_id');
    $values['popup_coupon_id'] = $couponId && db_value('SELECT 1 FROM coupons WHERE id = ?', [$couponId]) ? (string) $couponId : '';

    $error = null;
    if ($values['popup_title'] === '') {
        $error = 'Please give the popup a title.';
    } elseif ($values['popup_enabled'] === '1' && $values['popup_coupon_id'] !== '' && !coupons_enabled()) {
        $error = 'Coupons are switched off, so the code would not work. Switch them on in Coupons first.';
    }
    [$image, $imageError] = $error ? [null, null] : save_public_image('popup_image', 'popup', 800, 450, 'popup');
    $error ??= $imageError;
    if ($error) {
        flash('error', $error);
        redirect(admin_url('popup'));
    }
    if ($image) {
        $values['popup_image'] = $image;
    } elseif (input('remove_image') === '1') {
        $values['popup_image'] = '';
    }
    if (isset($values['popup_image']) && str_starts_with(setting('popup_image'), 'popup/') && setting('popup_image') !== $values['popup_image']) {
        delete_public_image(setting('popup_image'));
    }
    // A new version makes visitors who closed the old popup see this one.
    $values['popup_version'] = (string) time();
    foreach ($values as $key => $value) {
        setting_save($key, $value);
    }
    flash('success', $values['popup_enabled'] === '1' ? 'Popup saved and live on the website.' : 'Popup saved. It is switched off.');
    redirect(admin_url('popup'));
}

$coupons = db_all('SELECT * FROM coupons ORDER BY is_active DESC, created_at DESC');
$selectedCoupon = setting('popup_coupon_id') !== '' ? db_one('SELECT * FROM coupons WHERE id = ?', [(int) setting('popup_coupon_id')]) : null;
admin_view('popup', compact('coupons', 'selectedCoupon') + ['title' => 'Promo popup']);
