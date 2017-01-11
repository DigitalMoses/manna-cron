<?php

/**
 * Created by PhpStorm.
 * User: Vitalik
 * Date: 9/23/16
 * Time: 6:26 PM
 */

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Log extends CI_Model {

    public function clear() {
        $this->db->query("DELETE FROM log");
        $this->db->query("ALTER TABLE log AUTO_INCREMENT = 1");
    }

    public function registerEvent($event_name, $event_message) {

        $data = array(
            'session_id' => session_id(),
            'event_name' => $event_name,
            'event_message' => $event_message,
        );

        $this->db->insert('log', $data);
    }

    public function is_script_already_running($delay) {
        $is_run = false;
        $query = $this->db->query('SELECT DATEDIFF(S, (SELECT TOP(1) created_datetime FROM log ORDER BY id DESC), GetDate()) AS idle_seconds;');
        if ($query->num_rows() > 0) {
            $row = $query->row();
            $is_run = ($row->idle_seconds < $delay);
        }
        return $is_run;
    }

}
