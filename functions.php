<?php

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
  $new_notice = sprintf('PeerBoard: %s (%s) - %s', $notice, $function_name, __('please contact us at support_wp@peerboard.com', 'peerboard'));
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

    PEBO\API::add_sentry_error($notice, $function_name, $extra);
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

function peerboard_get_script_settings($peerboard_options)
{
  $peerboard_prefix = $peerboard_options['prefix'];
  $auth_token = $peerboard_options['auth_token'];
  $community_id = intval($peerboard_options['community_id']);
  $user = wp_get_current_user();

  $payload = peerboard_base64url_encode(json_encode(
    array(
      'communityID' => $community_id,
      'location' => peerboard_get_tail_path($peerboard_prefix),
    )
  ));

  $login_data_string = "";
  $isUserLogged = false;
  if (!function_exists('is_user_logged_in')) {
    if (!empty($user->ID)) {
      $isUserLogged = true;
    }
  } else {
    if (is_user_logged_in()) {
      $isUserLogged = true;
    }
  }

  $result = array(
    'board-id' => $community_id,
    'prefix' => $peerboard_prefix,
  );

  if ($isUserLogged) {
    $userdata = array(
      'email' =>  $user->user_email,
      'username' => $user->nickname,
      'bio' => urlencode($user->description),
      'photo_url' => get_avatar_url($user->user_email),
      'first_name' => '',
      'last_name' => ''
    );

    // Will send first and last name only if this true
    if ($peerboard_options['expose_user_data'] == '1') {
      $userdata['first_name'] = $user->first_name;
      $userdata['last_name'] = $user->last_name;
    }

    if (current_user_can('manage_options')) {
      $userdata['role'] = 'admin';
    }

    $hash = peerboard_get_auth_hash($userdata, $auth_token);
    $userdata['hash'] = $hash;
    $userdata = http_build_query($userdata);
    $result['wpPayload'] = "$payload?$userdata";
  }

  $result['baseURL'] = PEERBOARD_URL;
  $result['sdkURL'] = PEERBOARD_EMBED_URL;

  return $result;
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

function peerboard_is_embed_page($prefix)
{
  return (substr($_SERVER['REQUEST_URI'], 0, strlen($prefix) + 1) == "/" . $prefix);
}

function peerboard_get_tail_path($prefix)
{
  $r = $_SERVER['REQUEST_URI'];
  // Trim /peerboard from request - uses for login redirect
  $trimmed = substr($r, strlen($prefix) + 1);
  if ($trimmed === "") {
    $trimmed = "/";
  }
  return $trimmed;
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
    'prefix' => $data['hosting']['path'],
    'redirect' => $data['url'],
    'mode' => $mode,
  );
}

function peerboard_update_post_slug($slug)
{
  wp_update_post(array(
    "ID" => get_option('peerboard_post'),
    "post_name" => $slug,
  ), false, false);
}
