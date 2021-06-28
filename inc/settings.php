<?php

namespace PEBO;

// Exit if accessed directly
defined('ABSPATH') || exit;

class Settings
{

    public static function init()
    {
        add_action('admin_init', [__CLASS__, 'peerboard_settings_init']);
        add_action('admin_menu', [__CLASS__, 'peerboard_options_page']);
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
            'expose_user_data',
            __('Automatically import first and last names', 'peerboard'),
            [__CLASS__, 'peerboard_field_expose_cb'],
            'circles',
            'peerboard_section_options'
        );
    }

    public static function peerboard_integration_readme()
    {
        _e("You can find those values in your board settings in Integrations tab. If you don't have a board created yet, please visit ", 'peerboard');
        printf(__("<a href='%s' target='_blank'>%s</a>", 'peeerboard'), "https://peerboard.com/getstarted", "peerboard.com/getstarted");
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
        if ($sync_enabled === '1') {
            // 0 is a flag value for sync disable
            $option_count = 0;
        }
        if ($diff !== 0) {
            printf(__("You have %s users that can be imported to PeerBoard.<br/><br/><i>Note that this will send them a welcome email and subscribe to digests.</i><br/>", 'peerboard'), $diff);
        } else {
            if ($option_count === 0) {
                _e("Automatic user import is activated.<br/><br/><i>All WordPress registrations automatically receive welcome email and are subscribed to PeerBoard digest.</i><br/>", 'peerboard');
            } else {
                _e("Enable automatic import of your new WordPress users to PeerBoard.<br/><br/><i>Note that they will be receiving welcome emails and get subscribed to email digests.</i><br/>", 'peerboard');
            }
        }
        printf("<input name='peerboard_users_count' style='display:none' value='%s' />", $option_count);
    }




    public static function peerboard_show_readme()
    {
        $calendly_link = "<a href='https://peerboard.org/integration-call' target='_blank'>calendly link</a>";
        $contact_email = "<a href='mailto:integrations@peerboard.com' target='_blank'>integrations@peerboard.com</a>";
        printf(__("<br/><br/>If you experienced any problems during the setup, please don't hesitate to contact us at %s or book a time with our specialist using this %s", 'peerboard'), $contact_email, $calendly_link);
    }


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
?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('circles');
                do_settings_sections('circles');
                _e("For more information please check our ",'peerboard');
                printf("<a href='https://community.peerboard.com/post/396436794' target='_blank'>%s</a><br/><br/>",__('How-To guide for WordPress','peerboard'));
                self::peerboard_show_readme();
                submit_button('Save Settings');
                ?>
            </form>
            <form action="options.php" method="post">
                <?php
                settings_fields('peerboard_users_count');
                do_settings_sections('peerboard_users_count');
                $wp_users_count = count_users();
                $users_count = $wp_users_count['total_users'];
                $option_count = get_option('peerboard_users_count');
                $sync_enabled = get_option('peerboard_users_sync_enabled');
                if ($sync_enabled === '0') {
                    // 0 is a flag value for sync disable
                    $option_count = 0;
                }
                if ($option_count === false || $option_count === 0) {
                    // initial run - show button
                    submit_button(__('Activate Automatic Import','peerboard'));
                } else {
                    // auto import enabled
                    submit_button(__('Deactivate Automatic Import','peerboard'));
                }
                ?>
        </div>
<?php
    }
}

Settings::init();
