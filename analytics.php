<?php
DEFINE('PEERBOARD_AMPLITUDE_ENDPOINT', 'https://api.amplitude.com/2/httpapi');
DEFINE('PEERBOARD_AMPLITUDE_API_KEY', '58fd9c4d27c06daaed207bda06b7985c');

function peerboard_send_analytics($type, $community_id = 0) {
  $user = wp_get_current_user();
  $url_parts = explode('://', get_home_url());
  $domain = str_replace("www.", "", $url_parts[1]);

  $params = array(
    'event_type' => 'wordpress_' . $type,
    'device_id' => 'wordpress_' . $domain,
    'user_properties' => array(
     'email' => $user->user_email
    )
  );

  if ($community_id != 0) {
    $params['user_id'] = $community_id;
  }

  wp_remote_post(PEERBOARD_AMPLITUDE_ENDPOINT, array(
    'timeout'     => 5,
    'body' => json_encode(array(
    'api_key' => PEERBOARD_AMPLITUDE_API_KEY,
    'events' => $params
    ))
  ));
 }
