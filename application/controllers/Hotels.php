<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quản lý khách sạn (admin) theo bảng hotels mới:
 * id, place_id, name, slug, thumbnail, description, address, phone, price, rating, rating_count,
 * albums (JSON), image, crawler_href, amenities (JSON), is_trending, faqs (JSON), created_at, updated_at
 *
 * Trên form: albums / amenities nhập mỗi dòng 1 mục, faqs nhập câu hỏi 1 dòng + câu trả lời ở dòng dưới.
 * Controller tự chuyển qua lại với JSON (cùng định dạng crawler/brand đang lưu).
 *
 * @property Hotels_model $Hotels_model
 * @property Places_model $Places_model
 * @property CI_Form_validation $form_validation
 * @property CI_Upload $upload
 * @property CI_Pagination $pagination
 * @property CI_URI $uri
 */
class Hotels extends Admin_Controller
{
    const PER_PAGE = 10;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Hotels_model');
        $this->load->model('Places_model');
    }

    public function index()
    {
        $this->load->library('pagination');
        $this->pagination->initialize(admin_pagination_config(
            base_url('admin/hotels'),
            $this->Hotels_model->countAllHotels(),
            self::PER_PAGE
        ));

        $offset = max(0, (int) $this->uri->segment(3));
        $data['hotels_list']      = $this->Hotels_model->getAllHotels(self::PER_PAGE, $offset);
        $data['pagination_links'] = $this->pagination->create_links();
        $data['offset']           = $offset;
        $data['places_list']      = $this->Places_model->getAllPlaces();   // cho form crawl

        $this->load->view('admin/partials/header');
        $this->load->view('admin/hotels/list', $data);
        $this->load->view('admin/partials/footer');
    }

    public function add()
    {
        $data['places_list']  = $this->Places_model->getAllPlaces();
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

                    $this->Hotels_model->insertHotel($row);
                    $this->session->set_flashdata('success', 'Thêm khách sạn thành công!');
                    redirect('admin/hotels');
                }
                $data['upload_error'] = $upload['error'];
            }
        }

        $this->load->view('admin/partials/header');
        $this->load->view('admin/hotels/add', $data);
        $this->load->view('admin/partials/footer');
    }

    public function editHotel($id)
    {
        $hotel = $this->Hotels_model->getHotel($id);
        if (!$hotel) {
            $this->session->set_flashdata('error', 'Khách sạn không tồn tại');
            redirect('admin/hotels');
        }

        $data['hotel']        = $hotel;
        $data['places_list']  = $this->Places_model->getAllPlaces();
        $data['upload_error'] = '';

        // Giá trị JSON trong DB -> dạng từng dòng để hiện trên form
        $data['albums_text']    = $this->_json_to_lines($hotel->albums);
        $data['amenities_text'] = $this->_json_to_lines($hotel->amenities);
        $data['faqs_text']      = $this->_faqs_to_lines($hotel->faqs);

        if ($this->input->method() == 'post') {
            $this->_set_rules();

            if ($this->form_validation->run() == TRUE) {
                $upload = $this->_upload_image();

                if ($upload['error'] === '') {
                    $row = $this->_collect_data();
                    $row['slug']       = $this->_make_slug($row['name'], (int) $id);
                    $row['image']      = $upload['file'] !== NULL ? $upload['file'] : $hotel->image;
                    $row['updated_at'] = date('Y-m-d H:i:s');

                    $this->Hotels_model->updateHotel($id, $row);

                    // Có ảnh mới thì xoá file ảnh cũ
                    if ($upload['file'] !== NULL && !empty($hotel->image)) {
                        $old = FCPATH . 'uploads/hotels/' . $hotel->image;
                        if (is_file($old)) {
                            unlink($old);
                        }
                    }

                    $this->session->set_flashdata('success', 'Cập nhật khách sạn thành công!');
                    redirect('admin/hotels');
                }
                $data['upload_error'] = $upload['error'];
            }
        }

        $this->load->view('admin/partials/header');
        $this->load->view('admin/hotels/editHotel', $data);
        $this->load->view('admin/partials/footer');
    }

    public function deleteHotel($id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $hotel = $this->Hotels_model->getHotel($id);
        if (!$hotel) {
            $this->session->set_flashdata('error', 'Khách sạn không tồn tại');
            redirect('admin/hotels');
        }

        $this->Hotels_model->deleteHotel($id);

        if (!empty($hotel->image)) {
            $path = FCPATH . 'uploads/hotels/' . $hotel->image;
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->session->set_flashdata('success', 'Đã xóa khách sạn thành công');
        redirect('admin/hotels');
    }

    /* ------------------------------------------------------------------ */

    /** Luật kiểm tra dùng chung cho thêm mới / sửa (độ dài theo cột trong DB). */
    private function _set_rules()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="text-danger">', '</div>');

        $this->form_validation->set_rules('name', 'Tên khách sạn', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('slug', 'Slug', 'trim|max_length[255]');
        $this->form_validation->set_rules('place_id', 'Địa điểm', 'callback__place_exists');
        $this->form_validation->set_rules('address', 'Địa chỉ', 'trim|max_length[255]');
        $this->form_validation->set_rules('phone', 'Số điện thoại', 'trim|max_length[20]|regex_match[/^[0-9+\-\s().]*$/]');
        $this->form_validation->set_rules('price', 'Giá', 'trim|required|is_natural');
        $this->form_validation->set_rules('rating', 'Số sao', 'in_list[,1,2,3,4,5]');
        $this->form_validation->set_rules('rating_count', 'Số lượt đánh giá', 'trim|is_natural');
        $this->form_validation->set_rules('thumbnail', 'Ảnh đại diện (URL)', 'trim|max_length[255]|callback__valid_url_or_empty');
        $this->form_validation->set_rules('crawler_href', 'Link nguồn', 'trim|max_length[255]|callback__valid_url_or_empty');
        $this->form_validation->set_rules('description', 'Mô tả', 'trim');
        $this->form_validation->set_rules('albums', 'Album ảnh', 'trim');
        $this->form_validation->set_rules('amenities', 'Tiện ích', 'trim');
        $this->form_validation->set_rules('faqs', 'Câu hỏi thường gặp', 'trim|callback__valid_faqs');
    }

    /** Lấy dữ liệu từ form, chuẩn hoá đúng kiểu cột (chuỗi rỗng -> NULL, danh sách -> JSON). */
    private function _collect_data()
    {
        $nullable = function ($field) {
            $value = trim((string) $this->input->post($field, TRUE));
            return $value === '' ? NULL : $value;
        };

        $rating       = $nullable('rating');
        $rating_count = $nullable('rating_count');
        $place_id     = $nullable('place_id');

        return [
            'name'         => trim((string) $this->input->post('name', TRUE)),
            'place_id'     => $place_id === NULL ? NULL : (int) $place_id,
            'address'      => $nullable('address'),
            'phone'        => $nullable('phone'),
            'price'        => (int) $this->input->post('price'),
            'rating'       => $rating === NULL ? NULL : (int) $rating,
            'rating_count' => $rating_count === NULL ? NULL : (int) $rating_count,
            'thumbnail'    => $nullable('thumbnail'),
            'crawler_href' => $nullable('crawler_href'),
            'description'  => $nullable('description'),
            'albums'       => $this->_lines_to_json($this->input->post('albums', TRUE)),
            'amenities'    => $this->_lines_to_json($this->input->post('amenities', TRUE)),
            'faqs'         => $this->_lines_to_faqs($this->input->post('faqs', TRUE)),
            'is_trending'  => ($this->input->post('is_trending') === '1') ? 1 : 0,
        ];
    }

    /** Slug nhập tay (hoặc từ tên nếu để trống), chuẩn hoá và tự thêm -2, -3... nếu trùng. */
    private function _make_slug($name, $ignore_id = 0)
    {
        $input = trim((string) $this->input->post('slug', TRUE));
        return unique_slug('hotels', $input !== '' ? $input : $name, $ignore_id);
    }

    /**
     * Upload ảnh (không bắt buộc).
     * @return array ['file' => tên file mới hoặc NULL nếu không chọn file, 'error' => '' hoặc thông báo lỗi]
     */
    private function _upload_image()
    {
        if (empty($_FILES['image']['name'])) {
            return ['file' => NULL, 'error' => ''];
        }

        $dir = FCPATH . 'uploads/hotels/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, TRUE);
        }

        $this->load->library('upload', [
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp',
            'max_size'      => 2048,
            'encrypt_name'  => TRUE,   // tên file ngẫu nhiên, tránh ký tự lạ làm hỏng URL
        ]);

        if (!$this->upload->do_upload('image')) {
            return ['file' => NULL, 'error' => $this->upload->display_errors('<div class="text-danger">', '</div>')];
        }
        return ['file' => $this->upload->data('file_name'), 'error' => ''];
    }

    /* ----- Callback kiểm tra ----- */

    public function _place_exists($id)
    {
        if ($id === '' || $id === NULL) {
            return TRUE;
        }
        if ($this->Places_model->getPlace($id)) {
            return TRUE;
        }
        $this->form_validation->set_message('_place_exists', 'Địa điểm không tồn tại');
        return FALSE;
    }

    public function _valid_url_or_empty($url)
    {
        if ($url === '' || $url === NULL || filter_var($url, FILTER_VALIDATE_URL)) {
            return TRUE;
        }
        $this->form_validation->set_message('_valid_url_or_empty', '{field} phải là một đường dẫn hợp lệ (bắt đầu bằng http:// hoặc https://)');
        return FALSE;
    }

    public function _valid_faqs($text)
    {
        // Các dòng (bỏ dòng trống) đi theo cặp: câu hỏi, câu trả lời -> số dòng phải chẵn
        $lines = $this->_split_lines($text);
        if (count($lines) % 2 !== 0) {
            $this->form_validation->set_message('_valid_faqs', 'Câu hỏi "' . end($lines) . '" của {field} chưa có câu trả lời (câu trả lời viết ở dòng ngay dưới câu hỏi)');
            return FALSE;
        }
        return TRUE;
    }

    /* ----- Chuyển đổi danh sách <-> JSON ----- */

    private function _split_lines($text)
    {
        $lines = preg_split('/\R/u', (string) $text);
        return array_values(array_filter(array_map('trim', $lines), 'strlen'));
    }

    /** Mỗi dòng 1 mục -> JSON array (rỗng -> NULL). */
    private function _lines_to_json($text)
    {
        $items = $this->_split_lines($text);
        return $items ? json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : NULL;
    }

    /** JSON array -> mỗi dòng 1 mục (để hiện lên textarea). */
    private function _json_to_lines($json)
    {
        $items = json_decode((string) $json, TRUE);
        if (!is_array($items)) {
            return (string) $json;   // dữ liệu cũ không phải JSON: hiện nguyên văn
        }
        $out = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $item = isset($item['url']) ? $item['url'] : (isset($item['name']) ? $item['name'] : json_encode($item));
            }
            $out[] = $item;
        }
        return implode("\n", $out);
    }

    /**
     * Textarea -> JSON [{question, answer}] (cùng định dạng crawler).
     * Dòng câu hỏi, dòng dưới là câu trả lời; dòng trống giữa các cặp chỉ để dễ nhìn, được bỏ qua.
     */
    private function _lines_to_faqs($text)
    {
        $faqs = [];
        foreach (array_chunk($this->_split_lines($text), 2) as $pair) {
            $question = $pair[0];
            $answer   = isset($pair[1]) ? $pair[1] : '';
            $faqs[]   = ['question' => $question, 'answer' => $answer];
        }
        return $faqs ? json_encode($faqs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : NULL;
    }

    /**
     * JSON [{question, answer}] -> textarea:
     *   Câu hỏi 1
     *   Câu trả lời 1
     *
     *   Câu hỏi 2
     *   Câu trả lời 2
     */
    private function _faqs_to_lines($json)
    {
        $faqs = json_decode((string) $json, TRUE);
        if (!is_array($faqs)) {
            return (string) $json;
        }
        $out = [];
        foreach ($faqs as $faq) {
            $q = isset($faq['question']) ? $faq['question'] : '';
            $a = isset($faq['answer']) ? $faq['answer'] : '';
            // Câu hỏi / trả lời mỗi cái gói trong 1 dòng (gộp xuống dòng bên trong) để không lệch cặp
            $out[] = trim(preg_replace('/\s+/u', ' ', $q)) . "\n" . trim(preg_replace('/\s+/u', ' ', $a));
        }
        return implode("\n\n", $out);
    }
}
