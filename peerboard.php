<?php

/**
 * Plugin Name: WordPress Forum Plugin – PeerBoard
 * Plugin URI: https://peerboard.com/integrations/wordpress-forum-plugin
 * Description: Forum, Community & User Profile Plugin
 * Version: 0.8.1
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
		DEFINE('PEERBOARD_PLUGIN_VERSION', '0.8.1');
		DEFINE('PEERBOARD_PLUGIN_URL', plugins_url('', __FILE__));
		DEFINE('PEERBOARD_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));

		require_once plugin_dir_path(__FILE__) . "/inc/settings.php";
		require_once plugin_dir_path(__FILE__) . "functions.php";
		require_once plugin_dir_path(__FILE__) . "/inc/api.php";
		require_once plugin_dir_path(__FILE__) . "/inc/analytics.php";
		require_once plugin_dir_path(__FILE__) . "/inc/installation.php";

		add_action('plugins_loaded', [__CLASS__, 'true_load_plugin_textdomain']);

		add_action('init', [__CLASS__, 'init_plugin_logic_on_page']);

		/**
		 * Check if page have shortcode or not (for migration)
		 */
		add_filter('the_content', [__CLASS__, 'check_page_shortcode']);

		/**
		 * Creating shortcode
		 */
		add_shortcode('peerboard', [__CLASS__, 'shortcode']);

		/**
		 * Front scripts
		 */
		add_action('wp_enqueue_scripts', [__CLASS__, 'add_scripts']);

		/**
		 * Admin script
		 */
		add_action( 'admin_enqueue_scripts', [__CLASS__, 'load_admin_scripts']);

		/**
		 * Request function //TODO check this function do we need this
		 */
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
	 * check if page have shortcode if not add
	 *
	 * @return void
	 */
	public static function check_page_shortcode($content)
	{

		if (!is_page()) {
			return $content;
		}

		$post_id = intval(get_option('peerboard_post'));
		$current_page_id = get_the_ID();

		if ($post_id !== $current_page_id) {
			return $content;
		}

		if (!has_shortcode($content, 'peerboard')) {
			$content .= '[peerboard]';
			wp_update_post([
				'ID' => $post_id,
				'post_content' => $content
			]);
		}

		return $content;
	}

	/**
	 * Registering css and js
	 *
	 * @return void
	 */
	public static function add_scripts()
	{
		$assets = require PEERBOARD_PLUGIN_DIR_PATH . '/assets/frontend/frontend.asset.php';

		wp_register_style('peerboard_integration_styles', plugin_dir_url(__FILE__) . "/assets/frontend/main.css", array(), $assets['version']);
		wp_register_script('peerboard-integration', plugin_dir_url(__FILE__) . "/assets/frontend/frontend.js", array(), $assets['version']);
	}

	/**
	 * Register admin scripts
	 *
	 * @return void
	 */
	public static function load_admin_scripts(){
		$assets = require PEERBOARD_PLUGIN_DIR_PATH . '/assets/admin/admin.asset.php';

		wp_enqueue_style('peerboard_integration_styles', plugin_dir_url(__FILE__) . "/assets/admin/admin.css", array(), $assets['version']);
		wp_enqueue_script('peerboard-admin-js', plugin_dir_url(__FILE__) . "/assets/admin/admin.js", array(), $assets['version'],true);

	}

	/**
	 * Shortcode
	 *
	 * @return void
	 */
	public static function shortcode($atts)
	{
		global $peerboard_options;

		$post_id = intval(get_option('peerboard_post'));
		$current_page_id = get_the_ID();

		if ($post_id !== $current_page_id) {
			return;
		}

		/**
		 * Init styles and scripts
		 */
		wp_enqueue_style('peerboard_integration_styles');

		wp_enqueue_script('peerboard-integration');

		wp_localize_script('peerboard-integration', '_peerboardSettings', peerboard_get_script_settings($peerboard_options));

		ob_start();

		// include over required_once potentially fixes missing main header menu on the page
		include plugin_dir_path(__FILE__) . '/templates/front-template.php';

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
		delete_option('peerboard_post');
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
		if ($sync_enabled) {
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

	/**
	 * Feedback dialog on deactivation
	 */
	public static function add_deactivation_feedback_dialog_box()
	{
		$reasons = [
			[
				'id' => '1',
				'text' => __('I found a better plugin','peerboard'),
				'input_text' => __("What's the plugin's name?",'peerboard')
			],
			[
				'id' => '2',
				'text' => __("The plugin didn't work",'peerboard'),
			], 
			[
				'id' => '3',
				'text' => __("I don't like to share my information with you",'peerboard'),
			], 
			[
				'id' => '4',
				'text' => __("It's a temporary deactivation. I'm just debugging an issue.",'peerboard'),
			],
			[
				'id' => '5',
				'text' => __("Other",'peerboard'),
				'input_text' => __("Kindly tell us the reason so we can improve.",'peerboard')
			], 
		];

		ob_start();
		
		require PEERBOARD_PLUGIN_DIR_PATH.'/templates/admin/feedback-form.php';
		echo  ob_get_clean();
	}
}

PeerBoard::init();
