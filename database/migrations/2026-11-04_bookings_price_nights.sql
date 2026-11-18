-- Tổng tiền booking: số đêm khách sạn + giá chốt tại thời điểm đặt.
-- total_price = place_price + hotel_price * nights (không đặt khách sạn thì = place_price)

ALTER TABLE `bookings`
    ADD COLUMN `nights`      SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER `hotel_id`,
    ADD COLUMN `place_price` INT NULL DEFAULT NULL AFTER `booking_date`,
    ADD COLUMN `hotel_price` INT NULL DEFAULT NULL AFTER `place_price`,
    ADD COLUMN `total_price` INT NULL DEFAULT NULL AFTER `hotel_price`;

-- Booking cũ: chốt giá theo giá hiện tại của địa điểm / khách sạn
UPDATE `bookings` b
    JOIN `places` p ON p.id = b.place_id
    LEFT JOIN `hotels` h ON h.id = b.hotel_id
SET b.place_price = p.price,
    b.hotel_price = h.price,
    b.total_price = IFNULL(p.price, 0) + IFNULL(h.price, 0) * b.nights
WHERE b.total_price IS NULL;
