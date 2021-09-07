<?php

namespace PEBO;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Forum page logic with shortcode registration
 */
class ForumPage
{

    public static function init()
    {
        /**
         * Front scripts
         */
        add_action('wp_enqueue_scripts', [__CLASS__, 'add_scripts']);

        /**
         * Creating shortcode
         */
        add_shortcode('peerboard', [__CLASS__, 'shortcode']);

        /**
         * Check if page have shortcode or not (for migration)
         */
        add_filter('the_content', [__CLASS__, 'check_page_shortcode']);

        //add_action('init', [__CLASS__, 'init_plugin_logic_on_page']);

        add_filter('peerboard_check_comm_slug_before_req', [__CLASS__, 'fix_community_slug_before_req']);

        /**
         * Checking url and showing needed page (legacy)
         */
        add_filter('request', [__CLASS__, 'implement_comm_page']);
    }

    /**
     * Registering css and js
     *
     * @return void
     */
    public static function add_scripts()
    {
        $assets = require PEERBOARD_PLUGIN_DIR_PATH . '/assets/frontend/frontend.asset.php';

        wp_register_style('peerboard_integration_styles', PEERBOARD_PLUGIN_URL . "/assets/frontend/main.css", array(), $assets['version']);
        wp_register_script('peerboard-integration', PEERBOARD_PLUGIN_URL . "/assets/frontend/frontend.js", array(), $assets['version']);
    }

    /**
     * Checking url and showing needed page (legacy)
     */
    public static function implement_comm_page(array $query_vars)
    {
        $peerboard_options = get_option('peerboard_options');
        $peerboard_options['prefix'] = peerboard_get_comm_full_slug();
        if (peerboard_is_embed_page($peerboard_options['prefix'])) {
            $query_vars = array("page_id" => get_option("peerboard_post"));
            unset($query_vars['pagename']);
        }
        return $query_vars;
    }

    /**
     * Shortcode
     *
     * @return void
     */
    public static function shortcode($atts)
    {
        $peerboard_options = get_option('peerboard_options');
        $peerboard_options['prefix'] = peerboard_get_comm_full_slug();

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

        do_action('peerboard_before_forum');

        ob_start();

        // include over required_once potentially fixes missing main header menu on the page
        include PEERBOARD_PLUGIN_DIR_PATH . '/templates/front-template.php';

        do_action('peerboard_after_forum');

        return ob_get_clean();
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
     * init plugin logic
     *
     * @return void
     */
    public static function init_plugin_logic_on_page()
    {
        global $peerboard_options;
        $peerboard_options = get_option('peerboard_options', array());
        if (!array_key_exists('peerboard_version_synced', $peerboard_options)) {
            $success = API::peerboard_post_integration($peerboard_options['auth_token'], $peerboard_options['prefix'], peerboard_get_domain());

            if (!$success) {
                return false;
            }

            $peerboard_options['peerboard_version_synced'] = PEERBOARD_PLUGIN_VERSION;
            update_option('peerboard_options', $peerboard_options);
        } else if ($peerboard_options['peerboard_version_synced'] != PEERBOARD_PLUGIN_VERSION) {
            $success = API::peerboard_post_integration($peerboard_options['auth_token'], $peerboard_options['prefix'], peerboard_get_domain());

            if (!$success) {
                return false;
            }

            $peerboard_options['peerboard_version_synced'] = PEERBOARD_PLUGIN_VERSION;
            update_option('peerboard_options', $peerboard_options);
        }
    }

    /**
     * Check and fix comm url before req
     *
     * @return string
     */
    public static function fix_community_slug_before_req($prefix)
    {
        return peerboard_get_comm_full_slug();
    }
}

ForumPage::init();
