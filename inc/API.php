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

    // admin ajax
    add_action('wp_ajax_peerboard_feedback_request', [__CLASS__, 'feedback_request']);
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
    $request = wp_remote_post(PEERBOARD_API_BASE . 'hosting', array(
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

    if (is_wp_error($request) || $request['response']['code'] !== 200) {
      peerboard_add_notice($request['response']['message'].' (post_integration)', 'error');
    }
  }

  /**
   * Remove integration
   *
   * @param [type] $token
   * @return void
   */
  public static function peerboard_drop_integration($token)
  {
    $request = wp_remote_post(PEERBOARD_API_BASE . 'hosting', array(
      'timeout'     => 5,
      'headers' => array(
        'authorization' => "Bearer $token",
        "Partner" => "wordpress_default_partner_token"
      ),
      'body' => json_encode(array(
        "type" => 'none'
      ))
    ));

    if (is_wp_error($request) || $request['response']['code'] !== 200) {
      peerboard_add_notice($request['response']['message'].' (drop_integration)', 'error');
    }
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
    $request = wp_remote_post(PEERBOARD_API_BASE . 'users/batch', array(
      'timeout'     => 5,
      'headers' => array(
        'authorization' => "Bearer $token",
        "Partner" => "wordpress_default_partner_token"
      ),
      'body' => json_encode($users)
    ));

    if (is_wp_error($request) || $request['response']['code'] !== 200) {
      peerboard_add_notice($request['response']['message'].' (sync_users)', 'error');
      return $request;
    }

    return json_decode(wp_remote_retrieve_body($request), true);
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
    $request = wp_remote_post(PEERBOARD_API_BASE . 'users', array(
      'timeout'     => 5,
      'headers' => array(
        'authorization' => "Bearer $token",
        "Partner" => "wordpress_default_partner_token"
      ),
      'body' => json_encode($user)
    ));

    if (is_wp_error($request) || $request['response']['code'] !== 200) {
      peerboard_add_notice($request['response']['message'].' (create_user)', 'error');
      return $request;
    }

    return json_decode(wp_remote_retrieve_body($request), true);
  }

  /**
   * Undocumented function;
   *
   * @return void
   */
  public static function peerboard_create_community()
  {
    $request = wp_remote_post(PEERBOARD_API_BASE . 'communities', array(
      'timeout'     => 45,
      'headers' => array(
        "Content-type" => "application/json",
        "Partner" => "wordpress_default_partner_token"
      ),
      'body' => json_encode(peerboard_bloginfo_array()),
      'sslverify' => false,
    ));

    if (is_wp_error($request) || $request['response']['code'] !== 200) {
      peerboard_add_notice($request['response']['message'], 'error');
      return $request;
    }

    return json_decode(wp_remote_retrieve_body($request), true);
  }

  /**
   * Get community by token
   *
   * @param [type] $auth_token
   * @return void
   */
  public static function peerboard_get_community($auth_token)
  {
    $request = wp_remote_get(PEERBOARD_API_BASE . 'communities', array(
      'headers' => array(
        'authorization' => "Bearer $auth_token",
        "Partner" => "wordpress_default_partner_token"
      ),
    ));

    if (is_wp_error($request) || $request['response']['code'] !== 200) {
      peerboard_add_notice($request['response']['message'], 'error');
      return $request;
    }

    return json_decode(wp_remote_retrieve_body($request), true);
  }

  /**
   * Send feedback on plugin deactivation
   *
   * @return void
   */
  public static  function feedback_request()
  {
    $options = get_option('peerboard_options');
    // https://api.(peerboard.com|peerboard.dev|local.is)/events
    $api_link = PEERBOARD_API_URL . 'events';
    $body = [
      'type' => 'plugin_uninstalled',
      "platform" => "wordpress",
      "email" => get_option('admin_email'),
      "feedback" => !empty($_POST['additional_info']) ? sanitize_text_field($_POST['additional_info']) : '',
      "reason" => $_POST['main_reason'] ?? '',
      "community_id" => $options['community_id'],
      "website" => get_site_url(),
      "main_url" => get_site_url() . "/" . $options['prefix']
    ];

    $request = wp_remote_post($api_link, [
      'timeout'     => 45,
      'redirection' => 10,
      'headers' => array(
        "Content-type" => "application/json",
        "Partner" => "wordpress_default_partner_token"
      ),
      'body' => json_encode($body),
      'sslverify' => false,
    ]);

    if (is_wp_error($request) || $request['response']['code'] !== 200) {
      wp_send_json_error($request);
    }

    wp_send_json_success(wp_remote_retrieve_body($request));
  }
}

API::init();
