<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
    * Đánh giá của khách theo khách sạn (reviews.hotel_id).
    * Trang địa điểm hiển thị gộp đánh giá của các khách sạn thuộc địa điểm đó.
    *
    * @property CI_DB_query_builder $db
    */
    // LUỒNG ĐÁNH GIÁ: khách có đơn "Completed" -> viết đánh giá ở review/<id>-<slug> (Home::review)
    //   -> lưu với is_approved = 0 -> admin duyệt (Reviews::setApproval) -> is_approved = 1 -> hiện ra site.
    class Reviews_model extends CI_Model {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'reviews';
        }

        // Tổng số đánh giá (phân trang admin)
        public function countAllReviews()
        {
            return $this->db->count_all('reviews');
        }

        // Tất cả đánh giá (kể cả chưa duyệt) cho admin, kèm tên khách sạn
        public function getAllReviews($limit = NULL, $offset = 0)
        {
            // LEFT JOIN: khách sạn đã bị xoá thì đánh giá vẫn hiện trong admin để xử lý
            $this->db->select('reviews.*, hotels.name AS hotel_name');
            $this->db->from('reviews');
            $this->db->join('hotels', 'hotels.id = reviews.hotel_id', 'left');
            $this->db->order_by('reviews.id', 'DESC');
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            return $this->db->get()->result();
        }

        // 1 đánh giá theo id (kèm tên khách sạn)
        public function getReview($id)
        {
            $this->db->select('reviews.*, hotels.name AS hotel_name');
            $this->db->from('reviews');
            $this->db->join('hotels', 'hotels.id = reviews.hotel_id', 'left');
            $this->db->where('reviews.id', $id);
            return $this->db->get()->row();
        }

        // Lưu đánh giá mới (từ form review của khách)
        public function insertReview($data)
        {
            $this->db->insert($this->table , $data);
            return $data;
        }

        // Sửa đánh giá (admin duyệt / bỏ duyệt -> đổi is_approved)
        public function updateReview($id , $data)
        {
            $this->db->where('reviews.id', $id);
            $this->db->update($this->table , $data);
            return $data;
        }

        // Xoá đánh giá
        public function deleteReview($id)
        {
            $this->db->where('reviews.id', $id);
            return $this->db->delete($this->table);
        }

        // Các đánh giá đã duyệt (mới nhất trước), kèm tên khách sạn
        public function getApprovedReviews($limit = NULL, $offset = 0)
        {
            $this->db->select('reviews.id, reviews.name, reviews.content, reviews.rating, reviews.created_at, hotels.name AS hotel_name');
            $this->db->from('reviews');
            $this->db->join('hotels', 'hotels.id = reviews.hotel_id');
            $this->db->where('reviews.is_approved', 1);
            $this->db->order_by('reviews.id', 'DESC');
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            return $this->db->get()->result();
        }

        /** Email này đã đánh giá khách sạn này chưa (mỗi email 1 đánh giá / khách sạn). */
        public function reviewExists($email, $hotel_id)
        {
            $this->db->where('email', $email);
            $this->db->where('hotel_id', $hotel_id);
            return $this->db->count_all_results($this->table) > 0;
        }

        /** Đánh giá đã duyệt của 1 khách sạn (trang chi tiết khách sạn). */
        public function getApprovedReviewsByHotel($hotel_id)
        {
            // Chỉ lấy các cột cần hiển thị, không lấy email của khách
            $this->db->select('id, name, content, rating, created_at');
            $this->db->where('hotel_id', $hotel_id);
            $this->db->where('is_approved', 1);
            $this->db->order_by('id', 'DESC');
            return $this->db->get($this->table)->result();
        }

        // Thống kê đánh giá của 1 khách sạn: tổng số + điểm trung bình (chỉ đánh giá đã duyệt)
        public function getReviewStatsByHotel($hotel_id)
        {
            $this->db->select('COUNT(*) AS total, AVG(rating) AS avg_rating');
            $this->db->where('hotel_id', $hotel_id);
            $this->db->where('is_approved', 1);
            return $this->db->get($this->table)->row();
        }

        /** Đánh giá đã duyệt của các khách sạn thuộc 1 địa điểm, kèm tên/slug khách sạn để link sang. */
        public function getApprovedReviewsByPlace($place_id)
        {
            $this->db->select('reviews.id, reviews.name, reviews.content, reviews.rating, reviews.created_at, hotels.id AS hotel_id, hotels.name AS hotel_name, hotels.slug AS hotel_slug');
            $this->db->from('reviews');
            // Đánh giá -> khách sạn -> lọc khách sạn thuộc địa điểm
            $this->db->join('hotels', 'hotels.id = reviews.hotel_id');
            $this->db->where('hotels.place_id', $place_id);
            $this->db->where('reviews.is_approved', 1);
            $this->db->order_by('reviews.id', 'DESC');
            return $this->db->get()->result();
        }

        // Thống kê đánh giá gộp của mọi khách sạn trong 1 địa điểm
        public function getReviewStatsByPlace($place_id)
        {
            $this->db->select('COUNT(*) AS total, AVG(reviews.rating) AS avg_rating');
            $this->db->from('reviews');
            $this->db->join('hotels', 'hotels.id = reviews.hotel_id');
            $this->db->where('hotels.place_id', $place_id);
            $this->db->where('reviews.is_approved', 1);
            return $this->db->get()->row();
        }

        // N đánh giá đã duyệt mới nhất (khối testimonials trên trang chủ)
        public function getApprovedLatest($limit = 5)
        {
            return $this->db
                ->select('id, name, content, rating, created_at')
                ->where('is_approved', 1)
                ->order_by('created_at', 'DESC')
                ->limit($limit)
                ->get('reviews')
                ->result();
        }
    }
