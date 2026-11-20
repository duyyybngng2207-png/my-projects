<?php
     defined('BASEPATH') OR exit('No direct script access allowed');
    /**
    * @property CI_DB_query_builder $db
    */
    // ======================================================================
    // Model bảng bookings (đơn đặt lịch). LUỒNG TRẠNG THÁI của 1 đơn:
    //   1 = Pending (chờ xác nhận)  : khách vừa gửi form trên trang book/<slug>
    //   2 = Confirmed (đã xác nhận) : admin bấm xác nhận trong trang quản trị
    //   3 = Completed (hoàn thành)  : tự chuyển khi đã qua ngày đi (autoCompletePastBookings)
    //                                 -> lúc này khách mới được viết đánh giá khách sạn
    // ======================================================================
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

        // Danh sách đơn cho trang admin, kèm tên địa điểm + tên khách sạn, đơn mới nhất lên đầu
        public function getAllBookings($limit = NULL, $offset = 0)
        {
            $this->db->select('bookings.*, places.name as place_name, hotels.name as hotel_name');
            $this->db->from('bookings');
            // JOIN thường: bắt buộc có địa điểm
            $this->db->join('places', 'places.id = bookings.place_id');
            // LEFT JOIN: đơn "chỉ tour" không có khách sạn vẫn được lấy (hotel_name = NULL)
            $this->db->join('hotels', 'hotels.id = bookings.hotel_id', 'left');
            $this->db->order_by('bookings.id','DESC');
            if ($limit !== NULL) {
                $this->db->limit($limit, $offset);
            }
            return $this->db->get()->result();
        }

        // Chi tiết 1 đơn (trang admin/bookings/detail/<id>): kèm thông tin địa điểm + khách sạn
        public function getBooking($id)
        {
            // current_hotel_price = giá khách sạn HIỆN TẠI (để so với giá đã chốt lúc đặt trong bookings.hotel_price)
            $this->db->select('bookings.*, places.name as place_name, places.address as place_address,'
                . ' hotels.name as hotel_name, hotels.address as hotel_address, hotels.price as current_hotel_price, hotels.rating as hotel_rating');
            $this->db->from('bookings');
            $this->db->join('places', 'places.id = bookings.place_id');
            $this->db->join('hotels', 'hotels.id = bookings.hotel_id', 'left');
            $this->db->where('bookings.id', $id);
            return $this->db->get()->row();
        }

        // Lưu đơn mới (gọi từ Home::book() khi form hợp lệ)
        public function insertBooking($data)
        {
            $this->db->insert($this->table,$data);
            return $data;
        }

        // Sửa đơn (admin đổi trạng thái)
        public function updateBooking($id,$data)
        {
            $this->db->where('id', $id);
            $this->db->update($this->table,$data);
            return $data;
        }

        // Xoá đơn
        public function deleteBooking($id)
        {
            $this->db->where('id',$id);
            return $this->db->delete($this->table);
        }

        // Hằng số trạng thái (dùng thay cho số 1, 2, 3 cho dễ đọc: Bookings_model::STATUS_PENDING)
        const STATUS_PENDING   = 1;
        const STATUS_CONFIRMED = 2;
        const STATUS_COMPLETED = 3;

        // Số đêm khách sạn tối đa cho 1 đơn
        const MAX_NIGHTS = 30;

        /**
         * Tổng tiền = giá khách sạn/đêm × số đêm (địa điểm không có giá; không đặt khách sạn thì 0).
         * Trả về mảng để lưu thẳng vào bảng bookings. Cột bookings.place_price chỉ còn giữ cho booking cũ.
         */
        // static: gọi được mà không cần tạo model -> Bookings_model::calculatePrice($gia, $so_dem)
        public static function calculatePrice($hotel_price = NULL, $nights = 1)
        {
            // Ép số đêm vào khoảng 1..30 (khách sửa HTML gửi số âm / quá lớn cũng không sao)
            $nights      = max(1, min(self::MAX_NIGHTS, (int) $nights));
            // Không chọn khách sạn -> NULL
            $hotel_price = ($hotel_price === NULL || $hotel_price === '') ? NULL : (int) $hotel_price;

            // Các key trùng tên cột bảng bookings -> array_merge thẳng vào dữ liệu insert
            return [
                'nights'      => $nights,
                'place_price' => NULL,
                'hotel_price' => $hotel_price,      // giá/đêm chốt tại thời điểm đặt
                'total_price' => $hotel_price === NULL ? 0 : $hotel_price * $nights,
            ];
        }

        /**
         * Booking "Đã xác nhận" mà đã đến ngày đi (từ hôm nay trở về trước) thì chuyển sang "Đã hoàn thành".
         * Đơn "Chờ xác nhận" giữ nguyên vì "Đã hoàn thành" là điều kiện để khách viết review.
         *
         * @return int Số booking vừa được cập nhật
         */
        // Được gọi trước khi kiểm tra quyền viết review (Home::review) và trong cron (controllers/Cron.php)
        public function autoCompletePastBookings()
        {
            // 0h ngày mai -> booking_date < mốc này nghĩa là ngày đi <= hôm nay
            $tomorrow = date('Y-m-d 00:00:00', strtotime('+1 day'));

            // UPDATE bookings SET is_status = 3 ... WHERE is_status = 2 AND booking_date < ngày mai
            $this->db->where('is_status', self::STATUS_CONFIRMED);
            $this->db->where('booking_date <', $tomorrow);
            $this->db->update($this->table, [
                'is_status'  => self::STATUS_COMPLETED,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Số dòng vừa bị UPDATE
            return $this->db->affected_rows();
        }

        /** Email này có booking "Đã hoàn thành" (is_status = 3) kèm khách sạn này không -> được viết đánh giá. */
        public function hasCompletedBooking($email, $hotel_id)
        {
            $this->db->where('guest_email', $email);
            $this->db->where('hotel_id', $hotel_id);
            $this->db->where('is_status', 3);
            // Có ít nhất 1 đơn -> TRUE
            return $this->db->count_all_results($this->table) > 0;
        }
    }
