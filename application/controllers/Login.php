<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once (APPPATH . 'core/facebook.php');

class Login extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        if (isset($_SESSION['facebook_access_token'])) redirect('Home');
        $redirect = base_url() . 'sputnik/index.php/Login/oauth_finish';
        $data['url'] = getFacebookLoginURL($redirect);
        $this->load->template('login', $data);
    }
    
    public function logout() {        
        $this->session->sess_destroy();
        unset($_SESSION['facebook_access_token']);
        redirect('Login');
    }

    function oauth_finish() {
        $access_token = getFacebookAccessToken();
        $code = $this->input->get("code");
        $state = $this->input->get("state");

        if (!empty($access_token) && !empty($code) && !empty($state)) {
            $_SESSION['facebook_access_token'] = $access_token;
            $user = getProfile($_SESSION['facebook_access_token']);
            $_SESSION['USER'] = $user;
            $this->load->model('User');
            $this->User->update_user($user);
            redirect('Home');
        }

        redirect('Login');
    }

}
