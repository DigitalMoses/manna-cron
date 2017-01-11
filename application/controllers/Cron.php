<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once (APPPATH . 'libraries/vendor/autoload.php');

use FacebookAds\Api;
use FacebookAds\Object\Ad;
use FacebookAds\Object\AdSet;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\AdAccountUser;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Fields\AdFields;
use FacebookAds\Object\Fields\AdSetFields;
use FacebookAds\Object\Fields\AdImageFields;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\Fields\AdAccountFields;
use FacebookAds\Object\Fields\AdCreativeFields;
use FacebookAds\Object\Fields\OffsitePixelFields;

class Cron extends CI_Controller {

    private $sleep_seconds = 120;
    private $fields = array(
        'account_id',                       // numeric string
        'account_name',                     // string
        'campaign_id',                      // numeric string
        'campaign_name',                    // string
        'adset_id',                         // numeric string
        'adset_name',                       // string
        'ad_id',                            // numeric string
        'ad_name',                          // string
        'buying_type',                      // string
        'call_to_action_clicks',            // numeric string
        'canvas_avg_view_percent',          // float
        'canvas_avg_view_time',             // float
        'clicks',                           // numeric string
        'cost_per_inline_link_click',       // float
        'cost_per_inline_post_engagement',  // float
        'cost_per_total_action',            // float
        'cost_per_unique_click',            // float
        'cost_per_unique_inline_link_click',// float
        'cpc',                              // float
        'cpm',                              // float
        'cpp',                              // float
        'ctr',                              // float
        'date_start',                       // string
        'date_stop',                        // string
        'deeplink_clicks',                  // numeric string
        'frequency',                        // float
        'impressions',                      // string
        'inline_link_click_ctr',            // float
        'inline_link_clicks',               // numeric string
        'inline_post_engagement',           // numeric string
        'objective',                        // string
        'place_page_name',                  // string
        'reach',                            // numeric string
        'social_clicks',                    // numeric string
        'social_impressions',               // numeric string
        'social_reach',                     // numeric string
        'social_spend',                     // float
        'spend',                            // float
        'total_action_value',               // float
        'total_actions',                    // numeric string
        'total_unique_actions',             // numeric string
        'unique_clicks',                    // numeric string
        'unique_ctr',                       // float
        'unique_impressions',               // numeric string
        'unique_inline_link_click_ctr',     // float
        'unique_inline_link_clicks',        // numeric string
        'unique_link_clicks_ctr',           // float
        'unique_social_clicks',             // numeric string
        'unique_social_impressions',        // numeric string
        'website_clicks',                   // numeric string
        'relevance_score',                  // AdgroupRelevanceScore [status] = "NOT_ENOUGH_IMPRESSIONS"
        'action_values',                    // ! array ! +
        'actions',                          // ! array ! +
        'cost_per_10_sec_video_view',       // ! array !
        'cost_per_action_type',             // ! array ! +
        'cost_per_unique_action_type',      // ! array ! +
        'unique_actions',                   // ! array ! +
        'video_10_sec_watched_actions',     // ! array !
        'video_15_sec_watched_actions',     // ! array !
        'video_30_sec_watched_actions',     // ! array !
        'video_avg_pct_watched_actions',    // ! array !
        'video_avg_sec_watched_actions',    // ! array !
        'video_complete_watched_actions',   // ! array !
        'video_p100_watched_actions',       // ! array !
        'video_p25_watched_actions',        // ! array !
        'video_p50_watched_actions',        // ! array !
        'video_p75_watched_actions',        // ! array !
        'video_p95_watched_actions',        // ! array !
        'website_ctr',                      // ! array ! +
    );
    private $breakdown_list = array(
        "none",
        "age",
        "country",
        "gender",
        "age,gender",
        "frequency_value",
        "hourly_stats_aggregated_by_advertiser_time_zone",
        "hourly_stats_aggregated_by_audience_time_zone",
        "impression_device",
        "place_page_id",
        "placement",
        "device_platform",
        "product_id",
        "region"
    );
    private $breakdowns_list2 = array(
        "none",
        "action_type",
        "action_target_id",
        "impression_device",
        "action_device",
        "action_device,placement",
        "action_device,placement,impression_device",
        "action_device,publisher_platform",
        "action_device,publisher_platform,impression_device",
        "action_device,publisher_platform,platform_position",
        "action_device,publisher_platform,platform_position,impression_device",
        "age",
        "gender",
        "age,gender",
        "country",
        "region",
        "placement",
        "placement,impression_device",
        "publisher_platform",
        "publisher_platform,impression_device",
        "publisher_platform,platform_position",
        "publisher_platform,platform_position,impression_device",
        "product_id",
        "hourly_stats_aggregated_by_advertiser_time_zone",
        "hourly_stats_aggregated_by_audience_time_zone",
        "action_carousel_card_id",
        "action_carousel_card_id,placement,impression_device",
        "action_carousel_card_id,country",
        "action_carousel_card_id,age",
        "action_carousel_card_id,gender",
        "action_carousel_card_id,age,gender",
        "frequency_value",
        "place_page_id",
        "device_platform"
    );
    private $ad_account_fields_list = array(
        AdAccountFields::ACCOUNT_ID,
        AdAccountFields::ID,
        AdAccountFields::NAME,
    );
    private $ad_fields_list = array(
        AdFields::ID,
        AdFields::NAME,
        AdFields::ADSET_ID,
        AdFields::CAMPAIGN_ID,
        AdFields::CREATED_TIME);
    private $ad_image_fields_list = array(
        AdImageFields::ACCOUNT_ID,
        AdImageFields::CREATED_TIME,
        AdImageFields::CREATIVES,
        AdImageFields::HASH,
        AdImageFields::HEIGHT,
        AdImageFields::ID,
        AdImageFields::NAME,
        AdImageFields::ORIGINAL_HEIGHT,
        AdImageFields::ORIGINAL_WIDTH,
        AdImageFields::PERMALINK_URL,
        AdImageFields::STATUS,
        AdImageFields::UPDATED_TIME,
        AdImageFields::URL,
        AdImageFields::URL_128,
        AdImageFields::WIDTH,
        AdImageFields::BYTES,
        AdImageFields::COPY_FROM,
        AdImageFields::ZIPBYTES,
        AdImageFields::FILENAME,
    );
    private $ad_creatives_fields_list = array(
        AdCreativeFields::ID,
        AdCreativeFields::NAME,
        AdCreativeFields::BODY,
        AdCreativeFields::ADLABELS,
        AdCreativeFields::APPLINK_TREATMENT,
        AdCreativeFields::CALL_TO_ACTION_TYPE,
        AdCreativeFields::EFFECTIVE_INSTAGRAM_STORY_ID,
        AdCreativeFields::EFFECTIVE_OBJECT_STORY_ID,
        AdCreativeFields::IMAGE_CROPS,
        AdCreativeFields::IMAGE_HASH,
        AdCreativeFields::IMAGE_URL,
        AdCreativeFields::INSTAGRAM_ACTOR_ID,
        AdCreativeFields::INSTAGRAM_PERMALINK_URL,
        AdCreativeFields::INSTAGRAM_STORY_ID,
        AdCreativeFields::LINK_OG_ID,
        AdCreativeFields::LINK_URL,
        AdCreativeFields::OBJECT_ID,
        AdCreativeFields::OBJECT_STORY_ID,
        AdCreativeFields::OBJECT_STORY_SPEC,
        AdCreativeFields::OBJECT_TYPE,
        AdCreativeFields::OBJECT_URL,
        AdCreativeFields::PLATFORM_CUSTOMIZATIONS,
        AdCreativeFields::PRODUCT_SET_ID,
        AdCreativeFields::RUN_STATUS,
        AdCreativeFields::TEMPLATE_URL,
        AdCreativeFields::THUMBNAIL_URL,
        AdCreativeFields::TITLE,
        AdCreativeFields::URL_TAGS,
        AdCreativeFields::USE_PAGE_ACTOR_OVERRIDE,
        AdCreativeFields::ACTION_SPEC,
        AdCreativeFields::CALL_TO_ACTION,
        AdCreativeFields::DYNAMIC_AD_VOICE,
        AdCreativeFields::FOLLOW_REDIRECT,
        AdCreativeFields::IMAGE_FILE,
        AdCreativeFields::OBJECT_INSTAGRAM_ID,
        AdCreativeFields::VIDEO_ID,
    );
    private $ad_offsite_pixel_fields_list = array(
        OffsitePixelFields::ID,
        OffsitePixelFields::NAME,
        OffsitePixelFields::CREATOR,
        OffsitePixelFields::TAG,
        OffsitePixelFields::LAST_FIRING_TIME,
        OffsitePixelFields::JS_PIXEL
    );
    private $token = '';
    private $facebook_user_id = '';

