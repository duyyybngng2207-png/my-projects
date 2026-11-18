<?php 
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
    * @property CI_DB_query_builder|CI_DB_mysqli_driver $db  insert_id() nằm ở driver mysqli, không ở query builder
    */
    class Places_model extends CI_Model {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'places';
        }

        public function countAllPlaces()
        {
            return $this->db->count_all($this->table);
        }

        public function getPlace($id)
        {
            $this->db->select('places.*, categories.title as category_name');
            $this->db->from('places');
            $this->db->join('categories', 'categories.id = places.category_id', 'left');
            $this->db->where('places.id', $id);
            return $this->db->get()->row();
        }

        public function getAllPlaces($limit = NULL, $offset = 0)
        {
            $this->db->select('places.*, categories.title as category_name');
            $this->db->from('places');
            $this->db->join('categories', 'categories.id = places.category_id', 'left');
            if ($limit !== NULL) { 
                $this->db->limit($limit, $offset);
            }
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

        public function updatePlace($id , $data)
        {
            $this->db->where('id', $id);
            $this->db->update($this->table, $data);
            return $data;
        }

        public function deletePlace($id)
        {
            $this->db->where('id', $id);
            return $this->db->delete($this->table);
        }

        public function countBookings($id)
        {
            $this->db->where('place_id', $id);
            return $this->db->count_all_results('bookings');
        }

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

            if (!empty($filters['stars'])) {
                $this->db->where('rating', $filters['stars']);
            }

            if (!empty($filters['name'])) {
                $this->db->order_by('name', $filters['name']);
            }
            $this->db->order_by('id', 'DESC');

            return $this->db->get($this->table)->result();
        }

        public function getFeaturedPlaces($limit = 5)
        {
            $this->db->where('is_featured', 1);
            $this->db->order_by('id', 'DESC');
            $this->db->limit($limit);
            return $this->db->get($this->table)->result();
        }

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

        public function getPlaceBySlug($slug)
        {
            $this->db->select('*');
            $this->db->from('places');
            $this->db->where('slug', $slug);
            return $this->db->get()->row();
        }
    }
?>