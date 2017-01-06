<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class User extends CI_Model {

    public function get_default_token() {

        $token = '';
        $isOk = false;
        $query = $this->db->query("SELECT `access_token` FROM `facebook_user` WHERE `is_cron_user` = TRUE LIMIT 1");

        if ($query->num_rows() > 0) {
            $isOk = true;
        } else {
            $query = $this->db->query("SELECT `access_token` FROM `facebook_user` LIMIT 1");
            if ($query->num_rows() > 0) {
                $isOk = true;
            }
        }

        if ($isOk) {
            $row = $query->row();
            $token = $row->access_token;
        }

        return $token;
    }

    public function get_users() {
        $query = $this->db->query("SELECT * FROM `facebook_user` WHERE 1");
        $result = $query->result_array();
        return $result;
    }

    public function update_user($user) {

        $dumb = array(
            "id" => $user["id"],
            "user_name" => $user['name'],
            "user_email" => $user['email'],
            "access_token" => $_SESSION["facebook_access_token"],
        );

        if (isset($user['is_cron_user'])) $dumb["is_cron_user"] = $user['is_cron_user'];

        $query = $this->db->query("SELECT * FROM `facebook_user` WHERE `is_cron_user`= TRUE");
        if ($query->num_rows() == 0) {
            $dumb["is_cron_user"] = true;
        }

        $query = $this->db->query("SELECT * FROM `facebook_user` WHERE `id`={$user['id']}");
        if ($query->num_rows() > 0) {
            $this->db->where(array("id" => $user["id"]));
            $this->db->update("facebook_user", $dumb);
        } else {
            $this->db->insert("facebook_user", $dumb);
        }
    }

    public function get_fuid_by_ft($token) {
        $result = $this->db->query("SELECT `id` FROM `facebook_user` WHERE `access_token`='{$token}' LIMIT 1");
        $row = $result->row();
        return $row->id;
    }

    // Insert registration data in database
    /*
    public function registration_insert($data) {

        // Query to check whether username already exist or not
        $condition = "user_name =" . "'" . $data['user_name'] . "'";
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where($condition);
        $this->db->limit(1);
        $query = $this->db->get();
        if ($query->num_rows() == 0) {
            // Query to insert data in database
            $this->db->insert('users', $data);
            if ($this->db->affected_rows() > 0) {
                return true;
            }
        } else {
            return false;
        }
    }
    */

    // Read data using username and password
    /*
    public function login($data) {

        $condition = "user_name =" . "'" . $data['username'] . "' AND " . "user_password =" . "'" . md5($data['password']) . "'";
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where($condition);
        $this->db->limit(1);
        $query = $this->db->get();

        if ($query->num_rows() == 1) {
            return true;
        } else {
            return false;
        }
    }
    */

    // Read data from database to show data in admin page
    /*
    public function read_user_information($username) {

        $condition = "user_name =" . "'" . $username . "'";
        $this->db->select('*');
        $this->db->from('users');
        $this->db->where($condition);
        $this->db->limit(1);
        $query = $this->db->get();

        if ($query->num_rows() == 1) {
            return $query->result();
        } else {
            return false;
        }
    }
    */
}
