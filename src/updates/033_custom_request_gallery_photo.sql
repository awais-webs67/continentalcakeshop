-- The gallery photo a customer chose with "Order one like this" is kept with their custom cake
-- request (a copy, so it stays even if the photo later leaves the gallery).
ALTER TABLE custom_requests ADD COLUMN gallery_image varchar(255) DEFAULT NULL COMMENT 'Copy of the chosen gallery photo, inside storage/uploads' AFTER reference_image;
