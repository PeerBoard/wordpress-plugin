<?php 
/*
Plugin Name: Circles integration
Plugin URI: http://circles.is
Description: Circles forum integration plugin
Version: 0.0.2
Author: anton@circles.is
*/

DEFINE('EMBED_URL', 'https://static.dev.randomcoffee.us/embed/embed.js'); // TODO: grab from the correct env
DEFINE('STYLE_URL', plugin_dir_url(__FILE__)."style.css");
DEFINE('PREFIX', 'forum'); // TODO: get prefix from settings


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

add_action('wp_enqueue_scripts', function() {
	wp_register_style( 'circles', STYLE_URL );
	wp_enqueue_style( 'circles' );
});

register_activation_hook( __FILE__, 'myplugin_activate' );
function myplugin_activate(){
	if (is_null(get_option("circles_post"))) {
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

function isEmbedPage() {
	return (substr($_SERVER['REQUEST_URI'],0,6) == "/" . PREFIX);
}

function getTailPath() {
	$r = $_SERVER['REQUEST_URI'];
	$prefix_part = sprintf("/%s/", PREFIX);
	return ($r == $prefix_part) ? "/" : str_replace($prefix_part,"",$r);
}
add_filter('the_content', function( $content ) {
	if (isEmbedPage()) {
		// We store community id in our custom page
		preg_match_all('!\d+!', $content, $matches);
		if (count($matches[0]) == 0 || intval($matches[0][0]) == 0) {
			return "<H4>Please set community id into 'Circles forum integration' page content</H4>";
		}
		$community_id = intval($matches[0][0]);

		$user = wp_get_current_user();
		$payload = base64url_encode(json_encode(
			array(
				'communityID' => $community_id,
				'location' => getTailPath(),
			)
		));
		$userdata = http_build_query(array(
			'email' =>  $user->user_email,
			'username' => $user->nickname,
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
			'photo_url' => get_avatar_url($user->user_email),
		));

		$script_url = EMBED_URL;
		remove_filter( 'the_content', 'wpautop' );
		return "
		<script defer src='$script_url'
			data-forum-id='$community_id'
			data-forum-wp-login='$payload?$userdata'
			data-forum-prefix='".PREFIX ."'
			data-forum-hide-menu
			data-forum-container-id='circles-forum'></script>
		<div id='circles-forum'></div>";
	}
	return $content;
}, 0);

add_filter('request', function( array $query_vars ) {
	if (isEmbedPage()) {
		$query_vars = array("page_id" => get_option("circles_post"));
	}
	return $query_vars;
});
