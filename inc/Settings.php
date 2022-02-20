<?php

namespace PEBO;

// Exit if accessed directly
defined('ABSPATH') || exit;

class Settings
{

    public static $peerboard_options;
    public static $external_comm_settings;

    public static function init()
    {
        if (defined('PEERBOARD_ENV')) {
            if (PEERBOARD_ENV === "local") {
                DEFINE('PEERBOARD_EMBED_URL', 'http://static.local.is/embed/embed.js');
                DEFINE('PEERBOARD_URL', 'http://local.is/');
                DEFINE('PEERBOARD_API_BASE', 'http://api.local.is/v1/');
                DEFINE('PEERBOARD_API_URL', 'http://api.local.is/');
            } else if (PEERBOARD_ENV === "dev") {
                DEFINE('PEERBOARD_EMBED_URL', 'https://static.peerboard.dev/embed/embed.js');
                DEFINE('PEERBOARD_URL', 'https://peerboard.dev/');
                DEFINE('PEERBOARD_API_BASE', 'https://api.peerboard.dev/v1/');
                DEFINE('PEERBOARD_API_URL', 'https://api.peerboard.dev/');
            }
        } else {
            DEFINE('PEERBOARD_EMBED_URL', 'https://static.peerboard.com/embed/embed.js');
            DEFINE('PEERBOARD_URL', 'https://peerboard.com/');
            DEFINE('PEERBOARD_API_BASE', 'https://api.peerboard.com/v1/');
            DEFINE('PEERBOARD_API_URL', 'https://api.peerboard.com/');
        }

        add_action('admin_init', [__CLASS__, 'peerboard_settings_init']);
        add_action('admin_menu', [__CLASS__, 'peerboard_options_page']);

        /**
         * After forum_page_template option updated
         */
        add_action('updated_option', [__CLASS__, 'forum_page_template_updated'], 10, 3);

        /**
         * If community set as static page update prefix on peerboard side
         */
        add_action('update_option_page_on_front', [__CLASS__, 'update_peerboard_prefix'], 10, 3);

        /**
         * After update_comm_parent_page option updated
         */
        add_action('updated_option', [__CLASS__, 'update_comm_parent_page'], 10, 3);

        add_filter('pre_update_option_peerboard_options', [__CLASS__, 'pre_update_option_peerboard_options'], 10, 3);

        /**
         * Add script and style to admin page
         */
        add_filter('peerboard_admin_script_localize', [__CLASS__, 'peerboard_admin_scripts_data']);

        /**
         * Will load on all pages to prevent issues
         */
        add_action('init', [__CLASS__, 'new_version_changes']);
    }

    /**
     * Add settings page to menu
     *
     * @return void
     */
    public static function peerboard_options_page()
    {
        add_menu_page(
            '',
            'PeerBoard',
            'manage_options',
            'peerboard',
            [__CLASS__, 'peerboard_options_page_html']
        );
    }

    /**
     * Scripts and stiles on settings page
     *
     * @param [type] $hook
     * @return void
     */
    public static function peerboard_admin_scripts_data($localize_data_array)
    {

        $localize_data_array['user_sync_url'] = get_rest_url(null, 'peerboard/v1/members/sync');

        return $localize_data_array;
    }

    /**
     * Register settings page menu fields
     *
     * @return void
     */
    public static function peerboard_settings_init()
    {
        self::$peerboard_options = get_option('peerboard_options');
        self::$external_comm_settings = API::peerboard_get_community(self::$peerboard_options['auth_token']);

        register_setting('circles', 'peerboard_options');

        add_settings_section(
            'peerboard_section_users_sync',
            __('Users synchronisation', 'peerboard'),
            [__CLASS__, 'peerboard_users_sync_info'],
            'peerboard_users_count'
        );

        add_settings_section(
            'peerboard_section_integration',
            __('Integration Settings', 'peerboard'),
            [__CLASS__, 'peerboard_integration_readme'],
            'circles'
        );
    }

    /**
     * Before auth token input text
     *
     * @return void
     */
    public static function peerboard_integration_readme()
    {
        printf(__("Do you know where to find your Auth Token? If not, watch this short tutorial: <a href='%s' target='_blank'>How to Find My Auth Token.</a>", 'peerboard'), 'https://youtu.be/JMCtHRpZEx0');

        $structure = get_option('permalink_structure');

        // if set default permalinks ?p=post_id
        if (empty($structure)) {
            $permaling_structure = get_dashboard_url(0, 'options-permalink.php');
            var_dump($permaling_structure);
            printf(
                __(
                    '<div class="notice notice-error settings-error is-dismissible">
            <p>You do not have your postname in the URL of your posts and pages. It is highly
            recommended that you do, otherwise, our plugin will not work for you. Consider setting your permalink structure to %s.
            You can fix this on the <a href="%s">Permalink settings page</a>.<br>Why do I need to change the permalink structure? / How do I change the permalink structure? <a href="%s" target="_blank">Click here to learn more.</a></p></div>'
                ),
                '/%postname%/',
                $permaling_structure,
                'https://yoast.com/help/how-do-i-change-the-permalink-structure/'
            );
        }
    }



