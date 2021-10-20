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

        self::$peerboard_options = get_option('peerboard_options');

        /**
         * Create user on PeerBoard on user registration on WordPress
         */
        add_action('user_register', [__CLASS__, 'sync_user_if_enabled']);

        // Bulk add users
        add_filter('pre_update_option_peerboard_users_count', [__CLASS__, 'handle_users_sync_flag_changed'], 10, 3);

        // Update user on user update 
        add_action('profile_update', [__CLASS__, 'on_user_profile_update'], 10, 3);

        // on user deletion block user in peerboard
        add_action('delete_user', [__CLASS__, 'block_user_in_peerboard']);
    }
    /**
     * Create user on PeerBoard on user registration on WordPress
     */
    public static function sync_user_if_enabled($user_id)
    {
        $peerboard_options = self::$peerboard_options;

        $sync_enabled = get_option('peerboard_users_sync_enabled');
        if ($sync_enabled) {
            $user = get_userdata($user_id);

            $user_data = self::prepare_user_data($user);

            $user = self::peerboard_create_user($peerboard_options['auth_token'], $user_data);

            if (!$user) {
                return;
            }

            $count = intval(get_option('peerboard_users_count'));
            update_option('peerboard_users_count', $count + 1);
        }
    }

    /**
     * Import all users
     *
     * @param [type] $value
     * @param [type] $old_value
     * @param [type] $option
     * @return void
     */
    public static function handle_users_sync_flag_changed($value, $old_value, $option)
    {
        $peerboard_options = self::$peerboard_options;
        $wp_users_count = count_users();
        $users_count = $wp_users_count['total_users'];

        if ($users_count >= 100000) {
            return $old_value;
        }

        $users = get_users();

        $sync_enabled = get_option('peerboard_users_sync_enabled');
        if ($sync_enabled === '1') {
            if ($value === 0) {
                update_option('peerboard_users_sync_enabled', '0');
                return $old_value;
            }
            return $value;
        }

        $result = [];
        foreach ($users as $user) {

            $user_data = self::prepare_user_data($user);

            $result[] = $user_data;
        }

        $response = self::peerboard_sync_users($peerboard_options['auth_token'], $result);

        if (!$response) {
            return $value;
        }

        update_option('peerboard_users_sync_enabled', '1');
        if ($value === 0) {
            $value = $old_value;
        }

        return $response['result'] + intval($value);
    }

    /**
     * Prepare user data array for api request 
     *
     * @param object $user
     * @return array
     */
    public static function prepare_user_data($user)
    {

        $user_data = [
            'external_id' => strval($user->ID),
            'email' =>  $user->user_email,
            'bio' => urlencode($user->description),
            'profile_url' => get_avatar_url($user->user_email),
            'name' => $user->display_name,
            'last_name' => ''
        ];

        if (self::$peerboard_options['expose_user_data'] == '1') {
            $user_data['name'] = $user->first_name;
            $user_data['last_name'] = $user->last_name;
        }

        return $user_data;
    }

    /**
     * Create user
     *
     * @param [type] $token
     * @param [type] $user
     * @return void
     */
    public static function peerboard_create_user($token, $user)
    {
 
        $request = API::peerboard_api_call('users', $token, $user, 'POST');

        if (!$request) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($request), true);
    }

    /**
     * User sync function
     *
     * @param [type] $token
     * @param [type] $users
     * @return void
     */
    public static function peerboard_sync_users($token, $users)
    {
        $request = API::peerboard_api_call('users/batch', $token, $users, 'POST');

        if (!$request) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($request), true);
    }

    /**
     * On user profile update sync user
     *
     * @return void
     */
    public static function on_user_profile_update($user_id, $old_user_data, $new_user_data)
    {
        $peerboard_options = self::$peerboard_options;

        $token = $peerboard_options['auth_token'];

        $user_data = self::prepare_user_data(get_user_by('ID', $new_user_data['ID']));

        $request = API::peerboard_api_call(sprintf('members/%s?key=email', urlencode($user_data['email'])), $token, $user_data, 'POST', '', false);

        /**
         * If user is not found
         */
        if ($request['response']['code'] === 404) {
            $request = self::peerboard_create_user($token, $user_data);
        } elseif($request['response']['code'] >= 400){
            $message = json_decode(wp_remote_retrieve_body($request), true);
            $message = $message['message'];
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
        $peerboard_options = self::$peerboard_options;

        $token = $peerboard_options['auth_token'];

        $user_data = self::prepare_user_data(get_user_by('ID', $user_id));

        $user_data['role'] = 'BLOCKED';

        $request = API::peerboard_api_call(sprintf('members/%s?key=email', urlencode($user_data['email'])), $token, $user_data, 'POST');
    }
}

UserSync::init();