    public function __construct() {

        parent::__construct();

        $this->load->model('Log');
        $this->load->model('Ads');
        $this->load->model('User');
    }

    public function index() {
        log_message('debug', 'index - START');

        ini_set('max_execution_time', 999999999);
        ini_set('memory_limit', '512M');

        $amount_of_seconds_for_checking = ($this->config->item('debug_mode')) ? 1 : ($this->sleep_seconds) + 30;
        log_message('debug', 'min time since the last log date: ' + $amount_of_seconds_for_checking + ' seconds');

        $is_script_already_running = $this->Log->is_script_already_running($amount_of_seconds_for_checking);
        if ($is_script_already_running) {
            log_message('error', 'script is already running');
            return
        };

        $this->Ads->unlock_ad_account();
        $this->Log->registerEvent('Start', 'Start script.');

        $looking_date = date('Y-m-d', strtotime(date('Y-m-d') .' -1 day'));

        $this->token = $this->User->get_default_token();
        $this->facebook_user_id = $this->User->get_fuid_by_ft($this->token);

        if (isset($this->token) && !empty($this->token)) {
            try {
                Api::init(
                    $this->config->item('appID'),
                    $this->config->item('appSecret'),
                    $this->token
                );
                $this->prepare_to_work($looking_date);
                $this->work($looking_date);
            } catch (Exception $e) {
                $this->Log->registerEvent('Error', 'I can not init facebook API and got this error message: ' . $e->getMessage());
            }
        }

        $this->Log->registerEvent('Finish', 'Finish script.');
        log_message('debug', 'index - END');
    }

