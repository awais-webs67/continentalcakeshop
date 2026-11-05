<?php
// Super admin (owners): the shops, their details for customers and Google, and how each is doing.
$admin = require_admin('owner');

if (is_post()) {
    $id = post_int('id');
    $branch = $id ? get_branch($id) : null;
    $action = input('action');

    if ($action === 'save') {
        $errors = branch_form_save($branch, false);
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect(admin_url('branches', ['edit' => $id ?: 'new']));
        }
        flash('success', ($branch ? input('name') . ' saved.' : input('name') . ' added.') . (!$branch && input('is_active') !== '1' ? ' Switch on "Show to customers" when it is ready.' : ''));
        redirect(admin_url('branches'));
    }
    if ($branch && $action === 'toggle' && !(int) $branch['is_main']) {
        if (!$branch['is_active'] && (trim($branch['street']) === '' || trim($branch['phone']) === '')) {
            flash('error', 'Add the street address and phone of ' . $branch['name'] . ' first (Edit).');
        } else {
            db_run('UPDATE branches SET is_active = 1 - is_active WHERE id = ?', [$branch['id']]);
            flash('success', $branch['name'] . ($branch['is_active'] ? ' is hidden from customers. Its orders stay in the panel.' : ' is now shown to customers.'));
        }
    }
    if ($branch && $action === 'test_email') {
        branch_send_test_email($branch, $admin);
    }
    admin_back('branches');
}

// Edit or add one branch: its own page.
if (isset($_GET['edit'])) {
    $editing = $_GET['edit'] === 'new'
        ? ['id' => 0, 'slug' => '', 'name' => '', 'street' => '', 'suburb' => '', 'state' => 'ACT', 'postcode' => '', 'phone' => '', 'email' => '', 'latitude' => '', 'longitude' => '', 'maps_url' => '', 'reviews_url' => '', 'intro' => '', 'photo' => null, 'is_main' => 0, 'is_active' => 0]
        : get_branch(query_int('edit'));
    if (!$editing) {
        redirect(admin_url('branches'));
    }
    admin_view('branch-edit', ['editing' => $editing, 'limited' => false, 'title' => $editing['id'] ? 'Edit ' . $editing['name'] : 'Add a branch']);
}

admin_view('branches', ['stats' => branch_stats(), 'title' => 'Branches']);
