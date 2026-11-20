-- Quốc gia > Thành phố (places) > Khách sạn (hotels).
-- Bảng countries crawl từ trang quốc gia Kayak: /<Country>-Hotels.<id>.dc.html
-- Nhận diện khi crawl lại: crawler_href (link đã chuẩn hoá bằng canonicalHref) -> slug.
--
-- Lưu ý: DB local đã tạo bảng tay trước khi có file này (các cột bị DEFAULT 'NULL' - chuỗi chữ),
-- phần (2) sửa lại cho đúng. DB mới chạy phần (1) + (3) là đủ.

-- (1) Tạo bảng (DB chưa có)
CREATE TABLE IF NOT EXISTS `countries` (
    `id`             INT          NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(255) NOT NULL,
    `slug`           VARCHAR(255) NOT NULL,
    `code`           CHAR(2)      NULL DEFAULT NULL,
    `continent`      VARCHAR(100) NULL DEFAULT NULL,
    `image`          VARCHAR(255) NULL DEFAULT NULL,
    `content`        TEXT         NULL,
    `is_featured`    TINYINT(1)   NOT NULL DEFAULT 0,
    `crawler_href`   VARCHAR(255) NULL DEFAULT NULL,
    `crawler_credit` VARCHAR(500) NULL DEFAULT NULL,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- (2) Sửa bảng tạo tay: chuỗi 'NULL' -> NULL thật, default đúng
UPDATE `countries` SET `code`           = NULL WHERE `code`           = 'NULL';
UPDATE `countries` SET `continent`      = NULL WHERE `continent`      = 'NULL';
UPDATE `countries` SET `image`          = NULL WHERE `image`          = 'NULL';
UPDATE `countries` SET `crawler_href`   = NULL WHERE `crawler_href`   = 'NULL';
UPDATE `countries` SET `crawler_credit` = NULL WHERE `crawler_credit` = 'NULL';
UPDATE `countries` SET `is_featured`    = 0    WHERE `is_featured` IS NULL;

ALTER TABLE `countries`
    MODIFY `code`           CHAR(2)      NULL DEFAULT NULL,
    MODIFY `continent`      VARCHAR(100) NULL DEFAULT NULL,
    MODIFY `image`          VARCHAR(255) NULL DEFAULT NULL,
    MODIFY `is_featured`    TINYINT(1)   NOT NULL DEFAULT 0,
    MODIFY `crawler_href`   VARCHAR(255) NULL DEFAULT NULL,
    MODIFY `crawler_credit` VARCHAR(500) NULL DEFAULT NULL;

-- (3) Khoá unique + places.country_id
ALTER TABLE `countries`
    ADD UNIQUE KEY `uq_countries_slug` (`slug`),
    ADD UNIQUE KEY `uq_countries_crawler_href` (`crawler_href`);

-- places.country_id (DB local đã có -> bỏ qua câu này)
-- ALTER TABLE `places`
--     ADD COLUMN `country_id` INT NULL DEFAULT NULL AFTER `category_id`,
--     ADD KEY `fk_places_country` (`country_id`),
--     ADD CONSTRAINT `fk_places_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL;

-- Gán quốc gia cho thành phố cũ theo phần cuối address ("Hanoi, Vietnam" -> "Vietnam")
UPDATE `places` p
JOIN `countries` c ON c.name = TRIM(SUBSTRING_INDEX(p.address, ',', -1))
SET p.country_id = c.id
WHERE p.country_id IS NULL;
