<?php

namespace PEBO;



// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Class to sync and work with users
 */
class UserSync
{

    public static $peerboard_options;

    public static function init()
    {

        /**
         * Create user on PeerBoard on user registration on WordPress
         */
        add_action('user_register', [__CLASS__, 'sync_user_if_enabled']);

        add_action('rest_api_init', [__CLASS__, 'custom_api_end_points']);

        // Update user on user update 
        add_action('profile_update', [__CLASS__, 'on_user_profile_update'], 10, 3);

        // on user deletion block user in peerboard
        add_action('delete_user', [__CLASS__, 'block_user_in_peerboard']);
    }

    /**
     * Register new endpoints
     *
     * @return void
     */
    public static function custom_api_end_points()
    {
        // Sync users
        register_rest_route('peerboard/v1', '/members/sync', array(
            'methods' => 'POST',
            'callback' => [__CLASS__, 'manually_sync_users'],
            'permission_callback' => function () {
                return current_user_can('edit_others_pages');
            }
        ));
    }

    /**
     * Create user on PeerBoard on user registration on WordPress
     */
    public static function sync_user_if_enabled($user_id)
    {
        $peerboard_options = get_option('peerboard_options');

        $user_sync_enabled = empty($peerboard_options['peerboard_users_sync_enabled']) ? false : true;

        if (!$user_sync_enabled) {
            return;
        }

        $user = get_userdata($user_id);

        $user_data = self::prepare_user_data($user);

        $user_data = apply_filters('peerboard_before_user_creation', $user_data);

        $user = self::peerboard_create_user($peerboard_options['auth_token'], $user_data);

        if (!$user['success']) {
            return;
        }

        do_action('peerboard_after_user_successfully_created', $user_id);

        $count = intval(get_option('peerboard_users_count'));

        update_option('peerboard_users_count', $count + 1);
    }

    /**
     * Import all users manually from settings page
     */
    public static function manually_sync_users($request)
    {
        $peerboard_options = get_option('peerboard_options');
        $wp_users_count = count_users();
        $users_count = $wp_users_count['total_users'];

        // get how much pages we have by 1000 users
        $pages_count = ceil($users_count / 1000);

        for ($i = 1; $i <= $pages_count; $i++) {

            // get user by 1000 because there is some problems can be with peerboard api
            $users = get_users(['number' => 1000, 'paged' => $i, 'fields' => 'all']);

            $prepared_users_data = [];

            foreach ($users as $user) {

                $user_data = self::prepare_user_data($user);

                $prepared_users_data[] = $user_data;
            }

            $response = self::peerboard_sync_users($peerboard_options['auth_token'], $prepared_users_data);

            if (!$response['success']) {
                wp_send_json_error(sprintf('something goes wrong, please retry later: already imported:%s users', ($i - 1) * 1000));
            }
        }

        wp_send_json_success($response);
    }

    /**
     * Prepare user data array for api request 
     *
     * @param object $user
     * @return array
     */
    public static function prepare_user_data($user)
    {
        $peerboard_options = get_option('peerboard_options');

        if (is_object($user)) {
            $user_data = [
                'external_id' => strval($user->ID),
                'email' =>  $user->user_email,
                'bio' => urlencode($user->description),
                'avatar_url' => get_avatar_url($user->user_email),
                'name' => $user->display_name,
                'last_name' => ''
            ];

            if (empty($peerboard_options['expose_user_data']) ? false : true) {
                $user_data['last_name'] = $user->last_name;
            }
        }

        if (is_array($user)) {
            $user_data = [
                'external_id' => strval($user['ID']),
                'email' =>  $user['user_email'],
                'bio' => urlencode($user['description']),
                'avatar_url' => get_avatar_url($user['user_email']),
                'name' => $user['display_name'],
                'last_name' => ''
            ];

            if (empty($peerboard_options['expose_user_data']) ? false : true) {
                $user_data['last_name'] = $user['last_name'];
            }
        }

        $activate_emails = empty($peerboard_options['peerboard_bulk_activate_email']) ? false : true;

        if (empty($activate_emails)) {
            $user_data['activate_email'] = false;
        }

        return apply_filters('peerboard_prepare_user_data_before_sync', $user_data);
    }

    /**
     * Create user
     *
     * @param [type] $token
     * @param [type] $user
     * @return void
     */
    public static function peerboard_create_user($token, array $user)
    {

        $response = API::peerboard_api_call_with_success_check('members', $token, $user, 'POST');

        return $response;
    }

    /**
     * User sync function
     *
     * @param [type] $token
     * @param array $users
     * @return void
     */
    public static function peerboard_sync_users($token, $users)
    {
        $response = API::peerboard_api_call_with_success_check('members/batch', $token, $users, 'POST');

        return $response;
    }

    /**
     * On user profile update sync user
     *
     * @return void
     */
    public static function on_user_profile_update($user_id, $old_user_data, $new_user_data = [])
    {
        $peerboard_options = get_option('peerboard_options');
        $user_sync_enabled = empty($peerboard_options['peerboard_users_sync_enabled']) ? false : true;

        if (!$user_sync_enabled) {
            return;
        }

        $peerboard_options = get_option('peerboard_options');

        $token = $peerboard_options['auth_token'];
        $user_data = self::prepare_user_data($new_user_data);

        if (empty($user_data)) {
            return;
        }

        $user_email = $user_data['email'];
        $email_before_change = $old_user_data->user_email;

        if ($user_email !== $email_before_change) {
            $user_email = $email_before_change;
        }

        $api_call = API::peerboard_api_call_with_success_check(sprintf('members/%s?key=email', urlencode($user_email)), $token, $user_data, 'POST', '', ['report_error' => false]);

        $req_body = json_decode(wp_remote_retrieve_body($api_call['request']), true);
        $response = $api_call['request']['response'];
        $message = $response['message'];

        /**
         * If user is not found
         */
        if ($response['code'] === 404) {

            $create_account = self::peerboard_create_user($token, $user_data);
            $req_body = json_decode(wp_remote_retrieve_body($create_account['request']), true);
            $message = $req_body['message'];

            if (!$create_account['success'] && $message === 'user with such external_id or email already exists') {
                $api_call = API::peerboard_api_call_with_success_check(sprintf('members/%s', $user_data['external_id']), $token, $user_data, 'POST', '');
            }
        } elseif ($response['code'] >= 400) {
            peerboard_add_notice($message, __FUNCTION__, 'error', func_get_args());
        }
    }


    /**
     * Block user when user deleted from WP
     *
     * @param [type] $user_id
     * @return void
     */
    public static function block_user_in_peerboard($user_id)
    {
        $peerboard_options = get_option('peerboard_options');

        $token = $peerboard_options['auth_token'];

        $user_data = self::prepare_user_data(get_user_by('ID', $user_id));

        $user_data['role'] = 'BLOCKED';

        $request = API::peerboard_api_call_with_success_check(sprintf('members/%s?key=email', urlencode($user_data['email'])), $token, $user_data, 'POST');
    }
}

UserSync::init();
