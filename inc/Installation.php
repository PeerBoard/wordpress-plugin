<?php

namespace PEBO;

// Exit if accessed directly
defined('ABSPATH') || exit;

class Installation
{
  public static function init()
  {
    add_action('peerboard_activate', [__CLASS__, 'peerboard_install'], 10);

    add_action('peerboard_activate', [__CLASS__, 'peerboard_activate'], 11);

    add_action('admin_init', [__CLASS__, 'peerboard_plugin_redirect']);

    add_action('peerboard_deactivate', [__CLASS__, 'peerboard_deactivation']);
  }

  /**
   * On plugin installation
   *
   * @return void
   */
  public static function peerboard_install()
  {
    global $peerboard_options;
    if (!current_user_can('activate_plugins'))
      return;

    $forum_page_exist = get_option("peerboard_post");

    if ($forum_page_exist) {
      return;
    }

    $post_data = array(
      'post_title'    => 'Community',
      'post_name'    => 'community',
      'post_content'  => '[peerboard]',
      'post_status'   => 'publish',
      'post_type'     => 'page',
      'post_author'   => 1
    );

    $post_id = wp_insert_post($post_data);

    update_option("peerboard_post", $post_id);

    // Change default page template to our one
    update_post_meta($post_id, '_wp_page_template', PEERBOARD_PLUGIN_MAIN_TEMPLATE_NAME, true);
  }

  /**
   * On plugin activation
   *
   * @param [type] $plugin
   * @return void
   */
  public static function peerboard_activate($plugin)
  {
    global $peerboard_options;
    $peerboard_options = get_option('peerboard_options', array());
    if (count($peerboard_options) === 0) {
      $peerboard_options = array();

      $recovery = get_option('peerboard_recovery_token');
      if ($recovery !== false && $recovery !== NULL && $recovery !== '') {
        $peerboard_options = peerboard_get_options(API::peerboard_get_community($recovery));

        if (!$peerboard_options) {
          return false;
        }

        $peerboard_options['prefix'] = 'community';

        $integration = API::peerboard_post_integration($peerboard_options['auth_token'], $peerboard_options['prefix'], peerboard_get_domain());

        if (!$integration) {
          return false;
        }

        delete_option('peerboard_recovery_token');
      } else {

        $community = API::peerboard_create_community();

        if ($community) {
          $peerboard_options = peerboard_get_options($community);
        }
      }
      peerboard_send_analytics('activate_plugin', $peerboard_options["community_id"]);

      $peerboard_options['expose_user_data'] = '1';
      update_option('peerboard_options', $peerboard_options);
    }

    if (isset($peerboard_options['redirect'])) {
      add_option('peerboard_plugin_do_activation_redirect', true);
    }
  }

  /**
   * peerboard redirect to forum page
   */
  public static function peerboard_plugin_redirect()
  {
    if (get_option('peerboard_plugin_do_activation_redirect', false)) {
      delete_option('peerboard_plugin_do_activation_redirect');
      $page_id = get_option('peerboard_post');
      $page_link = get_page_link($page_id);
      wp_redirect($page_link);
    }
  }

  /**
   * On plugin deactivation
   *
   * @return void
   */
  public static function peerboard_deactivation()
  {
    global $peerboard_options;
    if (!current_user_can('activate_plugins')) return;
    $post_id = get_option('peerboard_post');
    wp_delete_post($post_id, true);
    $board_id = $peerboard_options['community_id'];
    peerboard_send_analytics('deactivate_plugin', $board_id);
    $success = API::peerboard_drop_integration($peerboard_options['auth_token']);

    if (!$success) {
      return;
    }

    update_option("peerboard_recovery_token", $peerboard_options['auth_token']);
    delete_option('peerboard_post');
    delete_option('peerboard_options');
  }
}

Installation::init();
