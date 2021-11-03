<?php

namespace PEBO;

// Exit if accessed directly
defined('ABSPATH') || exit;

use KubAT\PhpSimple\HtmlDomParser;

/**
 * Class to sync and work with users
 */
class SSR
{

    public static $peerboard_options;

    public static function init()
    {

        self::$peerboard_options = get_option('peerboard_options');

        add_action('wp_head', [__CLASS__, 'start_wp_head_buffer'], 0);

        add_action('wp_head', [__CLASS__, 'end_wp_head_buffer'], PHP_INT_MAX);
    }

    public static function start_wp_head_buffer()
    {
        if (!self::need_comm_header()) {
            return;
        }

        ob_start();
    }

    /**
     * Check if useragent exist if yes add right meta
     *
     * @return void
     */
    public static function end_wp_head_buffer()
    {
        if (!self::need_comm_header()) {
            return;
        }

        $wp_head = ob_get_clean();

        $useragents = require PEERBOARD_PLUGIN_DIR_PATH . '/templates/useragents.php';

        foreach ($useragents as $useragent) {
            $req_useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
            $useragent = strtolower($useragent);
            if (strlen(strstr($req_useragent, $useragent)) > 0) {

                $post_url = home_url() . $_SERVER['REQUEST_URI'];

                $wp_head = HtmlDomParser::str_get_html($wp_head);

                $wp_head->find('link[rel=canonical]', 0)->href = $post_url;

                self::get_post_data($post_url);

            }
        }

        echo $wp_head;
    }

    public static function get_post_data($post_url){
        // http://local.is/944256280/api/v2/forum/ssr?url=http://example.com/post/891565306
        $api_slug = self::$peerboard_options["community_id"].'/api/v2/forum/ssr?url='.$post_url;

        $post_meta = API::peerboard_api_call($api_slug, self::$peerboard_options['auth_token'], [], 'GET', PEERBOARD_URL);

        return $post_meta;
    }

    public static function need_comm_header()
    {
        if (!is_page()) {
            return false;
        }

        if (intval(get_option("peerboard_post")) !== get_the_ID()) return false;


        return true;
    }

}

SSR::init();
