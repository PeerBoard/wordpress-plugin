<?php

function peerboard_match_path($what, $where) {
	return count(array_intersect($what, $where)) == count($what);
}

function peerboard_proxy_graphql($target) {
	$headers = getallheaders();
	$settings = array(
		'timeout'     => 5,
		'body' 				=> file_get_contents('php://input'),
		'headers'			=> array(
			"Origin" => "wordpress",
			"Content-type" => "application/json",
			"Content-length" => $headers['Content-Length'],
		),
	);
	if (isset($_COOKIE['wp-peerboard-auth'])) {
		$settings['headers']['Cookie'] =  'forum.auth.v2=' . $_COOKIE['wp-peerboard-auth'];
	}
	$proxy = wp_remote_post($target, $settings);
	// Means that there was a problem on wordpress side
	if ( is_wp_error( $proxy ) ){
		echo $proxy->get_error_message();
	}
	echo wp_remote_retrieve_body($proxy);
	exit;
}

function peerboard_proxy_login($target) {
	$proxy = wp_remote_get($target, array(
		'timeout'     => 5,
		'headers'			=> array(
			"Origin" => "wordpress",
		),
	));
	// Means that there was a problem on wordpress side

	if ( is_wp_error( $proxy ) ){
		echo "WP_ERROR";
		echo $proxy->get_error_message();
	}

	if (count($proxy['cookies']) > 0) {
		// As for now we sure that auth_cookie is first one
		$cookie = $proxy['cookies'][0];
		$domain = str_replace("http://","",get_home_url());
		$domain = str_replace("https://","",$domain);
		$domain = str_replace("www.","",$domain);
		setcookie('wp-peerboard-auth', $cookie->value, 0, '/', $domain, false, true);
	}

	$redirect = wp_remote_retrieve_body($proxy);
	header("Location: $redirect");
	exit;
}

add_action('parse_request', 'peerboard_parse_request');
function peerboard_parse_request($request) {
	$splitted = explode('/', $request->request);
	if (count	($splitted) > 1) {
		if ($splitted[0] !== 'peerboard') {
			return;
		}
		$splitted = array_splice($splitted, 1);

		// Proxy graphql requests
		if (peerboard_match_path(array('api','v2','forum','graphql'), $splitted)) {
			return peerboard_proxy_graphql(PEERBOARD_PROXY_URL . implode('/', $splitted));
		}

		// Proxy login requests
		if (peerboard_match_path(array('api','v2','forum','login'), $splitted)) {
			$query = '?' . http_build_query($_GET);
			$proxy_url = PEERBOARD_PROXY_URL . implode('/', $splitted) . $query;
			return peerboard_proxy_login($proxy_url);
		}

		$proxy = wp_remote_get(PEERBOARD_PROXY_URL . implode('/', $splitted));
	  if ( is_wp_error( $proxy ) ){
	    echo $proxy->get_error_message();
	  }
		echo wp_remote_retrieve_body($proxy);
		exit;
	}
	global $wp_rewrite;
	$rewrite = $wp_rewrite->wp_rewrite_rules();
	if ( empty( $rewrite ) ) {
		// Here we'll need to ask user to chenge links to rewritable
		return;
	}
}
