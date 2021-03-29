<?php
if ( ! defined( 'DONOTCACHEPAGE' ) )
  define( 'DONOTCACHEPAGE', true );

function peerboard_match_path($what, $where) {
	return count(array_intersect($what, $where)) == count($what);
}

function peerboard_proxy_graphql($target, $token) {
	$ch = curl_init();
	$headers = array(
		'Authorization: '. $token,
		'Content-type: application/json',
	);
	if (isset($_COOKIE['wp-peerboard-auth'])) {
		$headers[] = 'Cookie: forum.auth.v2=' . $_COOKIE['wp-peerboard-auth'];
	}

	curl_setopt_array($ch, array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER     => $headers,
		CURLOPT_POST 					 => true,
		CURLOPT_POSTFIELDS     => file_get_contents('php://input'),
		CURLOPT_URL            => $target,
	));

	$result = curl_exec($ch);
	curl_close($ch);
	echo $result;
	exit;
}

function peerboard_proxy_password_login() {
  global $peerboard_options;
  peerboard_set_auth_cookie($_GET['token'], peerboard_get_full_domain() . "/" . $peerboard_options['prefix'] . '/login/finish?from=proxy');
  exit;
}

function peerboard_proxy_login($target, $token, $method) {
  $proxy = array();
  if ($method == 'GET') {
    $proxy = wp_remote_get($target, array(
  		'timeout'     => 5,
  		'headers'			=> array(
  			"Authorization" => "$token",
  		),
  	));
  } else {
    $proxy = wp_remote_post($target, array(
  		'timeout'     => 5,
  		'headers'			=> array(
  			"Authorization" => "$token",
  		),
  	));
  }

	// Means that there was a problem on wordpress side
	if ( is_wp_error( $proxy ) ){
		error_log("proxy login wp-error:" . $proxy->get_error_message());
		exit;
	}

	if (count($proxy['cookies']) == 0) {
		exit;
	}
	// As for now we sure that auth_cookie is first one
	$cookie = $proxy['cookies'][0];

	header("Expires: on, 01 Jan 1970 00:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header('Cache-Control: no-store, no-cache="Set-Cookie", must-revalidate, public');
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

	$result = str_replace(array("\r", "\n"), '', wp_remote_retrieve_body($proxy));

	// If its not oath login then we just redirect by result
	if (strpos($target, "/login/oauth2") === false) {
    if ($method == 'POST') {
      global $peerboard_options;
      echo peerboard_get_full_domain() . "/peerboard_internal/" . $peerboard_options['community_id'] . '/proxy/login?token=' . $cookie->value;
      exit;
    }
		peerboard_set_auth_cookie($cookie->value, $result);
	} else {
  	// otherwise we are printing script result (redirects by frontend)
    peerboard_set_auth_cookie($cookie->value);
		echo $result;
	}
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

function peerboard_proxy_get($splitted, $token) {
	$ch = curl_init();
	$headers = array(
		'Authorization: '. $token,
	);
	if (isset($_COOKIE['wp-peerboard-auth'])) {
		$headers[] =  'Cookie: forum.auth.v2=' . $_COOKIE['wp-peerboard-auth'];
	}

	curl_setopt_array($ch, array(
		CURLOPT_HTTPHEADER     => $headers,
		CURLOPT_POST 					 => false,
		CURLOPT_URL            => PEERBOARD_PROXY_URL . implode('/', $splitted),
		CURLOPT_TIMEOUT => 10,
	));
	curl_exec($ch);
	curl_close($ch);
	exit;
}

add_action('parse_request', 'peerboard_parse_request');
function peerboard_parse_request($request) {
	global $peerboard_options;
	$splitted = explode('/', $request->request);
	if (count	($splitted) > 1) {
		if ($splitted[0] != PEERBOARD_PROXY_PATH) {
			return;
		}
		if ($splitted[1] != $peerboard_options['community_id']) {
			echo "Provide community id";
			exit;
		}
		$splitted = array_splice($splitted, 1);

    // Proxy login requests
		if (peerboard_match_path(array('api','v2','forum','login'), $splitted)) {
			$query = '?' . http_build_query($_GET);
			$proxy_url = PEERBOARD_PROXY_URL . implode('/', $splitted) . $query;
      $method = 'GET';
      if (isset($_GET['password'])) {
        $method = 'POST';
      }
			return peerboard_proxy_login($proxy_url, $peerboard_options['auth_token'], $method);
		}

		// Proxy graphql requests
		if (peerboard_match_path(array('api','v2','forum','graphql'), $splitted)) {
			return peerboard_proxy_graphql(PEERBOARD_PROXY_URL . implode('/', $splitted), $peerboard_options['auth_token']);
		}

    // Proxy graphql requests
		if (peerboard_match_path(array('api','v2','forum','graphql'), $splitted)) {
			return peerboard_proxy_graphql(PEERBOARD_PROXY_URL . implode('/', $splitted), $peerboard_options['auth_token']);
		}

    // Proxy graphql requests
		if (peerboard_match_path(array('api','v2','forum','reset-password'), $splitted)) {
      $query = '?' . http_build_query($_GET);
			$proxy_url = PEERBOARD_PROXY_URL . implode('/', $splitted) . $query;
			return peerboard_proxy_graphql($proxy_url, $peerboard_options['auth_token']);
		}

    if (peerboard_match_path(array('proxy', 'login'), $splitted)) {
      return peerboard_proxy_password_login();
    }

		if (peerboard_match_path(array('file'), $splitted) && $_SERVER['REQUEST_METHOD'] === 'POST') {
			return peerboard_proxy_file_post(PEERBOARD_PROXY_URL . implode('/', $splitted), $peerboard_options['auth_token']);
		}

		return peerboard_proxy_get($splitted, $peerboard_options['auth_token']);
	}
	global $wp_rewrite;
	$rewrite = $wp_rewrite->wp_rewrite_rules();
	if ( empty( $rewrite ) ) {
		// Here we'll need to ask user to chenge links to rewritable
		return;
	}
}
