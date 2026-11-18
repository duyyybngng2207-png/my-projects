<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quản lý bài viết (admin), cùng cách làm với Hotels / Places.
 * Bảng posts: id, title, slug, content, image, is_published, crawler_href, crawler_credit, created_at, updated_at
 *
 * @property Posts_model        $Posts_model
 * @property CI_Form_validation $form_validation
 * @property CI_Upload          $upload
 * @property CI_Pagination      $pagination
 * @property CI_URI             $uri
 */
class Posts extends Admin_Controller
{
    const PER_PAGE = 10;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Posts_model');
    }

    public function index()
    {
        $this->load->library('pagination');
        $this->pagination->initialize(admin_pagination_config(
            base_url('admin/posts'),
            $this->Posts_model->countAllPosts(),
            self::PER_PAGE
        ));

        $offset = max(0, (int) $this->uri->segment(3));
        $data['posts_list']       = $this->Posts_model->getAllPosts(self::PER_PAGE, $offset);
        $data['pagination_links'] = $this->pagination->create_links();
        $data['offset']           = $offset;

        $this->load->view('admin/partials/header');
        $this->load->view('admin/posts/list', $data);
        $this->load->view('admin/partials/footer');
    }

    public function add()
    {
        $data['upload_error'] = '';

        if ($this->input->method() == 'post') {
            $this->_set_rules();

            if ($this->form_validation->run() == TRUE) {
                $upload = $this->_upload_image();

                if ($upload['error'] === '') {
                    $row = $this->_collect_data();
                    $row['slug']       = $this->_make_slug($row['title']);
                    $row['image']      = $upload['file'];
                    $row['created_at'] = date('Y-m-d H:i:s');

                    $this->Posts_model->insertPost($row);
                    $this->session->set_flashdata('success', 'Thêm bài viết thành công!');
                    redirect('admin/posts');
                }
                $data['upload_error'] = $upload['error'];
            }
        }

        $this->load->view('admin/partials/header');
        $this->load->view('admin/posts/add', $data);
        $this->load->view('admin/partials/footer');
    }

    public function editPost($id)
    {
        $post = $this->Posts_model->getPost($id);
        if (!$post) {
            $this->session->set_flashdata('error', 'Bài viết không tồn tại');
            redirect('admin/posts');
        }

        $data['post']         = $post;
        $data['upload_error'] = '';

        if ($this->input->method() == 'post') {
            $this->_set_rules();

            if ($this->form_validation->run() == TRUE) {
                $upload = $this->_upload_image();

                if ($upload['error'] === '') {
                    $row = $this->_collect_data();
                    $row['slug']       = $this->_make_slug($row['title'], (int) $id);
                    $row['image']      = $upload['file'] !== NULL ? $upload['file'] : $post->image;
                    $row['updated_at'] = date('Y-m-d H:i:s');

                    $this->Posts_model->updatePost($id, $row);

                    if ($upload['file'] !== NULL && !empty($post->image)) {
                        $old = FCPATH . 'uploads/posts/' . $post->image;
                        if (is_file($old)) {
                            unlink($old);
                        }
                    }

                    $this->session->set_flashdata('success', 'Cập nhật bài viết thành công!');
                    redirect('admin/posts');
                }
                $data['upload_error'] = $upload['error'];
            }
        }

        $this->load->view('admin/partials/header');
        $this->load->view('admin/posts/editPost', $data);
        $this->load->view('admin/partials/footer');
    }

    public function deletePost($id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $post = $this->Posts_model->getPost($id);
        if (!$post) {
            $this->session->set_flashdata('error', 'Bài viết không tồn tại');
            redirect('admin/posts');
        }

        $this->Posts_model->deletePost($id);

        if (!empty($post->image)) {
            $path = FCPATH . 'uploads/posts/' . $post->image;
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->session->set_flashdata('success', 'Đã xóa bài viết thành công');
        redirect('admin/posts');
    }

    /* ------------------------------------------------------------------ */

    private function _set_rules()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="text-danger">', '</div>');
        $this->form_validation->set_rules('title', 'Tiêu đề', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('slug', 'Slug', 'trim|max_length[255]');
        // Cột content kiểu TEXT: tối đa 65.535 byte
        $this->form_validation->set_rules('content', 'Nội dung', 'trim|required|callback__max_bytes[65000]');
    }

    private function _collect_data()
    {
        return [
            'title'        => trim((string) $this->input->post('title', TRUE)),
            'content'      => trim((string) $this->input->post('content', TRUE)),
            'is_published' => ($this->input->post('is_published') === '1') ? 1 : 0,
        ];
    }

    private function _make_slug($title, $ignore_id = 0)
    {
        $input = trim((string) $this->input->post('slug', TRUE));
        return unique_slug('posts', $input !== '' ? $input : $title, $ignore_id);
    }

    private function _upload_image()
    {
        if (empty($_FILES['image']['name'])) {
            return ['file' => NULL, 'error' => ''];
        }
        $dir = FCPATH . 'uploads/posts/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, TRUE);
        }
        $this->load->library('upload', [
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp',
            'max_size'      => 2048,
            'encrypt_name'  => TRUE,
        ]);
        if (!$this->upload->do_upload('image')) {
            return ['file' => NULL, 'error' => $this->upload->display_errors('<div class="text-danger">', '</div>')];
        }
        return ['file' => $this->upload->data('file_name'), 'error' => ''];
    }

    public function _max_bytes($text, $max)
    {
        if (strlen((string) $text) <= (int) $max) {
            return TRUE;
        }
        $this->form_validation->set_message('_max_bytes', '{field} quá dài (tối đa khoảng ' . number_format((int) $max) . ' byte)');
        return FALSE;
    }
}