    /**
     * User sync settings 
     *
     * @param [type] $args
     * @return void
     */
    public static function peerboard_users_sync_info($args)
    {
        $wp_users_count = count_users();
        $users_count = $wp_users_count['total_users'];
        $peerboard_options = get_option('peerboard_options');

        $option_count = get_option('peerboard_users_count');
        if ($option_count === false) {
            $option_count = 1;
        }

        $synced = intval($option_count);
        $diff =  $users_count - $synced;
        $sync_enabled = empty($peerboard_options['peerboard_users_sync_enabled']) ? false : true;

        if ($diff >= 0) {
            printf(__("You have %s users that can be imported to PeerBoard.<br/><br/><i>Note that this will send them a welcome email and subscribe them to digests.</i><br/>", 'peerboard'), $diff);
        } else {
            if ($sync_enabled) {
                _e("Automatic user import is activated.<br/><br/><i>All WordPress registrations automatically receive a welcome email and are subscribed to PeerBoard digests.</i><br/>", 'peerboard');
            } else {
                _e("Enable automatic import of your new WordPress users to PeerBoard.<br/><br/><i>Note that they will start receiving welcome emails and get subscribed to email digests.</i><br/>", 'peerboard');
            }
        }

        printf("<input name='peerboard_users_count' style='display:none' value='%s' />", $option_count);
    }

    /**
     * On peerboard options update
     *
     * @param [type] $value
     * @param [type] $old_value
     * @param [type] $option
     * @return void
     */
    public static function pre_update_option_peerboard_options($value, $old_value, $option)
    {
        if ($old_value === NULL || $old_value === false) {
            return $value;
        }

        if (!is_array($old_value) || !is_array($old_value)) {
            return $value;
        }
        
        //self::$peerboard_options = get_option('peerboard_options');
        //self::$external_comm_settings = API::peerboard_get_community(self::$peerboard_options['auth_token']);

        $external_comm_settings = self::$external_comm_settings;

        $args = [];

        /**
         * Update prefix if needed on peerboard side
         */
        if (isset($value['prefix'])) {
            $value['prefix'] = sanitize_title($value['prefix']);

            if ($value['prefix'] !== $old_value['prefix']) {
                // Case where we are connecting blank community by auth token, that we need to reuse old prefix | 'community'
                if ($value['prefix'] === '' || $value['prefix'] === NULL) {
                    if ($old_value['prefix'] === '' || $old_value['prefix'] === NULL) {
                        $old_value['prefix'] = 'community';
                    }
                    $value['prefix'] = $old_value['prefix'];
                }
                peerboard_update_post_slug($value['prefix']);

                $req = API::peerboard_post_integration($value['auth_token'], $value['prefix'], peerboard_get_domain());

                if (!$req['success']) {
                    return $old_value;
                }
            }
        } else {
            if (isset($old_value['prefix'])) {
                $value['prefix'] = $old_value['prefix'];
            } else {
                $value['prefix'] = peerboard_get_comm_full_slug();
            }
        }


        /**
         * Update auth_token on peerboard side if needed
         */
        if ($value['auth_token'] !== $old_value['auth_token']) {
            $community = API::peerboard_get_community($value['auth_token']);

            if (!$community) {
                return $old_value;
            }

            $value['community_id'] = $community['id'];
            peerboard_send_analytics('set_auth_token', $community['id']);
            $req = API::peerboard_post_integration($value['auth_token'], $value['prefix'], peerboard_get_domain());

            if (!$req['success']) {
                return $old_value;
            }

            if ($old_value['auth_token'] !== '' && $old_value['auth_token'] !== NULL) {
                $success = API::peerboard_drop_integration($old_value['auth_token']);

                if (!$success) {
                    return $old_value;
                }
            }
        }

        $value['external_login_url'] = empty($value['external_login_url'])?'':$value['external_login_url'];

        /**
         * External login url updating
         */
        if (self::get_board_full_login_url() !== $value['external_login_url']) {

            if (filter_var($value['external_login_url'], FILTER_VALIDATE_URL) || $value['external_login_url'] === '') {
                $args['external_login_url'] = $value['external_login_url'];

                $update_login_url = API::peerboard_post_integration($value['auth_token'], $value['prefix'], peerboard_get_domain(), $args);

                if (!$update_login_url['success']) {
                    $value['external_login_url'] = '';
                }
            }
        }


        return $value;
    }

