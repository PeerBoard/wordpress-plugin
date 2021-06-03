<?php

/**
 * Plugin Name: WordPress Forum Plugin – PeerBoard
 * Plugin URI: https://peerboard.com
 * Description: Forum, Community & User Profile Plugin
 * Version: 0.7.8
 * Text Domain: peerboard
 * Domain Path: /languages
 * Author: <a href='https://peerboard.com' target='_blank'>Peerboard</a>, forumplugin
 */

namespace PEBO;

// Exit if accessed directly
defined('ABSPATH') || exit;

register_activation_hook(__FILE__, ['PEBO\PeerBoard', 'plugin_activate']);
register_deactivation_hook(__FILE__, ['PEBO\PeerBoard', 'plugin_deactivate']);
register_uninstall_hook(__FILE__, ['PEBO\PeerBoard', 'peerboard_uninstall']);

class PeerBoard
{
	public static function init()
	{

		DEFINE('PEERBOARD_PROXY_PATH', 'peerboard_internal');
		DEFINE('PEERBOARD_PLUGIN_VERSION', '0.7.8');

		$peerboard_env_mode = getenv("PEERBOARD_ENV");
		if ($peerboard_env_mode === "local") {
			DEFINE('PEERBOARD_EMBED_URL', 'http://static.local.is/embed/embed.js');
			DEFINE('PEERBOARD_PROXY_URL', 'http://local.is/');
			DEFINE('PEERBOARD_API_BASE', 'http://api.local.is/v1/');
		} else if ($peerboard_env_mode === "dev") {
			DEFINE('PEERBOARD_EMBED_URL', 'https://static.peerboard.dev/embed/embed.js');
			DEFINE('PEERBOARD_PROXY_URL', 'https://peerboard.dev/');
			DEFINE('PEERBOARD_API_BASE', 'https://api.peerboard.dev/v1/');
		} else {
			DEFINE('PEERBOARD_EMBED_URL', 'https://static.peerboard.com/embed/embed.js');
			DEFINE('PEERBOARD_PROXY_URL', 'https://peerboard.com/');
			DEFINE('PEERBOARD_API_BASE', 'https://api.peerboard.com/v1/');
		}

		require_once plugin_dir_path(__FILE__) . "functions.php";
		require_once plugin_dir_path(__FILE__) . "settings.php";
		require_once plugin_dir_path(__FILE__) . "api.php";
		require_once plugin_dir_path(__FILE__) . "analytics.php";
		require_once plugin_dir_path(__FILE__) . "installation.php";

		add_action('plugins_loaded', [__CLASS__, 'true_load_plugin_textdomain']);

		add_action('init', [__CLASS__, 'init_plugin_logic_on_page']);

		/**
		 * Creating shortcode
		 */
		add_shortcode('peerboard', [__CLASS__, 'shortcode']);

		add_action('wp_enqueue_scripts', [__CLASS__, 'add_scripts']);

		add_filter('request', function (array $query_vars) {
			global $peerboard_options;
			if (peerboard_is_embed_page($peerboard_options['prefix'])) {
				$query_vars = array("page_id" => get_option("peerboard_post"));
				unset($query_vars['pagename']);
			}
			return $query_vars;
		});

		/**
		 * Sync users
		 */
		add_action('user_register', [__CLASS__, 'peerboard_sync_user_if_enabled']);

		//TODO check do we need this hook because i did not found any registered action with this name
		add_action('pre_update_option_peerboard_options', [__CLASS__, 'pre_update_option_peerboard_options'], 10, 3);
	}

	/**
	 * init plugin logic
	 *
	 * @return void
	 */
	public static function init_plugin_logic_on_page()
	{
		global $peerboard_options;
		$peerboard_options = get_option('peerboard_options', array());
		if (!array_key_exists('peerboard_version_synced', $peerboard_options)) {
			peerboard_post_integration($peerboard_options['auth_token'], $peerboard_options['prefix'], peerboard_get_domain());
			$peerboard_options['peerboard_version_synced'] = PEERBOARD_PLUGIN_VERSION;
			update_option('peerboard_options', $peerboard_options);
		} else if ($peerboard_options['peerboard_version_synced'] != PEERBOARD_PLUGIN_VERSION) {
			peerboard_post_integration($peerboard_options['auth_token'], $peerboard_options['prefix'], peerboard_get_domain());
			$peerboard_options['peerboard_version_synced'] = PEERBOARD_PLUGIN_VERSION;
			update_option('peerboard_options', $peerboard_options);
		}
	}

