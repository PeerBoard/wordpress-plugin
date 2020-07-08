<?php

function peerboard_match_path($what, $where) {
	return count(array_intersect($what, $where)) == count($what);
}

function peerboard_proxy_graphql($target, $token) {
	$headers = getallheaders();
	$settings = array(
		'timeout'     => 5,
		'body' 				=> file_get_contents('php://input'),
		'headers'			=> array(
			"Authorization" => "$token",
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

function peerboard_proxy_login($target,$token) {
	$proxy = wp_remote_get($target, array(
		'timeout'     => 5,
		'headers'			=> array(
			"Authorization" => "$token",
		),
	));
	// Means that there was a problem on wordpress side

	if ( is_wp_error( $proxy ) ){
		error_log("proxy login wp-error:" . $proxy->get_error_message());
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
	//error_log(print_r($proxy, 1));
	header("Location: $redirect");
	exit;
}

function peerboard_proxy_file_post($target, $token) {
	$ch = curl_init();
	$headers = array(
		'Authorization: '. $token,
	);
	if (isset($_COOKIE['wp-peerboard-auth'])) {
		$headers[] =  'Cookie: forum.auth.v2=' . $_COOKIE['wp-peerboard-auth'];
	}

	$file = $_FILES['data'];
	$cfile = new \CURLFile($file['tmp_name'], $file['type']);

	$post = array('data' => $cfile, 'type' => 'post_attachment');
	curl_setopt_array($ch, array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER     => $headers,
		CURLOPT_POST 					 => true,
		CURLOPT_URL            => $target,
		CURLOPT_POSTFIELDS     => $post,
	));

	$result = curl_exec($ch);
	curl_close($ch);
	echo $result;
	exit;
}

function peerboard_proxy_get($splitted) {
	$proxy = wp_remote_get(PEERBOARD_PROXY_URL . implode('/', $splitted));
	if ( is_wp_error( $proxy ) ){
		echo $proxy->get_error_message();
	}
	$response_headers = $proxy['headers']->getAll();
	if (array_key_exists('content-type', $response_headers)) {
		header('Content-Type: ', $response_headers['content-type']);
	}
	echo wp_remote_retrieve_body($proxy);
	exit;
}

add_action('parse_request', 'peerboard_parse_request');
function peerboard_parse_request($request) {
	global $peerboard_options;
	$splitted = explode('/', $request->request);
	if (count	($splitted) > 1) {
		if ($splitted[0] != $peerboard_options['prefix']) {
			return;
		}
		if ($splitted[1] != $peerboard_options['community_id']) {
			return;
		}
		$splitted = array_splice($splitted, 1);

		// Proxy graphql requests
		if (peerboard_match_path(array('api','v2','forum','graphql'), $splitted)) {
			return peerboard_proxy_graphql(PEERBOARD_PROXY_URL . implode('/', $splitted), $peerboard_options['auth_token']);
		}

		// Proxy login requests
		if (peerboard_match_path(array('api','v2','forum','login'), $splitted)) {
			$query = '?' . http_build_query($_GET);
			$proxy_url = PEERBOARD_PROXY_URL . implode('/', $splitted) . $query;
			return peerboard_proxy_login($proxy_url, $peerboard_options['auth_token']);
		}

		if (peerboard_match_path(array('file'), $splitted) && $_SERVER['REQUEST_METHOD'] === 'POST') {
			return peerboard_proxy_file_post(PEERBOARD_PROXY_URL . implode('/', $splitted), $peerboard_options['auth_token']);
		}

		return peerboard_proxy_get($splitted);
	}
	global $wp_rewrite;
	$rewrite = $wp_rewrite->wp_rewrite_rules();
	if ( empty( $rewrite ) ) {
		// Here we'll need to ask user to chenge links to rewritable
		return;
	}
}
