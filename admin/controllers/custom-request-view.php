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
        $depositText = trim(str_replace(['$', ','], '', input('deposit_amount')));
        $deposit = $depositText !== '' && is_numeric($depositText) && (float) $depositText > 0 && (float) $depositText < $price ? round((float) $depositText, 2) : null;
        $online = input('online_pay') === '1' && $price > 0 && !$request['order_id'];
        db_run("UPDATE custom_requests SET quoted_price = ?, deposit_amount = ?, quote_token = IF(? = 1, COALESCE(quote_token, ?), quote_token), quote_sent_at = NOW(),
                       status = IF(status = 'new', 'quoted', status) WHERE id = ?",
            [$price > 0 ? round($price, 2) : null, $deposit, $online ? 1 : 0, bin2hex(random_bytes(16)), $request['id']]);
        if (!$online && !$request['order_id']) {
            db_run('UPDATE custom_requests SET quote_token = NULL WHERE id = ?', [$request['id']]);
        }
        $request = db_one('SELECT * FROM custom_requests WHERE id = ?', [$request['id']]);
        // Keep the quote in the conversation, so the customer's answer lands next to it.
        add_thread_message(custom_request_thread($request), 'shop', ($request['quoted_price'] ? 'Quote: ' . money($request['quoted_price']) . "\n\n" : '') . $message, (int) $admin['id']);
        if (send_custom_quote_email($request, $message)) {
            flash('success', 'Your reply was emailed to ' . $request['email'] . '.' . ($online ? ' It has an "Accept and pay" button.' : ''));
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

$linkedOrder = $request['order_id'] ? get_order((int) $request['order_id']) : null;
$isImage = $request['reference_image'] && preg_match('/\.(jpe?g|png|webp|gif)$/i', $request['reference_image']);
$defaultMessage = "Thank you for your custom cake enquiry. We would love to make this cake for your " . strtolower($request['occasion']) . ".\n\n"
    . "Based on the details you sent, the price is shown below. To confirm, press \"Accept and pay\" in this email, or reply or call us if you would like to change anything.";
admin_view('custom-request-view', compact('request', 'isImage', 'defaultMessage', 'linkedOrder') + ['title' => 'Request from ' . $request['name']]);
