<?php
function peerboard_post_integration($token, $prefix, $domain) {
  wp_remote_post(PEERBOARD_API_BASE . 'integration', array(
    'timeout'     => 5,
    'headers' => array(
      'authorization' => "Bearer $token",
    ),
    'body' => json_encode(array(
      "domain" => $domain,
      "path_prefix" => $prefix,
      "type" => 'wordpress',
    ))
  ));
}

function peerboard_create_community() {
  $response = wp_remote_post(PEERBOARD_API_BASE . 'community', array(
    'timeout'     => 5,
    'headers' => array(
      "Content-type" => "application/json",
    ),
    'body' => json_encode(peerboard_bloginfo_array()),
    'sslverify' => false,
  ));
  if ( is_wp_error( $response ) ){
    error_log(print_r($response, true));
    return false;
	}
  error_log(print_r($response, true));
  return json_decode(wp_remote_retrieve_body($response), true);
}

function peerboard_get_community($auth_token) {
  $response = wp_remote_get(PEERBOARD_API_BASE . 'community', array(
   'headers' => array(
     'authorization' => "Bearer $auth_token",
   ),
  ));
  if ( is_wp_error( $response ) ){
    error_log(print_r($response, true));
    return false;
  }
  return json_decode(wp_remote_retrieve_body($response), true);
}
