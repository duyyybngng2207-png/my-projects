<?php 
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
     * @property CI_DB_query_builder $db
     */
    class Categories_model extends CI_Model {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'categories';
        }

        public function countAllCategories()
        {
            return $this->db->count_all($this->table);
        }

        public function insertCategory($data)
        {
            $this->db->insert($this->table , $data);
            return $data;
        }

        public function getCategory($id)
        {
            $this->db->where('id', $id);
            return $this->db->get($this->table)->row();
        }

        public function getCategoryBySlug($slug)
        {
            $this->db->where('slug', $slug);
            return $this->db->get($this->table)->row();
        }

        public function getAllCategories($limit = NULL, $offset = 0)
        {
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            return $this->db->get($this->table)->result();
        }

        public function updateCategory($id , $data)
        {
            $this->db->where('id', $id);
            $this->db->update($this->table, $data);
            return $data;
        }

        public function deleteCategory($id)
        {
            $this->db->where('id', $id);
            return $this->db->delete($this->table);
        }

        public function getCategoriesWithStats($limit = NULL, $offset = 0)
        {
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            $cover = "(SELECT p.image FROM places p
                    WHERE p.category_id = categories.id AND p.image IS NOT NULL AND p.image != ''
                    ORDER BY p.id ASC LIMIT 1) AS cover_image";

            // Địa điểm không có giá -> "giá từ" = giá khách sạn thấp nhất trong các địa điểm của danh mục (NULL nếu chưa có)
            $min_price = "(SELECT MIN(h.price) FROM hotels h JOIN places hp ON hp.id = h.place_id
                    WHERE hp.category_id = categories.id AND h.price > 0) AS min_price";

            $this->db->select('categories.*, COUNT(places.id) AS total_places, ' . $min_price . ', ' . $cover, FALSE);
            $this->db->from('categories');
            $this->db->join('places', 'places.category_id = categories.id', 'left');
            $this->db->group_by('categories.id');
            $this->db->order_by('categories.id', 'ASC');
            return $this->db->get()->result();
        }

        public function searchByKeyword($keyword, $limit = 20)
        {
            return $this->db
                ->select('id, title, slug, description')
                ->like('title', $keyword)
                ->limit($limit)
                ->get('categories')
                ->result();
        }
    }
?>