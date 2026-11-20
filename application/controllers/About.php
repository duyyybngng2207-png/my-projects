<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
     * Trang "About us" (/about) dựng lại theo template Travelix gốc
     * (styles/about_styles.css + js/about_custom.js).
     *
     * @property Continents_model $Continents_model
     * @property CI_DB_query_builder $db
     */
    class About extends Site_Controller
    {
        /**
         * Khối "stats" so sánh 2 năm: thanh ở giữa tự tính % tăng/giảm từ x (năm trước) -> y (năm nay)
         * (xem initStats() trong js/about_custom.js).
         * LƯU Ý: số liệu mẫu - DB chưa có dữ liệu theo năm, sửa lại cho đúng thực tế.
         */
        private $stats = [
            'years' => ['last' => 2025, 'new' => 2026],
            'items' => [
                ['icon' => 'stats_1.png', 'type' => 'Travelers', 'x' => 1642, 'y' => 3527, 'color' => '#fa9e1b'],
                ['icon' => 'stats_2.png', 'type' => 'Bookings',  'x' => 1128, 'y' => 2147, 'color' => '#31124b'],
                ['icon' => 'stats_3.png', 'type' => 'Reviews',   'x' => 864,  'y' => 1530, 'color' => '#fa9e1b'],
                ['icon' => 'stats_4.png', 'type' => 'Partners',  'x' => 312,  'y' => 276,  'color' => '#31124b'],
            ],
        ];

        public function index()
        {
            $this->load->model('Continents_model');

            // Dải quảng cáo: ảnh gốc add.jpg là biển đảo châu Á -> trỏ tới châu Á (không có thì châu lục đầu tiên có giá)
            $promo = null;
            foreach ($this->Continents_model->getContinentsWithStats() as $continent) {
                // Bỏ châu lục chưa có giá khách sạn (không có gì để ghi "From $...")
                if ($continent->min_price === null) {
                    continue;
                }
                // Gặp châu Á -> chọn luôn, dừng vòng lặp
                if ($continent->slug === 'asia') {
                    $promo = $continent;
                    break;
                }
                // Chưa gặp châu Á -> tạm giữ châu lục có giá đầu tiên làm dự phòng
                $promo = $promo ?: $continent;
            }

            // Milestones: số thật trong DB
            $data['milestones'] = [
                ['icon' => 'milestone_1.png', 'value' => $this->db->count_all('continents'), 'text' => 'Continents'],
                ['icon' => 'milestone_2.png', 'value' => $this->db->count_all('countries'),  'text' => 'Countries'],
                ['icon' => 'milestone_3.png', 'value' => $this->db->count_all('places'),     'text' => 'Destinations'],
                ['icon' => 'milestone_4.png', 'value' => $this->db->count_all('hotels'),     'text' => 'Hotels'],
            ];
            // Số liệu mẫu khai báo ở đầu class (xem $stats)
            $data['stats'] = $this->stats;
            $data['promo'] = $promo;

            $data['page_css'] = ['styles/about_styles.css', 'styles/about_responsive.css'];
            // Thứ tự giống template gốc: GSAP + ScrollMagic cho bộ đếm milestones, parallax cho banner
            $data['page_js'] = [
                'plugins/greensock/TweenMax.min.js',
                'plugins/greensock/TimelineMax.min.js',
                'plugins/scrollmagic/ScrollMagic.min.js',
                'plugins/greensock/animation.gsap.min.js',
                'plugins/greensock/ScrollToPlugin.min.js',
                'plugins/parallax-js-master/parallax.min.js',
                'js/about_custom.js',
            ];

            $this->load->view('site/partials/header', $data);
            $this->load->view('site/about', $data);
            $this->load->view('site/partials/footer', $data);
        }
    }
