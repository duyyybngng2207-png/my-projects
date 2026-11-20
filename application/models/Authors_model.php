<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
     * Bảng authors: id, name, slug, avatar, job_title, bio, social_links (JSON), crawler_href, crawler_credit, created_at, updated_at
     * @property CI_DB_query_builder|CI_DB_mysqli_driver $db
     */
    // Model tác giả bài viết. Dữ liệu do crawler (crawler/Brand.php -> saveAuthor) tạo khi crawl bài blog KAYAK.
    // Bài viết trỏ tới tác giả qua cột posts.author_id.
    class Authors_model extends CI_Model
    {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'authors';
        }

        /** @return object|null */
        // Lấy tác giả theo id (viết gọn kiểu nối chuỗi: where()->get()->row())
        public function getAuthor($id)
        {
            return $this->db->where('id', (int) $id)->get($this->table)->row();
        }

        /** @return object|null */
        // Lấy tác giả theo slug (crawler dùng để tránh tạo trùng người cùng tên)
        public function getAuthorBySlug($slug)
        {
            return $this->db->where('slug', (string) $slug)->get($this->table)->row();
        }

        /**
         * Tác giả đã crawl từ link trang tác giả này chưa (crawler dùng để cập nhật thay vì tạo trùng).
         * @return object|null
         */
        public function getAuthorByCrawlerHref($href)
        {
            return $this->db->where('crawler_href', (string) $href)->get($this->table)->row();
        }

        /** Thêm tác giả, trả về id vừa tạo (0 nếu insert lỗi). */
        public function insertAuthor($data)
        {
            if (!$this->db->insert($this->table, $data)) {
                return 0;
            }
            // insert_id() = id tự tăng vừa được tạo
            return (int) $this->db->insert_id();
        }

        // Cập nhật tác giả; trả TRUE / FALSE theo kết quả câu UPDATE
        public function updateAuthor($id, $data)
        {
            $this->db->where('id', (int) $id);
            return $this->db->update($this->table, $data);
        }
    }
