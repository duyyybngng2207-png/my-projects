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
class Login extends CI_Controller
{
    public function index()
    { 
        if ($this->input->method() == 'post') {
            $username = $this->input->post('username', TRUE);
            $password = $this->input->post('password', TRUE);

            $this->load->model('Users_model');
            $user = $this->Users_model->getUserByUsername($username);

            if ($user && password_verify($password, $user->password)) {
                $this->session->set_userdata([
                    'user_id'      => $user->id,
                    'username'     => $user->username,
                    'role'         => $user->role,
                    'is_logged_in' => TRUE
                ]);
                redirect('admin/places');
            } else {
                $this->session->set_flashdata('error', 'Sai tài khoản hoặc mật khẩu');
                redirect('login');
            }
        } else {
            $this->load->view('users/login');
        }
    }
}