<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * @property CI_Input  $input
 * @property CI_Output $output
 * @property Places_model $Places_model
 * @property Posts_model  $Posts_model
 * @property Hotels_model $Hotels_model
 * @property Countries_model $Countries_model
 * @property Rate_limiter $rate_limiter
 * @property CI_Cache $cache
 */
// Tìm kiếm trên site:
//   /search/suggest?q=...         -> suggest(): gợi ý khi đang gõ (AJAX từ js/site.js, trả JSON)
//   /search?q=...&type=hotels     -> index(): trang kết quả đầy đủ theo từng loại
class Search extends Site_Controller
{
    public function suggest()
    {
        // Chống spam: tối đa 30 request / 10 giây cho mỗi IP
        $this->load->library('rate_limiter');
        if (!$this->rate_limiter->attempt('search_suggest', 30, 10)) {
            return $this->rate_limiter->deny_json(['items' => []]);
        }

        // Từ khoá: bỏ khoảng trắng 2 đầu, cắt tối đa 100 ký tự
        $q = mb_substr(trim((string) $this->input->get('q', TRUE)), 0, 100);
        $items = [];

        // Cùng một từ khoá trong 60 giây thì lấy kết quả đã lưu, không truy vấn DB lại
        $this->load->driver('cache', ['adapter' => 'file']);       // cache lưu file trong application/cache
        $cache_key = 'suggest_' . md5(mb_strtolower($q));
        // Từ khoá dưới 2 ký tự -> không tìm (quá chung chung)
        $cached    = mb_strlen($q) >= 2 ? $this->cache->get($cache_key) : FALSE;

        if ($cached !== FALSE) {
            // Có trong cache -> dùng luôn
            $items = $cached;
        } elseif (mb_strlen($q) >= 2) {
            // Chưa có -> tìm trong DB: quốc gia, địa điểm, bài viết
            $this->load->model('Places_model');
            $this->load->model('Posts_model');

            // Mỗi kết quả là 1 mảng {type, title, url, image} -> JS dựng thành danh sách gợi ý
            foreach ($this->Countries_model->searchByKeyword($q, 3) as $c) {
                $items[] = [
                    'type'  => 'Country',
                    'title' => $c->name,
                    'url'   => country_url($c),
                    'image' => upload_url('countries', $c->image),
                ];
            }
            foreach ($this->Places_model->searchByKeyword($q) as $p) {
                $items[] = [
                    'type'  => 'Place',
                    'title' => $p->name,
                    'url' => place_url($p),
                    'image' => !empty($p->image) ? base_url('uploads/places/' . $p->image) : '',
                ];
            }
            foreach ($this->Posts_model->searchPublished($q) as $a) {
                $items[] = [
                    'type'  => 'News',
                    'title' => $a->title,
                    'url'   => post_url($a),
                    'image' => !empty($a->image) ? base_url('uploads/posts/' . $a->image) : '',
                ];
            }
            // Lưu cache 60 giây
            $this->cache->save($cache_key, $items, 60);
        }

        // Trả JSON {items: [...]}
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['items' => $items]));
    }

    public function index()
    {
        $q    = trim((string) $this->input->get('q', TRUE));
        // Loại kết quả (tab trên trang): chỉ nhận 4 giá trị, sai thì mặc định 'places'
        $type = (string) $this->input->get('type', TRUE);
        if (!in_array($type, ['places', 'hotels', 'news', 'countries'], TRUE)) {
            $type = 'places';
        }

        $items = [];
        if (mb_strlen($q) >= 2) {
            // Mỗi loại tìm ở 1 bảng, rồi đổi về cùng 1 dạng {title, text, price, url, image} để view in chung
            switch ($type) {
                case 'places':
                    $this->load->model('Places_model');
                    foreach ($this->Places_model->searchByKeyword($q, 20) as $r) {
                        $items[] = [
                            'title' => $r->name,
                            'text'  => $r->address,
                            'price' => null,   // địa điểm không có giá
                            'url'   => place_url($r),
                            'image' => !empty($r->image) ? base_url('uploads/places/' . $r->image) : '',
                        ];
                    }
                    break;
                case 'hotels':
                    $this->load->model('Hotels_model');
                    foreach ($this->Hotels_model->searchByKeyword($q, 20) as $r) {
                        $items[] = [
                            'title' => $r->name,
                            'text'  => $r->address,
                            'price' => $r->price,
                            'url'   => hotel_url($r),
                            'image' => hotel_image_url($r, 'images/trend_1.png'),
                        ];
                    }
                    break;
                case 'news':
                    $this->load->model('Posts_model');
                    foreach ($this->Posts_model->searchPublished($q, 20) as $r) {
                        $items[] = [
                            'title' => $r->title,
                            'text'  => '',
                            'price' => null,
                            'url'   => post_url($r),
                            'image' => !empty($r->image) ? base_url('uploads/posts/' . $r->image) : '',
                        ];
                    }
                    break;
                case 'countries':
                    // Countries_model đã nạp sẵn ở Site_Controller
                    foreach ($this->Countries_model->searchByKeyword($q, 20) as $r) {
                        $items[] = [
                            'title' => $r->name,
                            'text'  => (string) $r->continent_name,
                            'price' => null,
                            'url'   => country_url($r),
                            'image' => upload_url('countries', $r->image),
                        ];
                    }
                    break;
            }
        }

        // Dữ liệu cho view search.php + CSS/JS của trang
        $data = [
            'q'        => $q,
            'type'     => $type,
            'items'    => $items,
            'page_css' => ['plugins/colorbox/colorbox.css','styles/single_listing_styles.css','styles/single_listing_responsive.css'],
            'page_js'  => ['plugins/parallax-js-master/parallax.min.js','plugins/colorbox/jquery.colorbox-min.js','js/single_listing_custom.js'],
        ];

        $this->load->view('site/partials/header', $data);
        $this->load->view('site/search', $data);
        $this->load->view('site/partials/footer', $data);
    }
}
