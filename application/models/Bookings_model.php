<?php 
     defined('BASEPATH') OR exit('No direct script access allowed');
    /**
    * @property CI_DB_query_builder $db
    */
    class Bookings_model extends CI_Model {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'bookings';
        }

        /** Đếm khớp với getAllBookings() (INNER JOIN places: booking mất địa điểm không hiện, không đếm). */
        public function countAllBookings()
        {
            $this->db->from('bookings');
            $this->db->join('places', 'places.id = bookings.place_id');
            return $this->db->count_all_results();
        }

        public function getAllBookings($limit = NULL, $offset = 0)
        {
            $this->db->select('bookings.*, places.name as place_name, hotels.name as hotel_name');
            $this->db->from('bookings');
            $this->db->join('places', 'places.id = bookings.place_id');
            $this->db->join('hotels', 'hotels.id = bookings.hotel_id', 'left');
            $this->db->order_by('bookings.id','DESC');
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            return $this->db->get()->result();
        }

        public function getBooking($id)
        {
            $this->db->select('bookings.*, places.name as place_name, places.address as place_address,'
                . ' hotels.name as hotel_name, hotels.address as hotel_address, hotels.price as current_hotel_price, hotels.rating as hotel_rating');
            $this->db->from('bookings');
            $this->db->join('places', 'places.id = bookings.place_id');
            $this->db->join('hotels', 'hotels.id = bookings.hotel_id', 'left');
            $this->db->where('bookings.id', $id);
            return $this->db->get()->row();
        }

        public function insertBooking($data)
        {
            $this->db->insert($this->table,$data);
            return $data;
        }

        public function updateBooking($id,$data)
        {
            $this->db->where('id', $id);
            $this->db->update($this->table,$data);
            return $data;
        }

        public function deleteBooking($id)
        {
            $this->db->where('id',$id);
            return $this->db->delete($this->table);
        }

        const STATUS_PENDING   = 1;
        const STATUS_CONFIRMED = 2;
        const STATUS_COMPLETED = 3;

        const MAX_NIGHTS = 30;

        /**
         * Tổng tiền = giá khách sạn/đêm × số đêm (địa điểm không có giá; không đặt khách sạn thì 0).
         * Trả về mảng để lưu thẳng vào bảng bookings. Cột bookings.place_price chỉ còn giữ cho booking cũ.
         */
        public static function calculatePrice($hotel_price = NULL, $nights = 1)
        {
            $nights      = max(1, min(self::MAX_NIGHTS, (int) $nights));
            $hotel_price = ($hotel_price === NULL || $hotel_price === '') ? NULL : (int) $hotel_price;

            return [
                'nights'      => $nights,
                'place_price' => NULL,
                'hotel_price' => $hotel_price,
                'total_price' => $hotel_price === NULL ? 0 : $hotel_price * $nights,
            ];
        }

        /**
         * Booking "Đã xác nhận" mà đã đến ngày đi (từ hôm nay trở về trước) thì chuyển sang "Đã hoàn thành".
         * Đơn "Chờ xác nhận" giữ nguyên vì "Đã hoàn thành" là điều kiện để khách viết review.
         *
         * @return int Số booking vừa được cập nhật
         */
        public function autoCompletePastBookings()
        {
            $tomorrow = date('Y-m-d 00:00:00', strtotime('+1 day'));

            $this->db->where('is_status', self::STATUS_CONFIRMED);
            $this->db->where('booking_date <', $tomorrow);
            $this->db->update($this->table, [
                'is_status'  => self::STATUS_COMPLETED,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->db->affected_rows();
        }

        /** Email này có booking "Đã hoàn thành" (is_status = 3) kèm khách sạn này không -> được viết đánh giá. */
        public function hasCompletedBooking($email, $hotel_id)
        {
            $this->db->where('guest_email', $email);
            $this->db->where('hotel_id', $hotel_id);
            $this->db->where('is_status', 3);
            return $this->db->count_all_results($this->table) > 0;
        }
    }