<?php
/*
Plugin Name: WordPress Forum Plugin – PeerBoard
Plugin URI: https://peerboard.com
Description: Forum, Community & User Profile Plugin
Version: 0.4.4
Author: <a href='https://peerboard.com' target='_blank'>Peerboard</a>, forumplugin
*/
DEFINE('PEERBOARD_PROXY_PATH', 'peerboard_internal');

$peerboard_env_mode = getenv("PEERBOARD_ENV");
if ($peerboard_env_mode === "local") {
	DEFINE('PEERBOARD_EMBED_URL', 'http://static.local.is/embed/embed.js');
	DEFINE('PEERBOARD_PROXY_URL', 'http://local.is/');
	DEFINE('PEERBOARD_API_BASE', 'http://api.local.is/v1/');
	DEFINE('PEERBOARD_REDIRECT_URL', '');
} else if ($peerboard_env_mode === "dev") {
	DEFINE('PEERBOARD_EMBED_URL', 'https://static.peerboard.dev/embed/embed.js');
	DEFINE('PEERBOARD_PROXY_URL', 'https://peerboard.dev/');
	DEFINE('PEERBOARD_API_BASE', 'https://api.peerboard.dev/v1/');
	DEFINE('PEERBOARD_REDIRECT_URL', '');
} else {
	DEFINE('PEERBOARD_EMBED_URL', 'https://static.peerboard.com/embed/embed.js');
	DEFINE('PEERBOARD_PROXY_URL', 'https://peerboard.com/');
	DEFINE('PEERBOARD_API_BASE', 'https://api.peerboard.com/v1/');
	DEFINE('PEERBOARD_REDIRECT_URL', 'https://peerboard.com/getstarted');
}


require_once plugin_dir_path(__FILE__)."functions.php";
require_once plugin_dir_path(__FILE__)."settings.php";
require_once plugin_dir_path(__FILE__)."proxy.php";
require_once plugin_dir_path(__FILE__)."api.php";
require_once plugin_dir_path(__FILE__)."analytics.php";

add_action( 'init', function() {
	global $peerboard_options;
	$peerboard_options = get_option( 'peerboard_options', array() );
	if (!array_key_exists('mode', $peerboard_options)) {
		$peerboard_options['mode'] = 'sdk';
	}
});

add_action( 'activated_plugin', function( $plugin ) {
  global $peerboard_options;
  $peerboard_options = get_option( 'peerboard_options', array() );
  if (count($peerboard_options) === 0) {
		peerboard_send_analytics('activate_plugin');
    $peerboard_options = peerboard_get_options(peerboard_create_community());
    update_option('peerboard_options', $peerboard_options);
  }
	if( $plugin == plugin_basename( __FILE__ ) && array_key_exists('redirect', $peerboard_options)) {
		if ($peerboard_options['redirect']) {
			$url = $peerboard_options['redirect'];
			if (PEERBOARD_REDIRECT_URL !== '') {
				$url = PEERBOARD_REDIRECT_URL . '?redirect=' . urlencode($peerboard_options['redirect']) . '&communityId=' . $peerboard_options['community_id'];
			}
			exit( wp_redirect($url));
		}
	}
});

function peerboard_install() {
  global $peerboard_options;
  if ( ! current_user_can( 'activate_plugins' ) )
    return;

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
	global $peerboard_options;
  if ( ! current_user_can( 'activate_plugins' ) ) return;
  $post_id = get_option('peerboard_post');
  wp_delete_post($post_id, true);
	peerboard_send_analytics('uninstall_plugin', $peerboard_options['community_id']);
  delete_option('peerboard_post');
  delete_option('peerboard_options');
}

register_activation_hook( __FILE__, 'peerboard_install');
register_uninstall_hook( __FILE__, 'peerboard_uninstall');

add_filter('the_content', function( $content ) {
  global $peerboard_options;
  $peerboard_prefix = $peerboard_options['prefix'];
	$peerboard_mode = $peerboard_options['mode'];
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
    remove_filter( 'the_content', 'wpautop' );

    $script_url = PEERBOARD_EMBED_URL;
    $integration_tag_open = "<script defer src='$script_url'";
    $integration_tag_close = '></script>';

		$proxy_prefix = PEERBOARD_PROXY_PATH;
    $base_url = get_home_url() . "/$proxy_prefix";
		$prefix_string = '';
		if ($peerboard_mode === 'sdk') {
			$url_parts = explode('://', get_home_url());
	    $domain = str_replace("www.", "", $url_parts[1]);
	    $base_url = 'https://peerboard.'.$domain;
			$prefix_string = "data-forum-prefix='$peerboard_prefix'";
		} else {
			$prefix_string = "data-forum-prefix='$peerboard_prefix'";
			$prefix_string .= " data-forum-prefix-proxy='$proxy_prefix'";
		}

		return "$content
			$integration_tag_open
				data-forum-id='$community_id'
				$login_data_string
				$prefix_string
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
    wp_register_style( 'peerboard_integration_styles', plugin_dir_url(__FILE__)."/static/style.css", array(), '0.0.4' );
  	wp_enqueue_style( 'peerboard_integration_styles' );
	}
});

add_filter('request', function( array $query_vars ) {
	global $peerboard_options;
	if (peerboard_is_embed_page($peerboard_options['prefix'])) {
		$query_vars = array("page_id" => get_option("peerboard_post"));
		unset($query_vars['pagename']);
	}
	return $query_vars;
});

add_action('pre_update_option_peerboard_options', function( $value, $old_value, $option ) {
  if ($old_value === NULL || $old_value === false) {
    return $value;
  }

  error_log(print_r(array('old' => $old_value, 'new' => $value, 'option' => $option), true));

  if ($old_value['auth_token'] !== $value['auth_token'] ) {
		$community = peerboard_get_community($value['auth_token']);
    $value = peerboard_get_options($community);
		error_log(print_r(array('response' => $value), true));
		// Case where we are connecting blank community by auth token, that we need to reuse old prefix | 'community'
		if ($value['prefix'] === '') {
			if ($old_value['prefix'] === '' || $old_value['prefix'] === NULL) {
				$old_value['prefix'] = 'community';
			}
			$value['prefix'] = $old_value['prefix'];
			peerboard_post_integration($value['auth_token'], $value['prefix'], peerboard_get_domain());
		}
		peerboard_post_integration($value['auth_token'], $value['prefix'], peerboard_get_domain());
		peerboard_send_analytics('set_auth_token', $value['community_id']);
  }
  if ($old_value['prefix'] !== $value['prefix']) {
    if (array_key_exists('community_id', $value) && $value['community_id'] !== '') {
      peerboard_post_integration($value['auth_token'], $value['prefix'], peerboard_get_domain());
    }
  }
  return $value;
}, 10, 3);
