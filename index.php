<?php
/*
Plugin Name: WordPress Forum Plugin – PeerBoard
Plugin URI: https://peerboard.io
Description: Forum, Community & User Profile Plugin
Version: 0.2.7
Author: <a href='https://peerboard.io' target='_blank'>Peerboard</a>, forumplugin
*/
DEFINE('PEERBOARD_EMBED_URL', 'http://static.local.is/embed/embed.js');
DEFINE('PEERBOARD_PROXY_URL', 'http://local.is/');
DEFINE('PEERBOARD_API_BASE', 'http://api.local.is/v1/');

require_once plugin_dir_path(__FILE__)."functions.php";
require_once plugin_dir_path(__FILE__)."settings.php";
require_once plugin_dir_path(__FILE__)."analytics.php";
require_once plugin_dir_path(__FILE__)."proxy.php";
require_once plugin_dir_path(__FILE__)."api.php";

add_action( 'init', function() {
	global $peerboard_options;
	$peerboard_options = get_option( 'peerboard_options', array() );
});

add_action( 'activated_plugin', function( $plugin ) {
  global $peerboard_options;
  $peerboard_options = get_option( 'peerboard_options', array() );

  if (count($peerboard_options) === 0) {
    $result = peerboard_create_community();
    $peerboard_options = peerboard_get_options($result);
    update_option('peerboard_options', $peerboard_options);
  } else {
    if ($peerboard_options['community_id'] !== '') {
      peerboard_migrate_to_new_type($peerboard_options);
    }
  }

  error_log('Till here ->>');
  error_log(print_r($peerboard_options, true));
  if( $plugin == plugin_basename( __FILE__ ) && array_key_exists('redirect', $peerboard_options)) {
    if ($peerboard_options['redirect']) {
      exit( wp_redirect($peerboard_options['redirect']));
    }
  }
});

function peerboard_install() {
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
}

function peerboard_uninstall() {
  if ( ! current_user_can( 'activate_plugins' ) ) return;
  $post_id = get_option('peerboard_post');
  wp_delete_post($post_id, true);
  delete_option('peerboard_post');
  delete_option('peerboard_options');
  peerboard_send_analytics('uninstall_plugin');
}

register_activation_hook( __FILE__, 'peerboard_install');
register_uninstall_hook( __FILE__, 'peerboard_uninstall');

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

    $base_url = get_home_url() . "/$peerboard_prefix";
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

add_action( 'wp_enqueue_scripts', function() {
  global $peerboard_options;
	if (peerboard_is_embed_page($peerboard_options['prefix'])) {
    wp_register_style( 'peerboard_integration_styles', plugin_dir_url(__FILE__)."/static/style.css" );
  	wp_enqueue_style( 'peerboard_integration_styles' );
	}
});

add_filter('request', function( array $query_vars ) {
	global $peerboard_options;
	if (peerboard_is_embed_page($peerboard_options['prefix'])) {
		$query_vars = array("page_id" => get_option("peerboard_post"));
	}
	return $query_vars;
});

add_action('pre_update_option_peerboard_options', function( $value, $old_value, $option ) {
  if ($old_value === NULL) {
    return $value;
  }

  if ($old_value['auth_token'] !== $value['auth_token'] ) {
    $data = peerboard_get_community($value['auth_token']);
    $value = peerboard_get_options($data);
  }
  if ($old_value['prefix'] !== $value['prefix']) {
    if (array_key_exists('community_id', $value) && $value['community_id'] !== '') {
      peerboard_post_integration($value['auth_token'], $value['prefix']);
    }
  }
  error_log("VALUE!!!");
  error_log(print_r($value, true));
  return $value;
}, 10, 3);
