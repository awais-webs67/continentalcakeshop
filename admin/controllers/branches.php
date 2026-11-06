<?php
// Owners: every shop. /admin/branches lists them; /admin/branches?id=2&tab=email opens one shop
// with its tabs (Overview, Shop details, Email, Payments, Website page, Logins).
$admin = require_admin('owner');

$branch = query_int('id') ? get_branch(query_int('id')) : (post_int('id') ? get_branch(post_int('id')) : null);
$tab = isset(BRANCH_TABS[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'overview';
$self = static fn (array $b, string $t): string => admin_url('branches', ['id' => $b['id'], 'tab' => $t]);

if (is_post()) {
    $action = input('action');

    if ($action === 'create') {
        $errors = branch_save_details(null);
        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect(admin_url('branches', ['new' => 1]));
        }
        flash('success', input('name') . ' added. Next: its email and payments, then switch on "Show to customers".');
        redirect(admin_url('branches', ['id' => $GLOBALS['new_branch_id'] ?? 0, 'tab' => 'email']));
    }
    if (!$branch) {
        redirect(admin_url('branches'));
    }
    $result = match ($action) {
        'details'  => branch_save_details($branch),
        'email'    => branch_save_email($branch),
        'payments' => branch_save_payments($branch),
        'page'     => branch_save_page($branch),
        default    => null,
    };
    if ($result !== null) {
        flash($result ? 'error' : 'success', $result ? implode(' ', $result) : 'Saved.');
        redirect($self($branch, $action));
    }
    if ($action === 'toggle' && !(int) $branch['is_main']) {
        if (!$branch['is_active'] && (trim($branch['street']) === '' || trim($branch['phone']) === '')) {
            flash('error', 'Add the street address and phone of ' . $branch['name'] . ' first.');
        } else {
            db_run('UPDATE branches SET is_active = 1 - is_active WHERE id = ?', [$branch['id']]);
            flash('success', $branch['name'] . ($branch['is_active'] ? ' is hidden from customers. Its orders stay in the panel.' : ' is now shown to customers.'));
        }
    } elseif ($action === 'test_email') {
        branch_send_test_email($branch, $admin);
        redirect($self($branch, 'email'));
    } elseif ($action === 'check_inbox') {
        $line = inbox_check($branch);
        $error = (string) branch_setting((int) $branch['id'], 'inbox_error');
        flash($error === '' ? 'success' : 'error', $error === '' ? 'Inbox checked: ' . $line . '.' : 'The inbox could not be read: ' . $error);
        redirect($self($branch, 'email'));
    }
    admin_back('branches');
}

if (isset($_GET['new'])) {
    admin_view('branch', ['branch' => null, 'tab' => 'details', 'stats' => [], 'title' => 'Add a branch']);
}
if ($branch) {
    admin_view('branch', ['branch' => $branch, 'tab' => $tab, 'stats' => branch_stats()[(int) $branch['id']] ?? [], 'title' => $branch['name']]);
}
admin_view('branches', ['stats' => branch_stats(), 'title' => 'Branches']);
