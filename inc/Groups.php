<?php

namespace PEBO;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * In wp roles is the same as groups in Peerboard 
 */
class Groups
{

    public static function init()
    {
        add_filter('peerboard_before_user_creation', [__CLASS__, 'add_groups_to_user_data'], 10, 2);
    }

    public static function add_groups_to_user_data($user_id, $user_data){

        $user_groups = self::get_user_groups($user_id);

        foreach($user_groups as $group_external_id => $group_name){

            $is_group_exist = self::get_group($group_external_id);

            var_dump($is_group_exist);
        }

        return $user_data;
    }
    
    /**
     * Create new group
     *
     * @param string $name
     * @param string $external_id
     * @param string $visibility
     * @param string $color
     * @return array
     */
    public static function create_new_group(string $name, string $external_id, string $visibility = 'all', string $color)
    {
        $peerboard_options = get_option('peerboard_options');

        $token = $peerboard_options['auth_token'];

        $args = [
            'name' => $name,
            'external_id' => $external_id,
            'visibility' => $visibility
        ];

        if (!empty($color)) {
            $args['color'] = $color;
        }

        $api_call = API::peerboard_api_call_with_success_check('groups', $token, $args, 'POST');

        return $api_call;
    }

    /**
     * Add members to specific group
     *
     * @param string $group_external_id
     * @param array $users_id_array
     * @return array
     */
    public static function add_members_to_group(string $group_external_id, array $users_id_array = [])
    {
        $peerboard_options = get_option('peerboard_options');

        $token = $peerboard_options['auth_token'];

        $args = ["members" => []];

        foreach ($users_id_array as $user_id) {
            $args['members'][] = ["external_id" => strval($user_id)];
        }

        $api_call = API::peerboard_api_call_with_success_check(sprintf('groups/%s/add-members?key=external_id', $group_external_id), $token, $args, 'POST');

        return $api_call;
    }

    /**
     * Get existing group
     *
     * @param string $group_external_id
     * @return array
     */
    public static function get_group(string $group_external_id){

        $peerboard_options = get_option('peerboard_options');

        $token = $peerboard_options['auth_token'];

        $api_call = API::peerboard_api_call(sprintf('groups/%s?key=external_id', $group_external_id), $token, [], 'GET');

        return $api_call;
    }

    /**
     * Get clean user groups array
     *
     * @param int,string $user_id
     * @return array
     */
    public static function get_user_groups($user_id)
    {
        global $wp_roles;

        $all_roles = $wp_roles->roles;
        $editable_roles = apply_filters('editable_roles', $all_roles);

        $user_meta = get_userdata($user_id);

        if (!$user_meta) {
            return $user_meta;
        }

        $user_roles_array = [];

        $user_roles = $user_meta->roles;

        foreach ($user_roles as $user_role) {
            $user_roles_array[$user_role] = $editable_roles[$user_role]['name'];
        }

        return $user_roles_array;
    }
}

Groups::init();
