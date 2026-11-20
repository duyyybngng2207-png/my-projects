<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Session $session
 */
// Đăng xuất: URL /logout (route 'logout' -> logout/index)
// Kế thừa CI_Controller (không phải Admin_Controller) vì ai cũng có thể đăng xuất
class Logout extends CI_Controller
{
    public function index()
    {
        $this->session->sess_destroy();  // hủy toàn bộ session của người dùng
        redirect('login');   // chuyển về trang đăng nhập
    }
}
