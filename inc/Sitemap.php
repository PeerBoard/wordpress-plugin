<?php

namespace PEBO;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Class to sync and work with users
 */
class Sitemap
{

    public static $peerboard_options;

    public static function init()
    {

        self::$peerboard_options = get_option('peerboard_options');

        /**
         * Add link to sitemap home page
         */
        add_filter('init', [__CLASS__, 'register_sitemap_provider']);

        /**
         * Checking url and showing our sitemap
         */
        add_filter('request', [__CLASS__, 'implement_external_sitemap']);
    }

    /**
     * Register sitemap provider
     *
     * @return void
     */
    public static function register_sitemap_provider()
    {

        $provider = new peerboard_sitemap_provider();

        wp_register_sitemap_provider($provider->name, $provider);
    }

    /**
     * Implement external sitemap
     *
     * @param [type] $query_vars
     * @return void
     */
    public static function implement_external_sitemap($query_vars)
    {
        $is_sitemaps_enabled = wp_sitemaps_get_server()->sitemaps_enabled();

        if (!$is_sitemaps_enabled) {
            return $query_vars;
        }

        if (!is_array($query_vars)) {
            return $query_vars;
        }

        if (!isset($query_vars['sitemap'])) {
            return $query_vars;
        }

        if ($query_vars['sitemap'] === 'peerboard') {
            $comm_id = self::$peerboard_options["community_id"];

            $request = wp_remote_get(sprintf('https://peerboard.com/sitemap-%s.xml', $comm_id));

            $success = API::check_request_success($request, func_get_args());

            if (!$success) {
                return $query_vars;
            }

            header('Content-Type: ' . wp_remote_retrieve_header($request, 'content-type'));

            echo wp_remote_retrieve_body($request);

            exit();
        }

        return $query_vars;
    }
}

Sitemap::init();

/**
 * Adding link in wp sitemap home page
 */
class peerboard_sitemap_provider extends \WP_Sitemaps_Provider
{

    // make visibility not protected
    public $name;

    public function __construct()
    {

        $this->name        = 'peerboard';
        $this->object_type = 'peerboard';
    }

    public function get_url_list($page_num, $subtype = '')
    {

        $url_list = [];

        return $url_list;
    }

    public function get_max_num_pages($subtype = '')
    {
        return 1;
    }
}
