<?php

/**
 * Plugin Name: WordPress Forum Plugin – PeerBoard
 * Plugin URI: https://peerboard.com/integrations/wordpress-forum-plugin
 * Description: Forum, Community & User Profile Plugin
 * Version: 1.2.2
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
		DEFINE('PEERBOARD_PLUGIN_VERSION', '1.2.2');
		DEFINE('PEERBOARD_PLUGIN_URL', plugins_url('', __FILE__));
		DEFINE('PEERBOARD_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));
		DEFINE('PEERBOARD_PLUGIN_MAIN_TEMPLATE_NAME', 'page-full-width-template.php');

		// PSR4 composer autoload
		require_once plugin_dir_path(__FILE__) . '/vendor/autoload.php';
		require_once plugin_dir_path(__FILE__) . "/inc/Settings.php";
		require_once plugin_dir_path(__FILE__) . "functions.php";
		require_once plugin_dir_path(__FILE__) . "/inc/API.php";
		require_once plugin_dir_path(__FILE__) . "/inc/analytics.php";
		require_once plugin_dir_path(__FILE__) . "/inc/Installation.php";
		require_once plugin_dir_path(__FILE__) . "/inc/ForumPage.php";
		require_once plugin_dir_path(__FILE__) . "/inc/PageTemplate.php";
		require_once plugin_dir_path(__FILE__) . "/inc/UserSync.php";
		require_once plugin_dir_path(__FILE__) . "/inc/Sitemap.php";
		require_once plugin_dir_path(__FILE__) . "/inc/SSR.php";
		require_once plugin_dir_path(__FILE__) . "/inc/Groups.php";

		add_action('plugins_loaded', [__CLASS__, 'true_load_plugin_textdomain']);

		// Admin scripts
		add_action('admin_enqueue_scripts', [__CLASS__, 'load_admin_scripts']);

		// Get feedback dialog box by ajax
		add_action('wp_ajax_peerboard_add_deactivation_feedback_dialog_box', [__CLASS__, 'add_deactivation_feedback_dialog_box']);

		// Chow warning or error issues
		add_action('init', [__CLASS__, 'peerboard_add_notice_action']);
	}

	/**
	 * Register admin scripts
	 *
	 * @return void
	 */
	public static function load_admin_scripts()
	{
		$assets = require PEERBOARD_PLUGIN_DIR_PATH . '/build/admin.asset.php';

		wp_enqueue_style('peerboard_integration_styles', plugin_dir_url(__FILE__) . "/build/admin_style.css", array(), $assets['version']);
		wp_enqueue_script('peerboard-admin-js', plugin_dir_url(__FILE__) . "/build/admin.js", array(), $assets['version'], true);

		wp_localize_script('peerboard-admin-js', 'peerboard_admin', apply_filters('peerboard_admin_script_localize', ['ajax_url' => admin_url('admin-ajax.php')]));
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
	 * Check where user are to show notice
	 *
	 * @return void
	 */
	public static function peerboard_add_notice_action()
	{
		$user = wp_get_current_user();
		$allowed_roles = array('editor', 'administrator', 'author');
		// Show notice only for some specific user roles
		if (array_intersect($allowed_roles, $user->roles)) {
			if (is_admin()) {
				add_action('admin_notices', [__CLASS__, 'peerboard_notice']);
			} else {
				add_action('peerboard_before_forum',  [__CLASS__, 'peerboard_notice']);
			}
		}
	}

	/**
	 * Show notice on admin page
	 *
	 * @return void
	 */
	public static function peerboard_notice()
	{
		// Show notice after update in admin
		$saved_notices = get_transient('peerboard_notices');
		if ($saved_notices && is_array($saved_notices)) {
			foreach ($saved_notices as $notice) {
				printf('<div class="peerboard-notice notice notice-%s is-dismissible"><p>%s</p></div>', $notice['type'], $notice['notice']);
			}
			delete_transient('peerboard_notices');
		};
	}



	/**
	 * Feedback dialog on deactivation
	 */
	public static function add_deactivation_feedback_dialog_box()
	{
		$peerboard_options = get_option('peerboard_options');

		$board_id = $peerboard_options['community_id'];

		$reasons = [
			[
				'id' => '1',
				'text' => __('I found a better plugin', 'peerboard'),
				'input_text' => __("What's the plugin's name?", 'peerboard')
			],
			[
				'id' => '2',
				'text' => __("The plugin didn't work", 'peerboard'),
			],
			[
				'id' => '3',
				'text' => __("I don't like to share my information with you", 'peerboard'),
			],
			[
				'id' => '4',
				'text' => __("It's a temporary deactivation. I'm just debugging an issue.", 'peerboard'),
			],
			[
				'id' => '5',
				'text' => __("Other", 'peerboard'),
				'input_text' => __("Help us improve!", 'peerboard')
			],
		];

		ob_start();
		require PEERBOARD_PLUGIN_DIR_PATH . '/templates/admin/feedback-form.php';
		wp_send_json_success(ob_get_clean());
	}
}

PeerBoard::init();
