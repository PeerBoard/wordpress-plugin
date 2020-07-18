<?php
function peerboard_get_domain() {
  $info = peerboard_bloginfo_array();
  return $info['domain'];
}

function peerboard_bloginfo_array() {
    $fields = array('name', 'wpurl', 'admin_email');
    $data = array();
    foreach($fields as $field) {
      $field_data = get_bloginfo($field);
      if ($field === 'wpurl') {
        $field_data = explode('://', $field_data);
      }
      $data[$field] = $field_data;
    }
    $data['type'] = 'wordpress';
    return array(
      'name' => $data['name'],
      'domain' => $data['wpurl'],
      'email' => $data['admin_email'],
      'type' => 'wordpress',
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

function peerboard_is_embed_page($prefix) {
  return (substr($_SERVER['REQUEST_URI'],0,strlen($prefix) + 1) == "/" . $prefix);
}

function peerboard_get_tail_path($prefix) {
  $r = $_SERVER['REQUEST_URI'];
  // Trim /peerboard from request - uses for login redirect
  $trimmed = substr($r, strlen($prefix) + 1);
  if ($trimmed === "") {
    $trimmed = "/";
  }
	return $trimmed;
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
  $integration_type = $data['integration_type'];
  $mode = 'proxy';
  if ($integration_type === 'sdk') {
    $mode = 'sdk';
  }

  return array(
    'community_id' => $data['id'],
    'auth_token' => $data['auth_token'],
    'prefix' => $data['path_prefix'],
    'redirect' => $data['url'],
    'mode' => $mode,
  );
}
