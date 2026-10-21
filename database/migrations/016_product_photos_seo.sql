-- Several photos per cake with alt text, and a focus keyword for the SEO checklist.

CREATE TABLE IF NOT EXISTS product_images (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image      VARCHAR(255) NOT NULL COMMENT 'Path inside public/uploads',
    alt        VARCHAR(160) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_product_images (product_id, sort_order),
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE products ADD COLUMN image_alt VARCHAR(160) NULL COMMENT 'Describes the main photo for Google and screen readers' AFTER image;
ALTER TABLE products ADD COLUMN focus_keyword VARCHAR(80) NULL COMMENT 'Words people search for, e.g. black forest cake canberra' AFTER meta_description;
ALTER TABLE categories ADD COLUMN image_alt VARCHAR(160) NULL AFTER image;
