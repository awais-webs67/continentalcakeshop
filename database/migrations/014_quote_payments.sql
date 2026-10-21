-- Custom cake quotes customers can accept and pay online (a deposit or the full price).

ALTER TABLE custom_requests ADD COLUMN deposit_amount DECIMAL(10,2) NULL COMMENT 'Paid to accept the quote; NULL = full price' AFTER quoted_price;
ALTER TABLE custom_requests ADD COLUMN quote_token CHAR(32) NULL AFTER deposit_amount;
ALTER TABLE custom_requests ADD COLUMN quote_sent_at DATETIME NULL AFTER quote_token;
ALTER TABLE custom_requests ADD COLUMN order_id INT UNSIGNED NULL COMMENT 'Order made when the customer accepted the quote' AFTER quote_sent_at;
ALTER TABLE custom_requests ADD UNIQUE KEY uq_custom_quote_token (quote_token);

-- @data-start
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('quote_default_deposit_percent', '50');
-- @data-end
