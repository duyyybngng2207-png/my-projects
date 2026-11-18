<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quản lý địa điểm (admin), cùng cách làm với Hotels.
 * Bảng places: id, category_id, name, slug, address, image, content, rating,
 *              is_featured, crawler_href, crawler_credit, created_at, updated_at
 *
 * @property Places_model     $Places_model
 * @property Categories_model $Categories_model
 * @property CI_Form_validation $form_validation
 * @property CI_Upload        $upload
 * @property CI_Pagination    $pagination
 * @property CI_URI           $uri
 * @var array  $categories_list
 */
class Places extends Admin_Controller
{
    const PER_PAGE = 10;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Places_model');
        $this->load->model('Categories_model');
    }

    public function index()
    {
        $this->load->library('pagination');
        $this->pagination->initialize(admin_pagination_config(
            base_url('admin/places'),
            $this->Places_model->countAllPlaces(),
            self::PER_PAGE
        ));

        $offset = max(0, (int) $this->uri->segment(3));
        $data['places_list']      = $this->Places_model->getAllPlaces(self::PER_PAGE, $offset);
        $data['pagination_links'] = $this->pagination->create_links();
        $data['offset']           = $offset;

        $data['categories_list']  = $this->Categories_model->getAllCategories();


        $this->load->view('admin/partials/header');
        $this->load->view('admin/places/list', $data);
        $this->load->view('admin/partials/footer');
    }

    public function add()
    {
        $data['categories_list'] = $this->Categories_model->getAllCategories();
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

                    $this->Places_model->insertPlace($row);
                    $this->session->set_flashdata('success', 'Thêm địa điểm thành công!');
                    redirect('admin/places');
                }
                $data['upload_error'] = $upload['error'];
            }
        }

        $this->load->view('admin/partials/header');
        $this->load->view('admin/places/add', $data);
        $this->load->view('admin/partials/footer');
    }

    public function editPlace($id)
    {
        $place = $this->Places_model->getPlace($id);
        if (!$place) {
            $this->session->set_flashdata('error', 'Địa điểm không tồn tại');
            redirect('admin/places');
        }

        $data['place']           = $place;
        $data['categories_list'] = $this->Categories_model->getAllCategories();
        $data['upload_error']    = '';

        if ($this->input->method() == 'post') {
            $this->_set_rules();

            if ($this->form_validation->run() == TRUE) {
                $upload = $this->_upload_image();

                if ($upload['error'] === '') {
                    $row = $this->_collect_data();
                    $row['slug']       = $this->_make_slug($row['name'], (int) $id);
                    $row['image']      = $upload['file'] !== NULL ? $upload['file'] : $place->image;
                    $row['updated_at'] = date('Y-m-d H:i:s');

                    $this->Places_model->updatePlace($id, $row);

                    if ($upload['file'] !== NULL && !empty($place->image)) {
                        $old = FCPATH . 'uploads/places/' . $place->image;
                        if (is_file($old)) {
                            unlink($old);
                        }
                    }

                    $this->session->set_flashdata('success', 'Cập nhật địa điểm thành công');
                    redirect('admin/places');
                }
                $data['upload_error'] = $upload['error'];
            }
        }

        $this->load->view('admin/partials/header');
        $this->load->view('admin/places/editPlace', $data);
        $this->load->view('admin/partials/footer');
    }

    public function deletePlace($id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $place = $this->Places_model->getPlace($id);
        if (!$place) {
            $this->session->set_flashdata('error', 'Địa điểm không tồn tại');
            redirect('admin/places');
        }

        if ($this->Places_model->countBookings($id) > 0) {
            $this->session->set_flashdata('error', 'Không thể xóa: địa điểm này đã có lượt đặt lịch');
            redirect('admin/places');
        }

        $this->Places_model->deletePlace($id);

        if (!empty($place->image)) {
            $path = FCPATH . 'uploads/places/' . $place->image;
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->session->set_flashdata('success', 'Đã xóa địa điểm thành công');
        redirect('admin/places');
    }

    /* ------------------------------------------------------------------ */

    private function _set_rules()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="text-danger">', '</div>');

        $this->form_validation->set_rules('name', 'Tên địa điểm', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('slug', 'Slug', 'trim|max_length[255]');
        $this->form_validation->set_rules('category_id', 'Danh mục', 'callback__category_exists');
        $this->form_validation->set_rules('address', 'Địa chỉ', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('rating', 'Số sao', 'in_list[,1,2,3,4,5]');
        $this->form_validation->set_rules('content', 'Nội dung', 'trim');
    }

    private function _collect_data()
    {
        $category_id = trim((string) $this->input->post('category_id', TRUE));
        $rating      = trim((string) $this->input->post('rating', TRUE));
        $content     = trim((string) $this->input->post('content', TRUE));

        return [
            'name'           => trim((string) $this->input->post('name', TRUE)),
            'category_id'    => $category_id === '' ? NULL : (int) $category_id,
            'address'        => trim((string) $this->input->post('address', TRUE)),
            'rating'         => $rating === '' ? NULL : (int) $rating,
            'content'        => $content === '' ? NULL : $content,
            'is_featured'    => ($this->input->post('is_featured') === '1') ? 1 : 0,
        ];
    }

    private function _make_slug($name, $ignore_id = 0)
    {
        $input = trim((string) $this->input->post('slug', TRUE));
        return unique_slug('places', $input !== '' ? $input : $name, $ignore_id);
    }

    private function _upload_image()
    {
        if (empty($_FILES['image']['name'])) {
            return ['file' => NULL, 'error' => ''];
        }
        $dir = FCPATH . 'uploads/places/';
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

    public function _category_exists($id)
    {
        if ($id === '' || $id === NULL || $this->Categories_model->getCategory($id)) {
            return TRUE;
        }
        $this->form_validation->set_message('_category_exists', 'Danh mục không tồn tại');
        return FALSE;
    }
}
