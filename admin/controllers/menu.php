<?php
require_admin();

if (is_post()) {
    if (input('action') === 'reset') {
        setting_save('nav_menu', '');
        flash('success', 'The menu is back to the standard one.');
        redirect(admin_url('menu'));
    }
    [$menu, $errors] = menu_from_rows(array_values((array) ($_POST['rows'] ?? [])));
    if ($errors) {
        flash('error', implode(' ', array_slice($errors, 0, 3)));
        $_SESSION['menu_draft'] = $menu ?: null;
        redirect(admin_url('menu'));
    }
    setting_save('nav_menu', json_encode($menu, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    flash('success', 'Menu saved. It shows on the website now.');
    redirect(admin_url('menu'));
}

// After an error, show what was typed (what could be kept) rather than the saved menu.
$menu = $_SESSION['menu_draft'] ?? nav_menu_saved();
unset($_SESSION['menu_draft']);
$choices = menu_link_choices();
$isDefault = setting('nav_menu') === '';
admin_view('menu', compact('menu', 'choices', 'isDefault') + ['title' => 'Menu']);
