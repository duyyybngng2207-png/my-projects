<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Session $session
 */
class Logout extends CI_Controller
{
    public function index()
    {
        $this->session->sess_destroy();  // hủy toàn bộ session của người dùng
        redirect('login');   // chuyển về trang đăng nhập
    }
}