<?php 
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
     * @property Places_model $Places_model
     * @property Bookings_model $Bookings_model
     * @property Categories_model $Categories_model
     * @property CI_Form_validation $form_validation
     * @property Hotels_model $Hotels_model
     * @property Reviews_model $Reviews_model
     * @property Rate_limiter $rate_limiter
     */
    class Home extends Site_Controller 
    {
        public function index()
        {
            $this->load->model('Places_model');
            $this->load->model('Categories_model');
            $this->load->model('Hotels_model');
            $this->load->model('Reviews_model');

            $data['places_list'] = $this->Places_model->getAllPlaces(6,0);
            $data['categories_list'] = $this->Categories_model->getCategoriesWithStats();
            $data['trending_list'] = $this->Hotels_model->getTrendingHotels(8);
            $data['featured_list'] = $this->Places_model->getFeaturedPlaces(5);
            $data['testimonials'] = $this->Reviews_model->getApprovedLatest(5);

            $data['page_js'] = ['js/custom.js'];
            
            $this->load->helper('form');
            $this->load->view('site/partials/header');
            $this->load->view('site/home', $data);
            $this->load->view('site/partials/footer');
        }

        /**
         * Tìm địa điểm theo slug. Link cũ dạng số (place/2) hoặc sai hoa/thường
         * sẽ 301 sang slug chuẩn; bản ghi chưa có slug thì vẫn hiển thị theo id.
         */
        private function _find_place($key, $prefix)
        {
            $this->load->model('Places_model');
            $key = (string) $key;

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
        private function _canonical_redirect($key, $slug, $prefix)
        {
            if (!empty($slug) && $key !== $slug && $this->input->method() === 'get') {
                // Giữ query string (ví dụ book/1?hotel=3)
                $qs = (string) $this->input->server('QUERY_STRING');
                redirect(base_url($prefix . '/' . $slug) . ($qs !== '' ? '?' . $qs : ''), 'location', 301);
            }
        }

        public function detail($slug)
        {
            $place = $this->_find_place($slug, 'place');
            $id = $place->id;

            $data['place'] = $place;
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
        public function hotel($id, $slug = '')
        {
            $this->load->model('Hotels_model');

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

            $data['hotel']        = $hotel;
            $data['place']        = $place;
            $data['other_hotels'] = $place ? $this->Hotels_model->getHotelsByPlace($place->id, 4, $hotel->id) : [];

            $this->load->model('Reviews_model');
            $data['reviews']      = $this->Reviews_model->getApprovedReviewsByHotel($hotel->id);
            $data['review_stats'] = $this->Reviews_model->getReviewStatsByHotel($hotel->id);

            // Sửa mã unicode dạng chữ còn sót trong dữ liệu crawl (vd \u00E0 -> à)
            $hotel->description = decode_unicode_escapes($hotel->description);

            // Các cột JSON (albums / amenities / faqs) -> mảng để view hiển thị
            $data['albums']    = $this->_hotel_albums($hotel);
            $data['amenities'] = $this->_json_list($hotel->amenities);
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
                $url = is_array($item) ? ($item['url'] ?? ($item['contentUrl'] ?? '')) : $item;
                $url = trim((string) $url);
                if ($url !== '' && preg_match('#^https?://#i', $url) && !in_array($url, $urls, TRUE)) {
                    $urls[] = $url;
                }
            }
            return array_slice($urls, 0, 12);
        }

        public function book($slug)
        {
            $this->load->model('Bookings_model');
            $this->load->model('Hotels_model');
            $data['place'] = $this->_find_place($slug, 'book');
            $id = (int) $data['place']->id;

            // Khách sạn của địa điểm để khách chọn đặt kèm; ?hotel=<id> sẽ chọn sẵn
            $data['hotels']         = $this->Hotels_model->getHotelsByPlace($id, 50);
            $data['selected_hotel'] = (int) $this->input->get('hotel');

            if ($this->input->method() == 'post') {
                $this->load->library('form_validation');
                $this->form_validation->set_error_delimiters('<div class="text-danger">', '</div>');
                $this->form_validation->set_rules('guest_name', 'Name', 'trim|required|max_length[100]');
                $this->form_validation->set_rules('guest_email', 'Email', 'trim|required|valid_email|max_length[100]');
                $this->form_validation->set_rules('guest_phone', 'Phone', [
                    'trim',
                    'required',
                    'regex_match[/^(0|\+84)[0-9]{9}$/]'
                ]);
                $this->form_validation->set_rules('booking_date', 'Booking date', 'required|callback__future_date');
                $this->form_validation->set_rules('hotel_id', 'Hotel', 'callback__valid_hotel[' . $id . ']');

                $hotel_id = (int) $this->input->post('hotel_id');
                if ($hotel_id > 0) {
                    $this->form_validation->set_rules('nights', 'Hotel nights',
                        'required|is_natural_no_zero|less_than_equal_to[' . Bookings_model::MAX_NIGHTS . ']');
                }

                if ($this->form_validation->run() == TRUE) {
                    // Chốt giá tại thời điểm đặt: giá khách sạn × số đêm (địa điểm không có giá)
                    $hotel = $hotel_id > 0 ? $this->Hotels_model->getHotel($hotel_id) : NULL;
                    $price = Bookings_model::calculatePrice(
                        $hotel ? $hotel->price : NULL,
                        $hotel ? $this->input->post('nights') : 1
                    );

                    $booking = array_merge([
                        'place_id'     => $id,
                        'hotel_id'     => $hotel ? (int) $hotel->id : NULL,
                        'guest_name'   => $this->input->post('guest_name', TRUE),
                        'guest_email'  => $this->input->post('guest_email', TRUE),
                        'guest_phone'  => $this->input->post('guest_phone', TRUE),
                        'booking_date' => $this->input->post('booking_date', TRUE),
                        'is_status'    => Bookings_model::STATUS_PENDING,
                        'created_at'   => date('Y-m-d H:i:s')
                    ], $price);
                    $this->Bookings_model->insertBooking($booking);
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

            $this->load->view('site/partials/header', $data);
            $this->load->view('site/booking', $data);
            $this->load->view('site/partials/footer', $data);
        }

        /**
         * hotel_id để trống = chỉ đặt tour; có giá trị thì phải là khách sạn thuộc địa điểm này.
         */
        public function _valid_hotel($hotel_id, $place_id)
        {
            $hotel_id = (int) $hotel_id;
            if ($hotel_id === 0 || $this->Hotels_model->belongsToPlace($hotel_id, $place_id)) {
                return TRUE;
            }
            $this->form_validation->set_message('_valid_hotel', 'The selected hotel does not belong to this location.');
            return FALSE;
        }

        public function _future_date($date)
        {
            $d = DateTime::createFromFormat('Y-m-d', $date);
            $valid = $d && $d->format('Y-m-d') === $date;

            if (!$valid || $date < date('Y-m-d')) {
                $this->form_validation->set_message('_future_date', 'The booking date must be a valid date from today onwards.');
                return FALSE;
            }
            return TRUE;
        }

        /**
         * Đọc bộ lọc trang category từ query string, chỉ nhận giá trị hợp lệ.
         */
        private function _category_filters()
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

        public function category($slug)
        {
            $this->load->model('Categories_model');
            $this->load->model('Places_model');

            $slug = (string) $slug;
            $category = ctype_digit($slug)
                ? $this->Categories_model->getCategory((int) $slug)
                : $this->Categories_model->getCategoryBySlug($slug);

            if (!$category) {
                show_404();
            }
            $this->_canonical_redirect($slug, $category->slug, 'category');

            $filters = $this->_category_filters();
            $places  = $this->Places_model->getPlacesByCategory($category->id, $filters);

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

            $data['category']    = $category;
            $data['places_list'] = $places;
            $data['filters']     = $filters;
            $data['has_places']  = $this->Places_model->countByCategory($category->id) > 0;

            $data['page_css'] = [
                'styles/offers_styles.css',
                'styles/offers_responsive.css'
            ];
            $data['page_js'] = [
                'plugins/parallax-js-master/parallax.min.js',
                'js/offers_custom.js',
                'js/category_filter.js'
            ];

            $this->load->view('site/partials/header', $data);
            $this->load->view('site/category', $data);
            $this->load->view('site/partials/footer', $data);
        }

        /**
         * Form viết đánh giá cho khách sạn: review/<id>-<slug-tên>.
         */
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
                if (trim((string) $this->input->post('website')) !== '') {
                    $this->session->set_flashdata('success', 'Thank you! Your review will be published after it has been approved.');
                    redirect(review_url($hotel));
                }

                $this->load->library('form_validation');
                $this->form_validation->set_error_delimiters('<div class="text-danger">', '</div>');
                $this->form_validation->set_rules('name', 'Full name', 'trim|required|max_length[100]');
                $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[255]|callback__can_review[' . (int) $id . ']');
                $this->form_validation->set_rules('rating', 'Rating', 'required|in_list[1,2,3,4,5]');
                $this->form_validation->set_rules('content', 'Review', 'trim|required|min_length[10]|max_length[1000]');

                if ($this->form_validation->run() == TRUE) {
                    $data = [
                        'hotel_id'    => $id,
                        'name'        => $this->input->post('name', TRUE),
                        'email'       => strtolower(trim($this->input->post('email', TRUE))),
                        'content'     => $this->input->post('content', TRUE),
                        'rating'      => (int) $this->input->post('rating'),
                        'is_approved' => 0,
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