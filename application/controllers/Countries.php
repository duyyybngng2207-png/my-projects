<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quản lý quốc gia (admin), cùng cách làm với Places.
 * Bảng countries: id, name, slug, continent_id, code, image, content, is_featured,
 *                 crawler_href, crawler_credit, created_at, updated_at
 * Phân cấp: continents > countries (continent_id) > places (country_id) > hotels (place_id).
 *
 * @property Countries_model    $Countries_model
 * @property Continents_model   $Continents_model
 * @property Places_model       $Places_model
 * @property CI_Form_validation $form_validation
 * @property CI_Upload          $upload
 * @property CI_Pagination      $pagination
 * @property CI_URI             $uri
 */
// /admin/countries: CRUD quốc gia (form có ô chọn châu lục + upload ảnh)
class Countries extends Admin_Controller
{
    const PER_PAGE = 10;

    /** Ảnh quốc gia (admin upload + crawler) nằm ở uploads/countries/, DB chỉ lưu tên file. */
    const UPLOAD_DIR = 'uploads/countries/';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Countries_model');
        $this->load->model('Continents_model');    // cho ô chọn châu lục trong form
    }

    // Danh sách quốc gia
    public function index()
    {
        $this->load->library('pagination');
        $this->pagination->initialize(admin_pagination_config(
            base_url('admin/countries'),
            $this->Countries_model->countAllCountries(),
            self::PER_PAGE
        ));

        $offset = max(0, (int) $this->uri->segment(3));
        // Kèm số thành phố + giá khách sạn thấp nhất; hiện cả quốc gia chưa có thành phố
        $data['countries_list']   = $this->Countries_model->getCountriesWithStats(self::PER_PAGE, FALSE, $offset);
        $data['pagination_links'] = $this->pagination->create_links();
        $data['offset']           = $offset;

        $this->load->view('admin/partials/header');
        $this->load->view('admin/countries/list', $data);
        $this->load->view('admin/partials/footer');
    }

    // Thêm quốc gia
    public function add()
    {
        // Danh sách châu lục cho ô <select>
        $data['continents_list'] = $this->Continents_model->getAllContinents();
        $data['upload_error']    = '';

        if ($this->input->method() == 'post') {
            $this->_set_rules();

            if ($this->form_validation->run() == TRUE) {
                $upload = $this->_upload_image();

                if ($upload['error'] === '') {
                    $row = $this->_collect_data();
                    $row['slug']       = $this->_make_slug($row['name']);
                    $row['image']      = $upload['file'];
                    $row['created_at'] = date('Y-m-d H:i:s');

                    $this->Countries_model->insertCountry($row);
                    $this->session->set_flashdata('success', 'Thêm quốc gia thành công!');
                    redirect('admin/countries');
                }
                $data['upload_error'] = $upload['error'];
            }
        }

        $this->load->view('admin/partials/header');
        $this->load->view('admin/countries/add', $data);
        $this->load->view('admin/partials/footer');
    }

    // Sửa quốc gia
    public function editCountry($id)
    {
        $country = $this->Countries_model->getCountry($id);
        if (!$country) {
            $this->session->set_flashdata('error', 'Quốc gia không tồn tại');
            redirect('admin/countries');
        }

        $data['country']         = $country;
        $data['continents_list'] = $this->Continents_model->getAllContinents();
        $data['upload_error']    = '';

        if ($this->input->method() == 'post') {
            $this->_set_rules();

            if ($this->form_validation->run() == TRUE) {
                $upload = $this->_upload_image();

                if ($upload['error'] === '') {
                    $row = $this->_collect_data();
                    $row['slug']       = $this->_make_slug($row['name'], (int) $id);
                    $row['image']      = $upload['file'] !== NULL ? $upload['file'] : $country->image;
                    $row['updated_at'] = date('Y-m-d H:i:s');

                    $this->Countries_model->updateCountry($id, $row);

                    // Đổi ảnh -> xoá ảnh cũ
                    if ($upload['file'] !== NULL) {
                        $this->_delete_image($country->image);
                    }

                    $this->session->set_flashdata('success', 'Cập nhật quốc gia thành công');
                    redirect('admin/countries');
                }
                $data['upload_error'] = $upload['error'];
            }
        }

        $this->load->view('admin/partials/header');
        $this->load->view('admin/countries/editCountry', $data);
        $this->load->view('admin/partials/footer');
    }

    // Xoá quốc gia (chỉ POST, chỉ khi không còn thành phố)
    public function deleteCountry($id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $country = $this->Countries_model->getCountry($id);
        if (!$country) {
            $this->session->set_flashdata('error', 'Quốc gia không tồn tại');
            redirect('admin/countries');
        }

        // Không xoá khi còn thành phố: tránh thành phố / khách sạn mất quốc gia mà không ai biết
        $this->load->model('Places_model');
        if ($this->Places_model->countByCountry($id) > 0) {
            $this->session->set_flashdata('error', 'Không thể xóa: quốc gia này đang có thành phố. Hãy chuyển hoặc xóa các thành phố trước.');
            redirect('admin/countries');
        }

        $this->Countries_model->deleteCountry($id);
        $this->_delete_image($country->image);

        $this->session->set_flashdata('success', 'Đã xóa quốc gia');
        redirect('admin/countries');
    }

    /* ------------------------------------------------------------------ */

    // Luật kiểm tra form
    private function _set_rules()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="text-danger">', '</div>');

        $this->form_validation->set_rules('name', 'Tên quốc gia', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('slug', 'Slug', 'trim|max_length[255]');
        // Mã quốc gia 2 chữ cái (VN, FR...), không bắt buộc
        $this->form_validation->set_rules('code', 'Mã quốc gia', 'trim|exact_length[2]|alpha');
        // callback__continent_exists -> hàm _continent_exists() bên dưới
        $this->form_validation->set_rules('continent_id', 'Châu lục', 'callback__continent_exists');
        $this->form_validation->set_rules('content', 'Nội dung', 'trim');
    }

    // Gom dữ liệu form thành mảng lưu DB
    private function _collect_data()
    {
        $code         = strtoupper(trim((string) $this->input->post('code', TRUE)));   // viết hoa: vn -> VN
        $continent_id = (int) $this->input->post('continent_id', TRUE);
        $content      = trim((string) $this->input->post('content', TRUE));

        return [
            'name'         => trim((string) $this->input->post('name', TRUE)),
            'code'         => $code === '' ? NULL : $code,
            'continent_id' => $continent_id > 0 ? $continent_id : NULL,               // không chọn -> NULL
            'content'     => $content === '' ? NULL : $content,
            'is_featured' => ($this->input->post('is_featured') === '1') ? 1 : 0,   // checkbox "nổi bật"
        ];
    }

    /** Slug nhập tay hoặc tạo từ tên, trùng thì thêm -2, -3... */
    private function _make_slug($name, $ignore_id = 0)
    {
        $input = trim((string) $this->input->post('slug', TRUE));
        return unique_slug('countries', $input !== '' ? $input : $name, $ignore_id);
    }

    // Upload ảnh vào uploads/countries/ (giải thích chi tiết xem Posts::_upload_image)
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

    /** Châu lục không bắt buộc (quốc gia chưa phân loại vẫn lưu được), nhưng nếu chọn thì phải tồn tại. */
    public function _continent_exists($id)
    {
        // Để trống -> hợp lệ; có chọn -> phải là số và có trong bảng continents
        if ((string) $id === '' || (ctype_digit((string) $id) && $this->Continents_model->getContinent((int) $id))) {
            return TRUE;
        }
        $this->form_validation->set_message('_continent_exists', 'Châu lục không tồn tại');
        return FALSE;
    }

    // Xoá 1 file ảnh trong uploads/countries/
    private function _delete_image($file)
    {
        $file = basename((string) $file);
        if ($file !== '' && is_file(FCPATH . self::UPLOAD_DIR . $file)) {
            @unlink(FCPATH . self::UPLOAD_DIR . $file);
        }
    }
}
