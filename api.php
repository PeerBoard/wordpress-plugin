<?php
function peerboard_post_integration($token, $prefix, $domain) {
  wp_remote_post(PEERBOARD_API_BASE . 'hosting', array(
    'timeout'     => 5,
    'headers' => array(
      'authorization' => "Bearer $token",
    ),
    'body' => json_encode(array(
      "domain" => $domain,
      "path" => $prefix,
      "type" => 'wordpress',
    ))
  ));
}

function peerboard_drop_integration($token) {
  wp_remote_post(PEERBOARD_API_BASE . 'hosting', array(
    'timeout'     => 5,
    'headers' => array(
      'authorization' => "Bearer $token",
    ),
    'body' => json_encode(array(
      "type" => 'none'
    ))
  ));
}

function peerboard_sync_users($token, $users) {
  $response = wp_remote_post(PEERBOARD_API_BASE . 'users/batch', array(
    'timeout'     => 5,
    'headers' => array(
      'authorization' => "Bearer $token",
    ),
    'body' => json_encode($users)
  ));
  if ( is_wp_error( $response )) {
    return $response;
	}
  return json_decode(wp_remote_retrieve_body($response), true);
}

function peerboard_create_community() {
  $response = wp_remote_post(PEERBOARD_API_BASE . 'communities', array(
    'timeout'     => 45,
    'headers' => array(
      "Content-type" => "application/json",
      "Partner" => "wordpress_default_partner_token"
    ),
    'body' => json_encode(peerboard_bloginfo_array()),
    'sslverify' => false,
  ));
  if ( is_wp_error( $response )) {
    return $response;
	}
  return json_decode(wp_remote_retrieve_body($response), true);
}

function peerboard_get_community($auth_token) {
  $response = wp_remote_get(PEERBOARD_API_BASE . 'communities', array(
   'headers' => array(
     'authorization' => "Bearer $auth_token",
   ),
  ));
  if ( is_wp_error( $response ) ){
    return $response;
  }
  return json_decode(wp_remote_retrieve_body($response), true);
}
