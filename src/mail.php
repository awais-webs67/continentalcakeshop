<?php
declare(strict_types=1);

// Plain-text emails sent with PHP mail(). On most Australian shared hosts this works
// out of the box; swap send_mail() for an SMTP library later without touching callers.

function send_mail(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    if (!valid_email($to)) {
        return false;
    }
    $from = (string) config('mail_from', setting('store_email'));
    $headers = [
        'From: ' . setting('store_short_name') . ' <' . $from . '>',
        'Reply-To: ' . ($replyTo ?: setting('store_email')),
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP',
    ];
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    return @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
}

function order_summary_text(array $order): string
{
    $lines = [];
    foreach (get_order_items((int) $order['id']) as $item) {
        $line = sprintf('%d x %s (%s)  %s', $item['quantity'], $item['product_name'], $item['size_label'], money($item['line_total']));
        if ($item['cake_message']) {
            $line .= "\n    Message on cake: \"" . $item['cake_message'] . '"';
        }
        $lines[] = $line;
    }
    $label = $order['fulfilment'] === 'delivery' ? 'Delivery' : 'Pickup';
    $lines[] = '';
    $lines[] = 'Total: ' . money($order['total']);
    $lines[] = $label . ': ' . format_pickup($order['pickup_date'], $order['pickup_time']);
    if ($order['fulfilment'] === 'delivery') {
        $lines[] = 'Address: ' . $order['delivery_address'];
    } else {
        $lines[] = 'Pickup from: ' . store_address_line();
    }
    $lines[] = 'Payment: ' . payment_method_label($order['payment_method']) . ' (' . PAYMENT_STATUSES[$order['payment_status']] . ')';
    return implode("\n", $lines);
}

/** Sends the customer confirmation and the shop notification for a new order. */
function send_order_emails(array $order): void
{
    $summary = order_summary_text($order);
    $shop = setting('store_short_name');

    if (setting_bool('send_customer_emails')) {
        $body = "Hi {$order['customer_name']},\n\n"
            . "Thank you for ordering from {$shop}. Your order number is {$order['order_number']}.\n\n"
            . $summary . "\n\n"
            . "View your order: " . order_url($order) . "\n\n"
            . 'Questions? Call us on ' . setting('store_phone') . " or reply to this email.\n\n"
            . $shop . "\n" . store_address_line();
        send_mail($order['customer_email'], "Your order {$order['order_number']} - {$shop}", $body);
    }

    $notify = setting('order_notification_email');
    if ($notify !== '') {
        $body = "New online order {$order['order_number']}\n\n"
            . "Customer: {$order['customer_name']}\nPhone: {$order['customer_phone']}\nEmail: {$order['customer_email']}\n\n"
            . $summary
            . ($order['notes'] ? "\n\nNotes: {$order['notes']}" : '');
        send_mail($notify, "New order {$order['order_number']}", $body, $order['customer_email']);
    }
}
