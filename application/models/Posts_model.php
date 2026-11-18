<?php 
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
    * @property CI_DB_query_builder|CI_DB_mysqli_driver $db  insert_id() nằm ở driver mysqli, không ở query builder
    */
    class Posts_model extends CI_Model
    {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'posts';
        }

        public function countAllPosts()
        {
            return $this->db->count_all('posts');
        }

        public function getAllPosts($limit = NULL, $offset = 0)
        {
            $this->db->select('posts.*');
            $this->db->from('posts');
            $this->db->order_by('id', 'DESC');
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            return $this->db->get()->result();
        }

        public function getPost($id)
        {
            $this->db->select('posts.*');
            $this->db->from('posts');
            $this->db->where('posts.id', $id);
            return $this->db->get()->row();
        }

        public function getPostBySlug($slug)
        {
            $this->db->select('posts.*');
            $this->db->from('posts');
            $this->db->where('posts.slug', $slug);
            return $this->db->get()->row();
        }

        /** Bài đã crawl từ link nguồn này chưa (crawler dùng để cập nhật thay vì tạo trùng). */
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

        public function updatePost($id , $data)
        {
            $this->db->where('id', $id);
            $this->db->update($this->table, $data);
            return $data;
        }

        public function deletePost($id)
        {
            $this->db->where('id', $id);
            return $this->db->delete($this->table);
        }

        public function getPublishedPosts($limit = NULL, $offset = 0)
        {
            $this->db->where('is_published', 1);
            $this->db->order_by('id', 'DESC');
            if ($limit !== NULL) { 
                $this->db->limit($limit, $offset);
            }
            return $this->db->get($this->table)->result();
        }

        public function countPublishedPosts()
        {
            $this->db->where('is_published', 1);
            return $this->db->count_all_results($this->table);
        }

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