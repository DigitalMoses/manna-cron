<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once (APPPATH . 'libraries/vendor/autoload.php');

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

use FacebookAds\Api;
use FacebookAds\Object\Ad;
use FacebookAds\Object\Fields\AdFields;
use FacebookAds\Object\AdUser;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Fields\CampaignFields;

$db = null;

function getFacebookAccessToken() {
    log_message('debug', 'getFacebookAccessToken - START');
    
    $CI =& get_instance();
    $appID = $CI->config->item('appID');
    $appSecret = $CI->config->item('appSecret');

    $fb = new Facebook([
        'app_id' => $appID, // Replace {app-id} with your app id
        'app_secret' => $appSecret
    ]);

    $helper = $fb->getRedirectLoginHelper();

    try {
        $accessToken = $helper->getAccessToken();
        log_message('debug', 'got access token:' . $accessToken);
    } catch(FacebookResponseException $e) {
        // When Graph returns an error
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(FacebookSDKException $e) {
        // When validation fails or other local issues
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    if (! isset($accessToken)) {
        if ($helper->getError()) {
            header('HTTP/1.0 401 Unauthorized');
            echo "Error: " . $helper->getError() . "\n";
            echo "Error Code: " . $helper->getErrorCode() . "\n";
            echo "Error Reason: " . $helper->getErrorReason() . "\n";
            echo "Error Description: " . $helper->getErrorDescription() . "\n";
        } else {
            header('HTTP/1.0 400 Bad Request');
            echo 'Bad request';
        }
        exit;
    }
    
    // The OAuth 2.0 client handler helps us manage access tokens
    $oAuth2Client = $fb->getOAuth2Client();

    // Get the access token metadata from /debug_token
    $tokenMetadata = $oAuth2Client->debugToken($accessToken);

    // Validation (these will throw FacebookSDKException's when they fail)
    $tokenMetadata->validateAppId($appID); // Replace {app-id} with your app id
    // If you know the user ID this access token belongs to, you can validate it here
    //$tokenMetadata->validateUserId('123');
    $tokenMetadata->validateExpiration();

    if (! $accessToken->isLongLived()) {
        // Exchanges a short-lived access token for a long-lived one
        try {
            $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            log_message('debug', 'got long lived access token:' . $accessToken);
        } catch (FacebookSDKException $e) {
            echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
            exit;
        }
    }

    log_message('debug', 'getFacebookAccessToken - END');

    return (string) $accessToken;
}

function getFacebookLoginURL($redirect = '') {  
    
    $CI =& get_instance();
    $appID = $CI->config->item('appID');
    $appSecret = $CI->config->item('appSecret');

    $fb = new Facebook([
        'app_id' => $appID,
        'app_secret' => $appSecret,
    ]);     
    
    $helper = $fb->getRedirectLoginHelper();    
    $permissions = ['ads_management', 'email', 'ads_read', 'manage_pages'];
    $loginUrl = $helper->getLoginUrl($redirect, $permissions);
    
    return $loginUrl;
}

function getAdTargetURL($ad_id) {
    global $api;

    $CI =& get_instance();
    $appID = $CI->config->item('appID');
    $appSecret = $CI->config->item('appSecret');

    $fb = new Facebook([
        'app_id' => $appID, // Replace {app-id} with your app id
        'app_secret' => $appSecret
    ]);

    try {
        $ad = new Ad($ad_id, null, $api);   
        $fields = array(
                "actor_id" , 
                "actor_image_hash" , 
                "actor_name" , 
                "adlabels" , 
                "applink_treatment" , 
                "body" , 
                "call_to_action_type" , 
                "dynamic_ad_voice" ,             
                "id" ,
                "image_hash" ,             
                "image_url" , 
                "image_crops" , 
                "instagram_actor_id" , 
                "instagram_permalink_url" , 
                "link_deep_link_url" , 
                "link_url" , 
                "name" ,
                "object_id" , 
                "object_story_id" ,
                "object_story_spec" , 
                "object_store_url" , 
                "object_type" , 
                "object_url" , 
                "place_page_set_id" ,             
                "product_set_id" , 
                "run_status" , 
                "template_url" , 
                "thumbnail_url" ,
                "title" , 
                "url_tags" , 
                "video_id"
        );
        
        $creatives = $ad->getAdCreatives($fields);            
        $creative = $creatives->current();
        $creative_data = $creative->getData();
    
        $url = '';
        if ($creative_data["object_type"] == "INVALID") {
            return "UNKNOWN"; 
        }
        if ($creative_data["object_type"] == "SHARE") {
            if (isset($creative_data["object_story_spec"]["link_data"]["link"])) $url = $creative_data["object_story_spec"]["link_data"]["link"];
            else if (isset($creative_data["link_url"])) $url = $creative_data["link_url"];
            else $url = '';
        } else if ($creative_data["object_type"] == "PHOTO") {
            if (!empty($creative_data['object_story_id'])) {
                $dumb = explode("_", $creative_data['object_story_id']);
                if (isset($dumb[1])) $post_id = $dumb[1];
                else $post_id = $dumb[0];
                
                $response = $fb->get("/{$post_id}", $_SESSION['facebook_access_token']);
                $results = $response->getGraphNode();
                
                if (isset($results["message"])) $message = $results["message"];
                else if (isset($results["name"])) $message = $results["name"];
                else $message = "";
                
                $pattern = "#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#";
                preg_match($pattern, $message, $matches);        
                if (!empty($matches))
                    $url = $matches[0];
            }
        } else if ($creative_data["object_type"] == "DOMAIN") {
            $url = $creative_data["object_url"];
        }
        
        if ($url == '') {
            if (!empty($creative_data['object_story_id'])) {
                $dumb = explode("_", $creative_data['object_story_id']);
                if (isset($dumb[1])) $post_id = $dumb[1];
                else $post_id = $dumb[0];
                
                $response = $fb->get("/{$post_id}", $_SESSION['facebook_access_token']);
                $results = $response->getGraphNode();
                
                if (isset($results["message"])) $message = $results["message"];
                else if (isset($results["name"])) $message = $results["name"];
                else $message = "";
                
                $pattern = "#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#";
                preg_match($pattern, $message, $matches);        
                if (!empty($matches))
                    $url = $matches[0];
            }
        }
    } catch (Exception $e) {
        if ($e->getCode() == 17) {
            echo json_encode(['code' => $e->getCode(), 'message' => $e->getMessage()]); 
            die();
        } else {
            return "UNKNOWN";    
        }
        
    }
    
    return $url;
}

function getAdAccount() {

    $CI =& get_instance();
    $appID = $CI->config->item('appID');
    $appSecret = $CI->config->item('appSecret');

    Api::init(
        $appID,
        $appSecret,
        $_SESSION['facebook_access_token']
    );    
    
    // Add after Api::init()
    $me = new AdUser('me');
    $my_adaccount = $me->getAdAccounts()->current();  
    
    return $my_adaccount->getData();
}

function importInsights($row, $currency, $date) {
    global $db;

    $CI =& get_instance();
    $appID = $CI->config->item('appID');
    $appSecret = $CI->config->item('appSecret');

    Api::init(
        $appID,
        $appSecret,
        $_SESSION['facebook_access_token']
    );
    if (empty($db)) $db = DB();
    
    $ad = new Ad($row->ad_id);
    $insights = getAdInsights($ad, $date);
    $impressions = $insights['impressions'];
    $unique_clicks = $insights['unique_clicks'];
    $spend = $insights['spend'];
    $query = $db->query("INSERT INTO ads_insights (ads_details_id, impressions, unique_clicks, spend, currency, fb_date) 
        VALUES({$row->id}, {$impressions}, {$unique_clicks}, {$spend}, '{$currency}', '{$date}')
        ON DUPLICATE KEY UPDATE impressions='{$impressions}', unique_clicks='{$unique_clicks}', spend='{$spend}'");
}

function importAds($token, $ad_account, $currency, $pointer = "") {
    global $db, $api;

    $CI =& get_instance();
    $appID = $CI->config->item('appID');
    $appSecret = $CI->config->item('appSecret');

    try { 
        $api = Api::init(
            $appID,
            $appSecret,
            $token
        );
        
        if (empty($db)) $db = DB();
        
        $account = new AdAccount('act_' . $ad_account);
        $i = 0;
        if ($pointer == '') {
            $cursor = $account->getAds(
                array(
                    AdFields::ACCOUNT_ID,
                    AdFields::BID_AMOUNT,
                    AdFields::ADSET_ID,
                    AdFields::CAMPAIGN_ID,
                    AdFields::CONVERSION_SPECS,
                    AdFields::CREATED_TIME,
                    AdFields::AD_REVIEW_FEEDBACK,
                    AdFields::ID,
                    AdFields::NAME,
                    AdFields::RTB_FLAG,
                    AdFields::TARGETING,
                    AdFields::TRACKING_SPECS,
                    AdFields::UPDATED_TIME,
                    AdFields::CREATIVE,
                    AdFields::SOCIAL_PREFS,
                    AdFields::FAILED_DELIVERY_CHECKS,
                    AdFields::REDOWNLOAD,
                    AdFields::ADLABELS,
                    AdFields::ENGAGEMENT_AUDIENCE,
                    AdFields::EXECUTION_OPTIONS
                ),
                array(
                    'limit' => 500000,                
                ));
            if ($cursor->valid()) {
                $cursor->end();       
                $ad = $cursor->current();        
                $pointer = $cursor->getAfter();             
                saveAd($ad_account, $ad, $currency, $pointer);        
                $i++;                
            }
        }
        
        $cursor = $account->getAds(
                array(
                    AdFields::ACCOUNT_ID,
                    AdFields::BID_AMOUNT,
                    AdFields::ADSET_ID,
                    AdFields::CAMPAIGN_ID,
                    AdFields::CONVERSION_SPECS,
                    AdFields::CREATED_TIME,
                    AdFields::AD_REVIEW_FEEDBACK,
                    AdFields::ID,
                    AdFields::NAME,
                    AdFields::RTB_FLAG,
                    AdFields::TARGETING,
                    AdFields::TRACKING_SPECS,
                    AdFields::UPDATED_TIME,
                    AdFields::CREATIVE,
                    AdFields::SOCIAL_PREFS,
                    AdFields::FAILED_DELIVERY_CHECKS,
                    AdFields::REDOWNLOAD,
                    AdFields::ADLABELS,
                    AdFields::ENGAGEMENT_AUDIENCE,
                    AdFields::EXECUTION_OPTIONS
                ),
                array(
                    'limit' => 1,
                    'before' => $pointer
                ));
        
        while ($cursor->valid()) {
            $i ++;            
            $ad = $cursor->current();
            
            $current_pointer = $cursor->getBefore();        
            saveAd($ad_account, $ad, $currency, $current_pointer);
            
            if ($pointer != '') $cursor->fetchBefore();
            $cursor->prev();
            
            if ($i % 8 == 0) {
                sleep(60);
            }
            
        }
    } catch (Exception $e) {        
        echo json_encode(['code' => $e->getCode(), 'message' => $e->getMessage()]); 
        die();
    }
}

function saveAd($ad_account, $ad, $currency, $current_pointer) {
    global $db;
    
    $ad_id = $ad->{AdFields::ID};
    $ad_name = addslashes($ad->{AdFields::NAME});
    $adset_id = $ad->{AdFields::ADSET_ID};
    $campaign_id = $ad->{AdFields::CAMPAIGN_ID};
    $main_link = ''; //getAdTargetURL($ad_id);
    
    $campaign_name = addslashes(getCampaignName($campaign_id));
    $adset_name = addslashes(getCampaignName($adset_id));
    
    $insights = getAdInsights($ad);
    $impressions = $insights['impressions'];
    $unique_clicks = $insights['unique_clicks'];
    $spend = $insights['spend'];
    $date = $ad->created_time;
    $time = strtotime($date);
    $created_time = date('Y-m-d H:i:s', $time);
    $date = $ad->updated_time;
    $time = strtotime($date);
    $updated_time = date('Y-m-d H:i:s', $time);
    
    $targeting = serialize($ad->targeting);
    
    $query = $db->query("SELECT * FROM ads_details WHERE ad_id='{$ad_id}' AND adset_id='{$adset_id}' AND campaign_id='{$campaign_id}' AND facebook_user_id='{$_SESSION['USER']['id']}'");
    if ($query->num_rows() > 0) {
        $result = $query->result_array();
        $ads_details_id = $result[0]['id'];
        $db->query("UPDATE ads_details SET main_link='{$main_link}', ad_name='{$ad_name}', adset_name='{$adset_name}', campaign_name='{$campaign_name}', pointer='{$current_pointer}'  WHERE id={$ads_details_id}");
    } else {
        $query = $db->query("INSERT INTO ads_details (main_link, adaccount_id, ad_id, ad_name, adset_id, adset_name, campaign_id, campaign_name,
            impressions, unique_clicks, spend, currency, created_time, updated_time, targeting, facebook_user_id, pointer) 
            VALUES('{$main_link}', '{$ad_account}', '{$ad_id}', '{$ad_name}', '{$adset_id}', '{$adset_name}', '{$campaign_id}', '{$campaign_name}',
            '{$impressions}', '{$unique_clicks}', '{$spend}', '{$currency}', '{$created_time}', '{$updated_time}', '{$targeting}', '{$_SESSION['USER']['id']}', '{$current_pointer}')");
    
        $ads_details_id = $db->insert_id();    
    }
}

function getCampaignName($campaign_id) {
    global $api;
    $campaign = new Campaign($campaign_id, null, $api);
    $campaign->read(array(
        CampaignFields::NAME
    ));
    
    return $campaign->{CampaignFields::NAME};
}

function getAdsetName($adset_id) {
    global $api;
    
    $adset = new AdSet($adset_id, null, $api);
    $adset->read(array(
        AdSetFields::NAME
    ));
        
    return $adset->{AdSetFields::NAME};
}

function getAdInsights($ad, $date = "") {
    if (empty($date)) {
        $params = array( 'fields' => ['impressions', 'unique_clicks', 'spend', 'account_name'], 'date_preset'=> 'lifetime');    
    } else {
        $next_date = date('Y-m-d', strtotime($date .' +1 day'));
        $params = array(
            'fields' => ['impressions', 'unique_clicks', 'spend', 'account_name'],
            'time_range' => array(
                'since' => $date,
                'until' => $next_date,
            ),
        );
    }
    
    $insights = $ad->getInsights(array(), $params);                
    $stats = $insights->current();
    
    if (count($insights) == 1) {
        $result = array('spend' => $stats->spend, 'unique_clicks' => $stats->unique_clicks, 'impressions' => $stats->impressions);                    
    } else {
        $result = array('spend' => 0.00, 'unique_clicks' => 0, 'impressions' => 0);
    }
    
    return $result;
}

function getMyAdAccounts() {

    $CI =& get_instance();
    $appID = $CI->config->item('appID');
    $appSecret = $CI->config->item('appSecret');

    $fb = new Facebook([
        'app_id' => $appID,
        'app_secret' => $appSecret,
    ]);
            
    $response = $fb->get('/me/adaccounts?fields=id,account_id,name,currency,timezone_name,users', $_SESSION['facebook_access_token']);
    $results = $response->getGraphEdge();
    
    return $results;   
}

function getProfile($token) {

    $CI =& get_instance();
    $appID = $CI->config->item('appID');
    $appSecret = $CI->config->item('appSecret');

    $fb = new Facebook([
        'app_id' => $appID,
        'app_secret' => $appSecret,
    ]);

    try {        
        $response = $fb->get('/me?fields=id,name,email,picture', $token);
    } catch(FacebookResponseException $e) {
        redirect("login"); //echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(FacebookSDKException $e) {
        redirect("login");
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    $user = $response->getGraphUser();
    
    return $user;
}

function getBusiness($token) {

    $CI =& get_instance();
    $appID = $CI->config->item('appID');
    $appSecret = $CI->config->item('appSecret');

    $fb = new Facebook([
        'app_id' => $appID,
        'app_secret' => $appSecret,
    ]);

    $response = $fb->get('/me/businesses', $token);
    $businesses = $response->getGraphEdge();
    
    return $businesses;
}

function getMyBusiness($token, $username) {
    $businesses = getBusiness($token);
    $businesses = $businesses->asArray();
    
    foreach ($businesses as $business) {
        if ($business['name'] == $username) {
            return $business;
        }
    }
    
    return $businesses[0];
}

function get_ads($account_id, $breakdown = "none", $limit = 999999999) {

    $CI =& get_instance();
    $appID = $CI->config->item('appID');
    $appSecret = $CI->config->item('appSecret');

    $fb = new Facebook([
        'app_id' => $appID,
        'app_secret' => $appSecret,
    ]);
    
    $bd_query = "";
    if ($breakdown != 'none') {
        if ($breakdown == 'age_gender') {            
            $bd = "age','gender";
        } else {
            $bd = $breakdown;
        }
        $bd_query = ".breakdowns(['{$bd}'])";
    }
    
    //$next_date = date('Y-m-d', strtotime($date .' +1 day'));
    $ads_fields = "id,name,adset_id,campaign_id,created_time,insights.date_preset(yesterday)";
    $insights_fields = "impressions,reach,spend,unique_clicks,total_actions,cpc,cpm,cpp,ctr";
    $query = "/{$account_id}/ads?fields=" . $ads_fields . "{$bd_query}{" . $insights_fields . "}&limit={$limit}";    
    
    try {
        $response = $fb->get($query, $_SESSION['facebook_access_token']);
        $res = $response->getGraphEdge();    
    } catch (Exception $e) {
        return null;
    }
    
    return $res;    
}

function handleException($e, $type = 0) {
    if ($type == 1 && $e->code == 17) { // user request limit reached when importing ads
        sleep(120);
    }    
}

//convert an ISO8601 date to a different format
function vm_date($date) { // 
    $time = strtotime($date);
    $fixed = date('Y-m-d H:i:s', $time);
    return $fixed;
}