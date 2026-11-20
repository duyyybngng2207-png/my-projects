<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property Reviews_model $Reviews_model
 * @property CI_Pagination $pagination
 * @property CI_URI        $uri
 */
// Admin quản lý đánh giá của khách: /admin/reviews
// Kế thừa Admin_Controller -> phải đăng nhập admin mới vào được
class Reviews extends Admin_Controller
{
    // Số đánh giá mỗi trang
    const PER_PAGE = 10;

    // Danh sách đánh giá có phân trang: /admin/reviews, /admin/reviews/10, /admin/reviews/20...
    public function index()
    {
        $this->load->model('Reviews_model');

        // Khởi tạo phân trang (cấu hình giao diện ở helpers/admin_helper.php)
        $this->load->library('pagination');
        $this->pagination->initialize(admin_pagination_config(
            base_url('admin/reviews'),
            $this->Reviews_model->countAllReviews(),
            self::PER_PAGE
        ));

        // Đoạn thứ 3 của URL (admin/reviews/<offset>) = vị trí bắt đầu; không có / âm -> 0
        $offset = max(0, (int) $this->uri->segment(3));
        $data['reviews_list']     = $this->Reviews_model->getAllReviews(self::PER_PAGE, $offset);
        $data['pagination_links'] = $this->pagination->create_links();   // HTML các nút trang
        $data['offset']           = $offset;                             // view dùng để đánh số thứ tự

        // Ghép trang: header + nội dung + footer của khu admin
        $this->load->view('admin/partials/header');
        $this->load->view('admin/reviews/list', $data);
        $this->load->view('admin/partials/footer');
    }

    // Duyệt / bỏ duyệt 1 đánh giá (form trong bảng danh sách gửi POST is_approved = 1 hoặc 0)
    public function setApproval($id)
    {
        $this->load->model('Reviews_model');

        $value = $this->input->post('is_approved', TRUE);

        // Chỉ chấp nhận đúng '0' hoặc '1' (chống gửi giá trị lạ)
        if (!in_array($value, ['0', '1'], TRUE)) {
            $this->session->set_flashdata('error', 'Yêu cầu không hợp lệ');
            redirect('admin/reviews');
        }

        // Đánh giá phải tồn tại
        $review = $this->Reviews_model->getReview($id);
        if (!$review) {
            $this->session->set_flashdata('error', 'Đánh giá không tồn tại');
            redirect('admin/reviews');
        }

       $data = [
            'is_approved' => (int) $value,              // 1 = hiện ra site, 0 = ẩn
            'updated_at'  => date('Y-m-d H:i:s')
        ];

        $this->Reviews_model->updateReview($id, $data);

        // Báo kết quả rồi quay lại danh sách
        $this->session->set_flashdata('success', ($value === '1') ? 'Đã duyệt đánh giá' : 'Đã bỏ duyệt đánh giá');
        redirect('admin/reviews');
    }

    // Xoá đánh giá (chỉ nhận POST để không bị xoá nhầm khi ai đó mở link)
    public function deleteReview($id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->load->model('Reviews_model');

        $review = $this->Reviews_model->getReview($id);
        if (!$review) {
            $this->session->set_flashdata('error', 'Đánh giá không tồn tại');
            redirect('admin/reviews');
        }

        $this->Reviews_model->deleteReview($id);

        $this->session->set_flashdata('success', 'Đã xóa đánh giá thành công');
        redirect('admin/reviews');
    }
}
