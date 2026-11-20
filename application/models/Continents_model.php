<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
    * Châu lục: cấp trên cùng. continents > countries (continent_id) > places (country_id) > hotels (place_id).
    *
    * @property CI_DB_query_builder|CI_DB_mysqli_driver $db  insert_id() nằm ở driver mysqli, không ở query builder
    */
    // Model bảng continents. Dùng ở: trang chủ (khối "Explore by continent"), trang continent/<slug>,
    // trang About, admin châu lục, crawler.
    class Continents_model extends CI_Model {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'continents';
        }

        // 1 châu lục theo id
        public function getContinent($id)
        {
            $this->db->where('id', $id);
            return $this->db->get($this->table)->row();
        }

        // 1 châu lục theo slug (trang /continent/<slug>)
        public function getContinentBySlug($slug)
        {
            // Slug rỗng thì khỏi truy vấn
            if ($slug === null || $slug === '') {
                return null;
            }
            $this->db->where('slug', $slug);
            return $this->db->get($this->table)->row();
        }

        /** Châu lục đã crawl từ link nguồn này chưa (crawler dùng để cập nhật thay vì tạo trùng). */
        public function getContinentByCrawlerHref($href)
        {
            if ($href === null || $href === '') {
                return null;
            }
            $this->db->where('crawler_href', $href);
            return $this->db->get($this->table)->row();
        }

        // Tất cả châu lục, sắp theo thứ tự admin đặt (sort_order) rồi theo tên (dùng cho ô chọn trong form quốc gia)
        public function getAllContinents()
        {
            $this->db->order_by('sort_order', 'ASC');
            $this->db->order_by('name', 'ASC');
            return $this->db->get($this->table)->result();
        }

        // Tổng số châu lục (phân trang admin)
        public function countAllContinents()
        {
            return $this->db->count_all($this->table);
        }

        /**
         * Danh sách châu lục cho trang chủ / admin, kèm:
         *   total_countries  số quốc gia (trang chủ: chỉ đếm quốc gia đã có thành phố)
         *   total_places     số thành phố
         *   min_price        giá khách sạn thấp nhất trong châu lục (NULL nếu chưa có)
         *   cover_image / cover_folder  ảnh châu lục -> ảnh quốc gia -> ảnh thành phố (dùng với upload_url())
         *
         * @param bool      $only_with_places TRUE: chỉ đếm quốc gia đã có thành phố (trang chủ)
         * @param bool|null $hide_empty       TRUE: bỏ châu lục chưa có thành phố; NULL = theo $only_with_places
         */
        public function getContinentsWithStats($limit = NULL, $only_with_places = TRUE, $offset = 0, $hide_empty = NULL)
        {
            // Không truyền $hide_empty -> mặc định giống $only_with_places
            if ($hide_empty === NULL) {
                $hide_empty = $only_with_places;
            }
            // Mỗi biến dưới đây là 1 câu SELECT con, chạy riêng cho TỪNG châu lục (continents.id)
            // Số quốc gia: chỉ đếm quốc gia có thành phố (JOIN places) hoặc đếm tất cả
            $total_countries = $only_with_places
                ? "(SELECT COUNT(DISTINCT c.id) FROM countries c JOIN places p ON p.country_id = c.id
                    WHERE c.continent_id = continents.id) AS total_countries"
                : "(SELECT COUNT(*) FROM countries c WHERE c.continent_id = continents.id) AS total_countries";
            // Số thành phố thuộc các quốc gia của châu lục
            $total_places = "(SELECT COUNT(*) FROM places p JOIN countries c ON c.id = p.country_id
                    WHERE c.continent_id = continents.id) AS total_places";
            // Giá khách sạn rẻ nhất trong châu lục (đi qua hotels -> places -> countries)
            $min_price = "(SELECT MIN(h.price) FROM hotels h JOIN places p ON p.id = h.place_id
                    JOIN countries c ON c.id = p.country_id
                    WHERE c.continent_id = continents.id AND h.price > 0) AS min_price";
            // Ảnh dự phòng 1: ảnh của 1 quốc gia (ưu tiên quốc gia nổi bật)
            $country_cover = "(SELECT c.image FROM countries c
                    WHERE c.continent_id = continents.id AND c.image IS NOT NULL AND c.image != ''
                    ORDER BY c.is_featured DESC, c.id ASC LIMIT 1) AS country_cover";
            // Ảnh dự phòng 2: ảnh của 1 thành phố
            $place_cover = "(SELECT p.image FROM places p JOIN countries c ON c.id = p.country_id
                    WHERE c.continent_id = continents.id AND p.image IS NOT NULL AND p.image != ''
                    ORDER BY p.is_featured DESC, p.id ASC LIMIT 1) AS place_cover";

            // FALSE = không để CI tự thêm dấu ` (câu có SELECT con)
            $this->db->select("continents.*, $total_countries, $total_places, $min_price, $country_cover, $place_cover", FALSE);
            $this->db->from($this->table);
            // HAVING lọc theo cột tính toán (total_places) -> bỏ châu lục chưa có thành phố
            if ($hide_empty) {
                $this->db->having('total_places >', 0);
            }
            $this->db->order_by('continents.sort_order', 'ASC');
            $this->db->order_by('continents.name', 'ASC');
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }

            $rows = $this->db->get()->result();
            // Chọn ảnh bìa cho từng châu lục: ảnh riêng -> ảnh quốc gia -> ảnh thành phố
            // cover_folder cho biết ảnh nằm ở thư mục uploads/<folder>/ nào (view dùng upload_url(folder, file))
            foreach ($rows as $row) {
                if (!empty($row->image)) {
                    $row->cover_image  = $row->image;
                    $row->cover_folder = 'continents';
                } elseif (!empty($row->country_cover)) {
                    $row->cover_image  = $row->country_cover;
                    $row->cover_folder = 'countries';
                } else {
                    $row->cover_image  = $row->place_cover;
                    $row->cover_folder = 'places';
                }
            }
            return $rows;
        }

        /** Thêm châu lục, trả về id vừa tạo (0 nếu insert lỗi). */
        public function insertContinent($data)
        {
            if (!$this->db->insert($this->table, $data)) {
                return 0;
            }
            return $this->db->insert_id();
        }

        /** Cập nhật châu lục, trả về true/false theo kết quả query. */
        public function updateContinent($id, $data)
        {
            $this->db->where('id', $id);
            return (bool) $this->db->update($this->table, $data);
        }

        // Xoá châu lục (quốc gia thuộc châu lục này sẽ có continent_id = NULL nhờ khoá ngoại ON DELETE SET NULL)
        public function deleteContinent($id)
        {
            $this->db->where('id', $id);
            return $this->db->delete($this->table);
        }
    }
