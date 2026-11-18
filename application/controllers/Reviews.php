<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property Reviews_model $Reviews_model
 * @property CI_Pagination $pagination
 * @property CI_URI        $uri
 */
class Reviews extends Admin_Controller
{
    const PER_PAGE = 10;

    public function index()
    {
        $this->load->model('Reviews_model');

        $this->load->library('pagination');
        $this->pagination->initialize(admin_pagination_config(
            base_url('admin/reviews'),
            $this->Reviews_model->countAllReviews(),
            self::PER_PAGE
        ));

        $offset = max(0, (int) $this->uri->segment(3));
        $data['reviews_list']     = $this->Reviews_model->getAllReviews(self::PER_PAGE, $offset);
        $data['pagination_links'] = $this->pagination->create_links();
        $data['offset']           = $offset;

        $this->load->view('admin/partials/header');
        $this->load->view('admin/reviews/list', $data);
        $this->load->view('admin/partials/footer');
    }

    public function setApproval($id)
    {
        $this->load->model('Reviews_model');

        $value = $this->input->post('is_approved', TRUE);

        if (!in_array($value, ['0', '1'], TRUE)) {
            $this->session->set_flashdata('error', 'Yêu cầu không hợp lệ');
            redirect('admin/reviews');
        }

        $review = $this->Reviews_model->getReview($id);
        if (!$review) {
            $this->session->set_flashdata('error', 'Đánh giá không tồn tại');
            redirect('admin/reviews');
        }

       $data = [
            'is_approved' => (int) $value,
            'updated_at'  => date('Y-m-d H:i:s')
        ];

        $this->Reviews_model->updateReview($id, $data);

        $this->session->set_flashdata('success', ($value === '1') ? 'Đã duyệt đánh giá' : 'Đã bỏ duyệt đánh giá');
        redirect('admin/reviews');
    }

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