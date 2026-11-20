<?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
    * @property CI_DB_query_builder $db
    */
    // Model bảng users: tài khoản đăng nhập trang quản trị (dùng ở controllers/Login.php)
    class Users_model extends CI_Model {
        protected $table;   // tên bảng, gán trong __construct
        public function __construct()
        {
            parent::__construct();
            $this->table = 'users';
        }

        // Tìm tài khoản theo username -> object (id, username, password đã mã hoá, role...) hoặc NULL
        public function getUserByUsername($username)
        {
            // SELECT * FROM users WHERE username = ? (CI tự escape giá trị -> chống SQL injection)
            $this->db->where('username', $username);
            // row() = lấy 1 dòng đầu tiên dạng object
            return $this->db->get($this->table)->row();
        }
    }
?>