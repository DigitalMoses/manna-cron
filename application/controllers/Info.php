<?php

/**
 * Created by PhpStorm.
 * User: developer
 * Date: 8/9/16
 * Time: 12:18 PM
 */
class Info extends CI_Controller
{
    public function __construct() {
        parent::__construct();
    }

    public function terms() {
        $this->load->template('terms');
    }

    public function policy() {
        $this->load->template('policy');
    }
}