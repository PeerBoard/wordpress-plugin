<?php

namespace PEBO;

$peerboard_options = get_option('peerboard_options');

$wp_users_count = count_users();
$users_count = $wp_users_count['total_users'];
?>

<div class="settings">

    <div class="option-field form-table">

        <div class="option-name"><strong><?= __('Auth token', 'peerboard') ?></strong></div>

        <div class="option-val">
            <?php
            $peerboard_options = $peerboard_options;
            $token = $peerboard_options['auth_token'];
            ?>
            <input style='width: 300px;' name='peerboard_options[auth_token]' value='<?= $token ?>' />

            <?php
            $community_id = $peerboard_options['community_id'];
            echo "<input name='peerboard_options[community_id]' value='$community_id' style='display: none;'/>";
            $mode = $peerboard_options['mode'];
            echo "<input name='peerboard_options[mode]' value='$mode' style='display: none;'/>";
            ?>
        </div>

    </div>

    <!-- Community page template -->
    <div class="option-field form-table">

        <div class="option-name"><strong><?= __('Community page template', 'peerboard') ?></strong></div>

        <div class="option-val">
            <?php
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
            ?>
        </div>

    </div>
    <!-- Community page template -->

    <!-- Parent page -->
    <div class="option-field form-table">

        <div class="option-name"><strong><?= __('Parent page', 'peerboard') ?></strong></div>

        <div class="option-val">
            <?php
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
            ?>
        </div>

    </div>
    <!-- Parent page -->

    <!-- Board path -->
    <div class="option-field form-table">

        <div class="option-name"><strong><?= __('Board path', 'peerboard') ?></strong></div>

        <div class="option-val">
            <?php
            $prefix = $peerboard_options['prefix'] ?? 'community';
            $disabled = peerboard_is_comm_set_static_home_page() ? 'disabled' : '';

            printf("<input name='peerboard_options[prefix]' value='%s' %s />", $prefix, $disabled);

            echo '<br><br>';

            $post_id = intval(get_option('peerboard_post'));
            $community_link = get_permalink($post_id);
            if (peerboard_is_comm_set_static_home_page()) {
                printf(__("The community page is set as the homepage <a target='_blank' href='%s'>%s</a>", 'peerboard'), $community_link, $community_link);
                $user_ID = get_current_user_id();
                $reading_settings_url = get_dashboard_url($user_ID, 'options-reading.php');
                printf(__('To change the community page slug or the parent page, do not use it as a static homepage. You can change it <a target="_blank" href="%s">here</a>', 'peerboard'), $reading_settings_url);
            } else {
                printf(__("PeerBoard will be live at <a target='_blank' href='%s'>%s</a>", 'peerboard'), $community_link, $community_link);
            }
            ?>
        </div>

    </div>
    <!-- Board path -->

    <!-- Board external login url -->
    <div class="option-field form-table">

        <div class="option-name"><strong><?= __('Board external login url', 'peerboard') ?></strong></div>

        <div class="option-val">
            <?php
            printf("<input name='peerboard_options[external_login_url]' value='%s' style='width: 300px;'/>", Settings::get_board_full_login_url());

            // Board login link message
            $external_login_url = self::get_board_full_login_url();
            echo '<br><br>';
            printf(__('Your board login url: <a href="%s">%s</a>'), $external_login_url, $external_login_url);
            ?>
        </div>

    </div>
    <!-- Board external login url -->

    <div class="divider"></div>

    <!-- User sync options -->
    <div class="sync-settings">

        <!-- Automatic user import -->
        <div class="option-field form-table input-check">

            <div class="option-name"><strong><?= __('Automatic user import', 'peerboard') ?></strong></div>

            <div class="option-val">
                <?php
                $options = get_option('peerboard_options', array());
                $checked = (array_key_exists('peerboard_users_sync_enabled', $options)) ? checked('1', $options['peerboard_users_sync_enabled'], false) : '';
                echo "<input name='peerboard_options[peerboard_users_sync_enabled]' id='peerboard_users_sync_enabled' type='checkbox' value='1' $checked/>";
                ?>
            </div>

        </div>
        <!-- Automatic user import -->

        <!-- Automatically import first and last names -->
        <div class="sub_settings option-field form-table input-check">

            <div class="option-name"><strong><?= __('Automatically import first and last names', 'peerboard') ?></strong></div>

            <div class="option-val">
                <?php
                $options = get_option('peerboard_options', array());
                $checked = (array_key_exists('expose_user_data', $options)) ? checked('1', $options['expose_user_data'], false) : '';
                echo "<input name='peerboard_options[expose_user_data]' id='expose_user_data' type='checkbox' value='1' $checked/>";
                ?>
            </div>

        </div>
        <!-- Automatically import first and last names -->

        <!-- Send welcome email and subscribe new members to community digests. -->
        <div class="sub_settings option-field form-table input-check">

            <div class="option-name"><strong><?= __('Send welcome email and subscribe new members to community digests.', 'peerboard') ?></strong></div>

            <div class="option-val">
                <?php
                $options = get_option('peerboard_options', array());
                $checked = (array_key_exists('peerboard_bulk_activate_email', $options)) ? checked('1', $options['peerboard_bulk_activate_email'], false) : '';
                echo "<input name='peerboard_options[peerboard_bulk_activate_email]' id='peerboard_bulk_activate_email' type='checkbox' value='1' $checked/>";
                ?>
            </div>

        </div>
        <!-- Send welcome email and subscribe new members to community digests. -->

        <div class="import-wrap">
            <?php
            $_wpnonce = wp_create_nonce('wp_rest');
            // get how much pages we have by 1000 users
            $pages_count = ceil($users_count / 1000);
            $current_page = get_option('peerboard_stop_importing_on_page');
            $need_resume = false;

            if (empty($current_page)) {
                $current_page = 1;
            } elseif ($current_page >= $pages_count) {
                $current_page = 1;
            } elseif ($current_page < $pages_count) {
                $need_resume = true;
            }

            $progress_bar_persent_for_page = ceil(100  / $pages_count);

            $percent_imported = $need_resume ? $progress_bar_persent_for_page * intval($current_page) : 0;
            ?>
            <input type='hidden' id='_wp_rest_nonce' name='_wp_rest_nonce' value='<?= $_wpnonce ?>' />
            <button id="sync_users" class="sub_settings" type="button"><span><img src="<?= PEERBOARD_PLUGIN_URL . '/img/sync.png' ?>"></span><span class="text"><?= __('Import Existing Users', 'peerboard') ?></span></button>

            <div class="sync-progress-wrap" style="<?= $need_resume ? 'display:block;' : '' ?>">
                <p class="notice notice-warning"><?= __('Please do not reload the page until the users import will be finished!') ?></p>
                <div id="sync-progress" page-count=<?= $pages_count ?> current-page="<?= $current_page ?>">
                    <div id="bar" style="width:<?= $percent_imported ?>%"><?= $percent_imported ?>%</div>
                </div>
                <div class="numbers">
                    <p>0%</p>
                    <p>100%</p>
                </div>

            </div>

        </div>

    </div>
    <!-- User sync options -->

</div>