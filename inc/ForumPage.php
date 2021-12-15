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

        add_action('init', [__CLASS__, 'init_plugin_logic_on_page']);

        add_filter('peerboard_check_comm_slug_before_req', [__CLASS__, 'fix_community_slug_before_req']);

        /**
         * Add our custom simple template
         */
        add_action('plugins_loaded', [__CLASS__, 'add_custom_templates']);

        /**
         * Checking url and showing needed page (legacy leave here to not break old users pages)
         */
        add_filter('request', [__CLASS__, 'implement_comm_page']);

        /**
         * Disable the default home rewrite to a static page
         */
        add_filter('redirect_canonical', [__CLASS__, 'disable_default_home_rewrite']);
    }


    /**
     * Registering css and js
     *
     * @return void
     */
    public static function add_scripts()
    {
        $assets = require PEERBOARD_PLUGIN_DIR_PATH . '/build/frontend.asset.php';

        wp_register_style('peerboard_integration_styles', PEERBOARD_PLUGIN_URL . "/build/front_style.css", array(), $assets['version']);
        wp_register_script('peerboard-integration', PEERBOARD_PLUGIN_URL . "/build/frontend.js", array(), $assets['version']);
    }

    /**
     * Checking url and showing needed page (legacy)
     */
    public static function implement_comm_page(array $query_vars)
    {
        if (self::is_admin_request()) {
            return $query_vars;
        }

        if (isset($query_vars['rest_route'])) {
            return $query_vars;
        }

        $peerboard_options = get_option('peerboard_options');
        $peerboard_options['prefix'] = peerboard_get_comm_full_slug();

        // if the comm is not static page
        if (peerboard_is_embed_page($peerboard_options['prefix']) && !peerboard_is_comm_set_static_home_page()) {
            $query_vars = array("page_id" => get_option("peerboard_post"));
            unset($query_vars['pagename']);
        }

        // if the user set the community page as static home page
        if (peerboard_is_comm_set_static_home_page()) {

            // if we are on category or page slug
            if (isset($query_vars['category_name'])) {

                if (is_numeric($query_vars['category_name'])) {
                    $query_vars = array("page_id" => get_option("peerboard_post"));
                    unset($query_vars['pagename']);
                }
            }

            // if we are on post slug
            if (isset($query_vars['page']) && isset($query_vars['name'])) {
                if (is_numeric($query_vars['page']) && $query_vars['name'] === 'post') {
                    $query_vars = array("page_id" => get_option("peerboard_post"));
                    unset($query_vars['pagename']);
                }
            }
        }

        return $query_vars;
    }

    /**
     * Disable the default home rewrite to a static page
     */
    public static function disable_default_home_rewrite($redirect)
    {
        if (!peerboard_is_comm_set_static_home_page()) {
            return $redirect;
        }

        if (is_page() && $front_page = get_option('page_on_front')) {
            if (is_page($front_page))
                $redirect = false;
        }

        return $redirect;
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
     * Add custom templates
     *
     * @return void
     */
    public static function add_custom_templates()
    {
        $templates = [
            PEERBOARD_PLUGIN_MAIN_TEMPLATE_NAME => __('PeerBoard Full Width', 'peerboard')
        ];

        // Here advanced users can add their templates outside of the plugin
        $templates = apply_filters('peerboard_custom_templates', $templates);

        // Here advanced users can add their plugin path or theme path /templates will be added in class
        $plugin_path = apply_filters('peerboard_custom_templates_plugin_path', PEERBOARD_PLUGIN_DIR_PATH);

        new PageTemplate($templates, $plugin_path);
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

    /**
     * Check if this is a request at the backend.
     *
     * @return bool true if is admin request, otherwise false.
     */
    public static function is_admin_request()
    {
        /**
         * Get current URL.
         *
         * @link https://wordpress.stackexchange.com/a/126534
         */
        $current_url = home_url(add_query_arg(null, null));

        /**
         * Get admin URL and referrer.
         *
         * @link https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/pluggable.php#L1076
         */
        $admin_url = strtolower(admin_url());
        $referrer  = strtolower(wp_get_referer());
        /**
         * Check if this is a admin request. If true, it
         */
        if (0 === strpos($current_url, $admin_url)) {
            return true;
        } else {
            return false;
        }
    }
}

ForumPage::init();
