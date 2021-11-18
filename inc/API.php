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
   * API Call with success check
   *
   * @param string $slug
   * @param boolean $token
   * @param array $body
   * @param string $type
   * @return array
   */
  public static function peerboard_api_call_with_success_check($slug, $token = 0, array $body, $type = 'GET', $api_url = '', array $args = [])
  {

    $default_args = [
      'report_error' => true
    ];

    $args = array_merge($default_args, $args);

    $req_args = [];

    $req_args['body'] = $body;

    $req_args['ssl_verify'] = true;

    $request = self::peerboard_api_call($slug, $token, $req_args, $type, $api_url);

    // We are checking if we have SSL certificate problem
    if (self::check_if_ssl_issue($request)) {
      // Try to update certificate and fix ssl issue 
      $ssl_fix = self::update_wp_ca_bundle();
      if (isset($ssl_fix['success'])) {
        // Make the same request but without ssl issue checking
        $request = self::peerboard_api_call($slug, $token, $req_args, $type, $api_url);

        if (self::check_if_ssl_issue($request)) {
          // the last solution make request with disabled ssl and without ssl issue checking
          $req_args['ssl_verify'] = false;
          $request = self::peerboard_api_call($slug, $token, $req_args, $type, $api_url);
        }
      } else {
        // If we can not fix ssl issue make request with disabled ssl and without ssl issue checking
        $req_args['ssl_verify'] = false;
        $request = self::peerboard_api_call($slug, $token, $req_args, $type, $api_url);
      }
    }


    $check_response = self::check_request_success_and_report_error($request, func_get_args(), $args['report_error']);

    return $check_response;
  }

  /**
   * Check if API has error or not
   *
   * @param array or object $request
   * @return void
   */
  public static function check_request_success_and_report_error($request, $function_args = [], $report_error = true)
  {
    $success = true;

    if (is_wp_error($request)) {
      foreach ($request->errors as $notice => $message) {
        if ($report_error) {
          peerboard_add_notice(sprintf('%s : %s', $notice, $message[0]), __FUNCTION__, 'error', $function_args);
        }
      }
      $success = false;
    }

    if (is_array($request)) {
      if ($request['response']['code'] >= 400) {
        $message = json_decode(wp_remote_retrieve_body($request), true);
        $message = $message['message'];
        if ($report_error) {
          peerboard_add_notice($message, __FUNCTION__, 'error', $function_args);
        }
        $success = false;
      }
    }

    $response = [
      'success' => $success,
      'request' => $request
    ];

    return $response;
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

    $req = self::peerboard_api_call_with_success_check('hosting', $token, [
      "domain" => $domain,
      "path" => $prefix,
      "type" => 'sdk',
      "js_storage_auth" => true,
      "version" => PEERBOARD_PLUGIN_VERSION
    ], 'POST');

    return $req;
  }

  /**
   * Undocumented function
   *
   * @param string $slug
   * @param integer $token
   * @param array $req_args
   * @param string $type
   * @param string $api_url
   * @return object
   */
  public static function peerboard_api_call($slug, $token = 0, $req_args = [], $type = 'GET', $api_url = '')
  {
    // If not specified $api_url then take the default
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
      'headers' => $headers
    ];

    $args = array_merge($args, $req_args);

    if ($type === 'GET') {
      $request = wp_remote_get($url, $args);
    }

    if ($type === 'POST') {
      $args['body'] = json_encode($args['body']);
      $request = wp_remote_post($url, $args);
    }

    return $request;
  }


  /**
   * Check if we have cURL error 60: SSL certificate problem: certificate has expired issue
   *
   * @param [type] $request
   * @return void
   */
  public static function check_if_ssl_issue($request)
  {
    // We are checking if we have SSL certificate problem
    if (!is_wp_error($request)) {
      return false;
    }

    foreach ($request->errors as $notice => $message) {
      // If we have issue with ssl
      if ($message[0] === 'cURL error 60: SSL certificate problem: certificate has expired') {
        return true;
      }
    }

    return false;
  }

  /**
   * Remove integration
   *
   * @param [type] $token
   * @return void
   */
  public static function peerboard_drop_integration($token)
  {
    $response = self::peerboard_api_call_with_success_check('hosting', $token, ["type" => 'none'], 'POST');
    return $response['success'];
  }

  /**
   * Undocumented function;
   *
   * @return void
   */
  public static function peerboard_create_community()
  {
    $response = self::peerboard_api_call_with_success_check('communities', 0, peerboard_bloginfo_array(), 'POST');

    if (!$response['success']) {
      return false;
    }

    return json_decode(wp_remote_retrieve_body($response['request']), true);
  }

  /**
   * Get community by token
   *
   * @param [type] $auth_token
   * @return void
   */
  public static function peerboard_get_community($auth_token)
  {
    $response = self::peerboard_api_call_with_success_check('communities', $auth_token, []);

    if (!$response['success']) {
      return false;
    }

    return json_decode(wp_remote_retrieve_body($response['request']), true);
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

    $response = self::peerboard_api_call_with_success_check('events', 0, $body, 'POST', PEERBOARD_API_URL);

    if (!$response['success']) {
      wp_send_json_error(sprintf('%s %s', $response['request']['response']['message'], __FUNCTION__));
    }

    wp_send_json_success(wp_remote_retrieve_body($response['request']));
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
