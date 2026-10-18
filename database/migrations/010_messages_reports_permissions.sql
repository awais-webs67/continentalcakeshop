-- Admin password resets, staff permissions, customer and shop messaging, the daily
-- morning email, and SEO, ABN and GST settings.

-- ---------------------------------------------------------------------------
-- Structure
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS admin_password_resets (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT UNSIGNED NOT NULL,
    token_hash  CHAR(64) NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_resets_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Which parts of the admin panel a staff account may open (JSON list). NULL = everything
-- except the owner pages, which is how staff accounts worked before.
ALTER TABLE admins ADD COLUMN permissions TEXT NULL AFTER role;

-- Conversations between the shop and a customer, about an order, a custom cake request
-- or a contact form message. The customer opens them with the private link in emails.
CREATE TABLE IF NOT EXISTS threads (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    token              CHAR(32) NOT NULL UNIQUE,
    subject            VARCHAR(160) NOT NULL,
    customer_name      VARCHAR(120) NOT NULL,
    customer_email     VARCHAR(190) NOT NULL,
    customer_phone     VARCHAR(40) NULL,
    user_id            INT UNSIGNED NULL,
    order_id           INT UNSIGNED NULL,
    custom_request_id  INT UNSIGNED NULL,
    status             ENUM('open','closed') NOT NULL DEFAULT 'open',
    unread_admin       TINYINT(1) NOT NULL DEFAULT 0,
    unread_customer    TINYINT(1) NOT NULL DEFAULT 0,
    last_message_at    DATETIME NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_threads_order (order_id),
    UNIQUE KEY uq_threads_custom (custom_request_id),
    KEY idx_threads_inbox (status, last_message_at),
    KEY idx_threads_email (customer_email),
    CONSTRAINT fk_threads_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_threads_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_threads_custom FOREIGN KEY (custom_request_id) REFERENCES custom_requests (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS thread_messages (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    thread_id   INT UNSIGNED NOT NULL,
    sender      ENUM('customer','shop') NOT NULL,
    admin_id    INT UNSIGNED NULL,
    body        TEXT NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_thread_messages (thread_id, created_at),
    CONSTRAINT fk_thread_messages_thread FOREIGN KEY (thread_id) REFERENCES threads (id) ON DELETE CASCADE,
    CONSTRAINT fk_thread_messages_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Earlier contact form messages become conversations, so they all live in one inbox.
-- (Skipped when this migration is run again: the token is made from the old message id.)
INSERT IGNORE INTO threads (token, subject, customer_name, customer_email, customer_phone, status, unread_admin, last_message_at, created_at)
SELECT MD5(CONCAT('contact-', id, '-', created_at)), 'Website message', name, email, phone,
       IF(replied_at IS NULL, 'open', 'closed'), 1 - is_read, created_at, created_at
FROM contact_messages;
INSERT INTO thread_messages (thread_id, sender, body, created_at)
SELECT t.id, 'customer', c.message, c.created_at
FROM contact_messages c
JOIN threads t ON t.token = MD5(CONCAT('contact-', c.id, '-', c.created_at))
WHERE NOT EXISTS (SELECT 1 FROM thread_messages m WHERE m.thread_id = t.id);