    private function prepare_to_work($looking_date) {
        log_message('debug', 'prepare_to_work - START');

        $this->Log->registerEvent('Message', 'Start the function: prepare_to_work');
        // получаем список Ad Accounts, т.к. могли появиться новые. В случае появления новых - добавляем в базу.
        $this->Ads->save_ad_accounts($this->get_ad_accounts());
        // получаем список активных ad accounts
        $ad_accounts_list = $this->Ads->get_enabled_ad_accounts();

        log_message('debug', 'number of accounts: ' + count($ad_accounts_list));

        if (count($ad_accounts_list) > 0) {
            foreach ($ad_accounts_list as $ad_account) {
                $ad_list = $this->get_ad_list_for_ad_account($ad_account);
                if (count($ad_list)) { $this->Ads->save_ad_list($ad_list); }

                if ($this->config->item('debug_mode')) {
                    $looking_date = date('Y-m-d', strtotime($ad_list[0]['ad_created_date'] . '+3 days'));
                }

                $this->prepare_insights_dates($ad_account, $looking_date);

                $ads_image_list = $this->get_ad_image_list_for_ad_account($ad_account);
                if (count($ads_image_list)) { $this->Ads->save_ad_image_list($ads_image_list); }

                $this->Ads->save_ad_acreatives_list($this->get_ad_creatives_by_ad($ad_list));
            }
        }
        $this->Log->registerEvent('Message', 'Finish the function: prepare_to_work');
        log_message('debug', 'prepare_to_work - END');
    }

    private function work($looking_date) {
        log_message('debug', 'work - START');

        $this->Log->registerEvent('Message', 'Start the function: work');
        // получаем свободный ad_account
        $is_auxiliary_request = false;
        $locked_accounts = array();
        $ad_account = $this->Ads->get_free_ad_account_and_lock_it();
        while (isset($ad_account) && !empty($ad_account)) {
            $locked_accounts[] = $ad_account['id'];
            $insights_date = $this->Ads->get_next_insights_date_for_ad_account($ad_account['id']);

            // тут может вернуть сразу null, показывающая, что дат нет, тогда производить запрос на
            // определенную дату
            if (!isset($insights_date)) {
                $is_auxiliary_request = true;
                $this->Ads->clcear_auxiliary_sign();
                $the_day_before_yesterday = date('Y-m-d', strtotime($looking_date .' -1 day'));
                $insights_date = $this->Ads->get_next_insights_date_for_ad_account($ad_account['id'], $the_day_before_yesterday);
            }

            while (isset($insights_date) && !empty($insights_date)) {
                $ad = $this->Ads->get_next_ad_for_ad_account($ad_account['id'], $insights_date['id'], $is_auxiliary_request);
                while (isset($ad) && !empty($ad)) {

                    $ret_data = $this->load_ad_data_from_facebook(
                        $ad['id'],
                        $insights_date['insights_date'],
                        $ad['ad_created_date']
                    );

                    // записываем в ad_and_insights_date
                    $ad_and_insights_date_id = $this->Ads->mark_date_as_loaded($ad['id'], $insights_date['id']);
                    $this->Ads->save_ad_data($ad_and_insights_date_id, $ret_data);
                    $ad = $this->Ads->get_next_ad_for_ad_account($ad_account['id'], $insights_date['id'], $is_auxiliary_request);
                }

                $old_insights_date = $insights_date;
                $insights_date = $this->Ads->get_next_insights_date_for_ad_account($ad_account['id'], ($is_auxiliary_request) ? $looking_date : null);
                if ($insights_date == $old_insights_date) { $insights_date = null; }
            }
            $ad_account = $this->Ads->get_free_ad_account_and_lock_it();
        }

        foreach ($locked_accounts as $lock_id) {
            $this->Ads->unlock_ad_account($lock_id);
        }

        $this->Log->registerEvent('Message', 'Finish the function: work');

        log_message('debug', 'work - END');
    }