    /**
     * After forum_page_template option updated
     */
    public static function forum_page_template_updated($option_name, $old_value, $option_value)
    {
        if ($option_name !== 'peerboard_options') {
            return;
        }

        if (empty($option_value['forum_page_template'])) {
            return;
        }

        if ($old_value['forum_page_template'] !== $option_value['forum_page_template']) {
            $sel_template = $option_value['forum_page_template'] ?? 'default';
            $forum_page = intval(get_option('peerboard_post'));

            // in wordpress if '' mean default template
            if ($sel_template === 'default') {
                $sel_template = '';
            }
            /**
             * Updating page template
             */
            update_post_meta($forum_page, '_wp_page_template', $sel_template);
        }
    }

    /**
     * If community set as static page update prefix on peerboard side
     */
    public static function update_peerboard_prefix($old_value, $value, $option)
    {

        $peerboard_options = get_option('peerboard_options', true);

        if (peerboard_is_comm_set_static_home_page()) {
            $req = API::peerboard_post_integration($peerboard_options['auth_token'], '', peerboard_get_domain());
        } else {
            $req = API::peerboard_post_integration($peerboard_options['auth_token'], $peerboard_options['prefix'], peerboard_get_domain());
        }

        if (!$req['success']) {
            return;
        }
    }

    /**
     * After update_comm_parent_page option updated
     */
    public static function update_comm_parent_page($option_name, $old_value, $option_value)
    {
        if ($option_name !== 'peerboard_options') {
            return;
        }

        if (empty($option_value['peerboard_comm_parent'])) {
            return;
        }

        if ($old_value['peerboard_comm_parent'] !== $option_value['peerboard_comm_parent']) {
            $sel_page = $option_value['peerboard_comm_parent'] ?? 0;
            $forum_page = intval(get_option('peerboard_post'));

            // in wordpress if 0 mean do not have parent 
            if ($sel_page === 'none') {
                $sel_page = 0;
            }

            wp_update_post(['ID' => $forum_page, 'post_parent' => intval($sel_page)]);
        }
    }

    /**
     * Show settings page forms
     *
     * @return void
     */
    public static function peerboard_options_page_html()
    {
        $peerboard_options = get_option('peerboard_options', true);

        do_action('peerboard_before_admin_settings_form');

        if (!current_user_can('manage_options')) {
            return;
        }
        if (isset($_GET['settings-updated'])) {
            // add settings saved message with the class of "updated"
            add_settings_error('peerboard_messages', 'peerboard_message', __('Settings Saved', 'circles'), 'updated');
        }

        // show error/update messages
        settings_errors('peerboard_messages');

        echo '<div class="wrap">';
        printf('<h1></h1>', esc_html(get_admin_page_title()));
        
        echo '<div class="peerboard-settings-wrap">';

        echo '<form action="options.php" method="post">';

        settings_fields('circles');

        do_settings_sections('circles');

        require_once PEERBOARD_PLUGIN_DIR_PATH . 'templates/admin/settings-page.php';

        submit_button('Save Settings');

        echo '</form>';

        require_once PEERBOARD_PLUGIN_DIR_PATH . 'templates/admin/settings-sidebar.php';
        echo '</div>';

        echo '</div>';
    }

    /**
     * New version changes integration
     *
     * @return void
     */
    public static function new_version_changes()
    {

        $peerboard_options = get_option('peerboard_options', true);

        $old_sync_option = get_option('peerboard_users_sync_enabled');

        // sync was enabled update new value and delete old option
        if ($old_sync_option === '1') {
            $peerboard_options['peerboard_users_sync_enabled'] = '1';

            delete_option('peerboard_users_sync_enabled');
        }

        $old_sync_email = get_option('peerboard_bulk_activate_email');

        // sync email was enabled update new value and delete old option
        if ($old_sync_email === '1') {
            $peerboard_options['peerboard_bulk_activate_email'] = '1';

            delete_option('peerboard_users_sync_enabled');
        }


        update_option('peerboard_options', $peerboard_options);
    }

    public static function get_board_full_login_url()
    {
        $post_id = intval(get_option('peerboard_post'));
        $community_link = get_permalink($post_id);
        $external_login_url = false;

        if (isset(self::$external_comm_settings['hosting'])) {
            $external_login_url = self::$external_comm_settings['hosting']['external_login_url'];
        }

        if (empty($external_login_url)) {
            $external_login_url  = $community_link . 'login';
        }

        return $external_login_url;
    }
}

Settings::init();
