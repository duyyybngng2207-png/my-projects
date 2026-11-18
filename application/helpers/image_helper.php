<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('upload_url')) {
    /**
     * URL ảnh đã upload, dùng chung cho mọi view.
     *
     * - Mã hoá tên file (rawurlencode) để tên có dấu ngoặc, khoảng trắng, @, tiếng Việt...
     *   vẫn chạy được cả trong <img src> lẫn CSS background-image:url().
     * - Cột ảnh trống hoặc file không còn trên ổ đĩa thì trả về ảnh mặc định.
     *
     * @param string      $folder   Thư mục con trong uploads/, ví dụ 'places', 'hotels', 'posts'
     * @param string|null $file     Tên file lưu trong DB
     * @param string|null $fallback Ảnh mặc định trong thư mục gốc (ví dụ 'images/offer_1.jpg'); NULL = trả về ''
     */
    function upload_url($folder, $file, $fallback = NULL)
    {
        $file = basename(trim((string) $file));

        if ($file !== '' && is_file(FCPATH . 'uploads/' . $folder . '/' . $file)) {
            return base_url('uploads/' . $folder . '/' . rawurlencode($file));
        }

        return $fallback !== NULL ? base_url($fallback) : '';
    }
}

if (!function_exists('hotel_image_url')) {
    /**
     * Ảnh khách sạn: ưu tiên ảnh admin tải lên (image), không có thì dùng ảnh đại diện
     * dạng URL (thumbnail, thường lấy từ crawler), cuối cùng là ảnh mặc định.
     */
    function hotel_image_url($hotel, $fallback = 'images/trend_1.png')
    {
        $url = upload_url('hotels', $hotel->image ?? NULL);
        if ($url !== '') {
            return $url;
        }
        $thumb = trim((string) ($hotel->thumbnail ?? ''));
        if ($thumb !== '' && preg_match('#^https?://#i', $thumb)) {
            return $thumb;
        }
        return base_url($fallback);
    }
}

if (!function_exists('css_bg')) {
    /**
     * Thuộc tính style background-image đã đặt URL trong nháy đơn và escape cho HTML.
     */
    function css_bg($url)
    {
        return "background-image:url('" . html_escape(str_replace("'", '%27', (string) $url)) . "')";
    }
}
