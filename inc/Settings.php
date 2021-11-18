<?php

namespace PEBO;

// Exit if accessed directly
defined('ABSPATH') || exit;

class Settings
{

    public static $peerboard_options;

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

        self::$peerboard_options = get_option('peerboard_options');

        add_action('admin_init', [__CLASS__, 'peerboard_settings_init']);
        add_action('admin_menu', [__CLASS__, 'peerboard_options_page']);

        /**
         * After forum_page_template option updated
         */
        add_action('updated_option', [__CLASS__, 'forum_page_template_updated'], 10, 3);

        /**
         * After update_comm_parent_page option updated
         */
        add_action('updated_option', [__CLASS__, 'update_comm_parent_page'], 10, 3);

        add_filter('pre_update_option_peerboard_options', [__CLASS__, 'pre_update_option_peerboard_options'], 10, 3);
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
            'forum_page_template',
            __('Community page template', 'peerboard'),
            [__CLASS__, 'field_select_forum_page_template'],
            'circles',
            'peerboard_section_integration'
        );

        add_settings_field(
            'parent_page',
            __('Parent page', 'peerboard'),
            [__CLASS__, 'field_select_peerboard_page_parent'],
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
     * Live community link
     *
     * @return void
     */
    public static function peerboard_options_readme()
    {
        $post_id = intval(get_option('peerboard_post'));
        if (peerboard_is_comm_set_static_home_page()) {
            printf(__("The community page is set as the homepage <a target='_blank' href='%s'>%s</a>", 'peerboard'), get_permalink($post_id), get_permalink($post_id));
            $user_ID = get_current_user_id();
            $reading_settings_url = get_dashboard_url($user_ID, 'options-reading.php');
            echo '<br><br>';
            printf(__('To change the community page slug or the parent page, do not use it as a static homepage. You can change it <a target="_blank" href="%s">here</a>', 'peerboard'), $reading_settings_url);
        } else {
            printf(__("PeerBoard will be live at <a target='_blank' href='%s'>%s</a>", 'peerboard'), get_permalink($post_id), get_permalink($post_id));
        }
    }

    /**
     * Add page slug input
     *
     * @param [type] $args
     * @return void
     */
    public static function peerboard_field_prefix_cb($args)
    {
        $prefix = self::$peerboard_options['prefix'];
        $disabled = peerboard_is_comm_set_static_home_page() ? 'disabled' : '';

        printf("<input name='peerboard_options[prefix]' value='%s' %s />", $prefix, $disabled);
    }

    public static function peerboard_field_token_cb($args)
    {
        $peerboard_options = self::$peerboard_options;
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
            printf(__("You have %s users that can be imported to PeerBoard.<br/><br/><i>Note that this will send them a welcome email and subscribe them to digests.</i><br/>", 'peerboard'), $diff);
        } else {
            if ($sync_enabled) {
                _e("Automatic user import is activated.<br/><br/><i>All WordPress registrations automatically receive a welcome email and are subscribed to PeerBoard digests.</i><br/>", 'peerboard');
            } else {
                _e("Enable automatic import of your new WordPress users to PeerBoard.<br/><br/><i>Note that they will start receiving welcome emails and get subscribed to email digests.</i><br/>", 'peerboard');
            }
        }
        printf("<input name='peerboard_users_count' style='display:none' value='%s' />", $option_count);
        printf("<input name='peerboard_users_sync_enabled' style='display:none' value='%s' />", $sync_enabled ? 0 : 1);
    }




    public static function peerboard_show_readme()
    {
        $calendly_link = sprintf("<a href='https://peerboard.org/integration-call' target='_blank'>%s</a>", __('calendly link', 'peerboard'));
        $contact_email = "<a href='mailto:support_wp@peerboard.com' target='_blank'>support_wp@peerboard.com</a>";
        printf(__("If you have experienced any problems during the setup, please don't hesitate to contact us at %s or book a time with our specialist using this %s", 'peerboard'), $contact_email, $calendly_link);
    }

    /**
     * Select page parent
     *
     * @return void
     */
    public static function field_select_peerboard_page_parent()
    {
        $id = 'peerboard_comm_parent';
        $forum_page = get_post(intval(get_option('peerboard_post')));
        $pages = get_pages(['exclude' => [$forum_page->ID]]);
        $sel_parent = wp_get_post_parent_id($forum_page);

        if (empty($sel_parent)) {
            $sel_parent = 'default';
        }

        $options = [
            'none' => __('None', 'peerboard'),
        ];

        foreach ($pages as $page) {
            $options[$page->ID] = $page->post_title;
        }

        $disabled = peerboard_is_comm_set_static_home_page() ? 'disabled' : '';
        echo sprintf('<select name="peerboard_options[%s]" %s>', $id, $disabled);
        foreach ($options as $val => $option) {
            $selected = selected($val, $sel_parent, false);
            echo sprintf('<option value="%s" %s >%s</option>', $val, $selected, $option);
        }
        echo '</select>';
    }

    /**
     * community page template
     *
     * @return void
     */
    public static function field_select_forum_page_template()
    {
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

            $success = API::peerboard_post_integration($value['auth_token'], $value['prefix'], peerboard_get_domain());

            if (!$success) {
                return $old_value;
            }
        }

        if ($value['auth_token'] !== $old_value['auth_token']) {
            $community = API::peerboard_get_community($value['auth_token']);

            if (!$community) {
                return $old_value;
            }

            $value['community_id'] = $community['id'];
            peerboard_send_analytics('set_auth_token', $community['id']);
            $success = API::peerboard_post_integration($value['auth_token'], $value['prefix'], peerboard_get_domain());

            if (!$success) {
                return $old_value;
            }

            if ($old_value['auth_token'] !== '' && $old_value['auth_token'] !== NULL) {
                $success = API::peerboard_drop_integration($old_value['auth_token']);

                if (!$success) {
                    return $old_value;
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
        $wp_users_count = count_users();
        $users_count = $wp_users_count['total_users'];

        if ($users_count >= 100000) {
            _e('<h2>Note: this feature is manually activated for large customers, email us at <a href="mailto:support_wp@peerboard.com">support_wp@peerboard.com</a></h2>', 'peerboard');
        }

        settings_fields('peerboard_users_count');
        do_settings_sections('peerboard_users_count');

        $sync_enabled = get_option('peerboard_users_sync_enabled');

        if (!$sync_enabled) {
            submit_button(__('Activate Automatic Import', 'peerboard'));
        } else {
            submit_button(__('Deactivate Automatic Import', 'peerboard'));
        }
        echo '</form>';
        // Some info on the bottom 
        $sitemap_url = home_url('/') . Sitemap::$sitemap_path;
        printf('<p><strong>Sitemap:</strong> <a href="%s" target="_blank">%s</a></p>', $sitemap_url, $sitemap_url);

        $comm_id = self::$peerboard_options["community_id"];
        printf('<p><strong>Community ID:</strong> %s</p>', $comm_id);

        echo '</div>';
    }
}

Settings::init();
