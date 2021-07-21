<?php

namespace PEBO;

// Exit if accessed directly
defined('ABSPATH') || exit;

class Settings
{

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

        add_action('pre_update_option_peerboard_options', [__CLASS__, 'pre_update_option_peerboard_options'], 10, 3);
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
     * Register settings page menu fields
     *
     * @return void
     */
    public static function peerboard_settings_init()
    {
        register_setting('circles', 'peerboard_options');
        register_setting('peerboard_users_count', 'peerboard_users_count', 'intval');
        register_setting('peerboard_users_count', 'peerboard_users_sync_enabled', 'intval');

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

        add_settings_section(
            'peerboard_section_options',
            '',
            [__CLASS__, 'peerboard_options_readme'],
            'circles'
        );

        add_settings_field(
            'auth_token',
            __('Auth token', 'peerboard'),
            [__CLASS__, 'peerboard_field_token_cb'],
            'circles',
            'peerboard_section_integration'
        );

        add_settings_field(
            'prefix',
            __('Board path', 'peerboard'),
            [__CLASS__, 'peerboard_field_prefix_cb'],
            'circles',
            'peerboard_section_integration'
        );

        add_settings_field(
            'forum_page_template',
            __('Select forum page template', 'peerboard'),
            [__CLASS__, 'field_select_forum_page_template'],
            'circles',
            'peerboard_section_integration'
        );

        add_settings_field(
            'expose_user_data',
            __('Automatically import first and last names', 'peerboard'),
            [__CLASS__, 'peerboard_field_expose_cb'],
            'circles',
            'peerboard_section_options'
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
    }

    public static function peerboard_options_readme()
    {
        global $peerboard_options;
        $prefix = $peerboard_options['prefix'];
        $integration_url = get_home_url() . '/' . $prefix;
        printf("PeerBoard will be live at <a target='_blank' href='%s'>%s</a>", $integration_url, $integration_url);
    }

    public static function peerboard_field_prefix_cb($args)
    {
        global $peerboard_options;
        $prefix = $peerboard_options['prefix'];
        echo "<input name='peerboard_options[prefix]' value='$prefix' />";
    }

    public static function peerboard_field_token_cb($args)
    {
        global $peerboard_options;
        $token = $peerboard_options['auth_token'];
        echo "<input style='width: 300px;' name='peerboard_options[auth_token]' value='$token' />";

        $community_id = $peerboard_options['community_id'];
        echo "<input name='peerboard_options[community_id]' value='$community_id' style='display: none;'/>";
        $mode = $peerboard_options['mode'];
        echo "<input name='peerboard_options[mode]' value='$mode' style='display: none;'/>";
    }

    public static function peerboard_field_expose_cb($args)
    {
        $options = get_option('peerboard_options', array());
        $checked = (array_key_exists('expose_user_data', $options)) ? checked('1', $options['expose_user_data'], false) : '';
        echo "<input name='peerboard_options[expose_user_data]' type='checkbox' value='1' $checked/>";
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

        $option_count = get_option('peerboard_users_count');
        if ($option_count === false) {
            $option_count = 1;
        }

        $synced = intval($option_count);
        $diff =  $users_count - $synced;
        $sync_enabled = get_option('peerboard_users_sync_enabled');

        if ($diff !== 0) {
            printf(__("You have %s users that can be imported to PeerBoard.<br/><br/><i>Note that this will send them a welcome email and subscribe to digests.</i><br/>", 'peerboard'), $diff);
        } else {
            if ($sync_enabled) {
                _e("Automatic user import is activated.<br/><br/><i>All WordPress registrations automatically receive welcome email and are subscribed to PeerBoard digest.</i><br/>", 'peerboard');
            } else {
                _e("Enable automatic import of your new WordPress users to PeerBoard.<br/><br/><i>Note that they will be receiving welcome emails and get subscribed to email digests.</i><br/>", 'peerboard');
            }
        }
        printf("<input name='peerboard_users_count' style='display:none' value='%s' />", $option_count);
        printf("<input name='peerboard_users_sync_enabled' style='display:none' value='%s' />", $sync_enabled ? 0 : 1);
    }




    public static function peerboard_show_readme()
    {
        $calendly_link = sprintf("<a href='https://peerboard.org/integration-call' target='_blank'>%s</a>", __('calendly link', 'peerboard'));
        $contact_email = "<a href='mailto:integrations@peerboard.com' target='_blank'>integrations@peerboard.com</a>";
        printf(__("<br/><br/>If you experienced any problems during the setup, please don't hesitate to contact us at %s or book a time with our specialist using this %s", 'peerboard'), $contact_email, $calendly_link);
    }

    /**
     * Select forum page template
     *
     * @return void
     */
    public static function field_select_forum_page_template()
    {
        global $peerboard_options;
        $id = 'forum_page_template';
        $forum_page = intval(get_option('peerboard_post'));
        $templates = get_page_templates($forum_page);
        $sel_template = get_post_meta($forum_page, '_wp_page_template', true);

        if (empty($sel_template)) {
            $sel_template = 'default';
        }

        $options = [
            'default' => __('Default', 'peerboard'),
        ];

        foreach ($templates as $template => $file) {
            $options[$file] = $template;
        }

        echo sprintf('<select name="peerboard_options[%s]">', $id);
        foreach ($options as $val => $option) {
            $selected = selected($val, $sel_template, false);
            echo sprintf('<option value="%s" %s >%s</option>', $val, $selected, $option);
        }
        echo '</select>';
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
        if ($value['prefix'] !== $old_value['prefix']) {
            // Case where we are connecting blank community by auth token, that we need to reuse old prefix | 'community'
            if ($value['prefix'] === '' || $value['prefix'] === NULL) {
                if ($old_value['prefix'] === '' || $old_value['prefix'] === NULL) {
                    $old_value['prefix'] = 'community';
                }
                $value['prefix'] = $old_value['prefix'];
            }
            peerboard_update_post_slug($value['prefix']);
            API::peerboard_post_integration($value['auth_token'], $value['prefix'], peerboard_get_domain());
        }

        if ($value['auth_token'] !== $old_value['auth_token']) {
            $community = API::peerboard_get_community($value['auth_token']);
            $value['community_id'] = $community['id'];
            peerboard_send_analytics('set_auth_token', $community['id']);
            API::peerboard_post_integration($value['auth_token'], $value['prefix'], peerboard_get_domain());
            if ($old_value['auth_token'] !== '' && $old_value['auth_token'] !== NULL) {
                API::peerboard_drop_integration($old_value['auth_token']);
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
     * Show settings page forms
     *
     * @return void
     */
    public static function peerboard_options_page_html()
    {
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
        echo '<form action="options.php" method="post">';

        settings_fields('circles');

        do_settings_sections('circles');

        _e("For more information please check our ", 'peerboard');

        printf("<a href='https://community.peerboard.com/post/396436794' target='_blank'>%s</a><br/><br/>", __('How-To guide for WordPress', 'peerboard'));

        self::peerboard_show_readme();

        submit_button('Save Settings');

        echo '</form>';
        echo '<form action="options.php" method="post">';

        settings_fields('peerboard_users_count');
        do_settings_sections('peerboard_users_count');

        $sync_enabled = get_option('peerboard_users_sync_enabled');

        if (!$sync_enabled) {
            submit_button(__('Activate Automatic Import', 'peerboard'));
        } else {
            submit_button(__('Deactivate Automatic Import', 'peerboard'));
        }
        echo '</form>';
        echo '</div>';
    }
}

Settings::init();
