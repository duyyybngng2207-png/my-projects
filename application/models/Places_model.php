<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
    * @property CI_DB_query_builder|CI_DB_mysqli_driver $db  insert_id() nằm ở driver mysqli, không ở query builder
    */
    // Model bảng places (địa điểm = thành phố, vd Phu Quoc, Paris). Thuộc 1 quốc gia (places.country_id),
    // có nhiều khách sạn (hotels.place_id). Khách đặt lịch theo địa điểm: /book/<slug>.
    class Places_model extends CI_Model {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'places';
        }

        // Tổng số địa điểm (phân trang admin)
        public function countAllPlaces()
        {
            return $this->db->count_all($this->table);
        }

        // 1 địa điểm theo id, kèm tên danh mục, quốc gia, châu lục
        public function getPlace($id)
        {
            $this->db->select('places.*, categories.title as category_name, countries.name AS country_name, countries.slug AS country_slug,
                continents.name AS continent_name, continents.slug AS continent_slug');
            $this->db->from('places');
            $this->db->join('categories', 'categories.id = places.category_id', 'left');
            $this->db->join('countries', 'countries.id = places.country_id', 'left');
            $this->db->join('continents', 'continents.id = countries.continent_id', 'left');
            $this->db->where('places.id', $id);
            return $this->db->get()->row();
        }

        // Danh sách địa điểm (admin, ô chọn địa điểm trong form khách sạn)
        public function getAllPlaces($limit = NULL, $offset = 0)
        {
            $this->db->select('places.*, categories.title as category_name, countries.name AS country_name, countries.slug AS country_slug');
            $this->db->from('places');
            $this->db->join('categories', 'categories.id = places.category_id', 'left');
            $this->db->join('countries', 'countries.id = places.country_id', 'left');
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            return $this->db->get()->result();
        }

        /**
         * Khối "the best offers" trang chủ: mỗi châu lục 1 thành phố (mới nhất, đã có khách sạn)
         * để trang chủ trải đều các châu lục thay vì chỉ hiện các địa điểm cũ nhất.
         */
        public function getHomePlaces($limit = 6)
        {
            // Điều kiện "địa điểm có ít nhất 1 khách sạn"; %s được thay bằng bí danh bảng
            $has_hotel = 'EXISTS (SELECT 1 FROM hotels h WHERE h.place_id = %s.id)';
            // Chỉ giữ địa điểm có id lớn nhất (mới nhất) trong châu lục của nó -> mỗi châu lục 1 địa điểm
            $latest_in_continent = "places.id = (SELECT MAX(p2.id) FROM places p2
                JOIN countries c2 ON c2.id = p2.country_id
                WHERE c2.continent_id = countries.continent_id AND " . sprintf($has_hotel, 'p2') . ")";

            $this->db->select('places.*, categories.title as category_name, countries.name AS country_name, countries.slug AS country_slug');
            $this->db->from('places');
            $this->db->join('categories', 'categories.id = places.category_id', 'left');
            // JOIN thường: địa điểm phải có quốc gia (để biết châu lục)
            $this->db->join('countries', 'countries.id = places.country_id');
            // NULL, FALSE: điều kiện viết sẵn bằng SQL, CI không escape lại
            $this->db->where($latest_in_continent, NULL, FALSE);
            $this->db->order_by('places.id', 'DESC');
            $this->db->limit($limit);
            return $this->db->get()->result();
        }

        /** Thêm địa điểm, trả về id vừa tạo (0 nếu insert lỗi). */
        public function insertPlace($data)
        {
            if (!$this->db->insert($this->table, $data)) {
                return 0;
            }
            return $this->db->insert_id();
        }

        // Sửa địa điểm
        public function updatePlace($id , $data)
        {
            $this->db->where('id', $id);
            $this->db->update($this->table, $data);
            return $data;
        }

        // Xoá địa điểm
        public function deletePlace($id)
        {
            $this->db->where('id', $id);
            return $this->db->delete($this->table);
        }

        // Số đơn đặt lịch của địa điểm (admin: cảnh báo trước khi xoá địa điểm đang có đơn)
        public function countBookings($id)
        {
            $this->db->where('place_id', $id);
            return $this->db->count_all_results('bookings');
        }

        // Số địa điểm thuộc 1 danh mục
        public function countByCategory($category_id)
        {
            $this->db->where('category_id', $category_id);
            return $this->db->count_all_results($this->table);
        }

        /**
         * Địa điểm của một danh mục, có lọc/sắp xếp cho trang category.
         *
         * @param array $filters ['name' => ''|'asc'|'desc', 'stars' => 0..5]
         *                       Giá trị đã được controller kiểm tra (whitelist).
         */
        public function getPlacesByCategory($category_id, $filters = [])
        {
            $this->db->where('category_id', $category_id);

            // Lọc theo số sao
            if (!empty($filters['stars'])) {
                $this->db->where('rating', $filters['stars']);
            }

            // Sắp xếp theo tên A-Z / Z-A
            if (!empty($filters['name'])) {
                $this->db->order_by('name', $filters['name']);
            }
            $this->db->order_by('id', 'DESC');

            return $this->db->get($this->table)->result();
        }

        // Địa điểm nổi bật (slider "featured" trang chủ)
        public function getFeaturedPlaces($limit = 5)
        {
            $this->db->where('is_featured', 1);
            $this->db->order_by('id', 'DESC');
            $this->db->limit($limit);
            return $this->db->get($this->table)->result();
        }

        // Tìm địa điểm theo tên hoặc địa chỉ (ô tìm kiếm)
        public function searchByKeyword($keyword, $limit = 5)
        {
            return $this->db
                ->select('id, name, slug, address, image')
                ->group_start()
                    ->like('name', $keyword)
                    ->or_like('address', $keyword)
                ->group_end()
                ->limit($limit)
                ->get('places')
                ->result();
        }

        /** Địa điểm đã crawl từ link nguồn này chưa (crawler dùng để cập nhật thay vì tạo trùng). */
        public function getPlaceByCrawlerHref($href)
        {
            $this->db->where('crawler_href', $href);
            return $this->db->get($this->table)->row();
        }

        /** Địa điểm có tồn tại trong bảng places không (chỉ đếm theo khoá chính, không JOIN). */
        public function placeExists($id)
        {
            if ($id <= 0) {
                return false;
            }
            $this->db->where('id', $id);
            return $this->db->count_all_results($this->table) > 0;
        }

        // 1 địa điểm theo slug (trang /place/<slug>, /book/<slug>), kèm quốc gia + châu lục cho breadcrumb
        public function getPlaceBySlug($slug)
        {
            $this->db->select('places.*, countries.name AS country_name, countries.slug AS country_slug,
                continents.name AS continent_name, continents.slug AS continent_slug');
            $this->db->from('places');
            $this->db->join('countries', 'countries.id = places.country_id', 'left');
            $this->db->join('continents', 'continents.id = countries.continent_id', 'left');
            $this->db->where('places.slug', $slug);
            return $this->db->get()->row();
        }

        /**
         * Thành phố của một quốc gia, có lọc/sắp xếp cho trang country/<slug>.
         *
         * @param array $filters ['name' => ''|'asc'|'desc', 'stars' => 0..5]
         *                       Giá trị đã được controller kiểm tra (whitelist).
         */
        public function getPlacesByCountry($country_id, $filters = [])
        {
            $this->db->where('country_id', $country_id);

            // Lọc số sao (bộ lọc AJAX trên trang quốc gia)
            if (!empty($filters['stars'])) {
                $this->db->where('rating', $filters['stars']);
            }

            // Sắp theo tên nếu khách chọn, sau đó nổi bật trước, mới nhất trước
            if (!empty($filters['name'])) {
                $this->db->order_by('name', $filters['name']);
            }
            $this->db->order_by('is_featured', 'DESC');
            $this->db->order_by('id', 'DESC');

            return $this->db->get($this->table)->result();
        }

        // Số thành phố của 1 quốc gia (trang quốc gia: phân biệt "chưa có thành phố" và "lọc không ra kết quả")
        public function countByCountry($country_id)
        {
            $this->db->where('country_id', $country_id);
            return $this->db->count_all_results($this->table);
        }
    }
?>