<?php

namespace PEBO;

// Exit if accessed directly
defined('ABSPATH') || exit;

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
        ob_start();
    }

    public static function end_wp_head_buffer()
    {
        $wp_head = ob_get_clean();

        $useragents = require PEERBOARD_PLUGIN_DIR_PATH . '/templates/useragents.php';

        foreach ($useragents as $useragent) {
            if (strlen(strstr($_SERVER['HTTP_USER_AGENT'], $useragent)) > 0) {

                //do something

            }
        }


        echo $wp_head;
    }
}

SSR::init();
