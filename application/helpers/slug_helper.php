<?php defined('BASEPATH') OR exit('No direct script access allowed');

// Helper tạo slug + tạo URL các trang công khai. Được autoload nên dùng được ở mọi nơi.
// Slug = phần chữ không dấu trên URL, vd "Phú Quốc" -> "phu-quoc" -> /place/phu-quoc

if (!function_exists('slugify')) {
    // Chuyển chuỗi bất kỳ thành slug: "Đà Nẵng 2026!" -> "da-nang-2026"
    function slugify($text)
    {
        // Bước 1: viết thường (mb_ để đúng với chữ có dấu)
        $text = mb_strtolower(trim((string) $text), 'UTF-8');

        // Bước 2: bỏ dấu tiếng Việt: mỗi nhóm ký tự có dấu -> 1 chữ không dấu
        $map = [
            'a' => 'àáạảãâầấậẩẫăằắặẳẵ',
            'e' => 'èéẹẻẽêềếệểễ',
            'i' => 'ìíịỉĩ',
            'o' => 'òóọỏõôồốộổỗơờớợởỡ',
            'u' => 'ùúụủũưừứựửữ',
            'y' => 'ỳýỵỷỹ',
            'd' => 'đ',
        ];
        foreach ($map as $to => $chars) {
            $text = preg_replace('/[' . $chars . ']/u', $to, $text);
        }

        // Bước 3: mọi ký tự không phải a-z, 0-9 (khoảng trắng, dấu câu...) -> dấu "-"
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        // Bước 4: bỏ dấu "-" thừa ở 2 đầu
        return trim($text, '-');
    }
}

if (!function_exists('unique_slug')) {
    // Slug không trùng trong bảng $table: đã có "phu-quoc" thì trả "phu-quoc-2", "phu-quoc-3"...
    //   $ignore_id: khi SỬA bản ghi, bỏ qua chính nó (không tự coi là trùng với mình)
    function unique_slug($table, $text, $ignore_id = 0)
    {
        // Helper không có $this -> lấy instance CI để dùng database
        $CI =& get_instance();
        $base = slugify($text);
        // Tên toàn ký tự đặc biệt -> slug rỗng -> dùng tạm "item"
        if ($base === '') {
            $base = 'item';
        }

        $slug = $base;
        $i = 2;
        // Lặp tới khi tìm được slug chưa có ai dùng
        while (true) {
            $CI->db->where('slug', $slug);
            if ($ignore_id) {
                $CI->db->where('id !=', $ignore_id);
            }
            // Đếm số bản ghi đang dùng slug này; = 0 -> dùng được
            if ((int) $CI->db->count_all_results($table) === 0) {
                return $slug;
            }
            // Đã trùng -> thử thêm hậu tố -2, -3...
            $slug = $base . '-' . $i++;
        }
    }
}

/*
 * Tạo URL frontend theo slug. Bản ghi cũ chưa có slug thì tạm dùng id,
 * controller sẽ tự 301 sang slug khi bản ghi đã có slug.
 */
if (!function_exists('slug_key')) {
    // Phần cuối URL của 1 bản ghi: slug nếu có, không thì id
    function slug_key($row, $slug_field = 'slug', $id_field = 'id')
    {
        if (!empty($row->$slug_field)) {
            return $row->$slug_field;
        }
        return (string) (int) ($row->$id_field ?? 0);
    }
}

// ---- Các hàm tạo link, dùng trong view: href = place_url($place)... ----
// Mỗi hàm tương ứng 1 route trong config/routes.php

if (!function_exists('place_url')) {
    // Trang chi tiết thành phố: /place/<slug> -> Home::detail()
    function place_url($place)
    {
        return base_url('place/' . slug_key($place));
    }
}

if (!function_exists('book_url')) {
    // Form đặt lịch của thành phố: /book/<slug> -> Home::book()
    function book_url($place)
    {
        return base_url('book/' . slug_key($place));
    }
}

if (!function_exists('review_url')) {
    /** Form viết đánh giá cho khách sạn: review/<id>-<slug> (cùng phần đuôi với hotel_url). */
    function review_url($hotel)
    {
        // hotel_path() trả "hotel/12-ten-ks" -> cắt bỏ "hotel/" -> "review/12-ten-ks"
        return base_url('review/' . substr(hotel_path($hotel), strlen('hotel/')));
    }
}

if (!function_exists('continent_url')) {
    /** Trang châu lục: continent/<slug> (liệt kê các quốc gia). */
    function continent_url($continent)
    {
        return base_url('continent/' . slug_key($continent));
    }
}

if (!function_exists('country_url')) {
    /** Trang quốc gia: country/<slug> (liệt kê các thành phố). */
    function country_url($country)
    {
        return base_url('country/' . slug_key($country));
    }
}

if (!function_exists('category_url')) {
    // Link danh mục cũ (route này hiện chỉ 301 về trang chủ)
    function category_url($category)
    {
        return base_url('category/' . slug_key($category));
    }
}

if (!function_exists('post_url')) {
    // Chi tiết bài viết: /news/<slug> -> Blog::detail()
    function post_url($post)
    {
        return base_url('news/' . slug_key($post));
    }
}

/*
 * URL khách sạn dạng hotel/<id>-<slug>: id quyết định bản ghi, phần slug để đẹp/SEO
 * (slug trong DB thay đổi thì controller tự 301 sang URL mới).
 */
if (!function_exists('hotel_path')) {
    // Trả đường dẫn KHÔNG kèm tên miền: "hotel/12-ten-khach-san"
    function hotel_path($hotel)
    {
        // Ưu tiên cột slug trong DB, bản ghi cũ chưa có slug thì tạo từ tên
        $slug = !empty($hotel->slug) ? $hotel->slug : slugify($hotel->name ?? '');
        return 'hotel/' . (int) $hotel->id . ($slug !== '' ? '-' . $slug : '');
    }
}

if (!function_exists('hotel_url')) {
    // URL đầy đủ trang khách sạn -> Home::hotel()
    function hotel_url($hotel)
    {
        return base_url(hotel_path($hotel));
    }
}

if (!function_exists('hotel_book_url')) {
    /** Form đặt của địa điểm, chọn sẵn khách sạn. $place cần có slug/id của địa điểm. */
    function hotel_book_url($place, $hotel)
    {
        // ?hotel=<id> -> Home::book() đọc tham số này để chọn sẵn khách sạn trên form
        return book_url($place) . '?hotel=' . (int) $hotel->id;
    }
}

if (!function_exists('decode_unicode_escapes')) {
    /**
     * Đổi mã unicode dạng chữ (vd "à-la-carte") thành ký tự thật ("à-la-carte").
     * Dữ liệu crawl từ JSON-LD đôi khi bị escape 2 lần nên còn sót các mã này.
     */
    function decode_unicode_escapes($text)
    {
        // Không có "\u" thì trả nguyên, khỏi chạy regex
        if ($text === NULL || strpos((string) $text, '\u') === FALSE) {
            return $text;
        }
        // Tìm từng cụm \uXXXX (4 ký tự hex) -> đổi mã hex sang ký tự UTF-8
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($m) {
            return mb_convert_encoding(pack('n', hexdec($m[1])), 'UTF-8', 'UTF-16BE');
        }, (string) $text);
    }
}

if ( ! function_exists('dd')) {
    // Hàm debug: in biến ra màn hình (khung viền đỏ) rồi dừng chương trình. Chỉ dùng khi lập trình.
    function dd($data){
        echo '<pre style="border: solid #ff2222 1px;">';
        print_r($data);
        echo '</pre>';
        exit;
    }
}
