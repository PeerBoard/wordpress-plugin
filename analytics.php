<?php
DEFINE('PEERBOARD_AMPLITUDE_ENDPOINT', 'https://api.amplitude.com/2/httpapi');
DEFINE('PEERBOARD_AMPLITUDE_API_KEY', '381e48e71b68ae50a29454b78a4fa8c8');

function peerboard_send_analytics($type) {
  $user = wp_get_current_user();
  wp_remote_post(PEERBOARD_AMPLITUDE_ENDPOINT, array(
    'timeout'     => 5,
    'body' => json_encode(array(
    'api_key' => PEERBOARD_AMPLITUDE_API_KEY,
    'events' => array(
      'user_id' => get_home_url(),
      'event_type' => $type,
      'user_properties' => array(
       'email' => $user->user_email
      )
    )
    ))
  ));
 }
