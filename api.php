<?php
function peerboard_post_integration($token, $prefix) {
  wp_remote_post(PEERBOARD_API_BASE . 'integration', array(
    'timeout'     => 5,
    'headers' => array(
      'authorization' => "Bearer $token",
    ),
    'body' => json_encode(array(
      "path_prefix" => $prefix,
      "type" => 'wordpress',
    ))
  ));
}



function peerboard_create_community() {
  $proxy = wp_remote_post(PEERBOARD_API_BASE . 'community', array(
    'timeout'     => 5,
    'headers' => array(
      "Content-type" => "application/json",
    ),
    'body' => json_encode(array(
      'name' => 'wordpress community',
      'domain' => 'wordpress.is',
      'email' => 'anlopan@gmail.com',
      'type' => 'wp'
    ))
  ));
  if ( is_wp_error( $proxy ) ){
    error_log($proxy);
    return false;
	}
  return json_decode(wp_remote_retrieve_body($proxy), true);
}
