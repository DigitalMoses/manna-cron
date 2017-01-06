<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Home extends Auth_Controller {

    function __construct() {
        parent::__construct();        
    }

    function index() {
        if (!empty($_SESSION['USER'])) {
            $data['user'] = $_SESSION['USER'];
        }
        $this->load->model('Log');
        $this->Log->registerEvent('Home', 'Start Home controller.');
        $this->load->template('home');
    }
}

