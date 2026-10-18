-- Admin panel: staff accounts, order timeline and internal notes.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(120) NOT NULL,
    email          VARCHAR(190) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    role           ENUM('owner','staff') NOT NULL DEFAULT 'staff',
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    failed_logins  INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until   DATETIME NULL,
    last_login_at  DATETIME NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Everything that happens to an order (placed, paid, status changes, notes).
CREATE TABLE IF NOT EXISTS order_events (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED NOT NULL,
    admin_id   INT UNSIGNED NULL,
    event      VARCHAR(40) NOT NULL,
    message    VARCHAR(500) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_events_order (order_id, created_at),
    CONSTRAINT fk_events_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_events_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE orders ADD COLUMN admin_notes TEXT NULL AFTER notes;
ALTER TABLE custom_requests ADD COLUMN quoted_price DECIMAL(10,2) NULL AFTER status;
ALTER TABLE custom_requests ADD COLUMN admin_notes TEXT NULL AFTER quoted_price;
ALTER TABLE contact_messages ADD COLUMN replied_at DATETIME NULL AFTER is_read;

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('email_accent_note', 'Thank you for choosing a local Canberra cake shop.');
