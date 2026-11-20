-- Châu lục > Quốc gia > Thành phố (places) > Khách sạn (hotels).
-- Bảng continents crawl từ trang châu lục Kayak: /Cheap-Hotels-in-<Name>.<xx>.htc.html
-- (as = Asia, eu = Europe, na = North America, ... - 9 vùng theo cách chia của Kayak).
-- countries.continent (chữ) được thay bằng countries.continent_id.

CREATE TABLE IF NOT EXISTS `continents` (
    `id`             INT          NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(100) NOT NULL,                 -- "Asia"
    `slug`           VARCHAR(100) NOT NULL,                 -- "asia"
    `image`          VARCHAR(255) NULL DEFAULT NULL,        -- uploads/continents/, chỉ lưu tên file
    `content`        TEXT         NULL,
    `sort_order`     INT          NOT NULL DEFAULT 0,       -- thứ tự trên trang chủ (nhỏ lên trước)
    `crawler_href`   VARCHAR(255) NULL DEFAULT NULL,        -- https://www.kayak.com/Cheap-Hotels-in-Asia.as.htc.html
    `crawler_credit` VARCHAR(500) NULL DEFAULT NULL,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_continents_slug` (`slug`),
    UNIQUE KEY `uq_continents_crawler_href` (`crawler_href`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `countries`
    ADD COLUMN `continent_id` INT NULL DEFAULT NULL AFTER `slug`,
    ADD KEY `fk_countries_continent` (`continent_id`),
    ADD CONSTRAINT `fk_countries_continent` FOREIGN KEY (`continent_id`)
        REFERENCES `continents` (`id`) ON DELETE SET NULL;

-- Chuyển dữ liệu cũ: mỗi giá trị countries.continent -> 1 dòng continents
INSERT INTO `continents` (`name`, `slug`)
SELECT DISTINCT TRIM(`continent`), LOWER(REPLACE(TRIM(`continent`), ' ', '-'))
FROM `countries`
WHERE `continent` IS NOT NULL AND TRIM(`continent`) != '';

UPDATE `countries` c
JOIN `continents` ct ON ct.name = TRIM(c.continent)
SET c.continent_id = ct.id;

ALTER TABLE `countries` DROP COLUMN `continent`;
