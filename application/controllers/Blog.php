<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property Posts_model $Posts_model
 * @property CI_Pagination $pagination
 */
// Trang tin tức công khai:
//   /news, /news/page/2  -> index($page): danh sách bài đã đăng, 6 bài / trang
//   /news/<slug>         -> detail($slug): chi tiết 1 bài
class Blog extends Site_Controller
{
    public function index($page = 1)
    {
        $this->load->model('Posts_model');
        $this->load->library('pagination');

        $page        = (int) $page;
        $per_page    = 6;
        $total       = $this->Posts_model->countPublishedPosts();
        // Tổng số trang (ít nhất 1 trang kể cả khi chưa có bài)
        $total_pages = max(1, (int) ceil($total / $per_page));

        // Số trang không hợp lệ (vd /news/page/99) -> 404
        if ($page < 1 || $page > $total_pages) {
            show_404();
        }

        // Cấu hình phân trang theo SỐ TRANG (1, 2, 3) thay vì offset như trang admin
        $config['base_url']         = base_url('news/page');   // trang 2 -> news/page/2
        $config['first_url']        = base_url('news');        // trang 1 -> news (không có /page/1)
        $config['total_rows']       = $total;
        $config['per_page']         = $per_page;
        $config['uri_segment']      = 3;                       // news/page/<số trang>
        $config['use_page_numbers'] = TRUE;
        $this->pagination->initialize($config);

        // Bài của trang hiện tại: bỏ qua (trang - 1) * 6 bài đầu
        $data['posts_list']       = $this->Posts_model->getPublishedPosts($per_page, ($page - 1) * $per_page);
        // 4 bài mới nhất cho sidebar
        $data['latest_posts']     = $this->Posts_model->getPublishedPosts(4);
        $data['pagination_links'] = $this->pagination->create_links();

        // CSS / JS riêng của trang blog (template)
        $data['page_css'] = [
            'plugins/colorbox/colorbox.css',
            'styles/blog_styles.css',
            'styles/blog_responsive.css'
        ];
        $data['page_js'] = [
            'plugins/colorbox/jquery.colorbox-min.js',
            'plugins/parallax-js-master/parallax.min.js',
            'js/blog_custom.js'
        ];

        $this->load->view('site/partials/header', $data);
        $this->load->view('site/news', $data);
        $this->load->view('site/partials/footer', $data);
    }

    public function detail($slug)
    {
        $this->load->model('Posts_model');

        // Link cũ dạng news/5 (hoặc sai hoa/thường) thì 301 sang news/<slug>; bài chưa có slug vẫn xem theo id
        $slug = (string) $slug;
        // ctype_digit: chuỗi toàn chữ số -> coi là id, ngược lại là slug
        $post = ctype_digit($slug)
            ? $this->Posts_model->getPost((int) $slug)
            : $this->Posts_model->getPostBySlug($slug);

        // Không có bài hoặc bài còn là bản nháp -> 404
        if (!$post || $post->is_published != 1) {
            show_404();
        }
        // URL đang mở khác slug chuẩn -> chuyển hướng vĩnh viễn (301) về URL chuẩn (tốt cho SEO)
        if (!empty($post->slug) && $slug !== $post->slug) {
            redirect(base_url('news/' . $post->slug), 'location', 301);
        }
        $id = (int) $post->id;

        $data['post']         = $post;
        // Sidebar "Other posts": lấy 5 bài mới nhất, bỏ chính bài đang xem, giữ tối đa 4
        $others = $this->Posts_model->getPublishedPosts(5);
        $data['other_posts'] = array_slice(array_filter($others, function ($p) use ($id) { //hàm ẩn danh , viết luôn và truyền đi giá trị
            return (int) $p->id !== (int) $id;
        }), 0, 4);

        $data['page_css'] = [
            'plugins/colorbox/colorbox.css',
            'styles/blog_styles.css',
            'styles/blog_responsive.css',
            'styles/post_detail.css'
        ];
        $data['page_js'] = [
            'plugins/colorbox/jquery.colorbox-min.js',
            'plugins/parallax-js-master/parallax.min.js',
            'js/blog_custom.js'
        ];

        $this->load->view('site/partials/header', $data);
        $this->load->view('site/post_detail', $data);
        $this->load->view('site/partials/footer', $data);
    }
}
