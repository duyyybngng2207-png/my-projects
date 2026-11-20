<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
     * @property Places_model $Places_model
     * @property Bookings_model $Bookings_model
     * @property Countries_model $Countries_model
     * @property Continents_model $Continents_model
     * @property CI_Form_validation $form_validation
     * @property Hotels_model $Hotels_model
     * @property Reviews_model $Reviews_model
     * @property Rate_limiter $rate_limiter
     */
    // ======================================================================
    // CONTROLLER CHÍNH CỦA CÁC TRANG CÔNG KHAI (route trong config/routes.php):
    //   /                         -> index()      trang chủ
    //   /continent/<slug>         -> continent()  danh sách quốc gia của châu lục
    //   /country/<slug>           -> country()    danh sách thành phố của quốc gia (+ lọc AJAX)
    //   /place/<slug>             -> detail()     chi tiết thành phố
    //   /hotel/<id>-<slug>        -> hotel()      chi tiết khách sạn
    //   /book/<slug>              -> book()       form đặt lịch (+ AJAX "More hotels")
    //   /review/<id>-<slug>       -> review()     form viết đánh giá khách sạn
    //   /category/<slug>          -> category()   link cũ -> 301 về trang chủ
    // Kế thừa Site_Controller -> mọi trang đã có sẵn dữ liệu footer.
    // ======================================================================
    class Home extends Site_Controller
    {
        /** Trang đặt lịch: số khách sạn hiện lúc đầu (phần còn lại tải AJAX qua nút "More") và tối đa. */
        const BOOK_INITIAL_HOTELS = 3;
        const BOOK_MAX_HOTELS     = 50;

        // ---------------- TRANG CHỦ ----------------
        public function index()
        {
            $this->load->model('Places_model');
            $this->load->model('Hotels_model');
            $this->load->model('Reviews_model');

            // Khối "the best offers": mỗi châu lục 1 thành phố, tối đa 6
            $data['places_list'] = $this->Places_model->getHomePlaces(6);
            // Khối "Explore by continent": tất cả châu lục (chưa có thành phố thì hiện "coming soon"),
            // số quốc gia chỉ đếm quốc gia đã có thành phố để khớp với trang châu lục
            $this->load->model('Continents_model');
            $data['continents_list'] = $this->Continents_model->getContinentsWithStats(NULL, TRUE, 0, FALSE);
            // Khối "Trending now": 8 khách sạn được đánh dấu trending
            $data['trending_list'] = $this->Hotels_model->getTrendingHotels(8);
            // Slider nổi bật: 5 thành phố is_featured
            $data['featured_list'] = $this->Places_model->getFeaturedPlaces(5);
            // Khối đánh giá của khách: 5 đánh giá đã duyệt mới nhất
            $data['testimonials'] = $this->Reviews_model->getApprovedLatest(5);

            // JS riêng trang chủ (slider của template)
            $data['page_js'] = ['js/custom.js'];

            $this->load->helper('form');
            // header không nhận $data -> dùng CSS mặc định (main_styles.css)
            $this->load->view('site/partials/header');
            $this->load->view('site/home', $data);
            $this->load->view('site/partials/footer');
        }

        /**
         * Tìm địa điểm theo slug. Link cũ dạng số (place/2) hoặc sai hoa/thường
         * sẽ 301 sang slug chuẩn; bản ghi chưa có slug thì vẫn hiển thị theo id.
         */
        // Dùng chung cho detail() và book(); $prefix = 'place' hoặc 'book' (để dựng lại URL chuẩn)
        private function _find_place($key, $prefix)
        {
            $this->load->model('Places_model');
            $key = (string) $key;

            // Toàn chữ số -> tìm theo id; ngược lại tìm theo slug
            $place = ctype_digit($key)
                ? $this->Places_model->getPlace((int) $key)
                : $this->Places_model->getPlaceBySlug($key);

            if (!$place) {
                show_404();
            }
            $this->_canonical_redirect($key, $place->slug, $prefix);
            return $place;
        }

        /**
         * 301 sang URL chuẩn khi đường dẫn đang mở khác slug trong DB (chỉ với GET).
         */
        // Chỉ GET: nếu redirect cả POST thì dữ liệu form sẽ bị mất
        private function _canonical_redirect($key, $slug, $prefix)
        {
            if (!empty($slug) && $key !== $slug && $this->input->method() === 'get') {
                // Giữ query string (ví dụ book/1?hotel=3)
                $qs = (string) $this->input->server('QUERY_STRING');
                redirect(base_url($prefix . '/' . $slug) . ($qs !== '' ? '?' . $qs : ''), 'location', 301);
            }
        }

        // ---------------- CHI TIẾT THÀNH PHỐ: /place/<slug> ----------------
        public function detail($slug)
        {
            $place = $this->_find_place($slug, 'place');
            $id = $place->id;

            $data['place'] = $place;
            // CSS / JS của template trang chi tiết
            $data['page_css'] = [
                'plugins/colorbox/colorbox.css',
                'styles/single_listing_styles.css',
                'styles/single_listing_responsive.css'
            ];
            $data['page_js'] = [
                'plugins/parallax-js-master/parallax.min.js',
                'plugins/colorbox/jquery.colorbox-min.js',
                'js/single_listing_custom.js',
            ];

            // 6 khách sạn gợi ý của thành phố
            $this->load->model('Hotels_model');
            $data['hotels'] = $this->Hotels_model->getHotelsByPlace($id, 6);

            $this->load->model('Reviews_model');
            // Đánh giá viết theo khách sạn -> trang địa điểm gộp đánh giá của các khách sạn thuộc nó
            $data['reviews']      = $this->Reviews_model->getApprovedReviewsByPlace($id);
            $data['review_stats'] = $this->Reviews_model->getReviewStatsByPlace($id);

            $this->load->view('site/partials/header', $data);
            $this->load->view('site/detail', $data);
            $this->load->view('site/partials/footer', $data);
        }

        /**
         * Trang chi tiết khách sạn: hotel/<id>-<slug-tên>.
         */
        // ---------------- CHI TIẾT KHÁCH SẠN ----------------
        public function hotel($id, $slug = '')
        {
            $this->load->model('Hotels_model');

            // Lấy khách sạn kèm thành phố, quốc gia, châu lục
            $hotel = $this->Hotels_model->getHotelDetail((int) $id);
            if (!$hotel) {
                show_404();
            }

            // Thiếu hoặc sai phần tên (ví dụ đã đổi tên khách sạn) thì 301 về URL chuẩn
            $current = 'hotel/' . $id . ($slug !== '' ? '-' . $slug : '');
            if ($current !== hotel_path($hotel) && $this->input->method() === 'get') {
                redirect(hotel_url($hotel), 'location', 301);
            }

            // Thông tin địa điểm theo dạng object có slug/id để dùng với place_url()/book_url()
            $place = null;
            if (!empty($hotel->place_id)) {
                $place = (object) [
                    'id'      => $hotel->place_id,
                    'slug'    => $hotel->place_slug,
                    'name'    => $hotel->place_name,
                    'address' => $hotel->place_address,
                ];
            }

            // Châu lục + quốc gia của thành phố (breadcrumb Châu lục > Quốc gia > Thành phố > Khách sạn)
            $country = !empty($hotel->country_slug)
                ? (object) ['slug' => $hotel->country_slug, 'name' => $hotel->country_name]
                : null;
            $continent = !empty($hotel->continent_slug)
                ? (object) ['slug' => $hotel->continent_slug, 'name' => $hotel->continent_name]
                : null;

            $data['hotel']        = $hotel;
            $data['place']        = $place;
            $data['country']      = $country;
            $data['continent']    = $continent;
            // "Khách sạn khác cùng thành phố": 4 khách sạn, bỏ chính khách sạn đang xem
            $data['other_hotels'] = $place ? $this->Hotels_model->getHotelsByPlace($place->id, 4, $hotel->id) : [];

            // Đánh giá đã duyệt của khách sạn + thống kê
            $this->load->model('Reviews_model');
            $data['reviews']      = $this->Reviews_model->getApprovedReviewsByHotel($hotel->id);
            $data['review_stats'] = $this->Reviews_model->getReviewStatsByHotel($hotel->id);

            // Sửa mã unicode dạng chữ còn sót trong dữ liệu crawl (vd à -> à)
            $hotel->description = decode_unicode_escapes($hotel->description);

            // Các cột JSON (albums / amenities / faqs) -> mảng để view hiển thị
            $data['albums']    = $this->_hotel_albums($hotel);
            $data['amenities'] = $this->_json_list($hotel->amenities);
            // Tổng hợp đánh giá trên KAYAK (object JSON, không phải danh sách)
            $insights = json_decode((string) ($hotel->guest_insights ?? ''), TRUE);
            $data['insights'] = is_array($insights) ? $insights : [];
            // FAQ: chỉ giữ mục có câu hỏi, sửa mã unicode sót
            $data['faqs']      = [];
            foreach ($this->_json_list($hotel->faqs) as $faq) {
                if (is_array($faq) && trim((string) ($faq['question'] ?? '')) !== '') {
                    $data['faqs'][] = [
                        'question' => trim(decode_unicode_escapes((string) $faq['question'])),
                        'answer'   => trim(decode_unicode_escapes((string) ($faq['answer'] ?? ''))),
                    ];
                }
            }

            $data['page_css'] = [
                'plugins/colorbox/colorbox.css',
                'styles/single_listing_styles.css',
                'styles/single_listing_responsive.css'
            ];
            $data['page_js'] = [
                'plugins/parallax-js-master/parallax.min.js',
                'plugins/colorbox/jquery.colorbox-min.js',
                'js/single_listing_custom.js'
            ];

            $this->load->view('site/partials/header', $data);
            $this->load->view('site/hotel_detail', $data);
            $this->load->view('site/partials/footer', $data);
        }

        /** Giải mã cột JSON dạng danh sách; dữ liệu rỗng/hỏng trả về mảng rỗng. */
        private function _json_list($json)
        {
            // TRUE = trả về mảng PHP thay vì object
            $list = json_decode((string) $json, TRUE);
            return is_array($list) ? array_values($list) : [];
        }

        /**
         * Album ảnh khách sạn: chỉ giữ URL http(s) hợp lệ, bỏ trùng, tối đa 12 ảnh.
         * Phần tử có thể là chuỗi URL hoặc object {url: ...} tuỳ nguồn dữ liệu.
         */
        private function _hotel_albums($hotel)
        {
            $urls = [];
            foreach ($this->_json_list($hotel->albums) as $item) {
                // Crawler lưu {url, caption}, admin nhập tay lưu chuỗi URL -> lấy URL cho cả 2 kiểu
                $url = is_array($item) ? ($item['url'] ?? ($item['contentUrl'] ?? '')) : $item;
                $url = trim((string) $url);
                if ($url !== '' && preg_match('#^https?://#i', $url) && !in_array($url, $urls, TRUE)) {
                    $urls[] = $url;
                }
            }
            return array_slice($urls, 0, 12);
        }

        // ---------------- ĐẶT LỊCH: /book/<slug> ----------------
        // LUỒNG ĐẶT LỊCH:
        //   1. GET: hiện form (3 khách sạn đầu + nút "More")
        //   2. AJAX GET ?more=1: trả thêm các khách sạn còn lại (_book_more_hotels)
        //   3. POST: kiểm tra form -> tính tiền -> lưu đơn trạng thái Pending -> báo thành công
        //   4. Admin xác nhận đơn trong /admin/bookings (Pending -> Confirmed)
        public function book($slug)
        {
            $this->load->model('Bookings_model');
            $this->load->model('Hotels_model');
            $data['place'] = $this->_find_place($slug, 'book');
            $id = (int) $data['place']->id;

            // AJAX nút "More": trả về các khách sạn còn lại (trừ những khách sạn đã hiện trên form)
            if ($this->input->is_ajax_request() && $this->input->get('more')) {
                return $this->_book_more_hotels($id);
            }

            // Khách sạn của địa điểm để khách chọn đặt kèm: lúc đầu chỉ hiện BOOK_INITIAL_HOTELS khách sạn,
            // phần còn lại tải bằng AJAX khi bấm "More". ?hotel=<id> sẽ chọn sẵn.
            $data['selected_hotel'] = (int) $this->input->get('hotel');
            $data['hotels']         = $this->Hotels_model->getHotelsByPlace($id, self::BOOK_INITIAL_HOTELS);
            $data['hotels_total']   = min($this->Hotels_model->countByPlace($id), self::BOOK_MAX_HOTELS);

            // Khách sạn đang được chọn (vừa gửi form bị lỗi validate, hoặc ?hotel=) mà không nằm trong
            // 3 khách sạn đầu -> vẫn hiện để không mất lựa chọn của khách
            $chosen = (int) ($this->input->post('hotel_id') ?: $data['selected_hotel']);
            $shown  = array_map(function ($h) { return (int) $h->id; }, $data['hotels']);
            if ($chosen > 0 && !in_array($chosen, $shown, TRUE)) {
                $extra = $this->Hotels_model->getHotelOfPlace($chosen, $id);
                if ($extra) {
                    $data['hotels'][] = $extra;
                }
            }

            // Khách bấm "Submit booking request"
            if ($this->input->method() == 'post') {
                $this->load->library('form_validation');
                $this->form_validation->set_error_delimiters('<div class="text-danger">', '</div>');
                $this->form_validation->set_rules('guest_name', 'Name', 'trim|required|max_length[100]');
                $this->form_validation->set_rules('guest_email', 'Email', 'trim|required|valid_email|max_length[100]');
                // Số điện thoại Việt Nam: bắt đầu bằng 0 hoặc +84, theo sau 9 chữ số
                $this->form_validation->set_rules('guest_phone', 'Phone', [
                    'trim',
                    'required',
                    'regex_match[/^(0|\+84)[0-9]{9}$/]'
                ]);
                // Ngày đi phải từ hôm nay trở đi (hàm _future_date)
                $this->form_validation->set_rules('booking_date', 'Booking date', 'required|callback__future_date');
                // Khách sạn phải thuộc thành phố này (hàm _valid_hotel, truyền thêm id thành phố)
                $this->form_validation->set_rules('hotel_id', 'Hotel', 'callback__valid_hotel[' . $id . ']');

                // Có chọn khách sạn -> bắt buộc nhập số đêm (1..30)
                $hotel_id = (int) $this->input->post('hotel_id');
                if ($hotel_id > 0) {
                    $this->form_validation->set_rules('nights', 'Hotel nights',
                        'required|is_natural_no_zero|less_than_equal_to[' . Bookings_model::MAX_NIGHTS . ']');
                }

                if ($this->form_validation->run() == TRUE) {
                    // Chốt giá tại thời điểm đặt: giá khách sạn × số đêm (địa điểm không có giá)
                    // Giá lấy từ DB, KHÔNG lấy từ form -> khách không sửa được giá
                    $hotel = $hotel_id > 0 ? $this->Hotels_model->getHotel($hotel_id) : NULL;
                    $price = Bookings_model::calculatePrice(
                        $hotel ? $hotel->price : NULL,
                        $hotel ? $this->input->post('nights') : 1
                    );

                    // Gộp thông tin khách + tiền (nights, hotel_price, total_price) thành 1 dòng để lưu
                    $booking = array_merge([
                        'place_id'     => $id,
                        'hotel_id'     => $hotel ? (int) $hotel->id : NULL,     // NULL = chỉ đặt tour
                        'guest_name'   => $this->input->post('guest_name', TRUE),
                        'guest_email'  => $this->input->post('guest_email', TRUE),
                        'guest_phone'  => $this->input->post('guest_phone', TRUE),
                        'booking_date' => $this->input->post('booking_date', TRUE),
                        'is_status'    => Bookings_model::STATUS_PENDING,        // chờ admin xác nhận
                        'created_at'   => date('Y-m-d H:i:s')
                    ], $price);
                    $this->Bookings_model->insertBooking($booking);
                    // Báo thành công rồi tải lại trang (redirect sau POST -> F5 không gửi trùng đơn)
                    $this->session->set_flashdata('success', 'Thank you! Your booking request has been sent and we will contact you soon. Estimated total: $' . number_format($price['total_price']) . '.');
                    redirect(book_url($data['place']));
                }

            }

            $data['page_css'] = [
                'plugins/colorbox/colorbox.css',
                'styles/single_listing_styles.css',
                'styles/single_listing_responsive.css'
            ];
            $data['page_js'] = [
                'plugins/parallax-js-master/parallax.min.js',
                'plugins/colorbox/jquery.colorbox-min.js',
                'js/single_listing_custom.js'
            ];

            // JS nút "More hotels"
            $data['page_js'][] = 'js/booking_hotels.js';

            $this->load->view('site/partials/header', $data);
            $this->load->view('site/booking', $data);
            $this->load->view('site/partials/footer', $data);
        }

        /**
         * AJAX GET book/<slug>?more=1&exclude=1,2,3 -> JSON { html, count }:
         * các khách sạn còn lại của địa điểm (bỏ id đã hiện), render bằng cùng partial với lúc tải trang.
         */
        private function _book_more_hotels($place_id)
        {
            // Chống spam: tối đa 20 lần / 10 giây cho mỗi IP
            $this->load->library('rate_limiter');
            if (!$this->rate_limiter->attempt('book_more_hotels', 20, 10)) {
                return $this->rate_limiter->deny_json();
            }

            // "1,2,3" -> [1, 2, 3]; bỏ giá trị rác; giới hạn số phần tử
            $exclude = array_slice(array_filter(array_map('intval', explode(',', (string) $this->input->get('exclude')))), 0, self::BOOK_MAX_HOTELS);
            $hotels  = $this->Hotels_model->getHotelsByPlace($place_id, max(0, self::BOOK_MAX_HOTELS - count($exclude)), $exclude ?: 0);

            $this->output
                ->set_header('Vary: X-Requested-With')     // trình duyệt không lẫn cache JSON với trang HTML
                ->set_header('Cache-Control: no-store')
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'count' => count($hotels),
                    // load->view(..., TRUE): trả về chuỗi HTML thay vì in ra -> gửi trong JSON
                    'html'  => $this->load->view('site/partials/booking_hotel_options', [
                        'hotels'        => $hotels,
                        'current_hotel' => '', // khách sạn đang chọn luôn nằm trong phần đã hiện
                    ], TRUE),
                ]));
        }

        /**
         * hotel_id để trống = chỉ đặt tour; có giá trị thì phải là khách sạn thuộc địa điểm này.
         */
        // Luật callback__valid_hotel[<id thành phố>] -> $place_id nhận giá trị trong ngoặc vuông
        public function _valid_hotel($hotel_id, $place_id)
        {
            $hotel_id = (int) $hotel_id;
            if ($hotel_id === 0 || $this->Hotels_model->belongsToPlace($hotel_id, $place_id)) {
                return TRUE;
            }
            $this->form_validation->set_message('_valid_hotel', 'The selected hotel does not belong to this location.');
            return FALSE;
        }

        // Ngày đi: đúng định dạng YYYY-MM-DD và không trước hôm nay
        public function _future_date($date)
        {
            // Parse rồi format lại: nếu khác chuỗi gốc thì ngày không hợp lệ (vd 2026-02-31)
            $d = DateTime::createFromFormat('Y-m-d', $date);
            $valid = $d && $d->format('Y-m-d') === $date;

            // So sánh chuỗi YYYY-MM-DD được vì cùng định dạng
            if (!$valid || $date < date('Y-m-d')) {
                $this->form_validation->set_message('_future_date', 'The booking date must be a valid date from today onwards.');
                return FALSE;
            }
            return TRUE;
        }

        /**
         * Đọc bộ lọc trang quốc gia từ query string, chỉ nhận giá trị hợp lệ.
         */
        // ?name=asc|desc&stars=1..5 -> giá trị lạ bị bỏ (không đưa thẳng vào SQL)
        private function _place_filters()
        {
            $dir = function ($v) {
                $v = strtolower((string) $v);
                return in_array($v, ['asc', 'desc'], TRUE) ? $v : '';
            };
            $stars = (int) $this->input->get('stars');

            return [
                'name'  => $dir($this->input->get('name')),
                'stars' => ($stars >= 1 && $stars <= 5) ? $stars : 0,
            ];
        }

        /**
         * Trang vùng cũ (category/<slug>) đã thay bằng trang quốc gia -> 301 về trang chủ
         * để link cũ / kết quả tìm kiếm không bị 404.
         */
        public function category($slug = '')
        {
            redirect(base_url(), 'location', 301);
        }

        /**
         * Trang châu lục: continent/<slug> -> danh sách quốc gia đã có thành phố.
         */
        public function continent($slug)
        {
            $this->load->model('Continents_model');

            $slug = (string) $slug;
            $continent = ctype_digit($slug)
                ? $this->Continents_model->getContinent((int) $slug)
                : $this->Continents_model->getContinentBySlug($slug);

            if (!$continent) {
                show_404();
            }
            $this->_canonical_redirect($slug, $continent->slug, 'continent');

            $data['continent']      = $continent;
            // Quốc gia của châu lục (chỉ quốc gia đã có thành phố), kèm số thành phố + giá từ
            $data['countries_list'] = $this->Countries_model->getCountriesWithStats(NULL, TRUE, 0, $continent->id);

            $data['page_css'] = [
                'styles/offers_styles.css',
                'styles/offers_responsive.css'
            ];
            $data['page_js'] = [
                'plugins/parallax-js-master/parallax.min.js',
                'js/offers_custom.js',
            ];

            $this->load->view('site/partials/header', $data);
            $this->load->view('site/continent', $data);
            $this->load->view('site/partials/footer', $data);
        }

        /**
         * Trang quốc gia: country/<slug> -> danh sách thành phố (lọc AJAX theo tên / sao).
         */
        // Cùng 1 URL phục vụ 2 kiểu: mở trang (HTML đầy đủ) và lọc AJAX (JSON {total, html})
        public function country($slug)
        {
            $this->load->model('Places_model');

            $slug = (string) $slug;
            $country = ctype_digit($slug)
                ? $this->Countries_model->getCountry((int) $slug)
                : $this->Countries_model->getCountryBySlug($slug);

            if (!$country) {
                show_404();
            }
            $this->_canonical_redirect($slug, $country->slug, 'country');

            // Đọc bộ lọc từ URL rồi lấy danh sách thành phố
            $filters = $this->_place_filters();
            $places  = $this->Places_model->getPlacesByCountry($country->id, $filters);

            // AJAX (bộ lọc giá / tên / sao): chỉ trả về danh sách đã render
            if ($this->input->is_ajax_request()) {
                // Chống spam: tối đa 20 lần lọc / 10 giây cho mỗi IP
                $this->load->library('rate_limiter');
                if (!$this->rate_limiter->attempt('category_filter', 20, 10)) {
                    return $this->rate_limiter->deny_json();
                }

                $this->output
                    ->set_header('Vary: X-Requested-With')
                    ->set_header('Cache-Control: no-store')
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'total' => count($places),
                        'html'  => $this->load->view('site/partials/category_items', ['places_list' => $places], TRUE),
                    ]));
                return;
            }

            $data['country']     = $country;
            $data['places_list'] = $places;
            $data['filters']     = $filters;      // để view đánh dấu bộ lọc đang chọn
            // Phân biệt "quốc gia chưa có thành phố" với "lọc không ra kết quả"
            $data['has_places']  = $this->Places_model->countByCountry($country->id) > 0;

            $data['page_css'] = [
                'styles/offers_styles.css',
                'styles/offers_responsive.css'
            ];
            $data['page_js'] = [
                'plugins/parallax-js-master/parallax.min.js',
                'js/offers_custom.js',
                'js/category_filter.js'          // JS bộ lọc AJAX
            ];

            $this->load->view('site/partials/header', $data);
            $this->load->view('site/country', $data);
            $this->load->view('site/partials/footer', $data);
        }

        /**
         * Form viết đánh giá cho khách sạn: review/<id>-<slug-tên>.
         */
        // LUỒNG: khách nhập email -> kiểm tra email có đơn "Completed" ở khách sạn này và chưa đánh giá
        //        -> lưu với is_approved = 0 -> chờ admin duyệt
        public function review($id, $slug = '')
        {
            $this->load->model('Bookings_model');
            $this->load->model('Reviews_model');
            $this->load->model('Hotels_model');

            $hotel = $this->Hotels_model->getHotelDetail((int) $id);
            if (!$hotel) {
                show_404();
            }
            $id = (int) $hotel->id;

            // Thiếu hoặc sai phần tên thì 301 về URL chuẩn (giống trang khách sạn)
            $current = 'review/' . $id . ($slug !== '' ? '-' . $slug : '');
            if ($current !== substr(review_url($hotel), strlen(base_url())) && $this->input->method() === 'get') {
                redirect(review_url($hotel), 'location', 301);
            }
            $data['hotel'] = $hotel;

            // Booking đã đến ngày đi -> "Đã hoàn thành" trước khi kiểm tra quyền viết review
            $this->Bookings_model->autoCompletePastBookings();

            if ($this->input->method() == 'post') {

                // Bẫy chống bot: người thật không thấy ô này nên để trống
                // (bot tự điền mọi ô -> giả vờ thành công nhưng không lưu gì)
                if (trim((string) $this->input->post('website')) !== '') {
                    $this->session->set_flashdata('success', 'Thank you! Your review will be published after it has been approved.');
                    redirect(review_url($hotel));
                }

                $this->load->library('form_validation');
                $this->form_validation->set_error_delimiters('<div class="text-danger">', '</div>');
                $this->form_validation->set_rules('name', 'Full name', 'trim|required|max_length[100]');
                // callback__can_review[<id khách sạn>] -> kiểm tra quyền viết đánh giá
                $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[255]|callback__can_review[' . (int) $id . ']');
                $this->form_validation->set_rules('rating', 'Rating', 'required|in_list[1,2,3,4,5]');
                $this->form_validation->set_rules('content', 'Review', 'trim|required|min_length[10]|max_length[1000]');

                if ($this->form_validation->run() == TRUE) {
                    $data = [
                        'hotel_id'    => $id,
                        'name'        => $this->input->post('name', TRUE),
                        'email'       => strtolower(trim($this->input->post('email', TRUE))),   // viết thường để so trùng
                        'content'     => $this->input->post('content', TRUE),
                        'rating'      => (int) $this->input->post('rating'),
                        'is_approved' => 0,                                                    // chờ duyệt
                        'created_at'  => date('Y-m-d H:i:s')
                    ];

                    $this->Reviews_model->insertReview($data);
                    $this->session->set_flashdata('success', 'Thank you! Your review will be published after it has been approved.');
                    redirect(review_url($hotel));
                }
            }

            $data['page_css'] = [
                'plugins/colorbox/colorbox.css',
                'styles/single_listing_styles.css',
                'styles/single_listing_responsive.css'
            ];
            $data['page_js'] = [
                'plugins/parallax-js-master/parallax.min.js',
                'plugins/colorbox/jquery.colorbox-min.js',
                'js/single_listing_custom.js'
            ];

            $this->load->view('site/partials/header', $data);
            $this->load->view('site/reviews', $data);
            $this->load->view('site/partials/footer', $data);
        }

        // Quyền viết đánh giá: email phải có đơn "Completed" ở khách sạn này VÀ chưa từng đánh giá
        public function _can_review($email, $hotel_id)
        {
            if (!$this->Bookings_model->hasCompletedBooking($email, $hotel_id)) {
                $this->form_validation->set_message('_can_review', 'We could not find a completed stay at this hotel for this email address.');
                return FALSE;
            }
            if ($this->Reviews_model->reviewExists($email, $hotel_id)) {
                $this->form_validation->set_message('_can_review', 'This email address has already reviewed this hotel.');
                return FALSE;
            }
            return TRUE;
        }
    }
