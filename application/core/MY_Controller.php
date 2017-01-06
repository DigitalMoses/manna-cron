<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once (APPPATH . 'core/facebook.php');

class Auth_Controller extends CI_Controller {

    function __construct()
    {
        parent::__construct();
        date_default_timezone_set('UTC');

        if ( empty($_SESSION['facebook_access_token']) ) {
            redirect('index.php/Login');
        }
    }
}
