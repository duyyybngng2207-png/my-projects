<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quản lý châu lục (admin), cùng cách làm với Countries.
 * Bảng continents: id, name, slug, image, content, sort_order, crawler_href, crawler_credit, created_at, updated_at
 * Phân cấp: continents > countries (continent_id) > places (country_id) > hotels (place_id).
 *
 * @property Continents_model   $Continents_model
 * @property Countries_model    $Countries_model
 * @property CI_Form_validation $form_validation
 * @property CI_Upload          $upload
 * @property CI_Pagination      $pagination
 * @property CI_URI             $uri
 */
// /admin/continents: CRUD châu lục (mẫu giống Categories / Posts, có upload ảnh)
class Continents extends Admin_Controller
{
    const PER_PAGE = 10;

    /** Ảnh châu lục (admin upload + crawler) nằm ở uploads/continents/, DB chỉ lưu tên file. */
    const UPLOAD_DIR = 'uploads/continents/';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Continents_model');
    }

    // Danh sách châu lục + thống kê (số quốc gia, thành phố, giá từ)
    public function index()
    {
        $this->load->library('pagination');
        $this->pagination->initialize(admin_pagination_config(
            base_url('admin/continents'),
            $this->Continents_model->countAllContinents(),
            self::PER_PAGE
        ));

        $offset = max(0, (int) $this->uri->segment(3));
        // Hiện cả châu lục chưa có quốc gia / thành phố
        $data['continents_list']  = $this->Continents_model->getContinentsWithStats(self::PER_PAGE, FALSE, $offset);
        $data['pagination_links'] = $this->pagination->create_links();
        $data['offset']           = $offset;

        $this->load->view('admin/partials/header');
        $this->load->view('admin/continents/list', $data);
        $this->load->view('admin/partials/footer');
    }

    // Thêm châu lục: kiểm tra form -> upload ảnh -> lưu
    public function add()
    {
        $data['upload_error'] = '';

        if ($this->input->method() == 'post') {
            $this->_set_rules();

            if ($this->form_validation->run() == TRUE) {
                $upload = $this->_upload_image();

                if ($upload['error'] === '') {
                    $row = $this->_collect_data();
                    $row['slug']       = $this->_make_slug($row['name']);
                    $row['image']      = $upload['file'];
                    $row['created_at'] = date('Y-m-d H:i:s');

                    $this->Continents_model->insertContinent($row);
                    $this->session->set_flashdata('success', 'Thêm châu lục thành công!');
                    redirect('admin/continents');
                }
                $data['upload_error'] = $upload['error'];
            }
        }

        $this->load->view('admin/partials/header');
        $this->load->view('admin/continents/add', $data);
        $this->load->view('admin/partials/footer');
    }

    // Sửa châu lục
    public function editContinent($id)
    {
        $continent = $this->Continents_model->getContinent($id);
        if (!$continent) {
            $this->session->set_flashdata('error', 'Châu lục không tồn tại');
            redirect('admin/continents');
        }

        $data['continent']    = $continent;
        $data['upload_error'] = '';

        if ($this->input->method() == 'post') {
            $this->_set_rules();

            if ($this->form_validation->run() == TRUE) {
                $upload = $this->_upload_image();

                if ($upload['error'] === '') {
                    $row = $this->_collect_data();
                    $row['slug']       = $this->_make_slug($row['name'], (int) $id);
                    // Không chọn ảnh mới -> giữ ảnh cũ
                    $row['image']      = $upload['file'] !== NULL ? $upload['file'] : $continent->image;
                    $row['updated_at'] = date('Y-m-d H:i:s');

                    $this->Continents_model->updateContinent($id, $row);

                    // Có ảnh mới -> xoá file ảnh cũ
                    if ($upload['file'] !== NULL) {
                        $this->_delete_image($continent->image);
                    }

                    $this->session->set_flashdata('success', 'Cập nhật châu lục thành công');
                    redirect('admin/continents');
                }
                $data['upload_error'] = $upload['error'];
            }
        }

        $this->load->view('admin/partials/header');
        $this->load->view('admin/continents/editContinent', $data);
        $this->load->view('admin/partials/footer');
    }

    // Xoá châu lục (chỉ POST, chỉ khi không còn quốc gia nào)
    public function deleteContinent($id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $continent = $this->Continents_model->getContinent($id);
        if (!$continent) {
            $this->session->set_flashdata('error', 'Châu lục không tồn tại');
            redirect('admin/continents');
        }

        // Không xoá khi còn quốc gia: tránh quốc gia mất châu lục mà không ai biết
        $this->load->model('Countries_model');
        if ($this->Countries_model->countByContinent($id) > 0) {
            $this->session->set_flashdata('error', 'Không thể xóa: châu lục này đang có quốc gia. Hãy chuyển các quốc gia sang châu lục khác trước.');
            redirect('admin/continents');
        }

        $this->Continents_model->deleteContinent($id);
        $this->_delete_image($continent->image);

        $this->session->set_flashdata('success', 'Đã xóa châu lục');
        redirect('admin/continents');
    }

    /* ------------------------------------------------------------------ */

    // Luật kiểm tra form
    private function _set_rules()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="text-danger">', '</div>');

        $this->form_validation->set_rules('name', 'Tên châu lục', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('slug', 'Slug', 'trim|max_length[100]');
        $this->form_validation->set_rules('sort_order', 'Thứ tự', 'trim|integer');
        $this->form_validation->set_rules('content', 'Nội dung', 'trim');
    }

    // Gom dữ liệu form thành mảng lưu DB
    private function _collect_data()
    {
        $content = trim((string) $this->input->post('content', TRUE));

        return [
            'name'       => trim((string) $this->input->post('name', TRUE)),
            'sort_order' => (int) $this->input->post('sort_order', TRUE),   // thứ tự hiện trên trang chủ
            'content'    => $content === '' ? NULL : $content,
        ];
    }

    /** Slug nhập tay hoặc tạo từ tên, trùng thì thêm -2, -3... */
    private function _make_slug($name, $ignore_id = 0)
    {
        $input = trim((string) $this->input->post('slug', TRUE));
        return unique_slug('continents', $input !== '' ? $input : $name, $ignore_id);
    }

    // Upload ảnh vào uploads/continents/ (giải thích chi tiết xem Posts::_upload_image)
    private function _upload_image()
    {
        if (empty($_FILES['image']['name'])) {
            return ['file' => NULL, 'error' => ''];
        }
        $dir = FCPATH . self::UPLOAD_DIR;
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

    // Xoá 1 file ảnh trong uploads/continents/ (basename chống xoá file ngoài thư mục)
    private function _delete_image($file)
    {
        $file = basename((string) $file);
        if ($file !== '' && is_file(FCPATH . self::UPLOAD_DIR . $file)) {
            @unlink(FCPATH . self::UPLOAD_DIR . $file);
        }
    }
}
