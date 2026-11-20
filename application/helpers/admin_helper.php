<?php defined('BASEPATH') OR exit('No direct script access allowed');

// Helper chỉ dùng cho trang quản trị. Được nạp trong Admin_Controller::__construct()
// ($this->load->helper('admin')) nên mọi controller / view admin gọi được các hàm dưới đây.

// function_exists: tránh lỗi "khai báo hàm 2 lần" nếu helper bị nạp lại
if (!function_exists('admin_pagination_config')) {
    /**
     * Cấu hình CI_Pagination theo giao diện Bootstrap 5 của trang admin.
     */
    // Dùng trong controller admin: $this->pagination->initialize(admin_pagination_config(...))
    //   $base_url    : link gốc, vd base_url('admin/places') -> trang 2 là admin/places/10
    //   $total_rows  : tổng số bản ghi (để tính số trang)
    //   $per_page    : số bản ghi mỗi trang
    //   $uri_segment : vị trí đoạn URL chứa offset (admin/places/<offset> -> đoạn thứ 3)
    function admin_pagination_config($base_url, $total_rows, $per_page, $uri_segment = 3)
    {
        return [
            'base_url'        => $base_url,
            'total_rows'      => (int) $total_rows,
            'per_page'        => (int) $per_page,
            'uri_segment'     => $uri_segment,
            // Các thẻ HTML bao quanh link phân trang -> class của Bootstrap 5
            'full_tag_open'   => '<ul class="pagination mb-0">',
            'full_tag_close'  => '</ul>',
            'num_tag_open'    => '<li class="page-item">',                                // số trang thường
            'num_tag_close'   => '</li>',
            'cur_tag_open'    => '<li class="page-item active"><span class="page-link">', // trang hiện tại
            'cur_tag_close'   => '</span></li>',
            'next_tag_open'   => '<li class="page-item">',                                // nút ">"
            'next_tag_close'  => '</li>',
            'prev_tag_open'   => '<li class="page-item">',                                // nút "<"
            'prev_tag_close'  => '</li>',
            'first_tag_open'  => '<li class="page-item">',                                // nút "trang đầu"
            'first_tag_close' => '</li>',
            'last_tag_open'   => '<li class="page-item">',                                // nút "trang cuối"
            'last_tag_close'  => '</li>',
            'attributes'      => ['class' => 'page-link'],                                // class cho thẻ <a>
        ];
    }
}

if (!function_exists('admin_source_badge')) {
    /**
     * Nhãn nguồn dữ liệu trong bảng danh sách: "Crawler" (có link gốc) hoặc "Thủ công".
     */
    function admin_source_badge($crawler_href)
    {
        // Có link nguồn -> bản ghi do crawler tạo: hiện tên miền nguồn, bấm vào mở trang gốc
        if (!empty($crawler_href)) {
            // Lấy tên miền từ link (vd www.kayak.com), không lấy được thì ghi "Crawler"
            $host = parse_url($crawler_href, PHP_URL_HOST) ?: 'Crawler';
            return '<a href="' . html_escape($crawler_href) . '" target="_blank" rel="noopener noreferrer" '
                 . 'class="badge bg-info text-decoration-none" title="' . html_escape($crawler_href) . '">'
                 . html_escape(preg_replace('/^(www|en|vi)\./', '', $host)) . '</a>';   // bỏ "www." cho gọn
        }
        // Không có link nguồn -> admin tự nhập tay
        return '<span class="badge bg-secondary">Thủ công</span>';
    }
}