	/**
	 * Registering css and js
	 *
	 * @return void
	 */
	public static function add_scripts()
	{
		wp_register_style('peerboard_integration_styles', plugin_dir_url(__FILE__) . "/static/style.css", array(), '0.0.5');
		wp_register_script('peerboard-integration', plugin_dir_url(__FILE__) . "/static/peerboard-integration.js", array(), '0.0.7');
	}

	/**
	 * Shortcode
	 *
	 * @return void
	 */
	public static function shortcode($atts)
	{
		global $peerboard_options;
		/**
		 * Init styles and scripts
		 */
		wp_enqueue_style('peerboard_integration_styles');

		wp_enqueue_script('peerboard-integration');

		wp_localize_script('peerboard-integration', '_peerboardSettings', peerboard_get_script_settings($peerboard_options));

		ob_start();

		require_once plugin_dir_path(__FILE__) . '/templates/front-template.php';

		return ob_get_clean();
	}

	/**
	 * On plugin activate
	 *
	 * @return void
	 */
	public static function plugin_activate()
	{
		do_action('peerboard_activate');
	}

	/**
	 * On plugin deactivate
	 *
	 * @return void
	 */
	public static function plugin_deactivate()
	{
		do_action('peerboard_deactivate');
	}

	/**
	 * Remove plugin data on plugin uninstall
	 */
	function peerboard_uninstall()
	{
		delete_option('peerboard_recovery_token');
	}

	/**
	 * Add languages
	 *
	 * @return void
	 */
	public static function true_load_plugin_textdomain()
	{
		load_plugin_textdomain('peerboard', false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}

	/**
	 * Sync users
	 */
	public static function peerboard_sync_user_if_enabled($user_id)
	{
		global $peerboard_options;
		$sync_enabled = get_option('peerboard_users_sync_enabled');
		if ($sync_enabled === '1') {
			$user = get_userdata($user_id);
			$userdata = array(
				'email' =>  $user->user_email,
				'bio' => urlencode($user->description),
				'profile_url' => get_avatar_url($user->user_email),
				'name' => $user->display_name,
				'last_name' => ''
			);
			if ($peerboard_options['expose_user_data'] == '1') {
				$userdata['name'] = $user->first_name;
				$userdata['last_name'] = $user->last_name;
			}
			peerboard_create_user($peerboard_options['auth_token'], $userdata);
			$count = intval(get_option('peerboard_users_count'));
			update_option('peerboard_users_count', $count + 1);
		}
	}

	//TODO check do we need this hook because it did not found any registered action with this nam
	public static function pre_update_option_peerboard_options($value, $old_value, $option)
	{
		if ($old_value === NULL || $old_value === false) {
			return $value;
		}
		if ($value['prefix'] !== $old_value['prefix']) {
			// Case where we are connecting blank community by auth token, that we need to reuse old prefix | 'community'
			if ($value['prefix'] === '' || $value['prefix'] === NULL) {
				if ($old_value['prefix'] === '' || $old_value['prefix'] === NULL) {
					$old_value['prefix'] = 'community';
				}
				$value['prefix'] = $old_value['prefix'];
			}
			peerboard_update_post_slug($value['prefix']);
			peerboard_post_integration($value['auth_token'], $value['prefix'], peerboard_get_domain());
		}

		if ($value['auth_token'] !== $old_value['auth_token']) {
			$community = peerboard_get_community($value['auth_token']);
			$value['community_id'] = $community['id'];
			peerboard_send_analytics('set_auth_token', $community['id']);
			peerboard_post_integration($value['auth_token'], $value['prefix'], peerboard_get_domain());
			if ($old_value['auth_token'] !== '' && $old_value['auth_token'] !== NULL) {
				peerboard_drop_integration($old_value['auth_token']);
			}
		}

		return $value;
	}
}

PeerBoard::init();
