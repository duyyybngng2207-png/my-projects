<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller trang quản trị: bắt buộc đăng nhập với role = admin.
 *
 * @property CI_DB_query_builder $db
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Output $output
 * @property CI_Session $session
 * @property CI_Form_validation $form_validation
 * @property CI_Upload $upload
 */
class Admin_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('admin');

        $this->requireAdmin();
    }

    /**
     * Chưa đăng nhập -> trang login; đăng nhập nhưng không phải admin -> trang chủ.
     * Request AJAX nhận JSON 401/403 thay vì bị redirect sang trang HTML.
     */
    protected function requireAdmin()
    {
        if (!$this->session->userdata('is_logged_in')) {
            $this->deny(401, 'Bạn cần đăng nhập', 'login');
        }

        if ($this->session->userdata('role') !== 'admin') {
            $this->deny(403, 'Bạn không có quyền truy cập trang này', '');
        }
    }

    /** Chặn request: AJAX -> JSON lỗi, request thường -> redirect. Luôn dừng script. */
    private function deny($status, $message, $redirect)
    {
        if ($this->input->is_ajax_request()) {
            $this->output
                ->set_status_header($status)
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode(['status' => false, 'message' => $message], JSON_UNESCAPED_UNICODE))
                ->_display();
            exit;
        }

        redirect($redirect); // redirect() tự exit
    }
}

/**
 * Controller trang công khai: nạp sẵn dữ liệu footer.
 *
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property Posts_model $Posts_model
 * @property Categories_model $Categories_model
 */
class Site_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Posts_model');
        $this->load->model('Categories_model');

        // Biến dùng chung cho footer ở mọi trang công khai.
        // Request AJAX chỉ trả JSON, không có footer -> bỏ qua 2 truy vấn này cho nhẹ server.
        if (!$this->input->is_ajax_request()) {
            $this->load->vars('footer_posts', $this->Posts_model->getPublishedPosts(3));
            $this->load->vars('footer_categories', $this->Categories_model->getAllCategories());
        }
    }
}

/**
 * Controller crawler: chỉ admin đã đăng nhập mới chạy được (crawl ghi thẳng vào DB).
 * Chạy từ dòng lệnh (CLI, vd cron) thì không cần đăng nhập vì chỉ người có quyền vào server mới chạy được.
 * GET ?url=...  -> trả JSON (tiện kiểm tra)
 * POST từ form trong trang admin -> báo kết quả bằng flash message rồi quay lại trang admin.
 */
class Crawler_Controller extends Admin_Controller
{
    protected function requireAdmin()
    {
        if (is_cli()) {
            return;
        }
        parent::requireAdmin();
    }

    /**
     * Request có phải POST không.
     * Không dùng $this->input->method(): khi chạy CLI không có REQUEST_METHOD,
     * CI truyền null vào strtolower() -> Deprecated trên PHP 8.1+.
     */
    protected function isPost()
    {
        return strtoupper((string) $this->input->server('REQUEST_METHOD')) === 'POST';
    }

    /** Lấy tham số từ form (POST) hoặc query string (GET). */
    protected function param($name)
    {
        $value = $this->isPost()
            ? $this->input->post($name, TRUE)
            : $this->input->get($name, TRUE);
        return trim((string) $value);
    }

    /**
     * Trả kết quả: POST -> flash + redirect về trang admin, GET/CLI -> JSON.
     * @param array  $result   ['status' => bool, 'message' => string, ...]
     * @param string $redirect Trang admin quay về sau khi crawl từ form
     */
    protected function respond(array $result, $redirect)
    {
        // Phòng trường hợp hàm crawl trả mảng thiếu key -> không bị "Undefined array key"
        $status  = !empty($result['status']);
        $message = isset($result['message']) ? (string) $result['message'] : ($status ? 'Thành công' : 'Có lỗi xảy ra');

        if ($this->isPost()) {
            $this->session->set_flashdata($status ? 'success' : 'error', $message);
            redirect($redirect);
        }

        // Dữ liệu crawl có thể chứa UTF-8 lỗi -> json_encode trả false (body rỗng).
        // JSON_INVALID_UTF8_SUBSTITUTE thay ký tự lỗi bằng U+FFFD thay vì làm hỏng cả response.
        $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            $status = false;
            $json   = json_encode(['status' => false, 'message' => 'Không mã hoá được kết quả: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
        }

        $this->output
            ->set_status_header($status ? 200 : 422)
            ->set_content_type('application/json', 'utf-8')
            ->set_output($json);
    }
}
