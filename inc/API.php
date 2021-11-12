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

    // admin ajax
    add_action('wp_ajax_peerboard_feedback_request', [__CLASS__, 'feedback_request']);
  }

  /**
   * Check if API has error or not
   *
   * @param array or object $request
   * @return void
   */
  public static function check_request_success($request, $function_args = [])
  {
    $success = true;

    if (is_wp_error($request)) {
      foreach ($request->errors as $notice => $message) {
        if ($message[0] === 'cURL error 60: SSL certificate problem: certificate has expired') {
          return 'ssl_fix';
        } else {
          peerboard_add_notice(sprintf('%s : %s', $notice, $message[0]), __FUNCTION__, 'error', $function_args);
        }
      }
      $success = false;
    }

    if (is_array($request)) {
      if ($request['response']['code'] >= 400) {
        $message = json_decode(wp_remote_retrieve_body($request), true);
        $message = $message['message'];
        peerboard_add_notice($message, __FUNCTION__, 'error', $function_args);
        $success = false;
      }
    }

    return $success;
  }

  /**
   * API Call
   *
   * @param [type] $slug
   * @param [type] $token
   * @param [type] $body
   * @param string $type
   * @return void
   */
  public static function peerboard_api_call($slug, $token = 0, $body, $type = 'GET', $api_url = '', $check_success = true, $ssl_verify = true)
  {
    if (!empty($api_url)) {
      $url = $api_url . $slug;
    } else {
      $url = PEERBOARD_API_BASE . $slug;
    }

    $headers = [
      "Partner" => "wordpress_default_partner_token",
      "Content-type" => "application/json",
    ];

    if ($token) {
      $headers['authorization'] = "Bearer " . $token;
    }

    $args = [
      'timeout'     => 20,
      'headers' => $headers,
      'sslverify' => $ssl_verify // Last solution cURL error 60: SSL certificate problem
    ];

<<<<<<< HEAD

    if (peerboard_get_environment() === 'dev') {
      $args['sslverify'] = false;
    }
=======
    // For mac os and other situation we do not know we are getting issue - cURL error 60: SSL certificate problem
    $args['sslverify'] = false;
>>>>>>> master

    if ($type === 'GET') {
      $request = wp_remote_get($url, $args);
    }

    if ($type === 'POST') {
      $args['body'] = json_encode($body);
      $request = wp_remote_post($url, $args);
    }

    if ($check_success) {
      $success = self::check_request_success($request, func_get_args());
      // If we have issue with ssl
      if ($success === 'ssl_fix') {
        // Try to update certificate and fix ssl issue 
        $ssl_fix = self::update_wp_ca_bundle();

        if (isset($ssl_fix['success'])) {
          // Make the same request
          $success = self::make_api_req_again($slug, $token, $body, $type, $api_url);
          // the last solution disable ssl
          if (!$success) {
            self::make_api_req_again($slug, $token, $body, $type, $api_url, $check_success = true, $ssl_verify = false);
          }
        } else {
          self::make_api_req_again($slug, $token, $body, $type, $api_url, $check_success = true, $ssl_verify = false);
        }
      } else if (!$success) {
        return false;
      }
    }

    return $request;
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
    $prefix = apply_filters('peerboard_check_comm_slug_before_req', $prefix);

    $req = self::peerboard_api_call('hosting', $token, [
      "domain" => $domain,
      "path" => $prefix,
      "type" => 'sdk',
      "js_storage_auth" => true,
      "version" => PEERBOARD_PLUGIN_VERSION
    ], 'POST');

    return $req;
  }

  /**
   * Remove integration
   *
   * @param [type] $token
   * @return void
   */
  public static function peerboard_drop_integration($token)
  {
    return self::peerboard_api_call('hosting', $token, ["type" => 'none'], 'POST');
  }

  /**
   * Undocumented function;
   *
   * @return void
   */
  public static function peerboard_create_community()
  {
    $request = self::peerboard_api_call('communities', 0, peerboard_bloginfo_array(), 'POST');

    if (!$request) {
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
    $request = self::peerboard_api_call('communities', $auth_token, '');

    if (!$request) {
      return false;
    }

    return json_decode(wp_remote_retrieve_body($request), true);
  }

  /**
   * Make call again
   *
   * @param [type] $slug
   * @param integer $token
   * @param [type] $body
   * @param string $type
   * @param string $api_url
   * @param boolean $check_success
   * @param boolean $ssl_verify
   * @return void
   */
  public static function make_api_req_again($slug, $token = 0, $body, $type = 'GET', $api_url = '', $check_success = true, $ssl_verify = true)
  {
    return self::peerboard_api_call($slug, $token, $body, $type, $api_url, $check_success, $ssl_verify);
  }

  /**
   * Solution: cURL error 60: SSL certificate has expired
   * Issue information and solution
   * https://wp-kama.com/note/error-making-request-wordpress
   * @return void
   */
  public static function update_wp_ca_bundle()
  {
    $crt_file = ABSPATH . WPINC . '/certificates/ca-bundle.crt';

    $arr_context_options = [
      "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false,
      ],
    ];

    $new_crt_url = 'https://curl.se/ca/cacert.pem';

    if (is_writable($crt_file)) {
      $new_str = file_get_contents($new_crt_url, false, stream_context_create($arr_context_options));

      if ($new_str && strpos($new_str, 'Bundle of CA Root Certificates')) {
        $up = file_put_contents($crt_file, $new_str);

        return $up ? ['success' => 'ca-bundle.crt updated'] : ['error' => 'can`t put data to ca-bundle.crt'];
      } else {
        return ['error' => 'ERROR: can\'t download ' . $new_crt_url];
      }
    } else {
      return ['error' => 'ca-bundle.crt not writable'];
    }
  }

  /**
   * Send feedback on plugin deactivation
   *
   * @return void
   */
  public static  function feedback_request()
  {
    $options = get_option('peerboard_options');

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

    $request = self::peerboard_api_call('events', 0, $body, 'POST', PEERBOARD_API_URL);

    if (!$request) {
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
