<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Ads extends CI_Model {

    public function clear() {
        $this->db->query("DELETE FROM actions");
        $this->db->query("ALTER TABLE actions AUTO_INCREMENT = 1");

        $this->db->query("DELETE FROM action_values");
        $this->db->query("ALTER TABLE action_values AUTO_INCREMENT = 1");

        $this->db->query("DELETE FROM cost_per_action_type");
        $this->db->query("ALTER TABLE cost_per_action_type AUTO_INCREMENT = 1");

        $this->db->query("DELETE FROM cost_per_unique_action_type");
        $this->db->query("ALTER TABLE cost_per_unique_action_type AUTO_INCREMENT = 1");

        $this->db->query("DELETE FROM unique_actions");
        $this->db->query("ALTER TABLE unique_actions AUTO_INCREMENT = 1");

        $this->db->query("DELETE FROM website_ctr");
        $this->db->query("ALTER TABLE website_ctr AUTO_INCREMENT = 1");

        $this->db->query("DELETE FROM ad_details");
        $this->db->query("ALTER TABLE ad_details AUTO_INCREMENT = 1");

        $this->db->query("DELETE FROM ad_and_insights_date");
        $this->db->query("ALTER TABLE ad_and_insights_date AUTO_INCREMENT = 1");

        $this->db->query("DELETE FROM ad");
        $this->db->query("ALTER TABLE ad AUTO_INCREMENT = 1");

        $this->db->query("DELETE FROM insights_date");
        $this->db->query("ALTER TABLE insights_date AUTO_INCREMENT = 1");

        $this->db->query("DELETE FROM ad_image_creatives");
        $this->db->query("ALTER TABLE ad_image_creatives AUTO_INCREMENT = 1");

        $this->db->query("DELETE FROM ad_image");
        $this->db->query("ALTER TABLE ad_image AUTO_INCREMENT = 1");

        $this->db->query("DELETE FROM ad_account");
        $this->db->query("ALTER TABLE ad_account AUTO_INCREMENT = 1");
    }

    public function disable_ad_account($ad_account_id) {
        $this->db->query("UPDATE work_ad_accounts SET enabled=0 WHERE id={$ad_account_id}");
    }

    public function get_enabled_ad_accounts() {
        $query = $this->db->query('SELECT * FROM ad_account WHERE enabled = 1');
        return $query->result_array();
    }

    public function get_free_ad_account_and_lock_it() {
        $query = $this->db->query('SELECT
                                    TOP(1)
                                    *
                                    FROM ad_account
                                    WHERE enabled = 1 
                                    AND lock_datetime IS NULL 
                                    AND lock_session_id IS NULL 
                                    ORDER BY id
                                    ');
        if ($query->num_rows() > 0) {
            $ad_account = $query->row_array();
            $session_id = session_id();
            $this->db->query("
              UPDATE ad_account SET lock_session_id = '{$session_id}', lock_datetime = GetDate()
              WHERE id = {$ad_account['id']}
            ");

            return $ad_account;
        }

        return null;
    }

    /* not used */
    public function update_lock_datetime_for_ad_account($ad_account_id) {
        $session_id = session_id();
        $this->db->query("UPDATE ad_account SET lock_datetime = GetDate()
                          WHERE id = {$ad_account_id} AND lock_session_id = '{$session_id}'");
    }

    public function unlock_ad_account($ad_account_id = null) {
        $query = 'UPDATE ad_account SET lock_datetime = NULL, lock_session_id = NULL';
        if (isset($ad_account_id)) {
            $query .= " WHERE id = {$ad_account_id}";
        }
        $this->db->query($query);
    }

    /* not used */
    public function is_current_session_mine($ad_account_id) {
        $session_id = session_id();
        $query = $this->db->query("SELECT * FROM ad_account WHERE id={$ad_account_id} AND lock_session_id='{$session_id}'");
        return ($query->num_rows() > 0);
    }

    public function clcear_auxiliary_sign() {
        $this->db->query('UPDATE ad_and_insights_date SET is_loaded = FALSE');
    }

    public function get_next_insights_date_for_ad_account($ad_account_id, $interested_date = null) {

        if (!isset($interested_date)) {
            $query = $this->db->query("SELECT TOP(1) * FROM insights_date
                                    WHERE ad_account_id={$ad_account_id} 
                                    AND id NOT IN (SELECT insights_date_id FROM ad_and_insights_date GROUP BY insights_date_id) 
                                    ORDER BY id");
        } else {
            $query = $this->db->query("SELECT TOP(1) * FROM insights_date
                                        WHERE ad_account_id={$ad_account_id} 
                                        AND insights_date = '{$interested_date}'");
        }

        if ($query->num_rows() > 0) {
            return $query->row_array();
        }

        return null;
    }

    public function get_next_ad_for_ad_account($ad_account_id, $insights_date_id, $is_auxiliary_request) {
        if (!$is_auxiliary_request) {
            $query = $this->db->query("SELECT TOP(1) * FROM ad WHERE ad_account_id={$ad_account_id}
                                    AND id NOT IN (SELECT ad_id FROM ad_and_insights_date 
                                                      WHERE insights_date_id = '{$insights_date_id}' GROUP BY ad_id) 
                                    ORDER BY id");
        } else {
            $query = $this->db->query("
              SELECT TOP(1) * FROM ad WHERE ad_account_id={$ad_account_id}
              AND id NOT IN (SELECT ad_id FROM ad_and_insights_date 
              WHERE insights_date_id = '{$insights_date_id}' AND is_loaded = TRUE GROUP BY ad_id) 
              ORDER BY id
            ");
        }

        if ($query->num_rows() > 0) {
            $ret_array = $query->row_array();

            if ($is_auxiliary_request) {
                $this->db->query("
                  UPDATE ad_and_insights_date 
                  SET is_loaded = TRUE 
                  WHERE insights_date_id = '{$insights_date_id}' AND ad_id = {$ret_array['id']}
                ");
            }

            return $ret_array;
        }

        return null;
    }

    public function save_ad_dates($date_list) {
        $ad_account_id = $date_list[0]['ad_account_id'];
        $query = $this->db->query("SELECT insights_date FROM insights_date 
                                    WHERE ad_account_id = {$ad_account_id}");
        $array = $query->result_array();
        foreach ($date_list as $date) {
            if (!in_array(['insights_date' => $date['insights_date']], $array)) {
                $this->db->insert('insights_date', $date);
            }
        }
    }

    public function save_ad_data($ad_and_insights_date_id, $d) {
        if (count($d) > 0) {
            foreach ($d as $p) {
                $data = array(
                    'ad_and_insights_date_id'           => $ad_and_insights_date_id,

                    'breakdown'                         => $p['breakdown'],
                    'breakdown_value'                   => $p['breakdown_value'],
                    'app_store_clicks'                  => $p['app_store_clicks'],
                    'buying_type'                       => $p['buying_type'],
                    'call_to_action_clicks'             => $p['call_to_action_clicks'],
                    'canvas_avg_view_percent'           => $p['canvas_avg_view_percent'],
                    'canvas_avg_view_time'              => $p['canvas_avg_view_time'],
                    'clicks'                            => $p['clicks'],
                    'cost_per_inline_link_click'        => $p['cost_per_inline_link_click'],
                    'cost_per_inline_post_engagement'   => $p['cost_per_inline_post_engagement'],
                    'cost_per_total_action'             => $p['cost_per_total_action'],
                    'cost_per_unique_click'             => $p['cost_per_unique_click'],
                    'cost_per_unique_inline_link_click' => $p['cost_per_unique_inline_link_click'],
                    'cpc'                               => $p['cpc'],
                    'cpm'                               => $p['cpm'],
                    'cpp'                               => $p['cpp'],
                    'ctr'                               => $p['ctr'],
                    'date_start'                        => $p['date_start'],
                    'date_stop'                         => $p['date_stop'],
                    'deeplink_clicks'                   => $p['deeplink_clicks'],
                    'frequency'                         => $p['frequency'],
                    'impressions'                       => $p['impressions'],
                    'inline_link_click_ctr'             => $p['inline_link_click_ctr'],
                    'inline_link_clicks'                => $p['inline_link_clicks'],
                    'inline_post_engagement'            => $p['inline_post_engagement'],
                    'newsfeed_avg_position'             => $p['newsfeed_avg_position'],
                    'newsfeed_clicks'                   => $p['newsfeed_clicks'],
                    'newsfeed_impressions'              => $p['newsfeed_impressions'],
                    'objective'                         => $p['objective'],
                    'place_page_name'                   => $p['place_page_name'],
                    'reach'                             => $p['reach'],
                    'social_clicks'                     => $p['social_clicks'],
                    'social_impressions'                => $p['social_impressions'],
                    'social_reach'                      => $p['social_reach'],
                    'social_spend'                      => $p['social_spend'],
                    'spend'                             => $p['spend'],
                    'total_action_value'                => $p['total_action_value'],
                    'total_actions'                     => $p['total_actions'],
                    'total_unique_actions'              => $p['total_unique_actions'],
                    'unique_clicks'                     => $p['unique_clicks'],
                    'unique_ctr'                        => $p['unique_ctr'],
                    'unique_impressions'                => $p['unique_impressions'],
                    'unique_inline_link_click_ctr'      => $p['unique_inline_link_click_ctr'],
                    'unique_inline_link_clicks'         => $p['unique_inline_link_clicks'],
                    'unique_link_clicks_ctr'            => $p['unique_link_clicks_ctr'],
                    'unique_social_clicks'              => $p['unique_social_clicks'],
                    'unique_social_impressions'         => $p['unique_social_impressions'],
                    'website_clicks'                    => $p['website_clicks'],
                );

                $query = $this->db->query("SELECT id FROM ad_details WHERE ad_and_insights_date_id={$ad_and_insights_date_id} AND breakdown='{$p['breakdown']}' AND breakdown_value='{$p['breakdown_value']}'");
                $count_rows = $query->num_rows();
                if ($count_rows > 0) {
                    // тут выполняем update
                    $row = $query->row_array(0);
                    $last_ad_details_id = $row['id'];
                    $where = "id = {$last_ad_details_id}";
                    $str = $this->db->update_string('ad_details', $data, $where);
                    $this->db->query($str);

                    // если есть дубликаты - удаляем
                    if ($count_rows > 1) {
                        for ($i = 1; $i < $count_rows; $i++) {
                            $row = $query->row_array($i);
                            $this->db->query("DELETE FROM ad_details WHERE id={$row['id']}");
                        }
                    }

                } else {
                    // тут выполняем вставку новых значений
                    $this->db->insert('ad_details', $data);
                    $last_ad_details_id = $this->db->insert_id();
                }

                $this->save_array_to_table($last_ad_details_id, $p, 'action_values');
                $this->save_array_to_table($last_ad_details_id, $p, 'actions');
                $this->save_array_to_table($last_ad_details_id, $p, 'cost_per_action_type');
                $this->save_array_to_table($last_ad_details_id, $p, 'cost_per_unique_action_type');
                $this->save_array_to_table($last_ad_details_id, $p, 'unique_actions');
                $this->save_array_to_table($last_ad_details_id, $p, 'website_ctr');

            }
        }
    }

    public function mark_date_as_loaded($ad_id, $insights_date_id) {
        $ret_id = null;
        $query = $this->db->query("SELECT id FROM ad_and_insights_date WHERE ad_id = {$ad_id} AND insights_date_id = '{$insights_date_id}'");
        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            $ret_id = $row['id'];
        }
        else {
            $data = ['ad_id' => $ad_id, 'insights_date_id' => $insights_date_id];
            $this->db->insert('ad_and_insights_date', $data);
            $ret_id = $this->db->insert_id();
        }
        return $ret_id;
    }

    public function save_ad_list($ad_list) {
        $query = $this->db->query("SELECT id FROM ad WHERE ad_account_id={$ad_list[0]['ad_account_id']}");
        $array = $query->result_array();
        foreach ($ad_list as $ad) {
            if (!in_array(['id' => $ad['id']], $array)) {
                $this->db->insert('ad', $ad);
            }
        }
    }

    private function save_array_to_table($ads_details_id, $element, $table_name) {
        if (!empty($element[$table_name])) {
            foreach ($element[$table_name] as $c) {
                $query = $this->db->query("SELECT id FROM {$table_name} 
                                            WHERE ads_details_id = {$ads_details_id} 
                                            AND action_type = '{$c["action_type"]}'");
                $num_rows = $query->num_rows();
                if ($num_rows > 0) {
                    $row = $query->row_array();
                    $id = $row['id'];
                    $this->db->query("UPDATE {$table_name} SET action_value = {$c['value']} WHERE id = {$id}");
                    if ($num_rows > 1) {
                        for ($i = 1; $i < $num_rows; $i++) {
                            $row = $query->row_array($i);
                            $id = $row['id'];
                            $this->db->query("DELETE FROM {$table_name} WHERE id = {$id}");
                        }
                    }
                } else {
                    $this->db->insert($table_name, array(
                        "ads_details_id" => $ads_details_id,
                        "action_type" => $c["action_type"],
                        "action_value" => $c["value"],
                    ));
                }
            }
        }
    }

    public function save_ad_accounts($ad_accounts_list) {
        $query = $this->db->query('SELECT id FROM ad_account');
        $array = $query->result_array();
        foreach ($ad_accounts_list as $ad_account) {
            if (!in_array(['id' => $ad_account['id']], $array)) {
                $this->db->insert('ad_account', $ad_account);
            }
        }
    }

    public function save_ad_image_list($ad_images_list) {
        $ad_account_id = $ad_images_list[0]['ad_account_id'];
        $query = $this->db->query("SELECT id FROM ad_image WHERE ad_account_id={$ad_account_id}");
        $array = $query->result_array();
        foreach ($ad_images_list as $ad_image) {
            $creatives = $ad_image['creatives'];
            unset($ad_image['creatives']);
            if (!in_array(['id' => $ad_image['id']], $array)) {
                $this->db->insert('ad_image', $ad_image);
            }
            if (isset($creatives)) {
                $query2 = $this->db->query("SELECT creative_id FROM ad_image_creatives WHERE ad_image_id = '{$ad_image['id']}'");
                $creatives_array = $query2->result_array();
                foreach ($creatives as $creative)
                if (!in_array(['creative_id' => $creative], $creatives_array)) {
                    $this->db->insert('ad_image_creatives', array(
                        'ad_image_id' => $ad_image['id'],
                        'creative_id' => $creative,
                    ));
                }
            }
        }
    }

    public function save_ad_acreatives_list($ad_creatives) {
        $query = $this->db->query('SELECT id FROM ad_creative');
        $array = $query->result_array();
        foreach ($ad_creatives as $creative) {
            $item = array('id' => $creative['id']);
            if (!in_array($item, $array)) {
                $this->db->insert('ad_creative', $creative);
            }
            $array[] = $item;
        }
    }

}