    private function get_ad_accounts() {
        log_message('debug', 'get_ad_accounts - START');

        $this->Log->registerEvent('Message', 'Start the function: get_ad_accounts');

        $ad_accounts = [];
        $ad_user = new AdAccountUser("me");
        $is_success = false;

        while (!$is_success) {
            try {
                $ad_accounts_list = $ad_user->getAdAccounts($this->ad_account_fields_list, array('limit' => 9999999999));
                $is_success = true;
            } catch (Exception $e) {

                $this->Log->RegisterEvent("Error", "get_ad_accounts. Error Code: " . $e->getCode() . ". Error Message: " . $e->getMessage());

                if ($e->getCode() == 17) {
                    sleep($this->sleep_seconds);
                }
            }
        }

        foreach ($ad_accounts_list as $ad_account) {
            $ad_data = $ad_account->getData();
            $ad_accounts[] = array(
                'id' => $ad_data["account_id"],
                'facebook_user_id' => $this->facebook_user_id,
                'account_name' => $ad_data['name'],
            );
        }

        $this->Log->registerEvent('Message', 'Finish the function: get_ad_accounts');
        log_message('debug', 'get_ad_accounts - END');

        return $ad_accounts;
    }

    private function get_ad_list_for_ad_account($ad_account) {
        log_message('debug', 'get_ad_list_for_ad_account - START');

        $ad_account_id = "act_{$ad_account['id']}";
        $adAccount = new AdAccount($ad_account_id);
        $response = [];

        $is_success = false;
        while (!$is_success) {
            try {
                $limit = ($this->config->item('debug_mode')) ? 2 : 9999999999;
                $adsList = $adAccount->getAds($this->ad_fields_list, array('limit' => $limit));
                $campaignList = $this->convert_data_to_array($adAccount->getCampaigns(array(CampaignFields::ID, CampaignFields::NAME), array('limit' => 9999999999)));
                $adSetList = $this->convert_data_to_array($adAccount->getAdSets(array(AdSetFields::ID, AdSetFields::NAME), array('limit' => 9999999999)));

                foreach($adsList as $currentAd)
                {
                    $data = $currentAd->getData();
                    $ad_set_name = $this->get_name_from_list_by_id($adSetList, $data['adset_id']);
                    $campaign_name = $this->get_name_from_list_by_id($campaignList, $data['campaign_id']);

                    $response[] = array(
                        'id' => $data['id'],
                        'ad_account_id' => $ad_account['id'],
                        'ad_created_date' => date('Y-m-d', strtotime($data['created_time'])),
                        'ad_name' => $data['name'],
                        'adset_id' => $data['adset_id'],
                        'adset_name' => $ad_set_name,
                        'campaign_id' => $data['campaign_id'],
                        'campaign_name' => $campaign_name,
                    );
                }
                $is_success = true;
            } catch (Exception $e) {
                $this->Log->RegisterEvent("Error", "get_ads_list_for_adaccount. AdAccount: {$ad_account['id']}. Error Code: " . $e->getCode() . ". Error Message: " . $e->getMessage());

                if ($e->getCode() == 17)
                    sleep($this->sleep_seconds);
                else {
                    $this->Ads->disable_ad_account($ad_account['id']);
                    $is_success = true;
                }
            }
        }

        log_message('debug', 'get_ad_list_for_ad_account - END');
        return $response;
    }

    private function convert_data_to_array($objList) {
        $data = array();
        foreach ($objList as $item) {
            $data[] = $item->getData();
        }
        return $data;
    }

    private function get_name_from_list_by_id($array, $id) {
        $name = null;
        foreach ($array as $item) {
            if ($item['id'] == $id) {
                $name = $item['name'];
                break;
            }
        }
        return $name;
    }

    private function get_ad_image_list_for_ad_account($ad_account) {
        log_message('debug', 'get_ad_image_list_for_ad_account - START');

        $ad_account_id = "act_{$ad_account['id']}";
        $adAccount = new AdAccount($ad_account_id);
        $response = [];

        $is_success = false;
        while (!$is_success) {
            try {
                $adImagesList = $adAccount->getAdImages($this->ad_image_fields_list, array('limit' => 9999999999));
                foreach ($adImagesList as $adImage) {
                    $data = $adImage->getData();
                    $response[] = array(
                        'id' => $data['id'],
                        'ad_account_id' => $ad_account['id'],
                        'created_date' => date('Y-m-d', strtotime($data['created_time'])),
                        'hash' => $data['hash'],
                        'height' => $data['height'],
                        'name' => $data['name'],
                        'original_height' => $data['original_height'],
                        'original_width' => $data['original_width'],
                        'permalink_url' => $data['permalink_url'],
                        'status' => $data['status'],
                        'updated_date' => date('Y-m-d', strtotime($data['updated_time'])),
                        'url' => $data['url'],
                        'url_128' => $data['url_128'],
                        'width' => $data['width'],
                        'creatives' => $data['creatives'],
                    );
                }
                $is_success = true;
            } catch (Exception $e) {
                $this->Log->RegisterEvent("Error", "get_ads_image_list_for_ad_account. AdAccount: {$ad_account['ad_account_id']}. Error Code: " . $e->getCode() . ". Error Message: " . $e->getMessage());

                if ($e->getCode() == 17)
                    sleep($this->sleep_seconds);
                else {
                    $this->Ads->disable_ad_account($ad_account['id']);
                    $is_success = true;
                }
            }
        }

        log_message('debug', 'get_ad_image_list_for_ad_account - END');
        return $response;
    }

