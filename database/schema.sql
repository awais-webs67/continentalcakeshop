-- The Continental Cake Shop Canberra
-- MySQL 8 / MariaDB 10.4+ schema. Import into an empty database:
--   mysql -u USER -p DATABASE < database/schema.sql
-- Then import the starter data:
--   mysql -u USER -p DATABASE < database/seed.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS not_found_log;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS product_options;
DROP TABLE IF EXISTS addons;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS thread_messages;
DROP TABLE IF EXISTS threads;
DROP TABLE IF EXISTS order_events;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS product_sizes;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS custom_requests;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS blocked_dates;
DROP TABLE IF EXISTS gallery_images;
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS faqs;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS admin_password_resets;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS schema_migrations;

SET FOREIGN_KEY_CHECKS = 1;

-- Key/value store for everything the admin panel will edit:
-- store details, opening hours, payment switches, pickup rules, homepage text.
CREATE TABLE settings (
    setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(120) NOT NULL,
    slug             VARCHAR(140) NOT NULL UNIQUE,
    description      TEXT NULL,
    image            VARCHAR(255) NULL,
    image_alt        VARCHAR(160) NULL,
    sort_order       INT NOT NULL DEFAULT 0,
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    meta_title       VARCHAR(160) NULL,
    meta_description VARCHAR(255) NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_categories_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category_id       INT UNSIGNED NOT NULL,
    name              VARCHAR(160) NOT NULL,
    slug              VARCHAR(180) NOT NULL UNIQUE,
    short_description VARCHAR(255) NULL,
    description       TEXT NULL,
    image             VARCHAR(255) NULL,
    image_alt         VARCHAR(160) NULL COMMENT 'Describes the main photo for Google and screen readers',
    badge             VARCHAR(40) NULL,
    is_featured       TINYINT(1) NOT NULL DEFAULT 0,
    is_active         TINYINT(1) NOT NULL DEFAULT 1,
    allow_message     TINYINT(1) NOT NULL DEFAULT 1,
    is_enquiry        TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Priced on request: shows an enquiry button instead of add to cart',
    sold_out_until    DATE NULL COMMENT 'Last day the cake is sold out; 9999-12-31 = until switched back',
    lead_time_hours   INT UNSIGNED NULL COMMENT 'Overrides the global minimum notice when set',
    sort_order        INT NOT NULL DEFAULT 0,
    meta_title        VARCHAR(160) NULL,
    meta_description  VARCHAR(255) NULL,
    focus_keyword     VARCHAR(80) NULL COMMENT 'Words people search for, e.g. black forest cake canberra',
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_products_listing (is_active, category_id, sort_order),
    KEY idx_products_featured (is_active, is_featured),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_sizes (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    label      VARCHAR(60) NOT NULL,
    servings   VARCHAR(60) NULL,
    price      DECIMAL(10,2) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_sizes_product (product_id, is_active, sort_order),
    CONSTRAINT fk_sizes_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Choices on one cake that change its price, e.g. "Eggless (+$5)".
CREATE TABLE product_options (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    name       VARCHAR(80) NOT NULL,
    price      DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Added to the size price',
    sort_order INT NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_options_product (product_id, is_active, sort_order),
    CONSTRAINT fk_options_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extra photos of a cake (the main photo is products.image).
CREATE TABLE product_images (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image      VARCHAR(255) NOT NULL COMMENT 'Path inside public/uploads',
    alt        VARCHAR(160) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_product_images (product_id, sort_order),
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extras sold with any order: candles, cake knife, number topper, gift card...
CREATE TABLE addons (
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

-- Customer accounts. Guests can still check out without one.
CREATE TABLE users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL UNIQUE,
    phone           VARCHAR(40) NULL,
    password_hash   VARCHAR(255) NOT NULL,
    email_verified_at DATETIME NULL,
    auth_version    INT UNSIGNED NOT NULL DEFAULT 0,
    failed_logins   INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until    DATETIME NULL,
    last_login_at   DATETIME NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token_hash  CHAR(64) NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resets_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_number      VARCHAR(30) NOT NULL UNIQUE,
    user_id           INT UNSIGNED NULL,
    channel           VARCHAR(20) NOT NULL DEFAULT 'online' COMMENT 'online, phone, in_store or quote',
    custom_request_id INT UNSIGNED NULL,
    access_token      CHAR(32) NOT NULL,
    customer_name     VARCHAR(120) NOT NULL,
    customer_email    VARCHAR(190) NOT NULL,
    customer_phone    VARCHAR(40) NOT NULL,
    fulfilment        ENUM('pickup','delivery') NOT NULL DEFAULT 'pickup',
    delivery_address  VARCHAR(255) NULL,
    delivery_suburb   VARCHAR(80) NULL,
    delivery_postcode VARCHAR(4) NULL,
    delivery_window   VARCHAR(60) NULL COMMENT 'Customer preference; the shop sets the exact time',
    pickup_date       DATE NOT NULL,
    pickup_time       TIME NULL COMMENT 'Pickup time, or the delivery time once the shop sets it',
    delivery_time_to  TIME NULL COMMENT 'End of the delivery time range',
    subtotal          DECIMAL(10,2) NOT NULL,
    delivery_fee      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    coupon_code       VARCHAR(40) NULL,
    discount          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total             DECIMAL(10,2) NOT NULL,
    amount_paid       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    amount_refunded   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method    VARCHAR(20) NOT NULL,
    payment_status    ENUM('pending','awaiting_verification','partially_paid','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    order_status      ENUM('new','confirmed','in_progress','ready','completed','cancelled') NOT NULL DEFAULT 'new',
    payment_reference VARCHAR(190) NULL,
    payment_proof     VARCHAR(255) NULL COMMENT 'Path inside storage/uploads',
    stripe_session_id VARCHAR(255) NULL,
    notes             TEXT NULL,
    admin_notes       TEXT NULL,
    completed_at      DATETIME NULL,
    reminder_sent_at  DATETIME NULL,
    review_requested_at DATETIME NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_orders_pickup (pickup_date, pickup_time),
    KEY idx_orders_custom (custom_request_id),
    KEY idx_orders_status (order_status, payment_status),
    KEY idx_orders_stripe (stripe_session_id),
    KEY idx_orders_user (user_id, created_at),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NULL,
    size_id      INT UNSIGNED NULL,
    addon_id     INT UNSIGNED NULL,
    product_name VARCHAR(160) NOT NULL,
    size_label   VARCHAR(60) NOT NULL,
    options      VARCHAR(255) NULL COMMENT 'Chosen product options, e.g. Eggless',
    unit_price   DECIMAL(10,2) NOT NULL,
    quantity     INT UNSIGNED NOT NULL,
    cake_message VARCHAR(60) NULL,
    line_total   DECIMAL(10,2) NOT NULL,
    KEY idx_items_order (order_id),
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL,
    CONSTRAINT fk_items_size FOREIGN KEY (size_id) REFERENCES product_sizes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE custom_requests (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    phone           VARCHAR(40) NOT NULL,
    occasion        VARCHAR(60) NOT NULL,
    event_date      DATE NOT NULL,
    fulfilment      VARCHAR(20) NULL,
    servings        VARCHAR(40) NULL,
    flavour         VARCHAR(120) NULL,
    budget          VARCHAR(40) NULL,
    details         TEXT NOT NULL,
    reference_image VARCHAR(255) NULL COMMENT 'Path inside storage/uploads',
    status          ENUM('new','quoted','confirmed','closed') NOT NULL DEFAULT 'new',
    quoted_price    DECIMAL(10,2) NULL,
    deposit_amount  DECIMAL(10,2) NULL COMMENT 'Paid to accept the quote; NULL = full price',
    quote_token     CHAR(32) NULL,
    quote_sent_at   DATETIME NULL,
    order_id        INT UNSIGNED NULL COMMENT 'Order made when the customer accepted the quote',
    admin_notes     TEXT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_custom_quote_token (quote_token),
    KEY idx_custom_status (status, event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(190) NOT NULL,
    phone      VARCHAR(40) NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    replied_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    author     VARCHAR(120) NOT NULL,
    source     VARCHAR(40) NOT NULL DEFAULT 'Google',
    body       TEXT NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Days the shop takes no pickups (public holidays, closures).
CREATE TABLE blocked_dates (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    blocked_date DATE NOT NULL UNIQUE,
    reason       VARCHAR(160) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Photos of past custom cakes, shown on the homepage and the Custom Cakes page.
CREATE TABLE gallery_images (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    image      VARCHAR(255) NOT NULL COMMENT 'Path inside public/uploads',
    caption    VARCHAR(160) NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_gallery_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Editable content pages (privacy policy, terms, refund policy, allergens).
-- Body is HTML written by the shop; {store_phone}-style tokens are filled from settings.
CREATE TABLE pages (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug             VARCHAR(80) NOT NULL UNIQUE,
    type             VARCHAR(20) NOT NULL DEFAULT 'policy' COMMENT 'policy or landing',
    title            VARCHAR(160) NOT NULL,
    summary          VARCHAR(255) NULL,
    occasion         VARCHAR(60) NULL COMMENT 'Landing pages: occasion filled in on the quote form',
    show_gallery     TINYINT(1) NOT NULL DEFAULT 1,
    sort_order       INT NOT NULL DEFAULT 0,
    body             MEDIUMTEXT NOT NULL,
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    meta_title       VARCHAR(160) NULL,
    meta_description VARCHAR(255) NULL,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE faqs (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category   VARCHAR(60) NOT NULL,
    question   VARCHAR(255) NOT NULL,
    answer     TEXT NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_faqs_question (question),
    KEY idx_faqs_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admins (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(120) NOT NULL,
    email          VARCHAR(190) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    auth_version   INT UNSIGNED NOT NULL DEFAULT 0,
    role           ENUM('owner','staff') NOT NULL DEFAULT 'staff',
    permissions    TEXT NULL COMMENT 'JSON list of admin sections a staff account may open; NULL = all',
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    failed_logins  INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until   DATETIME NULL,
    last_login_at  DATETIME NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Everything that happens to an order (placed, paid, status changes, notes).
CREATE TABLE order_events (
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


-- Records which files in database/migrations are already included, so migrate.php skips them.
CREATE TABLE coupons (
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

CREATE TABLE admin_password_resets (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT UNSIGNED NOT NULL,
    token_hash  CHAR(64) NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_resets_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE threads (
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
    admin_seen_at      DATETIME NULL,
    customer_seen_at   DATETIME NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_threads_order (order_id),
    UNIQUE KEY uq_threads_custom (custom_request_id),
    KEY idx_threads_inbox (status, last_message_at),
    KEY idx_threads_email (customer_email),
    CONSTRAINT fk_threads_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_threads_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_threads_custom FOREIGN KEY (custom_request_id) REFERENCES custom_requests (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE thread_messages (
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

-- Every payment and refund on an order, so deposits and partial refunds add up correctly.
CREATE TABLE payments (
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

-- Addresses that were not found, so staff can redirect them (Admin > SEO).
CREATE TABLE not_found_log (
    path       VARCHAR(255) NOT NULL PRIMARY KEY,
    hits       INT UNSIGNED NOT NULL DEFAULT 1,
    referrer   VARCHAR(255) NULL,
    first_seen DATETIME NOT NULL,
    last_seen  DATETIME NOT NULL,
    KEY idx_not_found_last (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
    bucket       CHAR(40) NOT NULL PRIMARY KEY,
    hits         INT UNSIGNED NOT NULL DEFAULT 0,
    window_start DATETIME NOT NULL,
    KEY idx_rate_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE schema_migrations (
    migration  VARCHAR(190) NOT NULL PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
