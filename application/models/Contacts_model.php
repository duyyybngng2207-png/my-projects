<?php defined('BASEPATH') OR exit('No direct script access allowed');

// Model bảng contacts: lưu tin nhắn khách gửi từ form liên hệ (controllers/Contact.php)
class Contacts_model extends CI_Model
{
    // Thêm 1 tin nhắn; $data = ['name' => ..., 'email' => ..., 'message' => ..., 'created_at' => ...]
    // Trả TRUE nếu insert thành công
    public function addContact($data)
    {
        return $this->db->insert('contacts', $data);
    }
}