    private function get_ad_creatives_list_for_ad_account($ad_account)
    {
        log_message('debug', 'get_ad_creatives_list_for_ad_account - START');

        $ad_account_id = "act_{$ad_account['id']}";
        $adAccount = new AdAccount($ad_account_id);
        $response = [];

        $is_success = false;
        while (!$is_success) {
            try {
                $adCreativesList =  $adAccount->getAdCreatives($this->ad_creatives_fields_list, array('limit' => 9999999999));
                foreach ($adCreativesList as $adImage) {
                    $data = $adImage->getData();
                    $response[] = array(
                        'id' => $data['id'],
                        'ad_account_id' => $ad_account['id'],
                        'name' => $data['name'],
                        'body' => $data['body'],
                        'adlabels' => $data['adlabels'],        // *
                        'applink_treatment' => $data['applink_treatment'],
                        'call_to_action_type' => $data['call_to_action_type'],
                        'effective_instagram_story_id' => $data['effective_instagram_story_id'],
                        'effective_object_story_id' => $data['effective_object_story_id'],
                        'image_crops' => $data['image_crops'],  // *
                        'image_hash' => $data['image_hash'],
                        'image_url' => $data['image_url'],
                        'instagram_actor_id' => $data['instagram_actor_id'],
                        'instagram_permalink_url' => $data['instagram_permalink_url'],
                        'instagram_story_id' => $data['instagram_story_id'],
                        'link_og_id' => $data['link_og_id'],
                        'link_url' => $data['link_url'],
                        'object_id' => $data['object_id'],
                        'object_story_id' => $data['object_story_id'],
                        'object_story_spec' => $data['object_story_spec'],
                        'object_type' => $data['object_type'],
                        'object_url' => $data['object_url'],
                        'platform_customizations' => $data['platform_customizations'],
                        'product_set_id' => $data['product_set_id'],
                        'run_status' => $data['run_status'],
                        'template_url' => $data['template_url'],
                        'thumbnail_url' => $data['thumbnail_url'],
                        'title' => $data['title'],
                        'url_tags' => $data['url_tags'],
                        'use_page_actor_override' => $data['use_page_actor_override'],
                        'action_spec' => $data['action_spec'],
                        'call_to_action' => $data['call_to_action'],
                        'dynamic_ad_voice' => $data['dynamic_ad_voice'],
                        'follow_redirect' => $data['follow_redirect'],
                        'image_file' => $data['image_file'],
                        'object_instagram_id' => $data['object_instagram_id'],
                        'video_id' => $data['video_id'],
                    );
                }
                $is_success = true;
            } catch (Exception $e) {
                $this->Log->RegisterEvent("Error", "get_ad_creatives_list_for_ad_account. AdAccount: {$ad_account['ad_account_id']}. Error Code: " . $e->getCode() . ". Error Message: " . $e->getMessage());

                if ($e->getCode() == 17)
                    sleep($this->sleep_seconds);
                else {
                    $this->Ads->disable_ad_account($ad_account['id']);
                    $is_success = true;
                }
            }
        }
        log_message('debug', 'get_ad_creatives_list_for_ad_account - END');

        return $response;

    }

