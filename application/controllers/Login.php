<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Session $session
 */
/**
 * @property Users_model $Users_model
 */
// Đăng nhập trang quản trị: URL /login
// LUỒNG: GET -> hiện form (views/users/login.php)
//        POST -> kiểm tra username/password -> đúng: ghi session rồi vào /admin/places
//                                               sai: báo lỗi rồi quay lại /login
// Kế thừa CI_Controller (không phải Admin_Controller) vì chưa đăng nhập vẫn phải vào được trang này
class Login extends CI_Controller
{
    public function index()
    {
        // Form được gửi lên (bấm nút Đăng nhập)
        if ($this->input->method() == 'post') {
            // Lấy dữ liệu form, TRUE = lọc XSS
            $username = $this->input->post('username', TRUE);
            $password = $this->input->post('password', TRUE);

            // Tìm tài khoản theo username
            $this->load->model('Users_model');
            $user = $this->Users_model->getUserByUsername($username);

            // Có tài khoản VÀ mật khẩu khớp với mật khẩu đã mã hoá (password_hash) trong DB
            if ($user && password_verify($password, $user->password)) {
                // Lưu thông tin vào session -> Admin_Controller đọc 'is_logged_in' và 'role' để cho vào trang admin
                $this->session->set_userdata([
                    'user_id'      => $user->id,
                    'username'     => $user->username,
                    'role'         => $user->role,
                    'is_logged_in' => TRUE
                ]);
                redirect('admin/places');
            } else {
                // Sai: flashdata chỉ hiện 1 lần ở lần tải trang kế tiếp (view login.php in ra)
                $this->session->set_flashdata('error', 'Sai tài khoản hoặc mật khẩu');
                redirect('login');
            }
        } else {
            // Mở trang bình thường (GET) -> hiện form đăng nhập
            $this->load->view('users/login');
        }
    }
}
