-- A photo or PDF sent in a chat, by the customer or the shop (stored in storage/uploads/chat).
ALTER TABLE thread_messages ADD COLUMN attachment varchar(255) DEFAULT NULL COMMENT 'Path inside storage/uploads' AFTER body;
ALTER TABLE thread_messages ADD COLUMN attachment_name varchar(120) DEFAULT NULL COMMENT 'File name as it was sent' AFTER attachment;
