<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
    * @property CI_DB_query_builder|CI_DB_mysqli_driver $db  insert_id() nằm ở driver mysqli, không ở query builder
    */
    // Model bảng hotels. Mỗi khách sạn thuộc 1 địa điểm (hotels.place_id -> places.id).
    // Các cột albums / amenities / faqs / guest_insights lưu dạng chuỗi JSON (controller json_decode khi hiển thị).
    class Hotels_model extends CI_Model {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'hotels';
        }

        // Tổng số khách sạn (phân trang admin)
        public function countAllHotels()
        {
            return $this->db->count_all('hotels');
        }

        // Danh sách khách sạn cho admin, kèm tên địa điểm + quốc gia
        public function getAllHotels($limit = NULL, $offset = 0)
        {
            $this->db->select('hotels.*, places.name as place_name, countries.name AS country_name');
            $this->db->from('hotels');
            // khách sạn -> địa điểm -> quốc gia (LEFT: thiếu địa điểm vẫn hiện)
            $this->db->join('places', 'places.id = hotels.place_id', 'left');
            $this->db->join('countries', 'countries.id = places.country_id', 'left');
            $this->db->order_by('hotels.id', 'ASC'); // thứ tự cố định để phân trang không lặp / sót
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            return $this->db->get()->result();
        }

        // 1 khách sạn theo id (kèm tên địa điểm) - dùng ở admin, form đặt lịch, crawler
        public function getHotel($id)
        {
            $this->db->select('hotels.*, places.name as place_name');
            $this->db->from('hotels');
            $this->db->join('places', 'places.id = hotels.place_id', 'left');
            $this->db->where('hotels.id', $id);
            return $this->db->get()->row();
        }

        // Khách sạn được đánh dấu "trending" (khối "Trending now" trang chủ)
        public function getTrendingHotels($limit = 8)
        {
            $this->db->select('hotels.*, places.slug AS place_slug');
            $this->db->from($this->table);
            $this->db->join('places', 'places.id = hotels.place_id', 'left');
            $this->db->where('hotels.is_trending', 1);
            $this->db->order_by('hotels.id', 'DESC');
            $this->db->limit($limit);
            return $this->db->get()->result();
        }

        /**
         * Chi tiết khách sạn cho trang frontend, kèm thông tin địa điểm.
         */
        // Lấy thêm quốc gia + châu lục để dựng breadcrumb: Châu lục > Quốc gia > Thành phố > Khách sạn
        public function getHotelDetail($id)
        {
            $this->db->select('hotels.*, places.name AS place_name, places.slug AS place_slug, places.address AS place_address,
                countries.name AS country_name, countries.slug AS country_slug,
                continents.name AS continent_name, continents.slug AS continent_slug');
            $this->db->from($this->table);
            $this->db->join('places', 'places.id = hotels.place_id', 'left');
            $this->db->join('countries', 'countries.id = places.country_id', 'left');
            $this->db->join('continents', 'continents.id = countries.continent_id', 'left');
            $this->db->where('hotels.id', $id);
            return $this->db->get()->row();
        }

        /** Tìm khách sạn theo slug (crawler dùng để tránh lưu trùng). */
        public function getHotelBySlug($slug)
        {
            // Slug rỗng không được coi là trùng (tránh khớp nhầm bản ghi có slug = '')
            if ($slug === null || $slug === '') {
                return null;
            }
            // Chỉ cần biết có tồn tại hay không -> lấy mỗi cột id, 1 dòng
            $this->db->select('id');
            $this->db->where('slug', $slug);
            $this->db->limit(1);
            return $this->db->get($this->table)->row();
        }

        /**
         * Khách sạn đã crawl từ nguồn này chưa: khớp đúng link, hoặc cùng ID Kayak
         * (".311998.ksp") để nhận cả bản ghi cũ lưu link qua translate.goog / có query string.
         */
        public function getHotelByCrawlerHref($href, $source_id = null)
        {
            $href      = (string) $href;
            // Chỉ giữ chữ số của ID Kayak
            $source_id = preg_replace('/\D/', '', (string) $source_id);
            // Không có gì để so -> coi như chưa có
            if ($href === '' && $source_id === '') {
                return null;
            }
            $this->db->select('id, place_id, slug');
            // WHERE ( crawler_href = '<link>' OR crawler_href LIKE '%.<id>.ksp%' )
            $this->db->group_start();
            if ($href !== '') {
                $this->db->where('crawler_href', $href);
            }
            if ($source_id !== '') {
                $this->db->or_like('crawler_href', '.' . $source_id . '.ksp', 'both');
            }
            $this->db->group_end();
            $this->db->limit(1);
            return $this->db->get($this->table)->row();
        }

        /**
         * Khách sạn có thuộc địa điểm này không (dùng để kiểm tra form đặt phòng).
         */
        // Chống khách sửa HTML gửi id khách sạn của thành phố khác lên form đặt lịch
        public function belongsToPlace($hotel_id, $place_id)
        {
            $this->db->where('id', $hotel_id);
            $this->db->where('place_id', $place_id);
            return $this->db->count_all_results($this->table) > 0;
        }

        /**
         * Khách sạn gợi ý của một địa điểm: ưu tiên trending, rồi rating cao, rồi giá thấp.
         */
        // $exclude_id: 1 id (trang khách sạn: bỏ chính nó ở mục "khách sạn khác")
        //              hoặc mảng id (trang đặt lịch: bỏ các khách sạn đã hiện khi bấm "More")
        public function getHotelsByPlace($place_id, $limit = 6, $exclude_id = 0)
        {
            $this->db->where('place_id', $place_id);
            if (is_array($exclude_id)) {
                // Danh sách id đã hiển thị (trang đặt lịch: nút "More" chỉ lấy phần còn lại)
                // intval + array_filter: chỉ giữ số nguyên > 0 (chống dữ liệu rác từ URL)
                $exclude_id = array_values(array_filter(array_map('intval', $exclude_id)));
                if ($exclude_id) {
                    // WHERE id NOT IN (1, 2, 3)
                    $this->db->where_not_in('id', $exclude_id);
                }
            } elseif ($exclude_id) {
                $this->db->where('id !=', $exclude_id);
            }
            $this->db->order_by('is_trending', 'DESC');
            $this->db->order_by('rating', 'DESC');
            $this->db->order_by('price', 'ASC');
            $this->db->order_by('id', 'ASC'); // thứ tự ổn định giữa lần tải đầu và lần bấm "More"
            $this->db->limit($limit);
            return $this->db->get($this->table)->result();
        }

        /** Số khách sạn của một địa điểm. */
        // Trang đặt lịch dùng để tính còn bao nhiêu khách sạn -> chữ "More hotels (n)"
        public function countByPlace($place_id)
        {
            $this->db->where('place_id', $place_id);
            return (int) $this->db->count_all_results($this->table);
        }

        /** Một khách sạn của địa điểm (NULL nếu không thuộc địa điểm này). @return object|null */
        // Trang đặt lịch: khách sạn đang được chọn nằm ngoài 3 khách sạn đầu -> lấy riêng để vẫn hiện
        public function getHotelOfPlace($hotel_id, $place_id)
        {
            $this->db->where('id', (int) $hotel_id);
            $this->db->where('place_id', (int) $place_id);
            return $this->db->get($this->table)->row();
        }

        /** Thêm khách sạn, trả về id vừa tạo (0 nếu insert lỗi). */
        public function insertHotel($data)
        {
            if (!$this->db->insert($this->table, $data)) {
                return 0;
            }
            return $this->db->insert_id();
        }

        /** Cập nhật khách sạn, trả về true/false theo kết quả query. */
        public function updateHotel($id, $data)
        {
            $this->db->where('id', $id);
            return (bool) $this->db->update($this->table, $data);
        }

        // Xoá khách sạn
        public function deleteHotel($id)
        {
            $this->db->where('id', $id);
            return $this->db->delete($this->table);
        }

        // Tìm khách sạn theo tên hoặc địa chỉ (ô tìm kiếm trên site)
        public function searchByKeyword($keyword, $limit = 20)
        {
            return $this->db
                ->select('hotels.id, hotels.place_id, hotels.name, hotels.slug, hotels.address, hotels.price, hotels.rating, hotels.image, hotels.thumbnail, places.slug AS place_slug')
                ->from('hotels')
                ->join('places', 'places.id = hotels.place_id', 'left')
                // WHERE (name LIKE '%x%' OR address LIKE '%x%')
                ->group_start()
                    ->like('hotels.name', $keyword)
                    ->or_like('hotels.address', $keyword)
                ->group_end()
                ->limit($limit)
                ->get()
                ->result();
        }

    }
