<?php 
    defined('BASEPATH') OR exit('No direct script access allowed');
/**
    *  @property Contacts_model $Contacts_model
    *  @property CI_Output   $output
    *  @property CI_Security $security
    *  @property CI_Input    $input
    *  @property CI_Session  $session
    *  @property CI_Form_validation $form_validation
    *  @property Rate_limiter $rate_limiter
    */
    class Contact extends Site_Controller
    {
        public function send()
        {
            if ($this->input->method() !== 'post') {
                show_404();
            }

            $isAjax = $this->input->is_ajax_request();

            // Chống spam: mỗi IP gửi tối đa 5 tin nhắn / 10 phút
            $this->load->library('rate_limiter');
            if (!$this->rate_limiter->attempt('contact_send', 5, 600)) {
                if ($isAjax) {
                    return $this->rate_limiter->deny_json([
                        'csrf_name' => $this->security->get_csrf_token_name(),
                        'csrf_hash' => $this->security->get_csrf_hash(),
                    ]);
                }
                $this->session->set_flashdata('contact_error', 'You have sent too many messages. Please try again later.');
                redirect('/');
            }

            $this->load->library('form_validation');
            $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[255]');
            $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[255]');
            $this->form_validation->set_rules('message', 'Message', 'trim|required|max_length[2000]');

            if ($this->form_validation->run() === FALSE) {
                $msg = strip_tags(validation_errors(' ', ' '));
                if ($isAjax) {
                    return $this->_json(false, $msg);
                }
                $this->session->set_flashdata('contact_error', $msg);
                redirect('/');
            }

            $this->load->model('Contacts_model');
            $this->Contacts_model->addContact([
                'name'       => $this->input->post('name', TRUE),
                'email'      => $this->input->post('email', TRUE),
                'message'    => $this->input->post('message', TRUE),
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $ok = 'Thank you, we have received your message.';
            if ($isAjax) {
                return $this->_json(true, $ok);
            }
            $this->session->set_flashdata('contact_success', $ok);
            redirect('/');
        }

        private function _json($success, $message)
        {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success'   => $success,
                    'message'   => $message,
                    'csrf_name' => $this->security->get_csrf_token_name(),
                    'csrf_hash' => $this->security->get_csrf_hash(),
                ]));
        }

        public function index()
        {
            $data['page_css'] = ['styles/contact_styles.css', 'styles/contact_responsive.css'];
            $data['page_js']  = ['plugins/parallax-js-master/parallax.min.js', 'js/contact_custom.js'];

            $this->load->view('site/partials/header', $data);
            $this->load->view('site/contact', $data);
            $this->load->view('site/partials/footer', $data);
        }
    }

    