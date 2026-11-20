<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quản lý danh mục (admin). Bảng categories: id, title, slug, description, created_at, updated_at
 *
 * @property Categories_model   $Categories_model
 * @property Places_model       $Places_model
 * @property CI_Form_validation $form_validation
 * @property CI_Pagination $pagination
 * @property CI_URI $uri
 */
// Mẫu CRUD chuẩn của khu admin (các controller Places, Countries, Continents, Posts... làm tương tự):
//   index()  -> danh sách + phân trang
//   add()    -> GET: hiện form trống | POST: kiểm tra -> lưu -> về danh sách
//   edit..() -> GET: hiện form có dữ liệu cũ | POST: kiểm tra -> cập nhật -> về danh sách
//   delete..() -> chỉ nhận POST -> xoá -> về danh sách
class Categories extends Admin_Controller
{
    const PER_PAGE = 10;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Categories_model');
    }

    public function index()
    {
        // Kèm số địa điểm của mỗi danh mục
        $this->load->library('pagination');
        $this->pagination->initialize(admin_pagination_config(
            base_url('admin/categories'),
            $this->Categories_model->countAllCategories(),
            self::PER_PAGE
        ));

        $offset = max(0, (int) $this->uri->segment(3));
        $data['categories_list']  = $this->Categories_model->getCategoriesWithStats(self::PER_PAGE, $offset);
        $data['pagination_links'] = $this->pagination->create_links();
        $data['offset']           = $offset;

        $this->load->view('admin/partials/header');
        $this->load->view('admin/categories/list', $data);
        $this->load->view('admin/partials/footer');
    }

    public function add()
    {
        // Chỉ xử lý lưu khi form được gửi; mở trang lần đầu (GET) thì bỏ qua khối này
        if ($this->input->method() == 'post') {
            $this->_set_rules();

            // run() = kiểm tra theo luật trong _set_rules(); sai thì view tự hiện lỗi (form_error)
            if ($this->form_validation->run() == TRUE) {
                $title = trim((string) $this->input->post('title', TRUE));
                $this->Categories_model->insertCategory([
                    'title'       => $title,
                    'slug'        => $this->_make_slug($title),
                    'description' => $this->_description(),
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
                $this->session->set_flashdata('success', 'Thêm danh mục thành công!');
                redirect('admin/categories');
            }
        }

        // GET hoặc POST bị lỗi validate -> hiện (lại) form
        $this->load->view('admin/partials/header');
        $this->load->view('admin/categories/add');
        $this->load->view('admin/partials/footer');
    }

    public function editCategory($id)
    {
        // Bản ghi phải tồn tại
        $category = $this->Categories_model->getCategory($id);
        if (!$category) {
            $this->session->set_flashdata('error', 'Danh mục không tồn tại');
            redirect('admin/categories');
        }
        // Truyền dữ liệu cũ sang view để điền sẵn vào form
        $data['category'] = $category;

        if ($this->input->method() == 'post') {
            $this->_set_rules();

            if ($this->form_validation->run() == TRUE) {
                $title = trim((string) $this->input->post('title', TRUE));
                $this->Categories_model->updateCategory($id, [
                    'title'       => $title,
                    // truyền $id để không tự báo trùng slug với chính nó
                    'slug'        => $this->_make_slug($title, (int) $id),
                    'description' => $this->_description(),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
                $this->session->set_flashdata('success', 'Cập nhật danh mục thành công');
                redirect('admin/categories');
            }
        }

        $this->load->view('admin/partials/header');
        $this->load->view('admin/categories/editCategory', $data);
        $this->load->view('admin/partials/footer');
    }

    public function deleteCategory($id)
    {
        // Chỉ nhận POST (nút xoá nằm trong form có CSRF token)
        if ($this->input->method() !== 'post') {
            show_404();
        }
        if (!$this->Categories_model->getCategory($id)) {
            $this->session->set_flashdata('error', 'Danh mục không tồn tại');
            redirect('admin/categories');
        }

        // Không cho xoá danh mục còn địa điểm (tránh địa điểm trỏ tới danh mục không tồn tại)
        $this->load->model('Places_model');
        if (count($this->Places_model->getPlacesByCategory($id)) > 0) {
            $this->session->set_flashdata('error', 'Không thể xóa: danh mục này đang có địa điểm. Hãy chuyển các địa điểm sang danh mục khác trước.');
            redirect('admin/categories');
        }

        $this->Categories_model->deleteCategory($id);
        $this->session->set_flashdata('success', 'Đã xóa danh mục');
        redirect('admin/categories');
    }

    /* ------------------------------------------------------------------ */

    // Luật kiểm tra form thêm / sửa (dùng chung)
    private function _set_rules()
    {
        $this->load->library('form_validation');
        // Mỗi lỗi được bọc trong div chữ đỏ
        $this->form_validation->set_error_delimiters('<div class="text-danger">', '</div>');
        $this->form_validation->set_rules('title', 'Tên danh mục', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('slug', 'Slug', 'trim|max_length[255]');
        $this->form_validation->set_rules('description', 'Mô tả', 'trim');
    }

    /** Slug nhập tay hoặc tạo từ tên, luôn chữ thường, trùng thì thêm -2, -3... */
    private function _make_slug($title, $ignore_id = 0)
    {
        $input = trim((string) $this->input->post('slug', TRUE));
        // Admin để trống ô slug -> tạo từ tên
        return unique_slug('categories', $input !== '' ? $input : $title, $ignore_id);
    }

    // Mô tả: để trống thì lưu NULL thay vì chuỗi rỗng
    private function _description()
    {
        $value = trim((string) $this->input->post('description', TRUE));
        return $value === '' ? NULL : $value;
    }
}
