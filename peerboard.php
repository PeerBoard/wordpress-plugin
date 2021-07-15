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

		require_once plugin_dir_path(__FILE__) . "/inc/Settings.php";
		require_once plugin_dir_path(__FILE__) . "functions.php";
		require_once plugin_dir_path(__FILE__) . "/inc/API.php";
		require_once plugin_dir_path(__FILE__) . "/inc/analytics.php";
		require_once plugin_dir_path(__FILE__) . "/inc/Installation.php";
		require_once plugin_dir_path(__FILE__) . "/inc/ForumPage.php";

		add_action('plugins_loaded', [__CLASS__, 'true_load_plugin_textdomain']);

		/**
		 * Admin script
		 */
		add_action( 'admin_enqueue_scripts', [__CLASS__, 'load_admin_scripts']);
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
