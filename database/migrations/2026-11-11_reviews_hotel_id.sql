-- Đánh giá chuyển từ theo địa điểm sang theo khách sạn: reviews.place_id -> reviews.hotel_id.
-- Quyền viết đánh giá: email có booking "Đã hoàn thành" (is_status = 3) kèm khách sạn đó (bookings.hotel_id).
-- Trang địa điểm hiển thị gộp đánh giá của các khách sạn thuộc địa điểm.
--
-- Lưu ý: DB local đã được đổi tay trước khi có file này (cột hotel_id đã tồn tại, bảng reviews đang trống),
-- chỉ cần chạy trên DB nào còn cột place_id.

ALTER TABLE `reviews`
    CHANGE COLUMN `place_id` `hotel_id` INT NOT NULL;
