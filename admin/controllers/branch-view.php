<?php
// The branch menu at the top of the panel: show one branch, or every branch.
$admin = require_admin();
if (is_post() && !admin_branch_locked($admin)) {
    $id = post_int('branch');
    if ($id && get_branch($id)) {
        $_SESSION['admin_branch_view'] = $id;
    } else {
        unset($_SESSION['admin_branch_view']);
    }
}
admin_back('');
