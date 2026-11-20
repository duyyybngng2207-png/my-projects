-- Tổng hợp đánh giá của khách trên trang khách sạn Kayak (crawl cùng lúc với khách sạn).
-- Kayak không trả nội dung từng review trong HTML (tải sau bằng JS) nên chỉ lưu phần tổng hợp:
-- điểm + phân bố điểm, điểm theo hạng mục, tóm tắt "Guests say" (AI của Kayak), điểm theo nguồn,
-- từ khoá hay được nhắc, Q&A và Tips (AI tổng hợp từ review).
-- KHÔNG đổ vào bảng reviews: bảng đó chỉ dành cho đánh giá thật của khách đã đặt phòng trên site.
--
-- JSON: {"score":8.3,"label":"Very good","count":1829,
--        "histogram":[{"label":"Wonderful","count":769},...],
--        "summary":"...", "categories":[{"label":"Bed Comfort","score":9.5},...],
--        "providers":[{"name":"Booking.com","score":8.3,"reviews":1612},...],
--        "mentions":["spacious room",...],
--        "qa":[{"question":"...","answer":"..."}], "tips":[{"title":"...","text":"..."}],
--        "crawled_at":"2026-11-19 22:30:00"}

ALTER TABLE `hotels`
    ADD COLUMN `guest_insights` TEXT NULL DEFAULT NULL AFTER `faqs`;
