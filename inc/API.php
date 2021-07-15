<?php

namespace PEBO;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Class for API requests
 */
class API
{

  public static function init()
  {
    /**
     * Create user on PeerBoard on user registration on WordPress
     */
    add_action('user_register', [__CLASS__, 'sync_user_if_enabled']);
  }

  /**
   * Create user on PeerBoard on user registration on WordPress
   */
  public static function sync_user_if_enabled($user_id)
  {
    global $peerboard_options;
    $sync_enabled = get_option('peerboard_users_sync_enabled');
    if ($sync_enabled) {
      $user = get_userdata($user_id);
      $userdata = array(
        'email' =>  $user->user_email,
        'bio' => urlencode($user->description),
        'profile_url' => get_avatar_url($user->user_email),
        'name' => $user->display_name,
        'last_name' => ''
      );
      if ($peerboard_options['expose_user_data'] == '1') {
        $userdata['name'] = $user->first_name;
        $userdata['last_name'] = $user->last_name;
      }
      self::peerboard_create_user($peerboard_options['auth_token'], $userdata);
      $count = intval(get_option('peerboard_users_count'));
      update_option('peerboard_users_count', $count + 1);
    }
  }

  /**
   * PeerBoard integrations request
   *
   * @param [type] $token
   * @param [type] $prefix
   * @param [type] $domain
   * @return void
   */
  public static function peerboard_post_integration($token, $prefix, $domain)
  {
    wp_remote_post(PEERBOARD_API_BASE . 'hosting', array(
      'timeout'     => 5,
      'headers' => array(
        'authorization' => "Bearer $token",
        "Partner" => "wordpress_default_partner_token"
      ),
      'body' => json_encode(array(
        "domain" => $domain,
        "path" => $prefix,
        "type" => 'sdk',
        "js_storage_auth" => true,
        "version" => PEERBOARD_PLUGIN_VERSION
      ))
    ));
  }

  /**
   * Remove integration
   *
   * @param [type] $token
   * @return void
   */
  public static function peerboard_drop_integration($token)
  {
    wp_remote_post(PEERBOARD_API_BASE . 'hosting', array(
      'timeout'     => 5,
      'headers' => array(
        'authorization' => "Bearer $token",
        "Partner" => "wordpress_default_partner_token"
      ),
      'body' => json_encode(array(
        "type" => 'none'
      ))
    ));
  }

  /**
   * User sync function
   *
   * @param [type] $token
   * @param [type] $users
   * @return void
   */
  public static function peerboard_sync_users($token, $users)
  {
    $response = wp_remote_post(PEERBOARD_API_BASE . 'users/batch', array(
      'timeout'     => 5,
      'headers' => array(
        'authorization' => "Bearer $token",
        "Partner" => "wordpress_default_partner_token"
      ),
      'body' => json_encode($users)
    ));
    if (is_wp_error($response)) {
      return $response;
    }
    return json_decode(wp_remote_retrieve_body($response), true);
  }

  /**
   * Create user
   *
   * @param [type] $token
   * @param [type] $user
   * @return void
   */
  public static function peerboard_create_user($token, $user)
  {
    $response = wp_remote_post(PEERBOARD_API_BASE . 'users', array(
      'timeout'     => 5,
      'headers' => array(
        'authorization' => "Bearer $token",
        "Partner" => "wordpress_default_partner_token"
      ),
      'body' => json_encode($user)
    ));
    if (is_wp_error($response)) {
      return $response;
    }
    return json_decode(wp_remote_retrieve_body($response), true);
  }

  /**
   * Undocumented function;
   *
   * @return void
   */
  public static function peerboard_create_community()
  {
    $response = wp_remote_post(PEERBOARD_API_BASE . 'communities', array(
      'timeout'     => 45,
      'headers' => array(
        "Content-type" => "application/json",
        "Partner" => "wordpress_default_partner_token"
      ),
      'body' => json_encode(peerboard_bloginfo_array()),
      'sslverify' => false,
    ));
    if (is_wp_error($response)) {
      return $response;
    }
    return json_decode(wp_remote_retrieve_body($response), true);
  }

  /**
   * Get community by token
   *
   * @param [type] $auth_token
   * @return void
   */
  public static function peerboard_get_community($auth_token)
  {
    $response = wp_remote_get(PEERBOARD_API_BASE . 'communities', array(
      'headers' => array(
        'authorization' => "Bearer $auth_token",
        "Partner" => "wordpress_default_partner_token"
      ),
    ));
    if (is_wp_error($response)) {
      return $response;
    }
    return json_decode(wp_remote_retrieve_body($response), true);
  }

  /**
   * Send feedback on plugin deactivation
   *
   * @return void
   */
  public static  function feedback_request()
  {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.peerboard.dev/events-ingest', // https://api.(peerboard.com|peerboard.dev|local.is)/events-ingest
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => '{
    "type": "plugin_uninstalled",
    "platform": "wordpress",
    "email": "vlad@peerboard.com",
    "feedback": "Nothing works as expected", 
    "reason": "other",
    "community_id": 123123123,
    "website": "test.com",
    "main_url": "https://test.com/community"
}',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
      ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    echo $response;
  }
}

API::init();
