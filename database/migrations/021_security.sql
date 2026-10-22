-- Security hardening:
--  * users.email_verified_at: orders and messages linked only by email (placed as a guest) show in
--    an account once its email has been confirmed with the link we send.
--  * auth_version: changing or resetting a password signs out every other session.
--  * rate_limits: limits per internet address for logins, forms, uploads, coupons and orders,
--    shared by every session (the old limits lived in one session and were easy to dodge).
ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER password_hash;
ALTER TABLE users ADD COLUMN auth_version INT UNSIGNED NOT NULL DEFAULT 0 AFTER email_verified_at;
ALTER TABLE admins ADD COLUMN auth_version INT UNSIGNED NOT NULL DEFAULT 0 AFTER password_hash;

CREATE TABLE IF NOT EXISTS rate_limits (
    bucket       CHAR(40) NOT NULL PRIMARY KEY,
    hits         INT UNSIGNED NOT NULL DEFAULT 0,
    window_start DATETIME NOT NULL,
    KEY idx_rate_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
