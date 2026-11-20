<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Giới hạn số request theo IP trong một khoảng thời gian (fixed window), chống spam AJAX.
 *
 * Lưu bộ đếm trong application/cache/ratelimit/ (mỗi IP + hành động 1 file nhỏ),
 * dùng flock() để đếm đúng khi nhiều request tới cùng lúc. Không cần Redis/APCu.
 *
 * Cách dùng trong controller:
 *   $this->load->library('rate_limiter');
 *   if (!$this->rate_limiter->attempt('search_suggest', 30, 10)) {
 *       return $this->rate_limiter->deny_json();   // HTTP 429
 *   }
 */
// Đang được dùng ở: gợi ý tìm kiếm, lọc trang quốc gia, form liên hệ, nút "More" trang đặt lịch
class Rate_limiter
{
    /** @var CI_Controller */
    protected $CI;          // đối tượng CI hiện tại (để dùng $this->CI->input, ->output)

    /** @var string */
    protected $dir;         // thư mục chứa các file bộ đếm

    /** Số giây phải chờ của lần bị chặn gần nhất */
    protected $retry_after = 0;

    public function __construct()
    {
        // Library không kế thừa controller nên phải lấy instance CI để dùng input/output
        $this->CI  =& get_instance();
        $this->dir = APPPATH . 'cache/ratelimit/';

        // Chưa có thư mục thì tạo (TRUE = tạo cả thư mục cha nếu thiếu)
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0755, TRUE);
        }

        // Thỉnh thoảng (1%) dọn file của các cửa sổ đã hết hạn
        if (mt_rand(1, 100) === 1) {
            $this->cleanup();
        }
    }

    /**
     * Ghi nhận 1 request. Trả FALSE nếu IP này đã vượt $max request trong $seconds giây.
     *
     * @param string $action  Tên hành động, ví dụ 'search_suggest'
     * @param int    $max     Số request tối đa trong một cửa sổ
     * @param int    $seconds Độ dài cửa sổ (giây)
     */
    public function attempt($action, $max, $seconds)
    {
        // Mỗi cặp (hành động + IP) có 1 bộ đếm riêng -> tên file = md5 của cặp đó
        $key  = $action . '|' . $this->CI->input->ip_address();
        $file = $this->dir . md5($key) . '.json';
        $now  = time();

        // 'c+' = mở để đọc + ghi, chưa có thì tạo, KHÔNG xoá nội dung cũ
        $fp = @fopen($file, 'c+');
        if ($fp === FALSE) {
            // Không ghi được bộ đếm thì cho qua, tránh chặn nhầm người dùng thật
            log_message('error', 'Rate_limiter: cannot open ' . $file);
            return TRUE;
        }

        // Khoá file: request khác tới cùng lúc phải chờ -> không bị đếm sai
        flock($fp, LOCK_EX);
        // Nội dung file dạng {"count": 5, "reset": <thời điểm hết cửa sổ>}
        $data = json_decode((string) stream_get_contents($fp), TRUE);

        // File rỗng / hỏng / cửa sổ cũ đã hết hạn -> mở cửa sổ mới, đếm lại từ 0
        if (!is_array($data) || empty($data['reset']) || $data['reset'] <= $now) {
            $data = ['count' => 0, 'reset' => $now + $seconds];
        }
        // Tính thêm request hiện tại
        $data['count']++;

        // Ghi đè lại file: xoá nội dung -> về đầu file -> ghi JSON mới
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        fflush($fp);
        // Mở khoá + đóng file
        flock($fp, LOCK_UN);
        fclose($fp);

        // Vượt giới hạn -> ghi nhớ số giây phải chờ để deny_json() báo cho người dùng
        if ($data['count'] > $max) {
            $this->retry_after = max(1, $data['reset'] - $now);
            return FALSE;
        }
        return TRUE;
    }

    /** Số giây còn phải chờ (sau khi attempt() trả FALSE). */
    public function retry_after()
    {
        return $this->retry_after;
    }

    /**
     * Trả về HTTP 429 dạng JSON kèm header Retry-After.
     *
     * @param array $extra Thêm trường vào JSON (ví dụ csrf_hash cho form)
     */
    public function deny_json($extra = [])
    {
        $this->CI->output
            ->set_status_header(429)                              // 429 = Too Many Requests
            ->set_header('Retry-After: ' . $this->retry_after)    // báo trình duyệt chờ bao lâu
            ->set_content_type('application/json')
            ->set_output(json_encode(array_merge([
                'success'     => FALSE,
                'error'       => 'too_many_requests',
                'message'     => 'Too many requests. Please wait ' . $this->retry_after . ' seconds and try again.',
                'retry_after' => $this->retry_after,
            ], $extra)));
    }

    /** Xoá file bộ đếm cũ hơn 1 giờ. */
    protected function cleanup()
    {
        // Duyệt mọi file bộ đếm, file không được sửa trong 1 giờ qua thì xoá
        foreach ((array) glob($this->dir . '*.json') as $f) {
            if (is_file($f) && filemtime($f) < time() - 3600) {
                @unlink($f);
            }
        }
    }
}
