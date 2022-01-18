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


                $page_info = self::get_post_data($post_url);

                if(empty($page_info) || !$page_info['success']){
                    echo $wp_head;

                    return;
                }

                $page_info = json_decode($page_info['request']['body']);

                $wp_head->find('title', 0)->innertext = $page_info->title;

                $wp_head = self::add_or_update_header_meta_tags($wp_head, $page_info->metaTags);
            }
        }

        echo $wp_head;
    }

    /**
     * Add or update header meta tags
     */
    public static function add_or_update_header_meta_tags($wp_head, $meta_tags)
    {
        $meta_html = '';

        foreach ($meta_tags as $meta) {
            $meta_string = sprintf('meta[property=%s]', $meta->attrValue);
            $cur_meta = $wp_head->find($meta_string, 0);

            if (!empty($cur_meta)) {
                $wp_head->find(sprintf('meta[property=%s]', $meta->attrValue), 0)->content = $meta->content;
            }

            if (empty($cur_meta)) {
                $meta_html .= sprintf('<meta property="%s" content="%s" />', $meta->attrValue, $meta->content);
            }
        }

        // add new meta fields after title
        $wp_head->find('title', 0)->outertext = $wp_head->find('title', 0)->outertext . $meta_html;

        return $wp_head;
    }

    public static function get_post_data($post_url)
    {
        $post_meta = API::peerboard_api_call_with_success_check(untrailingslashit('ssr?url='.$post_url), self::$peerboard_options['auth_token'], [], 'GET');

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
