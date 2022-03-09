<?php

use PEBO\UserSync;

use Firebase\JWT\JWT;

/**
 * Add notice to show
 *
 * @param string $notice
 * @param integer $type
 * @return void
 */
function peerboard_add_notice($notice, $function_name, $type = "success", $args = [])
{
  $notices = is_array(get_transient('peerboard_notices')) ? get_transient('peerboard_notices') : [];
  $new_notice = sprintf(
    'PeerBoard: (Only administrator see this message) <br>%s (%s) - %s%s<br> %s',
    $notice,
    $function_name,
    $args['file'] ?? '',
    isset($args['line']) ? ':' . $args['line'] : '',
    __('please contact us at support_wp@peerboard.com', 'peerboard')
  );

  $notice_exist = false;

  // Check if notice already exist
  foreach ($notices as $notice) {
    if ($notice['notice'] === $new_notice) {
      $notice_exist = true;
    }
  }

  if (!$notice_exist) {
    $notices[] = [
      'notice' => $new_notice,
      'type' => $type
    ];

    $extra = [
      'args' => $args
    ];

    // temporary solution
    if ($notice !== 'provide auth token') {
      PEBO\API::add_sentry_error($notice, $function_name, $extra);
    }
  }

  set_transient('peerboard_notices', $notices, 60);
}

/**
 * Get environment
 *
 * @return void
 */
function peerboard_get_environment()
{
  $environment = 'prod';
  if (defined('PEERBOARD_ENV')) {
    $environment = PEERBOARD_ENV;
  }

  return $environment;
}

/**
 * Is wp installed in sub directory
 *
 * @return string
 */
function peerboard_get_wp_installed_sub_dir()
{
  $parsed_url = parse_url(home_url('/'));

  if (!empty($parsed_url['path'])) {
    return $parsed_url['path'];
  }

  return false;
}

/**
 * Returning community full slug (with parent page slug, and sub directory slug)
 *
 * @return string
 */
function peerboard_get_comm_full_slug()
{

  $post_id = intval(get_option('peerboard_post'));
  $post = get_post($post_id);

  if (!$post) {
    return '';
  }

  $slug = $post->post_name;

  $comm_slug = substr(get_permalink($post_id), strlen(home_url('/')));

  if (peerboard_get_wp_installed_sub_dir()) {
    $comm_slug = peerboard_get_wp_installed_sub_dir() . $comm_slug;
  }

  return untrailingslashit($comm_slug);
}

function peerboard_get_path_from_url($full_slug){

  $path = parse_url($full_slug)['path'];

  return untrailingslashit($path);
}

/**
 * Array to JWT
 *
 * @param [type] $payload
 * @return void
 */
function pebo_get_jwt_token($payload)
{

  $peerboard_options = get_option('peerboard_options');

  $auth_token = $peerboard_options['auth_token'];

  JWT::$leeway = 60 * 2; // $leeway in seconds
  $jwt = JWT::encode($payload, $auth_token, 'HS256');

  return $jwt;
}

function peerboard_get_domain()
{
  $info = peerboard_bloginfo_array();
  return $info['hosting']['domain'];
}

function peerboard_bloginfo_array()
{
  $fields = array('name', 'wpurl', 'admin_email');
  $data = array();
  foreach ($fields as $field) {
    $field_data = get_bloginfo($field);
    if ($field === 'wpurl') {
      $field_data = explode('://', $field_data);
      $field_data = explode('/', $field_data[1]);
      $field_data = $field_data[0];
    }
    $data[$field] = $field_data;
  }
  $data['type'] = 'wordpress';
  return array(
    'name' => $data['name'],
    'admins' => array(
      array(
        'email' => $data['admin_email'],
      )
    ),
    'hosting' => array(
      'domain' => $data['wpurl'],
      'type' => 'wordpress',
      'version' => PEERBOARD_PLUGIN_VERSION
    )
  );
}

function peerboard_base64url_encode($data)
{
  // First of all you should encode $data to Base64 string
  $b64 = base64_encode($data);

  // Make sure you get a valid result, otherwise, return FALSE, as the base64_encode() function do
  if ($b64 === false) {
    return false;
  }

  // Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”
  $url = strtr($b64, '+/', '-_');

  // Remove padding character from the end of line and return the Base64URL result
  return rtrim($url, '=');
}

/**
 * Check if comm page slug exist is url
 *
 * @return bool
 */
function peerboard_is_embed_page($prefix)
{
  $current_url = home_url(add_query_arg(null, null));
  $url_with_path = home_url($prefix);

  $is_embed_page = 0 === strpos($current_url, $url_with_path);

  return $is_embed_page;
}

function peerboard_get_auth_hash($params, $secret)
{
  $strings = array();
  foreach ($params as $key => $value) {
    $strings[] = $key . '=' . $value;
  }
  sort($strings);

  return hash_hmac('sha256', implode("\n", $strings), hash('sha256', $secret, true));
}

function peerboard_get_options($data)
{
  if(empty($data)){
    return false;
  }

  if (is_wp_error($data)) {
    return array("error" => $data);
  }
  $integration_type = $data['hosting']['type'];
  $mode = 'proxy';
  if ($integration_type === 'sdk') {
    $mode = 'sdk';
  }

  return array(
    'community_id' => $data['id'],
    'auth_token' => $data['auth_token'],
    'prefix' => $data['hosting']['path'] ?? '',
    'redirect' => $data['url'],
    'mode' => $mode,
  );
}

/**
 * Update post slug
 *
 * @param [type] $slug
 * @return void
 */
function peerboard_update_post_slug($slug)
{
  $sanitized_slug = sanitize_title($slug);

  wp_update_post(array(
    "ID" => intval(get_option('peerboard_post')),
    "post_name" => $sanitized_slug,
  ), false, false);
}

/**
 * Is community page set as home page
 *
 * @return boolean
 */
function peerboard_is_comm_set_static_home_page()
{
  $page_id = intval(get_option('peerboard_post'));
  $home_id = intval(get_option('page_on_front'));

  if (!$home_id) {
    return false;
  }

  if ($page_id === $home_id) {
    return true;
  }

  return false;
}


