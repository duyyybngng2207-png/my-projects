<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
    * Quốc gia: cấp trên của places (thành phố). Khách sạn thuộc quốc gia qua places.country_id.
    *
    * @property CI_DB_query_builder|CI_DB_mysqli_driver $db  insert_id() nằm ở driver mysqli, không ở query builder
    */
    // Model bảng countries. Dùng ở: trang country/<slug>, trang continent/<slug>, footer (12 quốc gia),
    // tìm kiếm, admin quốc gia, crawler.
    class Countries_model extends CI_Model {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'countries';
        }

        /** Kèm tên + slug châu lục (continent_name, continent_slug) cho breadcrumb / admin. */
        // Phần đầu truy vấn dùng chung: SELECT countries.*, tên châu lục FROM countries LEFT JOIN continents
        private function _select_with_continent()
        {
            $this->db->select('countries.*, continents.name AS continent_name, continents.slug AS continent_slug');
            $this->db->from($this->table);
            $this->db->join('continents', 'continents.id = countries.continent_id', 'left');
        }

        // 1 quốc gia theo id (kèm châu lục)
        public function getCountry($id)
        {
            $this->_select_with_continent();
            $this->db->where('countries.id', $id);
            return $this->db->get()->row();
        }

        // 1 quốc gia theo slug (trang /country/<slug>)
        public function getCountryBySlug($slug)
        {
            // Slug rỗng không được coi là trùng (tránh khớp nhầm bản ghi có slug = '')
            if ($slug === null || $slug === '') {
                return null;
            }
            $this->_select_with_continent();
            $this->db->where('countries.slug', $slug);
            return $this->db->get()->row();
        }

        // Số quốc gia thuộc 1 châu lục (admin: không cho xoá châu lục còn quốc gia...)
        public function countByContinent($continent_id)
        {
            $this->db->where('continent_id', $continent_id);
            return $this->db->count_all_results($this->table);
        }

        /** Quốc gia đã crawl từ link nguồn này chưa (crawler dùng để cập nhật thay vì tạo trùng). */
        public function getCountryByCrawlerHref($href)
        {
            if ($href === null || $href === '') {
                return null;
            }
            $this->db->where('crawler_href', $href);
            return $this->db->get($this->table)->row();
        }

        // Tổng số quốc gia (phân trang admin)
        public function countAllCountries()
        {
            return $this->db->count_all($this->table);
        }

        // Xoá quốc gia
        public function deleteCountry($id)
        {
            $this->db->where('id', $id);
            return $this->db->delete($this->table);
        }

        // Tất cả quốc gia theo tên A-Z (ô chọn quốc gia trong form địa điểm)
        public function getAllCountries()
        {
            $this->db->order_by('name', 'ASC');
            return $this->db->get($this->table)->result();
        }

        /**
         * Danh sách quốc gia cho trang chủ / footer, kèm:
         *   total_places  số thành phố
         *   min_price     giá khách sạn thấp nhất trong các thành phố của quốc gia (NULL nếu chưa có)
         *   cover_image   ảnh quốc gia; chưa có thì lấy ảnh thành phố đầu tiên (thư mục uploads/places/)
         *   cover_folder  'countries' | 'places' - thư mục chứa cover_image, dùng với upload_url()
         *   continent_name tên châu lục
         * Quốc gia nổi bật lên trước, rồi nhiều thành phố hơn, rồi theo tên.
         *
         * @param int|null $continent_id chỉ lấy quốc gia của châu lục này (trang continent/<slug>)
         */
        public function getCountriesWithStats($limit = NULL, $only_with_places = TRUE, $offset = 0, $continent_id = NULL)
        {
            // SELECT con: giá khách sạn rẻ nhất trong quốc gia
            $min_price = "(SELECT MIN(h.price) FROM hotels h JOIN places hp ON hp.id = h.place_id
                    WHERE hp.country_id = countries.id AND h.price > 0) AS min_price";
            // SELECT con: ảnh 1 thành phố (dự phòng khi quốc gia chưa có ảnh)
            $place_cover = "(SELECT p.image FROM places p
                    WHERE p.country_id = countries.id AND p.image IS NOT NULL AND p.image != ''
                    ORDER BY p.is_featured DESC, p.id ASC LIMIT 1) AS place_cover";

            // COUNT(places.id) = số thành phố của mỗi quốc gia (nhờ GROUP BY bên dưới)
            $this->db->select('countries.*, continents.name AS continent_name, COUNT(places.id) AS total_places, ' . $min_price . ', ' . $place_cover, FALSE);
            $this->db->from($this->table);
            $this->db->join('continents', 'continents.id = countries.continent_id', 'left');
            $this->db->join('places', 'places.country_id = countries.id', 'left');
            // Lọc theo châu lục nếu có truyền
            if ($continent_id !== NULL) {
                $this->db->where('countries.continent_id', (int) $continent_id);
            }
            $this->db->group_by('countries.id');
            // Chỉ lấy quốc gia đã có ít nhất 1 thành phố
            if ($only_with_places) {
                $this->db->having('total_places >', 0);
            }
            // Thứ tự: nổi bật trước -> nhiều thành phố hơn -> tên A-Z
            $this->db->order_by('countries.is_featured', 'DESC');
            $this->db->order_by('total_places', 'DESC');
            $this->db->order_by('countries.name', 'ASC');
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }

            $rows = $this->db->get()->result();
            // Chọn ảnh bìa: ảnh quốc gia nếu có, không thì ảnh thành phố
            foreach ($rows as $row) {
                if (!empty($row->image)) {
                    $row->cover_image  = $row->image;
                    $row->cover_folder = 'countries';
                } else {
                    $row->cover_image  = $row->place_cover;
                    $row->cover_folder = 'places';
                }
            }
            return $rows;
        }

        // Tìm quốc gia theo tên quốc gia HOẶC tên châu lục (gõ "asia" ra các nước châu Á)
        public function searchByKeyword($keyword, $limit = 20)
        {
            return $this->db
                ->select('countries.id, countries.name, countries.slug, countries.image, continents.name AS continent_name')
                ->from($this->table)
                ->join('continents', 'continents.id = countries.continent_id', 'left')
                // group_start/group_end = dấu ngoặc ( ... OR ... ) trong câu WHERE
                ->group_start()
                    ->like('countries.name', $keyword)
                    ->or_like('continents.name', $keyword)
                ->group_end()
                ->order_by('countries.name', 'ASC')
                ->limit($limit)
                ->get()
                ->result();
        }

        /** Thêm quốc gia, trả về id vừa tạo (0 nếu insert lỗi). */
        public function insertCountry($data)
        {
            if (!$this->db->insert($this->table, $data)) {
                return 0;
            }
            return $this->db->insert_id();
        }

        /** Cập nhật quốc gia, trả về true/false theo kết quả query. */
        public function updateCountry($id, $data)
        {
            $this->db->where('id', $id);
            return (bool) $this->db->update($this->table, $data);
        }
    }
