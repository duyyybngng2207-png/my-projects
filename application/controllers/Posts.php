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
// /admin/posts: danh sách / thêm / sửa / xoá bài viết (kèm upload ảnh vào uploads/posts/)
// Trên trang danh sách còn có form "Crawl bài viết từ blog KAYAK" -> gửi tới crawler/brand/post
class Posts extends Admin_Controller
{
    const PER_PAGE = 10;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Posts_model');
    }

    // Danh sách bài viết có phân trang
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

    // Thêm bài viết
    public function add()
    {
        // Lỗi upload ảnh (nếu có) để view hiển thị
        $data['upload_error'] = '';

        if ($this->input->method() == 'post') {
            $this->_set_rules();

            // Bước 1: kiểm tra chữ (tiêu đề, nội dung)
            if ($this->form_validation->run() == TRUE) {
                // Bước 2: upload ảnh (không chọn ảnh thì bỏ qua, không lỗi)
                $upload = $this->_upload_image();

                if ($upload['error'] === '') {
                    // Bước 3: gom dữ liệu + lưu DB
                    $row = $this->_collect_data();
                    $row['slug']       = $this->_make_slug($row['title']);
                    $row['image']      = $upload['file'];                // tên file đã lưu (hoặc NULL)
                    $row['created_at'] = date('Y-m-d H:i:s');

                    $this->Posts_model->insertPost($row);
                    $this->session->set_flashdata('success', 'Thêm bài viết thành công!');
                    redirect('admin/posts');
                }
                // Upload lỗi (sai định dạng, quá nặng...) -> hiện lại form kèm lỗi
                $data['upload_error'] = $upload['error'];
            }
        }

        $this->load->view('admin/partials/header');
        $this->load->view('admin/posts/add', $data);
        $this->load->view('admin/partials/footer');
    }

    // Sửa bài viết
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
                    // Có chọn ảnh mới -> dùng ảnh mới, không thì giữ ảnh cũ
                    $row['image']      = $upload['file'] !== NULL ? $upload['file'] : $post->image;
                    $row['updated_at'] = date('Y-m-d H:i:s');

                    $this->Posts_model->updatePost($id, $row);

                    // Đã thay ảnh mới -> xoá file ảnh cũ khỏi ổ đĩa cho đỡ rác
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

    // Xoá bài viết (chỉ POST) + xoá file ảnh
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

        // Xoá ảnh của bài trên ổ đĩa
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

    // Luật kiểm tra form
    private function _set_rules()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="text-danger">', '</div>');
        $this->form_validation->set_rules('title', 'Tiêu đề', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('slug', 'Slug', 'trim|max_length[255]');
        // Cột content kiểu TEXT: tối đa 65.535 byte
        // callback__max_bytes -> gọi hàm _max_bytes() ở cuối file
        $this->form_validation->set_rules('content', 'Nội dung', 'trim|required|callback__max_bytes[65000]');
    }

    // Lấy các trường chữ từ form thành mảng để lưu DB
    private function _collect_data()
    {
        return [
            'title'        => trim((string) $this->input->post('title', TRUE)),
            'content'      => trim((string) $this->input->post('content', TRUE)),
            // Checkbox "Đăng" được tick -> gửi '1', không tick -> không gửi gì -> 0 (nháp)
            'is_published' => ($this->input->post('is_published') === '1') ? 1 : 0,
        ];
    }

    // Slug nhập tay hoặc tạo từ tiêu đề, không trùng
    private function _make_slug($title, $ignore_id = 0)
    {
        $input = trim((string) $this->input->post('slug', TRUE));
        return unique_slug('posts', $input !== '' ? $input : $title, $ignore_id);
    }

    // Upload ảnh từ ô <input type="file" name="image">
    // Trả về ['file' => tên file đã lưu | NULL, 'error' => '' | thông báo lỗi]
    private function _upload_image()
    {
        // Không chọn file -> không phải lỗi, chỉ là không có ảnh
        if (empty($_FILES['image']['name'])) {
            return ['file' => NULL, 'error' => ''];
        }
        $dir = FCPATH . 'uploads/posts/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, TRUE);
        }
        // Thư viện upload của CI: chỉ nhận ảnh, tối đa 2MB, đổi tên file ngẫu nhiên (encrypt_name) tránh trùng
        $this->load->library('upload', [
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp',
            'max_size'      => 2048,
            'encrypt_name'  => TRUE,
        ]);
        if (!$this->upload->do_upload('image')) {
            return ['file' => NULL, 'error' => $this->upload->display_errors('<div class="text-danger">', '</div>')];
        }
        // Thành công -> tên file mới (chỉ tên, không có đường dẫn) để lưu DB
        return ['file' => $this->upload->data('file_name'), 'error' => ''];
    }

    // Luật kiểm tra tuỳ chỉnh: nội dung không quá $max byte (tiếng Việt 1 ký tự có thể 2-3 byte)
    // Hàm phải public để form_validation gọi được; tên bắt đầu bằng "_" nên không mở được qua URL
    public function _max_bytes($text, $max)
    {
        if (strlen((string) $text) <= (int) $max) {
            return TRUE;
        }
        $this->form_validation->set_message('_max_bytes', '{field} quá dài (tối đa khoảng ' . number_format((int) $max) . ' byte)');
        return FALSE;
    }
}
