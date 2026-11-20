<?php defined('BASEPATH') OR exit('No direct script access allowed');

// Helper xử lý đường dẫn ảnh. Được autoload (config/autoload.php) nên dùng được ở mọi controller / view.
// Quy ước: DB chỉ lưu TÊN FILE ảnh, file nằm ở uploads/<bảng>/ (uploads/places, uploads/hotels...).

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
    // Ví dụ: upload_url('places', $place->image, 'images/offer_1.jpg')
    function upload_url($folder, $file, $fallback = NULL)
    {
        // basename(): chỉ giữ tên file, bỏ mọi "../" -> không đọc được file ngoài thư mục uploads
        $file = basename(trim((string) $file));

        // Có tên file VÀ file thật sự tồn tại trên ổ đĩa -> trả URL đầy đủ
        if ($file !== '' && is_file(FCPATH . 'uploads/' . $folder . '/' . $file)) {
            return base_url('uploads/' . $folder . '/' . rawurlencode($file));
        }

        // Không có ảnh: có ảnh mặc định thì trả ảnh mặc định, không thì chuỗi rỗng (view tự ẩn thẻ <img>)
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
        // 1) Ảnh admin upload (cột image)
        $url = upload_url('hotels', $hotel->image ?? NULL);
        if ($url !== '') {
            return $url;
        }
        // 2) Ảnh đại diện crawler lưu dạng URL đầy đủ (cột thumbnail), chỉ nhận http/https
        $thumb = trim((string) ($hotel->thumbnail ?? ''));
        if ($thumb !== '' && preg_match('#^https?://#i', $thumb)) {
            return $thumb;
        }
        // 3) Ảnh mặc định của template
        return base_url($fallback);
    }
}

if (!function_exists('css_bg')) {
    /**
     * Thuộc tính style background-image đã đặt URL trong nháy đơn và escape cho HTML.
     */
    // Dùng trong view: thẻ div có style = css_bg($image) -> ảnh nền của khối
    function css_bg($url)
    {
        // Đổi ' thành %27 để URL không làm vỡ chuỗi url('...'); html_escape chống chèn mã HTML
        return "background-image:url('" . html_escape(str_replace("'", '%27', (string) $url)) . "')";
    }
}
