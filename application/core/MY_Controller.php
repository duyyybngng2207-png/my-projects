<?php
// Chặn truy cập trực tiếp file này qua trình duyệt (chỉ chạy khi đi qua index.php của CI)
defined('BASEPATH') OR exit('No direct script access allowed');

// ======================================================================
// FILE NÀY CHỨA 3 LỚP CONTROLLER CHA, mọi controller của dự án kế thừa 1 trong 3:
//   Admin_Controller   -> trang quản trị (Places, Hotels, Posts...): bắt buộc là admin
//   Site_Controller    -> trang công khai (Home, Blog, About...): nạp sẵn dữ liệu footer
//   Crawler_Controller -> crawler (crawler/Brand): là admin hoặc chạy từ dòng lệnh
// CI tự nạp file core/MY_Controller.php nên các lớp ở đây dùng được ở mọi controller.
// ======================================================================

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
    // Hàm khởi tạo: chạy TRƯỚC mọi hàm của controller con (index, add, edit...)
    public function __construct()
    {
        // Gọi khởi tạo của CI_Controller (nạp session, database... theo autoload.php)
        parent::__construct();
        // Nạp helpers/admin_helper.php (các hàm hiển thị dùng trong view admin)
        $this->load->helper('admin');

        // Kiểm tra quyền ngay từ đầu: không phải admin thì dừng luôn, không chạy tới controller con
        $this->requireAdmin();
    }

    /**
     * Chưa đăng nhập -> trang login; đăng nhập nhưng không phải admin -> trang chủ.
     * Request AJAX nhận JSON 401/403 thay vì bị redirect sang trang HTML.
     */
    protected function requireAdmin()
    {
        // Session 'is_logged_in' được ghi lúc đăng nhập thành công (controllers/Login.php)
        if (!$this->session->userdata('is_logged_in')) {
            // Chưa đăng nhập -> 401, chuyển về trang /login
            $this->deny(401, 'Bạn cần đăng nhập', 'login');
        }

        // Đã đăng nhập nhưng role khác 'admin' (vd tài khoản thường) -> 403, về trang chủ
        if ($this->session->userdata('role') !== 'admin') {
            $this->deny(403, 'Bạn không có quyền truy cập trang này', '');
        }
    }

    /** Chặn request: AJAX -> JSON lỗi, request thường -> redirect. Luôn dừng script. */
    private function deny($status, $message, $redirect)
    {
        // Request gửi bằng JavaScript (AJAX) -> trả JSON để JS tự hiện lỗi
        if ($this->input->is_ajax_request()) {
            $this->output
                ->set_status_header($status)                              // mã HTTP 401 / 403
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode(['status' => false, 'message' => $message], JSON_UNESCAPED_UNICODE))
                ->_display();                                             // gửi ngay ra trình duyệt
            exit;                                                         // dừng hẳn, không chạy controller con
        }

        // Request thường (mở trang) -> chuyển hướng
        redirect($redirect); // redirect() tự exit
    }
}

/**
 * Controller trang công khai: nạp sẵn dữ liệu footer.
 *
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property Posts_model $Posts_model
 * @property Countries_model $Countries_model
 */
class Site_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Nạp 2 model dùng cho footer (controller con cũng dùng lại được: $this->Posts_model...)
        $this->load->model('Posts_model');
        $this->load->model('Countries_model');

        // Biến dùng chung cho footer ở mọi trang công khai.
        // Request AJAX chỉ trả JSON, không có footer -> bỏ qua 2 truy vấn này cho nhẹ server.
        if (!$this->input->is_ajax_request()) {
            // load->vars(): biến dùng được trong MỌI view nạp sau đó (footer.php đọc $footer_posts)
            $this->load->vars('footer_posts', $this->Posts_model->getPublishedPosts(3));            // 3 bài mới nhất
            $this->load->vars('footer_countries', $this->Countries_model->getCountriesWithStats(12)); // 12 quốc gia
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
    // Ghi đè hàm kiểm tra quyền của Admin_Controller
    protected function requireAdmin()
    {
        // Chạy bằng lệnh "php index.php crawler/brand/..." -> cho qua
        if (is_cli()) {
            return;
        }
        // Chạy qua trình duyệt -> kiểm tra như trang admin bình thường
        parent::requireAdmin();
    }

    /**
     * Request có phải POST không.
     * Không dùng $this->input->method(): khi chạy CLI không có REQUEST_METHOD,
     * CI truyền null vào strtolower() -> Deprecated trên PHP 8.1+.
     */
    protected function isPost()
    {
        // Ép về chuỗi để CLI (không có REQUEST_METHOD) không bị lỗi
        return strtoupper((string) $this->input->server('REQUEST_METHOD')) === 'POST';
    }

    /** Lấy tham số từ form (POST) hoặc query string (GET). */
    protected function param($name)
    {
        // Tham số thứ 2 = TRUE: lọc XSS
        $value = $this->isPost()
            ? $this->input->post($name, TRUE)   // gửi từ form admin
            : $this->input->get($name, TRUE);   // gõ trên URL: ?url=...
        // Không có tham số -> chuỗi rỗng (không phải NULL) để so sánh cho dễ
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

        // Crawl từ form admin: lưu thông báo vào flashdata (chỉ hiện 1 lần) rồi quay lại trang admin
        if ($this->isPost()) {
            $this->session->set_flashdata($status ? 'success' : 'error', $message);
            redirect($redirect);
        }

        // Dữ liệu crawl có thể chứa UTF-8 lỗi -> json_encode trả false (body rỗng).
        // JSON_INVALID_UTF8_SUBSTITUTE thay ký tự lỗi bằng U+FFFD thay vì làm hỏng cả response.
        $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            // Vẫn không mã hoá được -> trả JSON báo lỗi thay vì trang trắng
            $status = false;
            $json   = json_encode(['status' => false, 'message' => 'Không mã hoá được kết quả: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
        }

        // GET / CLI: in JSON ra (thành công 200, thất bại 422)
        $this->output
            ->set_status_header($status ? 200 : 422)
            ->set_content_type('application/json', 'utf-8')
            ->set_output($json);
    }
}
