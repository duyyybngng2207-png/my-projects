-- Đặt kèm khách sạn trong form booking + sửa bookings.id thiếu AUTO_INCREMENT.
-- Lưu ý: dòng đang có id = 0 sẽ được MySQL đánh lại số mới khi bật AUTO_INCREMENT.

ALTER TABLE `bookings`
    MODIFY `id` INT NOT NULL AUTO_INCREMENT,
    ADD COLUMN `hotel_id` INT NULL DEFAULT NULL AFTER `place_id`,
    ADD KEY `fk_bookings_hotel` (`hotel_id`),
    ADD CONSTRAINT `fk_bookings_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE SET NULL;
