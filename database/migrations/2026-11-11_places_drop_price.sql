-- Bỏ giá của địa điểm: nguồn crawl (Kayak) không có giá địa điểm, giá chỉ còn ở khách sạn.
-- Booking tính tiền = giá khách sạn × số đêm. Cột bookings.place_price giữ lại để booking cũ vẫn xem được số tiền đã chốt.
-- "Giá từ" của danh mục lấy từ giá khách sạn thấp nhất trong các địa điểm của danh mục.

ALTER TABLE `places`
    DROP COLUMN `price`,
    DROP COLUMN `discount_price`;
