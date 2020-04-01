<?php
/*
Plugin Name: Circles integration
Plugin URI: http://circles.is
Description: Circles forum integration plugin
Version: 0.0.8
Author: anton@circles.is
*/
DEFINE('EMBED_URL', 'https://static.circles.is/embed/embed.js');
DEFINE('STYLE_URL', plugin_dir_url(__FILE__)."style.css");


require(plugin_dir_path(__FILE__)."settings.php");

function base64url_encode($data)
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
	global $circles_options;
	$options = get_option( 'circles_options', array() );
	if (!array_key_exists('prefix', $options) || $options['prefix'] == "") {
		$options['prefix'] = 'forum';
	}
	if (!array_key_exists('expose_user_data', $options)) {
		$options['expose_user_data'] = false;
	}
	$circles_options = $options;
});

add_action('wp_enqueue_scripts', function() {
	wp_register_style( 'circles', STYLE_URL );
	wp_enqueue_style( 'circles' );
});

register_activation_hook( __FILE__, 'circles_activate' );
function circles_activate(){
  $circles_post = get_option("circles_post");
	if (is_null($circles_post) || !$circles_post) {
		$post_data = array(
			'post_title'    => 'Circles forum integration',
			'post_alias'    => 'forum',
			'post_content'  => '',
			'post_status'   => 'publish',
			'post_type'     => 'page',
			'post_author'   => 1
		);
		$post_id = wp_insert_post( $post_data );
		update_option( "circles_post", $post_id);
	}
}

function isEmbedPage($prefix) {
	return (substr($_SERVER['REQUEST_URI'],0,strlen($prefix) + 1) == "/" . $prefix);
}

function getTailPath($prefix) {
	$r = $_SERVER['REQUEST_URI'];
	$prefix_part = sprintf("/%s/", $prefix);
	return ($r == $prefix_part) ? "/" : str_replace($prefix_part,"",$r);
}

add_filter('the_content', function( $content ) {
  global $circles_options;
  $circles_prefix = $circles_options['prefix'];
  if (isEmbedPage($circles_prefix)) {
    $community_id = $circles_options['community_id'];
    if (is_null($community_id) || !$community_id || intval($community_id) == 0) {
      return "<H4>Please set community id into 'Circles forum integration' page content</H4>";
    }
    $community_id = intval($community_id);

    $user = wp_get_current_user();

    $login_data_string = '';
    if ($user->ID != 0) {
      $payload = base64url_encode(json_encode(
        array(
          'communityID' => $community_id,
          'location' => getTailPath($circles_prefix),
        )
      ));

      $userdata = array(
        'email' =>  $user->user_email,
        'username' => $user->nickname,
        'bio' => $user->description,
        'photo_url' => get_avatar_url($user->user_email)
      );

      // Will send first and last name only if this true
      if ($circles_options['expose_user_data'] == '1') {
        $userdata['first_name'] = $user->first_name;
        $userdata['last_name'] = $user->last_name;
      }
      $userdata = http_build_query($userdata);
      $login_data_string = "data-forum-wp-login='$payload?$userdata'"
    }


    $script_url = EMBED_URL;
    $override_url = $circles_options['embed_script_url'];
    if ($override_url != NULL && $override_url != '') {
      $script_url = $override_url;
    }

    $url_parts = explode('://',get_home_url());
    $base_url = $url_parts[0].'://forum.'.$url_parts[1];
    remove_filter( 'the_content', 'wpautop' );
    return "$content
      <script defer src='$script_url'
      data-forum-id='$community_id'
      $login_data_string
      data-forum-prefix='$circles_prefix'
      data-forum-scroll='top'
      data-forum-hide-menu
      data-forum-resize
      data-forum-container-id='circles-forum'
      data-forum-base-url='$base_url'></script>
      <div id='circles-forum'></div>";
  }
  return $content;
}, 0);

add_filter('request', function( array $query_vars ) {
	global $circles_options;
	if (isEmbedPage($circles_options['prefix'])) {
		$query_vars = array("page_id" => get_option("circles_post"));
	}
	return $query_vars;
});
