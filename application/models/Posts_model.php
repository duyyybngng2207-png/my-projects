<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
    * @property CI_DB_query_builder|CI_DB_mysqli_driver $db  insert_id() nằm ở driver mysqli, không ở query builder
    */
    // Model bảng posts (bài viết / tin tức). Bài có thể do admin viết tay hoặc crawler lấy từ blog KAYAK.
    // Chỉ bài is_published = 1 mới hiện ra trang /news.
    class Posts_model extends CI_Model
    {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'posts';
        }

        /** Cột tác giả đi kèm bài viết (LEFT JOIN authors, bài không có tác giả -> NULL). */
        // Đặt bí danh author_* để không trùng tên với cột của posts (vd authors.slug vs posts.slug)
        const AUTHOR_COLUMNS = 'authors.name AS author_name, authors.slug AS author_slug, authors.avatar AS author_avatar, authors.job_title AS author_job_title, authors.bio AS author_bio, authors.social_links AS author_social_links, authors.crawler_href AS author_href';

        // Phần đầu câu truy vấn dùng chung: SELECT posts.*, <cột tác giả> FROM posts LEFT JOIN authors
        // Các hàm bên dưới gọi hàm này rồi nối thêm WHERE / ORDER BY / LIMIT
        private function _withAuthor()
        {
            $this->db->select('posts.*, ' . self::AUTHOR_COLUMNS);
            $this->db->from('posts');
            $this->db->join('authors', 'authors.id = posts.author_id', 'left');
        }

        // Tổng số bài (kể cả bản nháp) - phân trang admin
        public function countAllPosts()
        {
            return $this->db->count_all('posts');
        }

        // Tất cả bài (kể cả nháp) cho trang admin, mới nhất lên đầu
        public function getAllPosts($limit = NULL, $offset = 0)
        {
            $this->_withAuthor();
            $this->db->order_by('posts.id', 'DESC');
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            return $this->db->get()->result();
        }

        // 1 bài theo id (kèm tác giả)
        public function getPost($id)
        {
            $this->_withAuthor();
            $this->db->where('posts.id', $id);
            return $this->db->get()->row();
        }

        // 1 bài theo slug (trang /news/<slug>)
        public function getPostBySlug($slug)
        {
            $this->_withAuthor();
            $this->db->where('posts.slug', $slug);
            return $this->db->get()->row();
        }

        /**
         * Bài đã crawl từ link nguồn này chưa (crawler dùng để cập nhật thay vì tạo trùng).
         * @return object|null
         */
        public function getPostByCrawlerHref($href)
        {
            $this->db->where('crawler_href', $href);
            return $this->db->get($this->table)->row();
        }

        /** Thêm bài viết, trả về id vừa tạo (0 nếu insert lỗi). */
        public function insertPost($data)
        {
            if (!$this->db->insert($this->table, $data)) {
                return 0;
            }
            return (int) $this->db->insert_id();
        }

        /** Cập nhật bài viết, trả về TRUE nếu câu UPDATE chạy thành công. */
        public function updatePost($id , $data)
        {
            $this->db->where('id', $id);
            return (bool) $this->db->update($this->table, $data);
        }

        // Xoá bài theo id
        public function deletePost($id)
        {
            $this->db->where('id', $id);
            return $this->db->delete($this->table);
        }

        // Bài ĐÃ ĐĂNG (trang /news, sidebar, footer), mới nhất lên đầu
        public function getPublishedPosts($limit = NULL, $offset = 0)
        {
            $this->_withAuthor();
            $this->db->where('posts.is_published', 1);
            $this->db->order_by('posts.id', 'DESC');
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            return $this->db->get()->result();
        }

        // Số bài đã đăng (phân trang trang /news)
        public function countPublishedPosts()
        {
            $this->db->where('is_published', 1);
            return $this->db->count_all_results($this->table);
        }

        // Tìm bài đã đăng có tiêu đề chứa từ khoá (ô tìm kiếm trên site)
        public function searchPublished($keyword, $limit = 5)
        {
            return $this->db
                ->select('id, title, slug, image')
                ->where('is_published', 1)
                ->like('title', $keyword)
                ->limit($limit)
                ->get('posts')
                ->result();
        }
    }
