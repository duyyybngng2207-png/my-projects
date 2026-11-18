<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('slugify')) {
    function slugify($text)
    {
        $text = mb_strtolower(trim((string) $text), 'UTF-8');

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

        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}

if (!function_exists('unique_slug')) {
    function unique_slug($table, $text, $ignore_id = 0)
    {
        $CI =& get_instance();
        $base = slugify($text);
        if ($base === '') {
            $base = 'item';
        }

        $slug = $base;
        $i = 2;
        while (true) {
            $CI->db->where('slug', $slug);
            if ($ignore_id) {
                $CI->db->where('id !=', $ignore_id);
            }
            if ((int) $CI->db->count_all_results($table) === 0) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }
}

/*
 * Tạo URL frontend theo slug. Bản ghi cũ chưa có slug thì tạm dùng id,
 * controller sẽ tự 301 sang slug khi bản ghi đã có slug.
 */
if (!function_exists('slug_key')) {
    function slug_key($row, $slug_field = 'slug', $id_field = 'id')
    {
        if (!empty($row->$slug_field)) {
            return $row->$slug_field;
        }
        return (string) (int) ($row->$id_field ?? 0);
    }
}

if (!function_exists('place_url')) {
    function place_url($place)
    {
        return base_url('place/' . slug_key($place));
    }
}

if (!function_exists('book_url')) {
    function book_url($place)
    {
        return base_url('book/' . slug_key($place));
    }
}

if (!function_exists('review_url')) {
    /** Form viết đánh giá cho khách sạn: review/<id>-<slug> (cùng phần đuôi với hotel_url). */
    function review_url($hotel)
    {
        return base_url('review/' . substr(hotel_path($hotel), strlen('hotel/')));
    }
}

if (!function_exists('category_url')) {
    function category_url($category)
    {
        return base_url('category/' . slug_key($category));
    }
}

if (!function_exists('post_url')) {
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
    function hotel_path($hotel)
    {
        // Ưu tiên cột slug trong DB, bản ghi cũ chưa có slug thì tạo từ tên
        $slug = !empty($hotel->slug) ? $hotel->slug : slugify($hotel->name ?? '');
        return 'hotel/' . (int) $hotel->id . ($slug !== '' ? '-' . $slug : '');
    }
}

if (!function_exists('hotel_url')) {
    function hotel_url($hotel)
    {
        return base_url(hotel_path($hotel));
    }
}

if (!function_exists('hotel_book_url')) {
    /** Form đặt của địa điểm, chọn sẵn khách sạn. $place cần có slug/id của địa điểm. */
    function hotel_book_url($place, $hotel)
    {
        return book_url($place) . '?hotel=' . (int) $hotel->id;
    }
}

if (!function_exists('decode_unicode_escapes')) {
    /**
     * Đổi mã unicode dạng chữ (vd "\u00E0-la-carte") thành ký tự thật ("à-la-carte").
     * Dữ liệu crawl từ JSON-LD đôi khi bị escape 2 lần nên còn sót các mã này.
     */
    function decode_unicode_escapes($text)
    {
        if ($text === NULL || strpos((string) $text, '\u') === FALSE) {
            return $text;
        }
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($m) {
            return mb_convert_encoding(pack('n', hexdec($m[1])), 'UTF-8', 'UTF-16BE');
        }, (string) $text);
    }
}

if ( ! function_exists('dd')) {
    function dd($data){
        echo '<pre style="border: solid #ff2222 1px;">';
        print_r($data);
        echo '</pre>';
        exit;
    }
}