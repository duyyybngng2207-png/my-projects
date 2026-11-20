<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
     * @property Bookings_model $Bookings_model
     * @property CI_Pagination  $pagination
     * @property CI_URI         $uri
     */
    // Admin quản lý đơn đặt lịch: /admin/bookings
    // Đây là nơi admin xác nhận đơn khách gửi từ trang /book/<slug> (Pending -> Confirmed)
    class Bookings extends Admin_Controller
    {
        // Số đơn mỗi trang
        const PER_PAGE = 10;

        public function __construct()
        {
            // Admin_Controller::__construct() kiểm tra đăng nhập trước
            parent::__construct();
            // Model dùng cho mọi hàm bên dưới
            $this->load->model('Bookings_model');
        }

        // Danh sách đơn có phân trang
        public function index()
        {
            // Lịch "Đã xác nhận" đã đến ngày đi -> tự chuyển "Đã hoàn thành"
            // (view hiện thông báo nếu có đơn vừa được chuyển)
            $data['auto_completed'] = $this->Bookings_model->autoCompletePastBookings();

            $this->load->library('pagination');
            $this->pagination->initialize(admin_pagination_config(
                base_url('admin/bookings'),
                $this->Bookings_model->countAllBookings(),
                self::PER_PAGE
            ));

            // admin/bookings/<offset>
            $offset = max(0, (int) $this->uri->segment(3));
            $data['bookings_list']    = $this->Bookings_model->getAllBookings(self::PER_PAGE, $offset);
            $data['pagination_links'] = $this->pagination->create_links();
            $data['offset']           = $offset;

            $this->load->view('admin/partials/header');
            $this->load->view('admin/bookings/list',$data);
            $this->load->view('admin/partials/footer');
        }

        // Đổi trạng thái 1 đơn (ô chọn trạng thái trong danh sách gửi POST is_status = 1 / 2 / 3)
        public function updateStatus($id)
        {
            $this->load->model('Bookings_model');

            $status = $this->input->post('is_status', TRUE);

            // Chỉ nhận 3 giá trị hợp lệ: 1 Pending, 2 Confirmed, 3 Completed
            if (!in_array($status, ['1', '2', '3'], TRUE)) {
                $this->session->set_flashdata('error', 'Trạng thái không hợp lệ!');
                redirect('admin/bookings');
            }

            $data = [
                'is_status'  => $this->input->post('is_status', TRUE),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->Bookings_model->updateBooking($id, $data);
            $this->session->set_flashdata('success', 'Đã cập nhật trạng thái thành công!');
            redirect('admin/bookings');
        }

        // Xoá đơn (chỉ nhận POST)
        public function deleteBooking($id)
        {
            if ($this->input->method() !== 'post') {
                show_404();
            }
            $this->load->model('Bookings_model');

            // Kiểm tra đơn tồn tại trước khi xoá
            $booking = $this->Bookings_model->getBooking($id);

            if (!$booking) {
                $this->session->set_flashdata('error', 'lịch đặt không tồn tại');
                redirect('admin/bookings');
            }

            $this->Bookings_model->deleteBooking($id);

            $this->session->set_flashdata('success', 'Đã xóa lịch đặt thành công!');
            redirect('admin/bookings');
        }

        // Xem chi tiết 1 đơn: thông tin khách, địa điểm, khách sạn, số đêm, tổng tiền
        public function detail($id)
        {
            // Cập nhật trạng thái trước để trang chi tiết hiện đúng
            $this->Bookings_model->autoCompletePastBookings();

            $data['booking'] = $this->Bookings_model->getBooking($id);

            if (!$data['booking']) {
                $this->session->set_flashdata('error', 'Lịch đặt không tồn tại');
                redirect('admin/bookings');
            }

            $this->load->view('admin/partials/header');
            $this->load->view('admin/bookings/detail', $data);
            $this->load->view('admin/partials/footer');
        }
    }
