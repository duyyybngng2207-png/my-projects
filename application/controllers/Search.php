<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * @property CI_Input  $input
 * @property CI_Output $output
 * @property Places_model $Places_model
 * @property Posts_model  $Posts_model
 * @property Hotels_model $Hotels_model
 * @property Categories_model $Categories_model
 * @property Rate_limiter $rate_limiter
 * @property CI_Cache $cache
 */
class Search extends Site_Controller
{
    public function suggest()
    {
        // Chống spam: tối đa 30 request / 10 giây cho mỗi IP
        $this->load->library('rate_limiter');
        if (!$this->rate_limiter->attempt('search_suggest', 30, 10)) {
            return $this->rate_limiter->deny_json(['items' => []]);
        }

        $q = mb_substr(trim((string) $this->input->get('q', TRUE)), 0, 100);
        $items = [];

        // Cùng một từ khoá trong 60 giây thì lấy kết quả đã lưu, không truy vấn DB lại
        $this->load->driver('cache', ['adapter' => 'file']);
        $cache_key = 'suggest_' . md5(mb_strtolower($q));
        $cached    = mb_strlen($q) >= 2 ? $this->cache->get($cache_key) : FALSE;

        if ($cached !== FALSE) {
            $items = $cached;
        } elseif (mb_strlen($q) >= 2) {
            $this->load->model('Places_model');
            $this->load->model('Posts_model');

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
            $this->cache->save($cache_key, $items, 60);
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['items' => $items]));
    }

    public function index()
    {
        $q    = trim((string) $this->input->get('q', TRUE));
        $type = (string) $this->input->get('type', TRUE);
        if (!in_array($type, ['places', 'hotels', 'news', 'categories'], TRUE)) {
            $type = 'places';
        }

        $items = [];
        if (mb_strlen($q) >= 2) {
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
                case 'categories':
                    $this->load->model('Categories_model');
                    foreach ($this->Categories_model->searchByKeyword($q, 20) as $r) {
                        $items[] = [
                            'title' => $r->title,
                            'text'  => $r->description,
                            'price' => null,
                            'url'   => category_url($r),
                            'image' => '',
                        ];
                    }
                    break;
            }
        }

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