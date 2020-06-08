<?php
/*
Plugin Name: WordPress Forum Plugin – PeerBoard
Plugin URI: https://peerboard.io
Description: Forum, Community & User Profile Plugin
Version: 0.2.1
Author: <a href='https://peerboard.io' target='_blank'>Peerboard</a>, forumplugin
*/
DEFINE('PEERBOARD_EMBED_URL', 'https://static.peerboard.org/embed/embed.js');

require(plugin_dir_path(__FILE__)."settings.php");
require(plugin_dir_path(__FILE__)."analytics.php");

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
		$options['prefix'] = 'community';
	}
	if (!array_key_exists('expose_user_data', $options)) {
		$options['expose_user_data'] = false;
	}
  if (!array_key_exists('auth_token', $options)) {
    $options['auth_token'] = '';
  }
  if (!array_key_exists('domain_activated', $options)) {
    $options['domain_activated'] = '';
  }
	$peerboard_options = $options;
});

register_activation_hook( __FILE__, 'peerboard_plugin_activate' );
function peerboard_plugin_activate(){
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
	return (substr($_SERVER['REQUEST_URI'],0,strlen($prefix) + 1) == "/" . $prefix);
}

function peerboard_get_tail_path($prefix) {
	$r = $_SERVER['REQUEST_URI'];
	$prefix_part = sprintf("/%s/", $prefix);
	return ($r == $prefix_part) ? "/" : str_replace($prefix_part,"",$r);
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
  $override_url = $peerboard_options['embed_script_url'];
  $domain_activated = $peerboard_options['domain_activated'];

  if (peerboard_is_embed_page($peerboard_prefix)) {
    $auth_token = $peerboard_options['auth_token'];
    if ($domain_activated !== "1") {
      echo "PeerBoard integration status:<br/>";
      if ($auth_token == '') {
        return "PeerBoard plugin is connected. To continue the setup process please set your PeerBoard community auth token (can be found in Settings → Integrations) in WordPress plugin settings for PeerBoard.";
      }
      $api_url = 'https://api.peerboard.org/integration';
      if ($override_url != NULL && $override_url != '') {
        if ($override_url == 'http://static.local.is/embed/embed.js') {
          // Change this val for local testing
          $peerboard_options['community_id'] = 561465857;
          $peerboard_options['domain_activated'] = '1';
          update_option('peerboard_options', $peerboard_options);
          return peerboard_show_readme();
        } else {
          $api_url = 'https://api.peerboard.dev/integration';
        }
      }
      $response = wp_remote_get($api_url, array(
        'headers' => array(
          'authorization' => "Bearer $auth_token",
        ),
      ));
      if ( is_wp_error( $result ) ){
      	echo $response->get_error_message();
      }
      $result = json_decode(wp_remote_retrieve_body($response), true);
      $peerboard_options['community_id'] = $result['community_id'];
      $status = $result['status'];
      if ($status === 4) {
        $peerboard_options['domain_activated'] = '1';
        update_option('peerboard_options', $peerboard_options);
        //var_dump(get_option('peerboard_options'));
        echo "Congratulations, it's done! You finished the setup and should soon get access to your embedded PeerBoard!<br/>If you still don't see it, it may be a DNS propagation issue, allow it a few minutes to resolve.<br/><br/>";
      } else {
        $perfecto = "Perfecto, we detected the required CNAME change and are issuing SSL certificates now.<br/>Shouldn't take more than a minute.<br/><br/>";
        switch ($status) {
          case 0:
            echo 'You are almost done connecting PeerBoard. To finish, sign in to your domain name provider (such as Godaddy.com or NameCheap.com) and add a new DNS record of CNAME type for "peerboard" pointing to "peerboard.org".<br/><br/>This is needed for us to proxy API calls through your domain to avoid using cross-domain cookies.<br/><br/>';
            break;
          case 1:
            echo $perfecto;
            break;
          case 2:
            echo $perfecto;
            break;
          case 3:
            echo $perfecto;
            break;
        }
      }
      return peerboard_show_readme();
    }

    $community_id = intval($peerboard_options['community_id']);
    $user = wp_get_current_user();

    $payload = peerboard_base64url_encode(json_encode(
      array(
        'communityID' => $community_id,
        'location' => peerboard_get_tail_path($peerboard_prefix),
      )
    ));

    $login_data_string = "data-forum-wp-login='$payload?logout=true'";
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

    $script_url = PEERBOARD_EMBED_URL;
    $is_local = false;
    if ($override_url != NULL && $override_url != '') {
      $script_url = $override_url;
      if ($override_url == 'http://static.local.is/embed/embed.js') {
        $is_local = true;
      }
    }

    $url_parts = explode('://', get_home_url());
    $base_url = 'https://peerboard.'.$url_parts[1];
    if ($is_local) {
      $base_url = 'http://peerboard.wordpress.is';
    }
    $integration_tag_open = "<script defer src='$script_url'";
    $integration_tag_close = '></script>';
    remove_filter( 'the_content', 'wpautop' );
    return "$content
      $integration_tag_open
        data-forum-id='$community_id'
        $login_data_string
        data-forum-prefix='$peerboard_prefix'
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

add_action('pre_update_option_peerboard_options', function( $value, $old_value, $option ) {
  if ($old_value['prefix'] !== $value['prefix']) {
    $override_url = $value['embed_script_url'];
    $auth_token = $value['auth_token'];
    $api_url = 'https://api.peerboard.org/integration';
    if ($override_url != NULL && $override_url != '') {
      $api_url = 'https://api.peerboard.dev/integration';
    }
    wp_remote_post($api_url, array(
      'timeout'     => 5,
      'headers' => array(
        'authorization' => "Bearer $auth_token",
      ),
      'body' => json_encode(array(
        "path_prefix" => $value['prefix'],
      ))
    ));
  }
  if ($old_value['auth_token'] !== $value['auth_token']) {
    $value['domain_activated'] = '';
    peerboard_send_analytics('set_auth_token');
  }
  return $value;
}, 10, 3);
