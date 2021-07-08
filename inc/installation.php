<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * On plugin installation
 *
 * @return void
 */
function peerboard_install()
{
  global $peerboard_options;
  if (!current_user_can('activate_plugins'))
    return;
  
  $forum_page_exist = get_option("peerboard_post");

  if($forum_page_exist){
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
}

add_action('peerboard_activate', 'peerboard_install', 10);

/**
 * On plugin activation
 *
 * @param [type] $plugin
 * @return void
 */
function peerboard_activate($plugin)
{
  global $peerboard_options;
  $peerboard_options = get_option('peerboard_options', array());
  if (count($peerboard_options) === 0) {
    $peerboard_options = array();

    $recovery = get_option('peerboard_recovery_token');
    if ($recovery !== false && $recovery !== NULL && $recovery !== '') {
      $peerboard_options = peerboard_get_options(peerboard_get_community($recovery));
      $peerboard_options['prefix'] = 'community';
      peerboard_post_integration($peerboard_options['auth_token'], $peerboard_options['prefix'], peerboard_get_domain());
      delete_option('peerboard_recovery_token');
    } else {
      $peerboard_options = peerboard_get_options(peerboard_create_community());
    }
    peerboard_send_analytics('activate_plugin', $peerboard_options["community_id"]);

    $peerboard_options['expose_user_data'] = '1';
    update_option('peerboard_options', $peerboard_options);
  }

  if (isset($peerboard_options['redirect'])) {
      add_option('peerboard_plugin_do_activation_redirect', true);
  }
}

add_action('peerboard_activate', 'peerboard_activate', 11);

/**
 * peerboard redirect to forum page
 */
function peerboard_plugin_redirect()
{
  if (get_option('peerboard_plugin_do_activation_redirect', false)) {
    delete_option('peerboard_plugin_do_activation_redirect');
    $page_id = get_option('peerboard_post');
    $page_link = get_page_link($page_id);
    wp_redirect($page_link);
  }
}

add_action('admin_init', 'peerboard_plugin_redirect');

/**
 * On plugin deactivation
 *
 * @return void
 */
function peerboard_deactivation()
{
  global $peerboard_options;
  if (!current_user_can('activate_plugins')) return;
  $post_id = get_option('peerboard_post');
  wp_delete_post($post_id, true);
  $board_id = $peerboard_options['community_id'];
  peerboard_send_analytics('deactivate_plugin', $board_id);
  peerboard_drop_integration($peerboard_options['auth_token']);
  echo "<script>alert(`Note, that your board is still available at peerboard.com/$board_id`)</script>";

  update_option("peerboard_recovery_token", $peerboard_options['auth_token']);
  delete_option('peerboard_post');
  delete_option('peerboard_options');
}

add_action('peerboard_deactivate', 'peerboard_deactivation');
