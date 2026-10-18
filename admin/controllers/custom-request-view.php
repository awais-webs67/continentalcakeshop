<?php
$admin = require_admin();

$id = query_int('id') ?: post_int('id');
$request = $id ? db_one('SELECT * FROM custom_requests WHERE id = ?', [$id]) : null;
if (!$request) {
    flash('error', 'That request was not found.');
    redirect(admin_url('custom-requests'));
}
$self = admin_url('custom-requests/view', ['id' => $request['id']]);

if (is_post()) {
    $action = input('action');
    if ($action === 'quote') {
        $price = (float) str_replace(['$', ','], '', input('quoted_price'));
        $message = trim(input('message'));
        if ($message === '') {
            flash('error', 'Please write a short message for the customer.');
            redirect($self);
        }
        db_run("UPDATE custom_requests SET quoted_price = ?, status = IF(status = 'new', 'quoted', status) WHERE id = ?", [$price > 0 ? round($price, 2) : null, $request['id']]);
        $request = db_one('SELECT * FROM custom_requests WHERE id = ?', [$request['id']]);
        // Keep the quote in the conversation, so the customer's answer lands next to it.
        add_thread_message(custom_request_thread($request), 'shop', ($request['quoted_price'] ? 'Quote: ' . money($request['quoted_price']) . "\n\n" : '') . $message, (int) $admin['id']);
        if (send_custom_quote_email($request, $message)) {
            flash('success', 'Your reply was emailed to ' . $request['email'] . '.');
        } else {
            flash('error', 'The quote was saved but the email could not be sent. Check the mail settings in config.php.');
        }
    } elseif ($action === 'status' && isset(CUSTOM_STATUSES[input('status')])) {
        db_run('UPDATE custom_requests SET status = ? WHERE id = ?', [input('status'), $request['id']]);
        flash('success', 'Status changed to ' . CUSTOM_STATUSES[input('status')] . '.');
    } elseif ($action === 'notes') {
        db_run('UPDATE custom_requests SET admin_notes = ? WHERE id = ?', [str_limit(trim(input('admin_notes')), 5000) ?: null, $request['id']]);
        flash('success', 'Notes saved.');
    } elseif ($action === 'delete') {
        db_run('DELETE FROM custom_requests WHERE id = ?', [$request['id']]);
        if ($request['reference_image'] && !str_contains($request['reference_image'], '..')) {
            @unlink(STORAGE_PATH . '/uploads/' . $request['reference_image']);
        }
        flash('success', 'The request was deleted.');
        redirect(admin_url('custom-requests'));
    }
    redirect($self);
}

$isImage = $request['reference_image'] && preg_match('/\.(jpe?g|png|webp|gif)$/i', $request['reference_image']);
$defaultMessage = "Thank you for your custom cake enquiry. We would love to make this cake for your " . strtolower($request['occasion']) . ".\n\n"
    . "Based on the details you sent, the price is shown below. To confirm the order, please reply to this email or call us, and we will arrange a deposit and pickup time.";
admin_view('custom-request-view', compact('request', 'isImage', 'defaultMessage') + ['title' => 'Request from ' . $request['name']]);
