<?php
DEFINE('PEERBOARD_AMPLITUDE_ENDPOINT', 'https://api.amplitude.com/2/httpapi');
$amp_api_key = getenv("PEERBOARD_AMPLITUDE_API_KEY");
if (!$amp_api_key) {
    $amp_api_key = '58fd9c4d27c06daaed207bda06b7985c';
}
DEFINE('PEERBOARD_AMPLITUDE_API_KEY', $amp_api_key);

function peerboard_send_analytics($type, $community_id = 0) {
  $user = wp_get_current_user();

  $params = array(
    'event_type' => 'wordpress_' . $type,
    'user_properties' => array(
     'email' => $user->user_email
    )
  );

  if ($community_id !== 0) {
    $params['user_id'] = $community_id;
  }

  wp_remote_post(PEERBOARD_AMPLITUDE_ENDPOINT, array(
    'timeout'     => 5,
    'body' => json_encode(array(
    'api_key' => PEERBOARD_AMPLITUDE_API_KEY,
    'events' => array($params)
    ))
  ));
 }
