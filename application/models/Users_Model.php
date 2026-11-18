<?php 
    defined('BASEPATH') OR exit('No direct script access allowed');
    /**
    * @property CI_DB_query_builder $db
    */
    class Users_model extends CI_Model {
        protected $table;
        public function __construct()
        {
            parent::__construct();
            $this->table = 'users';
        }

        public function getUserByUsername($username)
        {
            $this->db->where('username', $username);
            return $this->db->get($this->table)->row();
        }
    }
?>