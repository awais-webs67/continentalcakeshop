-- The Continental Cake Shop Canberra
-- MySQL 8 / MariaDB 10.4+ schema. Import into an empty database:
--   mysql -u USER -p DATABASE < database/schema.sql
-- Then import the starter data:
--   mysql -u USER -p DATABASE < database/seed.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS order_events;
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
    badge             VARCHAR(40) NULL,
    is_featured       TINYINT(1) NOT NULL DEFAULT 0,
    is_active         TINYINT(1) NOT NULL DEFAULT 1,
    allow_message     TINYINT(1) NOT NULL DEFAULT 1,
    lead_time_hours   INT UNSIGNED NULL COMMENT 'Overrides the global minimum notice when set',
    sort_order        INT NOT NULL DEFAULT 0,
    meta_title        VARCHAR(160) NULL,
    meta_description  VARCHAR(255) NULL,
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

-- Customer accounts. Guests can still check out without one.
CREATE TABLE users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL UNIQUE,
    phone           VARCHAR(40) NULL,
    password_hash   VARCHAR(255) NOT NULL,
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
    access_token      CHAR(32) NOT NULL,
    customer_name     VARCHAR(120) NOT NULL,
    customer_email    VARCHAR(190) NOT NULL,
    customer_phone    VARCHAR(40) NOT NULL,
    fulfilment        ENUM('pickup','delivery') NOT NULL DEFAULT 'pickup',
    delivery_address  VARCHAR(255) NULL,
    pickup_date       DATE NOT NULL,
    pickup_time       TIME NOT NULL,
    subtotal          DECIMAL(10,2) NOT NULL,
    delivery_fee      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total             DECIMAL(10,2) NOT NULL,
    payment_method    VARCHAR(20) NOT NULL,
    payment_status    ENUM('pending','awaiting_verification','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    order_status      ENUM('new','confirmed','in_progress','ready','completed','cancelled') NOT NULL DEFAULT 'new',
    payment_reference VARCHAR(190) NULL,
    payment_proof     VARCHAR(255) NULL COMMENT 'Path inside storage/uploads',
    stripe_session_id VARCHAR(255) NULL,
    notes             TEXT NULL,
    admin_notes       TEXT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_orders_pickup (pickup_date, pickup_time),
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
    product_name VARCHAR(160) NOT NULL,
    size_label   VARCHAR(60) NOT NULL,
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
    servings        VARCHAR(40) NULL,
    flavour         VARCHAR(120) NULL,
    budget          VARCHAR(40) NULL,
    details         TEXT NOT NULL,
    reference_image VARCHAR(255) NULL COMMENT 'Path inside storage/uploads',
    status          ENUM('new','quoted','confirmed','closed') NOT NULL DEFAULT 'new',
    quoted_price    DECIMAL(10,2) NULL,
    admin_notes     TEXT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
    title            VARCHAR(160) NOT NULL,
    summary          VARCHAR(255) NULL,
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
    role           ENUM('owner','staff') NOT NULL DEFAULT 'staff',
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
CREATE TABLE schema_migrations (
    migration  VARCHAR(190) NOT NULL PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
