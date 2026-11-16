-- Earlier versions of each cake's SEO text, so a version written by the AI (or by hand)
-- can be restored from the product editor.
CREATE TABLE IF NOT EXISTS product_seo_history (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  product_id int(10) unsigned NOT NULL,
  fields mediumtext NOT NULL COMMENT 'JSON: the SEO fields as they were before the change',
  replaced_by varchar(20) NOT NULL DEFAULT 'edit' COMMENT 'edit, ai or restore: what replaced this version',
  admin_name varchar(120) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY idx_seo_history_product (product_id),
  CONSTRAINT fk_seo_history_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
