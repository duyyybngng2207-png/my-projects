<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Contacts_model extends CI_Model
{
    public function addContact($data)
    {
        return $this->db->insert('contacts', $data);
    }
}