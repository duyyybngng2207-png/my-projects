-- Tác giả bài viết, crawl từ blog Kayak (JSON-LD node "Person" trên trang bài / trang tác giả
-- https://www.kayak.com/news/author/<slug>/). Bài viết trỏ tới tác giả qua posts.author_id.
-- Nhận diện khi crawl lại: crawler_href (link trang tác giả) -> slug.

CREATE TABLE IF NOT EXISTS `authors` (
    `id`             INT          NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(150) NOT NULL,
    `slug`           VARCHAR(150) NOT NULL,
    `avatar`         VARCHAR(255) NULL DEFAULT NULL,        -- uploads/authors/, chỉ lưu tên file
    `job_title`      VARCHAR(150) NULL DEFAULT NULL,
    `bio`            TEXT         NULL,
    `social_links`   TEXT         NULL,                     -- JSON: ["https://www.instagram.com/...", ...]
    `crawler_href`   VARCHAR(255) NULL DEFAULT NULL,        -- https://www.kayak.com/news/author/lydia-rajteric/
    `crawler_credit` VARCHAR(500) NULL DEFAULT NULL,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_authors_slug` (`slug`),
    UNIQUE KEY `uq_authors_crawler_href` (`crawler_href`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `posts`
    ADD COLUMN `author_id` INT NULL DEFAULT NULL AFTER `slug`,
    ADD KEY `fk_posts_author` (`author_id`),
    ADD CONSTRAINT `fk_posts_author` FOREIGN KEY (`author_id`)
        REFERENCES `authors` (`id`) ON DELETE SET NULL;
