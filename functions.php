<?php


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

function peerboard_migrate_to_new_type($options) {
  $options['migrated'] = true;
  update_option('peerboard_options', $options);
  peerboard_post_integration($options['auth_token'], $options['prefix']);
}

function peerboard_is_embed_page($prefix) {
  return (get_the_ID() == get_option("peerboard_post")) || (substr($_SERVER['REQUEST_URI'],0,strlen($prefix) + 1) == "/" . $prefix);
}

function peerboard_get_tail_path($prefix) {
  $r = $_SERVER['REQUEST_URI'];
	error_log($r);
	return "/";
}

function peerboard_get_auth_hash($params, $secret) {
  $strings = array();
  foreach ($params as $key => $value) {
    $strings[] = $key . '=' . $value;
  }
  sort($strings);

  return hash_hmac('sha256', implode("\n", $strings), hash('sha256', $secret, true));
}

function peerboard_get_options($data) {
  if ($data === false) {
    return;
  }
  return array(
    'community_id' => $data['id'],
    'auth_token' => $data['auth_token'],
    'prefix' => $data['path_prefix'],
    'redirect' => $data['url'],
    'migrated' => true,
  );
}