    private function get_ad_creatives_by_ad($ad_list) {
        log_message('debug', 'get_ad_creatives_by_ad - START');

        $response = [];
        foreach ($ad_list as $ad) {
            $is_success = false;
            while (!$is_success) {
                try {
                    $this->Log->registerEvent("Message","Start loading creatives for Ad Id: " . $ad['id']);
                    $obj_ad = new Ad($ad['id']);
                    $list = $obj_ad->getAdCreatives($this->ad_creatives_fields_list, array('limit' => 9999999999));
                    foreach ($list as $creative) {
                        $data = $creative->getData();
                        $response[] = array(
                            'id' => $data['id'],
                            'ad_id' => $ad['id'],
                            'name' => $data['name'],
                            'body' => $data['body'],
                            'applink_treatment' => $data['applink_treatment'],
                            'call_to_action_type' => $data['call_to_action_type'],
                            'effective_instagram_story_id' => $data['effective_instagram_story_id'],
                            'effective_object_story_id' => $data['effective_object_story_id'],
                            'image_hash' => $data['image_hash'],
                            'image_url' => $data['image_url'],
                            'instagram_actor_id' => $data['instagram_actor_id'],
                            'instagram_permalink_url' => $data['instagram_permalink_url'],
                            'instagram_story_id' => $data['instagram_story_id'],
                            'link_og_id' => $data['link_og_id'],
                            'link_url' => $data['link_url'],
                            'object_id' => $data['object_id'],
                            'object_story_id' => $data['object_story_id'],
                            'object_type' => $data['object_type'],
                            'object_url' => $data['object_url'],
//                'platform_customizations' => $data['platform_customizations'],
                            'product_set_id' => $data['product_set_id'],
                            'run_status' => $data['run_status'],
                            'template_url' => $data['template_url'],
                            'thumbnail_url' => $data['thumbnail_url'],
                            'title' => $data['title'],
                            'url_tags' => $data['url_tags'],
//                'use_page_actor_override' => $data['use_page_actor_override'],
//                'action_spec' => $data['action_spec'],
//                'call_to_action' => $data['call_to_action'],
//                'dynamic_ad_voice' => $data['dynamic_ad_voice'],
//                'follow_redirect' => $data['follow_redirect'],
//                'image_file' => $data['image_file'],
//                'object_instagram_id' => $data['object_instagram_id'],
//                'video_id' => $data['video_id'],
                        );
                    }
                    $is_success = true;
                    $this->Log->registerEvent("Message","Finish loading creatives for Ad Id: " . $ad['id']);
                } catch (Exception $e) {
                    if ($e->getCode() == 17) {
                        $this->Log->registerEvent("Alert", "get_ad_creatives_by_ad. Let's sleep during " . $this->sleep_seconds . " seconds because of next error message: " . $e->getMessage());
                        sleep($this->sleep_seconds);
                        $this->Log->registerEvent("Alert", "get_ad_creatives_by_ad. I'm woke up for continue working.");
                    }
                    else {
                        $this->Log->RegisterEvent("Error", "get_ad_creatives_by_ad. Ad_id: {$ad['id']}. Error Code: " . $e->getCode() . ". Error Message: " . $e->getMessage());
                        $is_success = true;
                    }
                }
            }
        }
        log_message('debug', 'get_ad_creatives_by_ad - END');

        return $response;
    }

    private function prepare_insights_dates($ad_account, $looking_date) {
        log_message('debug', 'prepare_insights_dates - START');

        if (isset($ad_account['start_date']) && !empty($ad_account['start_date'])) {
            $cur_date = ($this->config->item('debug_mode')) ?
                date('Y-m-d', strtotime($looking_date . '-3 days')) :
                date('Y-m-d', strtotime($ad_account['start_date']));
            $date_list = [];
            while ($cur_date <= $looking_date) {
                $date_list[] = array(
                    'ad_account_id' => $ad_account['id'],
                    'insights_date' => $cur_date,
                );
                $cur_date = date('Y-m-d', strtotime($cur_date . ' + 1 day'));
            }
            $this->Ads->save_ad_dates($date_list);
        }
        log_message('debug', 'prepare_insights_dates - END');
    }


