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
      $user = self::peerboard_create_user($peerboard_options['auth_token'], $userdata);

      if (!$user) {
        return;
      }

      $count = intval(get_option('peerboard_users_count'));
      update_option('peerboard_users_count', $count + 1);
    }
  }

  public static function check_request_error($request)
  {
    $error = true;

    if (is_wp_error($request)) {
      foreach ($request->errors as $notice => $message) {
        peerboard_add_notice(sprintf('%s : %s', $notice, $message[0]), __FUNCTION__, 'error', func_get_args());
      }
      $error = false;
    }

    if (is_array($request)) {
      if ($request['response']['code'] >= 400) {
        peerboard_add_notice($request['response']['message'], __FUNCTION__, 'error', func_get_args());
        $error = false;
      }
    }

    return $error;
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

    self::check_request_error($request);
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

    self::check_request_error($request);
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

    $error = self::check_request_error($request);

    if($error){
      return false;
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

    $error = self::check_request_error($request);

    if($error){
      return false;
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

    $error = self::check_request_error($request);

    if($error){
      return false;
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

    $error = self::check_request_error($request);

    if($error){
      return false;
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

    $error = self::check_request_error($request);

    if($error){
      wp_send_json_error(sprintf('%s %s', $request['response']['message'], __FUNCTION__));
    }

    wp_send_json_success(wp_remote_retrieve_body($request));
  }

  /**
   * Store errors to Sentry
   *
   * @return void
   */
  public static function add_sentry_error($message, $function_name, $extra = [])
  {
    $timestamp = time();
    $body = [
      "culprit" => $function_name,
      "timestamp" => $timestamp,
      "message" => $message,
      "environment" => peerboard_get_environment(),
      "extra" => $extra
    ];

    $request = wp_remote_post('https://150cbac0a6e941bd89c935104211614e@o468053.ingest.sentry.io/api/5900112/store/', [
      'timeout'     => 45,
      'redirection' => 10,
      'headers' => [
        "Content-type" => "application/json",
        "X-Sentry-Auth" => "Sentry sentry_version=7,sentry_key=150cbac0a6e941bd89c935104211614e,sentry_timestamp=" . $timestamp,
      ],
      'body' => json_encode($body),
      'sslverify' => false,
    ]);

    return $request;
  }
}

API::init();
