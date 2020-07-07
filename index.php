<?php
/*
Plugin Name: WordPress Forum Plugin – PeerBoard
Plugin URI: https://peerboard.io
Description: Forum, Community & User Profile Plugin
Version: 0.2.6
Author: <a href='https://peerboard.io' target='_blank'>Peerboard</a>, forumplugin
*/
DEFINE('PEERBOARD_EMBED_URL', 'http://static.local.is/embed/embed.js');
DEFINE('PEERBOARD_PROXY_URL', 'http://local.is/');

require(plugin_dir_path(__FILE__)."settings.php");
require(plugin_dir_path(__FILE__)."analytics.php");
require(plugin_dir_path(__FILE__)."proxy.php");

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

add_action( 'init', function() {
	global $peerboard_options;
	$options = get_option( 'peerboard_options', array() );
	if (!array_key_exists('prefix', $options)) {
		$options['prefix'] = 'peerboard';
	}
	if (!array_key_exists('expose_user_data', $options)) {
		$options['expose_user_data'] = false;
	}
  if (!array_key_exists('auth_token', $options)) {
    $options['auth_token'] = '';
  }
  if (!array_key_exists('community_id', $options)) {
    $options['community_id'] = '';
  }

	$peerboard_options = $options;
});

function peerboard_activation_redirect( $plugin ) {
  global $peerboard_options;
  if( $plugin == plugin_basename( __FILE__ ) ) {
    exit( wp_redirect($peerboard_options['redirect']));
  }
}
add_action( 'activated_plugin', 'peerboard_activation_redirect' );

register_activation_hook( __FILE__, 'peerboard_plugin_activate' );
function peerboard_plugin_activate(){
  global $peerboard_options;
  if ( ! current_user_can( 'activate_plugins' ) )
    return;

  peerboard_send_analytics('activate_plugin');

  $peerboard_post = get_option("peerboard_post");
	if (is_null($peerboard_post) || !$peerboard_post) {
		$post_data = array(
			'post_title'    => 'Community',
			'post_alias'    => 'community',
			'post_content'  => '',
			'post_status'   => 'publish',
			'post_type'     => 'page',
			'post_author'   => 1
		);
		$post_id = wp_insert_post( $post_data );
		update_option( "peerboard_post", $post_id);
	}
  $proxy = wp_remote_post('http://api.local.is/v1/community', array(
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
    return;
	}
  $result = json_decode(wp_remote_retrieve_body($proxy), true);
  $peerboard_options['community_id'] = $result['id'];
  $peerboard_options['auth_token'] = $result['auth_token'];
  $peerboard_options['prefix'] = $result['path_prefix'];
  $peerboard_options['redirect'] = $result['url'];
  update_option('peerboard_options', $peerboard_options);
}

register_uninstall_hook( __FILE__, 'peerboard_plugin_uninstall' );
function peerboard_plugin_uninstall(){
  if ( ! current_user_can( 'activate_plugins' ) )
    return;

  $post_id = get_option('peerboard_post');
  wp_delete_post($post_id, true);
  delete_option('peerboard_post');
  delete_option('peerboard_options');
  peerboard_send_analytics('uninstall_plugin');
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

add_filter('the_content', function( $content ) {
  global $peerboard_options;
  $peerboard_prefix = $peerboard_options['prefix'];
  if (peerboard_is_embed_page($peerboard_prefix)) {
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
    if ( !function_exists('is_user_logged_in') ) {
      if ( !empty($user->ID) ) {
        $isUserLogged = true;
      }
    } else {
      if ( is_user_logged_in() ) {
        $isUserLogged = true;
      }
    }

    if ($isUserLogged) {
      $userdata = array(
        'email' =>  $user->user_email,
        'username' => $user->nickname,
        'bio' => $user->description,
        'photo_url' => get_avatar_url($user->user_email),
        'first_name' => '',
        'last_name' => ''
      );

      // Will send first and last name only if this true
      if ($peerboard_options['expose_user_data'] == '1') {
        $userdata['first_name'] = $user->first_name;
        $userdata['last_name'] = $user->last_name;
      }

      if (current_user_can( 'manage_options' )) {
        $userdata['role'] = 'admin';
      }

      $hash = peerboard_get_auth_hash($userdata, $auth_token);
      $userdata['hash'] = $hash;
      $userdata = http_build_query($userdata);

      $login_data_string = "data-forum-wp-login='$payload?$userdata'";
    }

    $base_url = get_home_url() . "/peerboard";
    $script_url = PEERBOARD_EMBED_URL;
    $integration_tag_open = "<script defer src='$script_url'";
    $integration_tag_close = '></script>';
    remove_filter( 'the_content', 'wpautop' );
    return "$content
      $integration_tag_open
        data-forum-id='$community_id'
        $login_data_string
        data-forum-prefix-proxy='$peerboard_prefix'
        data-forum-scroll='top'
        data-forum-hide-menu
        data-forum-resize
        data-forum-container-id='circles-forum'
        data-forum-base-url='$base_url'
      $integration_tag_close
      <div id='circles-forum'></div>";
  }
  return $content;
}, 9999999);

add_action( 'wp_enqueue_scripts', 'peerboard_include_files' );
function peerboard_include_files() {
  global $peerboard_options;
	if (peerboard_is_embed_page($peerboard_options['prefix'])) {
    wp_register_style( 'peerboard_integration_styles', plugin_dir_url(__FILE__)."/static/style.css" );
  	wp_enqueue_style( 'peerboard_integration_styles' );
	}
}

add_filter('request', function( array $query_vars ) {
	global $peerboard_options;
	if (peerboard_is_embed_page($peerboard_options['prefix'])) {
		$query_vars = array("page_id" => get_option("peerboard_post"));
	}
	return $query_vars;
});
