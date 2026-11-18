<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('admin_pagination_config')) {
    /**
     * Cấu hình CI_Pagination theo giao diện Bootstrap 5 của trang admin.
     */
    function admin_pagination_config($base_url, $total_rows, $per_page, $uri_segment = 3)
    {
        return [
            'base_url'        => $base_url,
            'total_rows'      => (int) $total_rows,
            'per_page'        => (int) $per_page,
            'uri_segment'     => $uri_segment,
            'full_tag_open'   => '<ul class="pagination mb-0">',
            'full_tag_close'  => '</ul>',
            'num_tag_open'    => '<li class="page-item">',
            'num_tag_close'   => '</li>',
            'cur_tag_open'    => '<li class="page-item active"><span class="page-link">',
            'cur_tag_close'   => '</span></li>',
            'next_tag_open'   => '<li class="page-item">',
            'next_tag_close'  => '</li>',
            'prev_tag_open'   => '<li class="page-item">',
            'prev_tag_close'  => '</li>',
            'first_tag_open'  => '<li class="page-item">',
            'first_tag_close' => '</li>',
            'last_tag_open'   => '<li class="page-item">',
            'last_tag_close'  => '</li>',
            'attributes'      => ['class' => 'page-link'],
        ];
    }
}

if (!function_exists('admin_source_badge')) {
    /**
     * Nhãn nguồn dữ liệu trong bảng danh sách: "Crawler" (có link gốc) hoặc "Thủ công".
     */
    function admin_source_badge($crawler_href)
    {
        if (!empty($crawler_href)) {
            $host = parse_url($crawler_href, PHP_URL_HOST) ?: 'Crawler';
            return '<a href="' . html_escape($crawler_href) . '" target="_blank" rel="noopener noreferrer" '
                 . 'class="badge bg-info text-decoration-none" title="' . html_escape($crawler_href) . '">'
                 . html_escape(preg_replace('/^(www|en|vi)\./', '', $host)) . '</a>';
        }
        return '<span class="badge bg-secondary">Thủ công</span>';
    }
}
