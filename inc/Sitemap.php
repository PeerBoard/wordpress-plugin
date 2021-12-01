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

    public static $sitemap_path;

    public static function init()
    {

        self::$peerboard_options = get_option('peerboard_options');

        self::$sitemap_path = 'sitemap_peerboard.xml';

        /**
         * Checking url and showing our sitemap
         */
        add_filter('request', [__CLASS__, 'implement_external_sitemap']);

        /**
         * Add xml link to robots
         */
        add_filter('robots_txt', [__CLASS__, 'add_xml_link_to_robots'], 10, 2);
    }

    /**
     * Add xml link to robots
     */
    public static function add_xml_link_to_robots($output, $public)
    {
        if (!$public) {
            return $output;
        }

        $output .= PHP_EOL . 'Sitemap: ' . home_url('/') . self::$sitemap_path;

        return $output;
    }

    /**
     * Implement external sitemap
     *
     * @param [type] $query_vars
     * @return void
     */
    public static function implement_external_sitemap($query_vars)
    {

        if (self::is_sitemap_page(self::$sitemap_path)) {
            $comm_id = self::$peerboard_options["community_id"];

            $request = wp_remote_get(sprintf('https://peerboard.com/sitemap-%s.xml', $comm_id));

            $success = API::check_request_success_and_report_error($request, func_get_args());

            if (!$success) {
                return $query_vars;
            }

            header('Content-Type: ' . wp_remote_retrieve_header($request, 'content-type'));

            echo wp_remote_retrieve_body($request);

            exit();
        }

        return $query_vars;
    }

    /**
     * Check if this is sitemap page
     *
     * @param [type] $sitemap_link
     * @return boolean
     */
    public static function is_sitemap_page(string $sitemap_path)
    {
        $parse_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $slug = basename($parse_url);
        $sitemap_path = untrailingslashit($sitemap_path);

        return $slug === $sitemap_path;
    }
}

Sitemap::init();
