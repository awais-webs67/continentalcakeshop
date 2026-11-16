-- Other search words for each cake ("tags"): found by the website search and given to Google.
-- Existing cakes get the same automatic words a new cake gets: its name, the focus keyword,
-- "<name> canberra", "<category> canberra" and "halal cakes canberra".
ALTER TABLE `products` ADD COLUMN `search_words` varchar(255) DEFAULT NULL COMMENT 'Other words people search for, comma separated (site search, Google data)' AFTER `focus_keyword`;
UPDATE `products` p JOIN `categories` c ON c.id = p.category_id
SET p.search_words = LOWER(CONCAT_WS(', ',
    p.name,
    IF(p.focus_keyword IS NULL OR p.focus_keyword = '' OR p.focus_keyword = LOWER(CONCAT(p.name, ' canberra')), NULL, p.focus_keyword),
    CONCAT(p.name, ' canberra'),
    CONCAT(REPLACE(c.name, '&', 'and'), ' canberra'),
    'halal cakes canberra'))
WHERE p.search_words IS NULL OR p.search_words = '';
