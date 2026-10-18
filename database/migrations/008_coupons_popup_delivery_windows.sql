-- Discount coupons, the promo popup card, admin-editable delivery time windows,
-- delivery time ranges on orders, free delivery over an amount and editable payment texts.

-- ---------------------------------------------------------------------------
-- Structure
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS coupons (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code           VARCHAR(40) NOT NULL UNIQUE,
    description    VARCHAR(160) NULL,
    discount_type  ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order      DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '0 = any order',
    max_uses       INT UNSIGNED NULL COMMENT 'NULL = unlimited',
    used_count     INT UNSIGNED NOT NULL DEFAULT 0,
    starts_at      DATE NULL,
    expires_at     DATE NULL COMMENT 'Last day the code works; NULL = no expiry',
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(40) NULL AFTER delivery_fee;
ALTER TABLE orders ADD COLUMN discount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER coupon_code;
-- Delivery orders can be booked for a time range, e.g. 1pm to 3pm.
ALTER TABLE orders ADD COLUMN delivery_time_to TIME NULL AFTER pickup_time;
-- Preferred windows are now set by the shop, so keep the customer's choice as text.
ALTER TABLE orders MODIFY delivery_window VARCHAR(60) NULL;

-- ---------------------------------------------------------------------------
-- Data (also part of seed.sql)
-- ---------------------------------------------------------------------------

-- @data-start
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('delivery_windows',         '["9am to 12pm","12pm to 3pm","3pm to 6pm"]'),
('delivery_free_over',       '0'),
('coupons_enabled',          '1'),
('payment_stripe_label',     'Credit or debit card'),
('payment_stripe_note',      'Pay securely with Visa, Mastercard, Amex, Apple Pay or Google Pay through Stripe.'),
('payment_payid_label',      'PayID'),
('payment_payid_note',       'Send the total to our PayID from your banking app, then upload a screenshot of the payment.'),
('payment_bank_label',       'Bank transfer'),
('payment_bank_note',        'Transfer the total to our bank account, then upload a screenshot or PDF of the receipt.'),
('popup_enabled',            '0'),
('popup_title',              'Sweet deal for you'),
('popup_text',               'Order your next cake online and save.'),
('popup_coupon_id',          ''),
('popup_image',              ''),
('popup_button_text',        'Order Online'),
('popup_button_url',         'shop'),
('popup_delay_seconds',      '3'),
('popup_pages',              'all'),
('popup_frequency',          'once'),
('popup_version',            '1');
-- @data-end
