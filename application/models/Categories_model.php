<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
     * @property CI_DB_query_builder $db
     */
    // Model bảng categories (danh mục địa điểm, vd "Biển", "Núi"...). Mỗi place có thể thuộc 1 danh mục.
    class Categories_model extends CI_Model {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'categories';
        }

        // Tổng số danh mục (dùng cho phân trang admin)
        public function countAllCategories()
        {
            return $this->db->count_all($this->table);
        }

        // Thêm danh mục; trả lại chính $data (không trả id)
        public function insertCategory($data)
        {
            $this->db->insert($this->table , $data);
            return $data;
        }

        // Lấy 1 danh mục theo id (NULL nếu không có)
        public function getCategory($id)
        {
            $this->db->where('id', $id);
            return $this->db->get($this->table)->row();
        }

        // Lấy 1 danh mục theo slug
        public function getCategoryBySlug($slug)
        {
            $this->db->where('slug', $slug);
            return $this->db->get($this->table)->row();
        }

        // Danh sách danh mục; truyền $limit để phân trang (LIMIT $offset, $limit)
        public function getAllCategories($limit = NULL, $offset = 0)
        {
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            // result() = mảng các object
            return $this->db->get($this->table)->result();
        }

        // Sửa danh mục theo id
        public function updateCategory($id , $data)
        {
            $this->db->where('id', $id);
            $this->db->update($this->table, $data);
            return $data;
        }

        // Xoá danh mục theo id
        public function deleteCategory($id)
        {
            $this->db->where('id', $id);
            return $this->db->delete($this->table);
        }

        // Danh mục kèm thống kê: số địa điểm, giá khách sạn thấp nhất, ảnh bìa
        public function getCategoriesWithStats($limit = NULL, $offset = 0)
        {
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            // Câu SELECT con: ảnh của địa điểm đầu tiên trong danh mục -> làm ảnh bìa
            $cover = "(SELECT p.image FROM places p
                    WHERE p.category_id = categories.id AND p.image IS NOT NULL AND p.image != ''
                    ORDER BY p.id ASC LIMIT 1) AS cover_image";

            // Địa điểm không có giá -> "giá từ" = giá khách sạn thấp nhất trong các địa điểm của danh mục (NULL nếu chưa có)
            $min_price = "(SELECT MIN(h.price) FROM hotels h JOIN places hp ON hp.id = h.place_id
                    WHERE hp.category_id = categories.id AND h.price > 0) AS min_price";

            // FALSE: không để CI tự thêm dấu ` vào câu SELECT (vì có câu con)
            $this->db->select('categories.*, COUNT(places.id) AS total_places, ' . $min_price . ', ' . $cover, FALSE);
            $this->db->from('categories');
            // LEFT JOIN: danh mục chưa có địa điểm nào vẫn được liệt kê (total_places = 0)
            $this->db->join('places', 'places.category_id = categories.id', 'left');
            // Gom theo danh mục để COUNT đếm số địa điểm của từng danh mục
            $this->db->group_by('categories.id');
            $this->db->order_by('categories.id', 'ASC');
            return $this->db->get()->result();
        }

        // Tìm danh mục có tiêu đề chứa từ khoá (LIKE '%từ khoá%')
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