-- Nguồn dữ liệu crawl cho places / posts (cùng tên cột crawler_href như bảng hotels).
-- crawler_credit: dòng ghi nguồn + giấy phép (bắt buộc khi dùng lại nội dung CC BY-SA
-- từ Wikivoyage / Wikipedia và ảnh từ Wikimedia Commons), hiển thị dưới nội dung trên site.

ALTER TABLE `places`
    ADD COLUMN `crawler_href`   VARCHAR(255) NULL DEFAULT NULL AFTER `is_featured`,
    ADD COLUMN `crawler_credit` VARCHAR(500) NULL DEFAULT NULL AFTER `crawler_href`;

ALTER TABLE `posts`
    ADD COLUMN `crawler_href`   VARCHAR(255) NULL DEFAULT NULL AFTER `is_published`,
    ADD COLUMN `crawler_credit` VARCHAR(500) NULL DEFAULT NULL AFTER `crawler_href`;
