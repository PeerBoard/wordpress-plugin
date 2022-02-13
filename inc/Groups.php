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
        // the api is not working properly on user creation group are not adding
        add_filter('peerboard_prepare_user_data_before_sync', [__CLASS__, 'add_groups_to_user_data'], 10, 2);
        // after user created we adding user to role/group on peerboard
        //add_action('peerboard_after_user_successfully_created', [__CLASS__, 'add_member_to_group_after_user_creation']);

        // Update group on user update 
        add_action('set_user_role', [__CLASS__, 'on_user_profile_update_update_group'], 10, 3);
    }

    /**
     * Before user is created add groups
     *
     * @param [type] $user_id
     * @param [type] $user_data
     * @return void
     */
    public static function add_groups_to_user_data($user_data)
    {

        $user_id = $user_data['external_id'];

        $user_groups = self::get_user_groups($user_id);

        $user_data['groups'] = [];

        foreach ($user_groups as $group_external_id => $group_name) {
            $is_group_exist = self::is_group_exist($group_external_id);

            if (!$is_group_exist) {
                $group_created = self::create_new_group($group_name, $group_external_id);
            }

            $user_data['groups'][] = ['external_id' => $group_external_id];
        }

        return $user_data;
    }

    /**
     * Add member to group after user creation
     *
     * @param string,int $user_id
     * @return void
     */
    public static function add_member_to_group_after_user_creation($user_id)
    {

        $user_groups = self::get_user_groups($user_id);

        // check if groups exist if not create
        $check_create_groups = self::check_groups_create($user_groups);

        foreach ($user_groups as $group_external_id => $group_name) {
            $add_member_to_groups = self::add_members_to_group($group_external_id, [$user_id]);
        }
    }

    /**
     * Update group on user update 
     *
     * @return void
     */
    public static function on_user_profile_update_update_group($user_id, $role, $old_roles = [])
    {

        $user_groups = self::get_user_groups($user_id);

        // check if groups exist if not create
        $check_create_groups = self::check_groups_create($user_groups);

        // remove from old groups
        foreach ($old_roles as $group_id) {
            $remove_members = self::remove_members_from_group($group_id, [$user_id]);
        }

        // add to new group
        foreach ($user_groups as $external_id => $group) {
            $add_members = self::add_members_to_group($external_id, [$user_id]);
        }
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
    public static function create_new_group(string $name, string $external_id, string $visibility = 'all', string $color = '')
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
     * Remove members from group
     *
     * @param string $group_external_id
     * @param array $users_id_array
     * @return array
     */
    public static function remove_members_from_group(string $group_external_id, array $users_id_array = [])
    {
        $peerboard_options = get_option('peerboard_options');

        $token = $peerboard_options['auth_token'];

        $args = ["members" => []];

        foreach ($users_id_array as $user_id) {
            $args['members'][] = ["external_id" => strval($user_id)];
        }

        $api_call = API::peerboard_api_call_with_success_check(sprintf('groups/%s/remove-members?key=external_id', $group_external_id), $token, $args, 'POST');

        return $api_call;
    }

    /**
     * Get existing group
     *
     * @param string $group_external_id
     * @return array
     */
    public static function get_group(string $group_external_id)
    {

        $peerboard_options = get_option('peerboard_options');

        $token = $peerboard_options['auth_token'];

        $api_call = API::peerboard_api_call_with_success_check(sprintf('groups/%s?key=external_id', $group_external_id), $token, [], 'GET', '', ['report_error' => false]);

        return $api_call;
    }

    /**
     * Is group exist in peerboard
     *
     * @param [type] $group_external_id
     * @return boolean
     */
    public static function is_group_exist($group_external_id)
    {

        // peerboard already have admin role so 
        if ('administrator' === $group_external_id) {
            return true;
        }

        $is_group_exist = self::get_group($group_external_id);

        return $is_group_exist['success'];
    }

    /**
     * Check groups if not exist create
     *
     * @param array $groups
     * @return array
     */
    public static function check_groups_create(array $groups)
    {

        foreach ($groups as $group_external_id => $group_name) {
            $is_group_exist = self::is_group_exist($group_external_id);

            if (!$is_group_exist) {
                $group_created = self::create_new_group($group_name, $group_external_id);
            }
        }

        return $groups;
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