    private function load_ad_data_from_facebook($ad_id, $looking_date, $ad_created_date) {
        log_message('debug', 'load_ad_data_from_facebook - START');

        $data = [];

        if ($ad_created_date <= $looking_date) {
            $params = array(
                'time_range' => array(
                    'since' => $looking_date,
                    'until' => $looking_date,
                ),
                'limit' => 9999999999,
            );

            foreach ($this->breakdown_list as $breakdown) {

                $f = $this->fields;

                if ($breakdown !== 'none') {
                    $params['breakdowns'] = explode(",", $breakdown);
                } else {
                    array_push($f,
                        'app_store_clicks',         // numeric string   - exclude if use breakdown
                        'newsfeed_avg_position',    // float            - exclude if use breakdown
                        'newsfeed_clicks',          // numeric string   - exclude if use breakdown
                        'newsfeed_impressions'      // numeric string   - exclude if use breakdown
                    );
                }

                $obj_ad = new Ad($ad_id);

                $is_success = false;
                while (!$is_success) {
                    try {
                        $this->Log->registerEvent("Message","Start loading data for Ad Id: " . $ad_id . " and date: " . $looking_date . " and breakdown: " . $breakdown);
                        $insight = $obj_ad->getInsights($f, $params);
                        $is_success = true;
                        $this->Log->registerEvent("Message","Finish loading data for Ad Id: " . $ad_id . " and date: " . $looking_date . " and breakdown: " . $breakdown);
                    } catch (Exception $e) {
                        if ($e->getCode() == 17) {
                            // сообщение в лог
                            $this->Log->registerEvent("Alert", "Let's sleep during " . $this->sleep_seconds . " seconds because of next error message: " . $e->getMessage());
                            // и спать
                            sleep($this->sleep_seconds);
                            $this->Log->registerEvent("Alert", "I'm woke up for continue working.");
                        } else {
                            $this->Log->RegisterEvent("Error", "load_ad_data_from_facebook. Ad id: {$ad_id}. Error Code: {$e->getCode()}. Error Message: {$e->getMessage()}");
                            $is_success = true;
                        }
                    }
                }

                $stat = $insight->current();
                while ($stat) {
                    $d = $stat->getData();

                    $breakdown_value = isset($params['breakdowns']) ? $d[$params['breakdowns'][0]] : "";
                    if (isset($params['breakdowns']) && count($params['breakdowns'] > 1)) {
                        for ($i = 1; $i < count($params['breakdowns']); $i++) {
                            if (isset($d[$params['breakdowns'][$i]]))
                            $breakdown_value .= ',' . $d[$params['breakdowns'][$i]];
                        }
                    }

                    $data[] = array(
                        'breakdown'                         => $breakdown,
                        'breakdown_value'                   => $breakdown_value,    //isset($d[$breakdown]) ? $d[$breakdown] : "",
                        'app_store_clicks'                  => isset($d['app_store_clicks']) ? $d['app_store_clicks'] : 0,
                        'buying_type'                       => isset($d['buying_type']) ? $d['buying_type'] : "",
                        'call_to_action_clicks'             => isset($d['call_to_action_clicks']) ? $d['call_to_action_clicks'] : 0,
                        'canvas_avg_view_percent'           => isset($d['canvas_avg_view_percent']) ? $d['canvas_avg_view_percent'] : 0,
                        'canvas_avg_view_time'              => isset($d['canvas_avg_view_time']) ? $d['canvas_avg_view_time'] : 0,
                        'clicks'                            => isset($d['clicks']) ? $d['clicks'] : 0,
                        'cost_per_inline_link_click'        => isset($d['cost_per_inline_link_click']) ? $d['cost_per_inline_link_click'] : 0,
                        'cost_per_inline_post_engagement'   => isset($d['cost_per_inline_post_engagement']) ? $d['cost_per_inline_post_engagement'] : 0,
                        'cost_per_total_action'             => isset($d['cost_per_total_action']) ? $d['cost_per_total_action'] : 0,
                        'cost_per_unique_click'             => isset($d['cost_per_unique_click']) ? $d['cost_per_unique_click'] : 0,
                        'cost_per_unique_inline_link_click' => isset($d['cost_per_unique_inline_link_click']) ? $d['cost_per_unique_inline_link_click'] : 0,
                        'cpc'                               => isset($d['cpc']) ? $d['cpc'] : 0,
                        'cpm'                               => isset($d['cpm']) ? $d['cpm'] : 0,
                        'cpp'                               => isset($d['cpp']) ? $d['cpp'] : 0,
                        'ctr'                               => isset($d['ctr']) ? $d['ctr'] : 0,
                        'date_start'                        => isset($d['date_start']) ? $d['date_start'] : date("Y-m-d"),
                        'date_stop'                         => isset($d["date_stop"]) ? $d["date_stop"] : date("Y-m-d"),
                        'deeplink_clicks'                   => isset($d["deeplink_clicks"]) ? $d["deeplink_clicks"] : 0,
                        'frequency'                         => isset($d["frequency"]) ? $d["frequency"] : 0,
                        'impressions'                       => isset($d["impressions"]) ? $d["impressions"] : 0,
                        'inline_link_click_ctr'             => isset($d["inline_link_click_ctr"]) ? $d["inline_link_click_ctr"] : 0,
                        'inline_link_clicks'                => isset($d["inline_link_clicks"]) ? $d["inline_link_clicks"] : 0,
                        'inline_post_engagement'            => isset($d["inline_post_engagement"]) ? $d["inline_post_engagement"] : 0,
                        'newsfeed_avg_position'             => isset($d['newsfeed_avg_position']) ? $d['newsfeed_avg_position'] : 0,
                        'newsfeed_clicks'                   => isset($d['newsfeed_clicks']) ? $d['newsfeed_clicks'] : 0,
                        'newsfeed_impressions'              => isset($d['newsfeed_impressions']) ? $d['newsfeed_impressions'] : 0,
                        'objective'                         => isset($d["objective"]) ? $d["objective"] : "",
                        'place_page_name'                   => isset($d["place_page_name"]) ? $d["place_page_name"] : "",
                        'reach'                             => isset($d["reach"]) ? $d["reach"] : 0,
                        'social_clicks'                     => isset($d["social_clicks"]) ? $d["social_clicks"] : 0,
                        'social_impressions'                => isset($d["social_impressions"]) ? $d["social_impressions"] : 0,
                        'social_reach'                      => isset($d["social_reach"]) ? $d["social_reach"] : 0,
                        'social_spend'                      => isset($d["social_spend"]) ? $d["social_spend"] : 0,
                        'spend'                             => isset($d["spend"]) ? $d["spend"] : 0,
                        'total_action_value'                => isset($d["total_action_value"]) ? $d["total_action_value"] : 0,
                        'total_actions'                     => isset($d["total_actions"]) ? $d["total_actions"] : 0,
                        'total_unique_actions'              => isset($d["total_unique_actions"]) ? $d["total_unique_actions"] : 0,
                        'unique_clicks'                     => isset($d["unique_clicks"]) ? $d["unique_clicks"] : 0,
                        'unique_ctr'                        => isset($d["unique_ctr"]) ? $d["unique_ctr"] : 0,
                        'unique_impressions'                => isset($d["unique_impressions"]) ? $d["unique_impressions"] : 0,
                        'unique_inline_link_click_ctr'      => isset($d["unique_inline_link_click_ctr"]) ? $d["unique_inline_link_click_ctr"] : 0,
                        'unique_inline_link_clicks'         => isset($d["unique_inline_link_clicks"]) ? $d["unique_inline_link_clicks"] : 0,
                        'unique_link_clicks_ctr'            => isset($d["unique_link_clicks_ctr"]) ? $d["unique_link_clicks_ctr"] : 0,
                        'unique_social_clicks'              => isset($d["unique_social_clicks"]) ? $d["unique_social_clicks"] : 0,
                        'unique_social_impressions'         => isset($d["unique_social_impressions"]) ? $d["unique_social_impressions"] : 0,
                        'website_clicks'                    => isset($d["website_clicks"]) ? $d["website_clicks"] : 0,
                        'relevance_score'                   => isset($d["relevance_score"]) ? $d["relevance_score"] : "",
                        'action_values'                     => isset($d["action_values"]) ? $d["action_values"] : array(),
                        'actions'                           => isset($d["actions"]) ? $d["actions"] : array(),
                        'cost_per_10_sec_video_view'        => isset($d["cost_per_10_sec_video_view"]) ? $d["cost_per_10_sec_video_view"] : array(),
                        'cost_per_action_type'              => isset($d["cost_per_action_type"]) ? $d["cost_per_action_type"] : array(),
                        'cost_per_unique_action_type'       => isset($d["cost_per_unique_action_type"]) ? $d["cost_per_unique_action_type"] : array(),
                        'unique_actions'                    => isset($d["unique_actions"]) ? $d["unique_actions"] : array(),
                        'video_10_sec_watched_actions'      => isset($d["video_10_sec_watched_actions"]) ? $d["video_10_sec_watched_actions"] : array(),
                        'video_15_sec_watched_actions'      => isset($d["video_15_sec_watched_actions"]) ? $d["video_15_sec_watched_actions"] : array(),
                        'video_30_sec_watched_actions'      => isset($d["video_30_sec_watched_actions"]) ? $d["video_30_sec_watched_actions"] : array(),
                        'video_avg_pct_watched_actions'     => isset($d["video_avg_pct_watched_actions"]) ? $d["video_avg_pct_watched_actions"] : array(),
                        'video_avg_sec_watched_actions'     => isset($d["video_avg_sec_watched_actions"]) ? $d["video_avg_sec_watched_actions"] : array(),
                        'video_complete_watched_actions'    => isset($d["video_complete_watched_actions"]) ? $d["video_complete_watched_actions"] : array(),
                        'video_p100_watched_actions'        => isset($d["video_p100_watched_actions"]) ? $d["video_p100_watched_actions"] : array(),
                        'video_p25_watched_actions'         => isset($d["video_p25_watched_actions"]) ? $d["video_p25_watched_actions"] : array(),
                        'video_p50_watched_actions'         => isset($d["video_p50_watched_actions"]) ? $d["video_p50_watched_actions"] : array(),
                        'video_p75_watched_actions'         => isset($d["video_p75_watched_actions"]) ? $d["video_p75_watched_actions"] : array(),
                        'video_p95_watched_actions'         => isset($d["video_p95_watched_actions"]) ? $d["video_p95_watched_actions"] : array(),
                        'website_ctr'                       => isset($d["website_ctr"]) ? $d["website_ctr"] : array(),
                    );
                    $insight->next();
                    $stat = $insight->current();
                }
            }
        }

        log_message('debug', 'load_ad_data_from_facebook - END');

        return $data;
    }

    public function cleardb() {
        $this->Log->clear();
        $this->Ads->clear();
    }
}
