<?php
// A branch manager's own shop: phone, order email, Google links and its page on the website.
$admin = require_admin();
$branch = admin_branch_locked($admin) && admin_is_manager($admin) ? get_branch((int) $admin['branch_id']) : null;
if (!$branch) {
    flash('error', 'Only a branch manager has a shop page here. Owners edit shops in Branches.');
    redirect(admin_url());
}

if (is_post()) {
    if (input('action') === 'test_email') {
        branch_send_test_email($branch, $admin);
        redirect(admin_url('my-shop'));
    }
    $errors = branch_form_save($branch, true);
    flash($errors ? 'error' : 'success', $errors ? implode(' ', $errors) : 'Your shop details are saved.');
    redirect(admin_url('my-shop'));
}

admin_view('branch-edit', ['editing' => $branch, 'limited' => true, 'title' => 'My shop']);
