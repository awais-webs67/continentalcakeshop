-- Payments ledger (deposits, part payments, partial refunds), orders taken by staff,
-- add-ons at checkout (candles, knife...) and product options (e.g. eggless).

-- ---------------------------------------------------------------------------
-- Structure
-- ---------------------------------------------------------------------------

-- Every payment and refund on an order, so deposits and partial refunds add up correctly.
-- The reference (Stripe payment, bank receipt...) is unique, so the same payment is never counted twice.
CREATE TABLE IF NOT EXISTS payments (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED NOT NULL,
    kind       ENUM('payment','refund') NOT NULL DEFAULT 'payment',
    amount     DECIMAL(10,2) NOT NULL,
    method     VARCHAR(20) NOT NULL,
    reference  VARCHAR(190) NULL,
    note       VARCHAR(255) NULL,
    admin_id   INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payments_reference (reference),
    KEY idx_payments_order (order_id, created_at),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE orders MODIFY payment_status ENUM('pending','awaiting_verification','partially_paid','paid','failed','refunded') NOT NULL DEFAULT 'pending';
ALTER TABLE orders ADD COLUMN channel VARCHAR(20) NOT NULL DEFAULT 'online' COMMENT 'online, phone, in_store or quote' AFTER user_id;
ALTER TABLE orders ADD COLUMN amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total;
ALTER TABLE orders ADD COLUMN amount_refunded DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER amount_paid;
ALTER TABLE orders ADD COLUMN custom_request_id INT UNSIGNED NULL AFTER channel;
ALTER TABLE orders ADD COLUMN completed_at DATETIME NULL AFTER admin_notes;
ALTER TABLE orders ADD COLUMN reminder_sent_at DATETIME NULL AFTER completed_at;
ALTER TABLE orders ADD COLUMN review_requested_at DATETIME NULL AFTER reminder_sent_at;
ALTER TABLE orders ADD KEY idx_orders_custom (custom_request_id);

ALTER TABLE order_items ADD COLUMN addon_id INT UNSIGNED NULL AFTER size_id;
ALTER TABLE order_items ADD COLUMN options VARCHAR(255) NULL COMMENT 'Chosen product options, e.g. Eggless' AFTER size_label;

-- Extras sold with any order: candles, cake knife, number topper, gift card...
CREATE TABLE IF NOT EXISTS addons (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    price       DECIMAL(10,2) NOT NULL,
    max_qty     INT UNSIGNED NOT NULL DEFAULT 10,
    sort_order  INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Choices on one cake that change its price, e.g. "Eggless (+$5)" or "Extra fruit (+$8)".
CREATE TABLE IF NOT EXISTS product_options (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    name       VARCHAR(80) NOT NULL,
    price      DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Added to the size price',
    sort_order INT NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_options_product (product_id, is_active, sort_order),
    CONSTRAINT fk_options_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Data
-- ---------------------------------------------------------------------------

-- @data-start
-- Orders paid or refunded before the ledger existed get one matching row each, so totals stay right.
INSERT IGNORE INTO payments (order_id, kind, amount, method, reference, note, created_at)
SELECT id, 'payment', total, payment_method, CONCAT('legacy-pay-', id), 'Recorded before payment history was kept', updated_at
FROM orders WHERE payment_status IN ('paid', 'refunded');
INSERT IGNORE INTO payments (order_id, kind, amount, method, reference, note, created_at)
SELECT id, 'refund', total, payment_method, CONCAT('legacy-refund-', id), 'Recorded before payment history was kept', updated_at
FROM orders WHERE payment_status = 'refunded';
UPDATE orders SET amount_paid = total WHERE payment_status IN ('paid', 'refunded') AND amount_paid = 0;
UPDATE orders SET amount_refunded = total WHERE payment_status = 'refunded' AND amount_refunded = 0;
UPDATE orders SET completed_at = updated_at WHERE order_status = 'completed' AND completed_at IS NULL;
-- Old completed orders never get a review request email.
UPDATE orders SET review_requested_at = updated_at WHERE order_status = 'completed' AND review_requested_at IS NULL;

-- Starter add-ons, switched off until the shop checks the prices.
INSERT INTO addons (name, description, price, max_qty, sort_order, is_active)
SELECT * FROM (
    SELECT 'Birthday candles' AS name, 'Pack of 12 candles' AS description, 3.00 AS price, 5 AS max_qty, 1 AS sort_order, 0 AS is_active UNION ALL
    SELECT 'Number candle', 'One gold number candle. Tell us the number in the order notes', 4.00, 4, 2, 0 UNION ALL
    SELECT 'Cake knife', 'Plastic cake knife', 2.00, 3, 3, 0 UNION ALL
    SELECT 'Happy Birthday topper', 'Gold acrylic topper', 8.00, 2, 4, 0 UNION ALL
    SELECT 'Gift card', 'Handwritten card. Write your message in the order notes', 5.00, 3, 5, 0
) AS starter WHERE NOT EXISTS (SELECT 1 FROM addons);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('addons_enabled', '0'),
('addons_title',   'Add something extra');
-- @data-end
