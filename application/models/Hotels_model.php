<?php 
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
    * @property CI_DB_query_builder|CI_DB_mysqli_driver $db  insert_id() nằm ở driver mysqli, không ở query builder
    */
    class Hotels_model extends CI_Model {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'hotels';
        }

        public function countAllHotels()
        {
            return $this->db->count_all('hotels');
        }

        public function getAllHotels($limit = NULL, $offset = 0)
        {
            $this->db->select('hotels.*, places.name as place_name');
            $this->db->from('hotels');
            $this->db->join('places', 'places.id = hotels.place_id', 'left');
            $this->db->order_by('hotels.id', 'ASC'); // thứ tự cố định để phân trang không lặp / sót
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            return $this->db->get()->result();
        }

        public function getHotel($id)
        {
            $this->db->select('hotels.*, places.name as place_name');
            $this->db->from('hotels');
            $this->db->join('places', 'places.id = hotels.place_id', 'left');
            $this->db->where('hotels.id', $id);
            return $this->db->get()->row();
        }

        public function getTrendingHotels($limit = 8) 
        {
            $this->db->select('hotels.*, places.slug AS place_slug');
            $this->db->from($this->table);
            $this->db->join('places', 'places.id = hotels.place_id', 'left');
            $this->db->where('hotels.is_trending', 1);
            $this->db->order_by('hotels.id', 'DESC');
            $this->db->limit($limit);
            return $this->db->get()->result();
        }

        /**
         * Chi tiết khách sạn cho trang frontend, kèm thông tin địa điểm.
         */
        public function getHotelDetail($id)
        {
            $this->db->select('hotels.*, places.name AS place_name, places.slug AS place_slug, places.address AS place_address');
            $this->db->from($this->table);
            $this->db->join('places', 'places.id = hotels.place_id', 'left');
            $this->db->where('hotels.id', $id);
            return $this->db->get()->row();
        }

        /** Tìm khách sạn theo slug (crawler dùng để tránh lưu trùng). */
        public function getHotelBySlug($slug)
        {
            // Slug rỗng không được coi là trùng (tránh khớp nhầm bản ghi có slug = '')
            if ($slug === null || $slug === '') {
                return null;
            }
            $this->db->select('id');
            $this->db->where('slug', $slug);
            $this->db->limit(1);
            return $this->db->get($this->table)->row();
        }

        /**
         * Khách sạn đã crawl từ nguồn này chưa: khớp đúng link, hoặc cùng ID Kayak
         * (".311998.ksp") để nhận cả bản ghi cũ lưu link qua translate.goog / có query string.
         */
        public function getHotelByCrawlerHref($href, $source_id = null)
        {
            $href      = (string) $href;
            $source_id = preg_replace('/\D/', '', (string) $source_id);
            if ($href === '' && $source_id === '') {
                return null;
            }
            $this->db->select('id, place_id, slug');
            $this->db->group_start();
            if ($href !== '') {
                $this->db->where('crawler_href', $href);
            }
            if ($source_id !== '') {
                $this->db->or_like('crawler_href', '.' . $source_id . '.ksp', 'both');
            }
            $this->db->group_end();
            $this->db->limit(1);
            return $this->db->get($this->table)->row();
        }

        /**
         * Khách sạn có thuộc địa điểm này không (dùng để kiểm tra form đặt phòng).
         */
        public function belongsToPlace($hotel_id, $place_id)
        {
            $this->db->where('id', $hotel_id);
            $this->db->where('place_id', $place_id);
            return $this->db->count_all_results($this->table) > 0;
        }

        /**
         * Khách sạn gợi ý của một địa điểm: ưu tiên trending, rồi rating cao, rồi giá thấp.
         */
        public function getHotelsByPlace($place_id, $limit = 6, $exclude_id = 0)
        {
            $this->db->where('place_id', $place_id);
            if ($exclude_id) {
                $this->db->where('id !=', $exclude_id);
            }
            $this->db->order_by('is_trending', 'DESC');
            $this->db->order_by('rating', 'DESC');
            $this->db->order_by('price', 'ASC');
            $this->db->limit($limit);
            return $this->db->get($this->table)->result();
        }

        /** Thêm khách sạn, trả về id vừa tạo (0 nếu insert lỗi). */
        public function insertHotel($data)
        {
            if (!$this->db->insert($this->table, $data)) {
                return 0;
            }
            return $this->db->insert_id();
        }

        /** Cập nhật khách sạn, trả về true/false theo kết quả query. */
        public function updateHotel($id, $data)
        {
            $this->db->where('id', $id);
            return (bool) $this->db->update($this->table, $data);
        }

        public function deleteHotel($id)
        {
            $this->db->where('id', $id);
            return $this->db->delete($this->table);
        }

        public function searchByKeyword($keyword, $limit = 20)
        {
            return $this->db
                ->select('hotels.id, hotels.place_id, hotels.name, hotels.slug, hotels.address, hotels.price, hotels.rating, hotels.image, hotels.thumbnail, places.slug AS place_slug')
                ->from('hotels')
                ->join('places', 'places.id = hotels.place_id', 'left')
                ->group_start()
                    ->like('hotels.name', $keyword)
                    ->or_like('hotels.address', $keyword)
                ->group_end()
                ->limit($limit)
                ->get()
                ->result();
        }

    }