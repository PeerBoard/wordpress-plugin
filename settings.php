<?php
function peerboard_integration_readme() {
	echo "You can find those values in your board settings in Integrations tab. If you don't have a board created yet, please visit ";
	echo "<a href='https://peerboard.com/getstarted' target='_blank'>peerboard.com/getstarted</a>";
}

function peerboard_options_readme() {
	global $peerboard_options;
	$prefix = $peerboard_options['prefix'];
	$integration_url = get_home_url() . '/' . $prefix;
	echo "PeerBoard will be live at <a target='_blank' href='$integration_url'>" . $integration_url . '</a>';
}

function peerboard_field_prefix_cb( $args ) {
	global $peerboard_options;
	$prefix = $peerboard_options['prefix'];
	echo "<input name='peerboard_options[prefix]' value='$prefix' />";
}

function peerboard_field_token_cb( $args ) {
	global $peerboard_options;
	$token = $peerboard_options['auth_token'];
	echo "<input style='width: 300px;' name='peerboard_options[auth_token]' value='$token' />";

	$community_id = $peerboard_options['community_id'];
	echo "<input name='peerboard_options[community_id]' value='$community_id' style='display: none;'/>";
	$mode = $peerboard_options['mode'];
	echo "<input name='peerboard_options[mode]' value='$mode' style='display: none;'/>";
}

function peerboard_field_expose_cb( $args ) {
	$options = get_option( 'peerboard_options', array() );
	$checked = (array_key_exists('expose_user_data', $options)) ? checked( '1', $options['expose_user_data'], false) : '';
	echo "<input name='peerboard_options[expose_user_data]' type='checkbox' value='1' $checked/>";
}

function peerboard_users_sync_info( $args ) {
	$users_count = (count_users())['total_users'];

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
		echo "You have " . $diff . " users that can be imported to PeerBoard.<br/><br/><i>Note that this will send them a welcome email and subscribe to digests.</i><br/>";
	} else {
		if ($option_count === 0) {
			echo "Automatic user import is activated.<br/><br/><i>All WordPress registrations automatically receive welcome email and are subscribed to PeerBoard digest.</i><br/>";
		} else {
			echo "Enable automatic import of your new WordPress users to PeerBoard.<br/><br/><i>Note that they will be receiving welcome emails and get subscribed to email digests.</i><br/>";
		}
	}
	echo "<input name='peerboard_users_count' style='display:none' value='$option_count' />";
}

function peerboard_settings_init() {
	register_setting( 'circles', 'peerboard_options' );
	register_setting( 'peerboard_users_count', 'peerboard_users_count', 'intval');

	add_settings_section(
		'peerboard_section_users_sync',
		'Users synchronisation',
		'peerboard_users_sync_info',
		'peerboard_users_count'
	);

	add_settings_section(
		'peerboard_section_integration',
		'Integration Settings',
		'peerboard_integration_readme',
		'circles'
	);

	add_settings_section(
		'peerboard_section_options',
		'',
		'peerboard_options_readme',
		'circles'
	);

	add_settings_field(
		'auth_token',
		'Auth token',
		'peerboard_field_token_cb',
		'circles',
		'peerboard_section_integration'
	);

	add_settings_field(
		'prefix',
		'Board path',
		'peerboard_field_prefix_cb',
		'circles',
		'peerboard_section_integration'
	);

	add_settings_field(
		'expose_user_data',
		'Automatically import first and last names',
		'peerboard_field_expose_cb',
		'circles',
		'peerboard_section_options'
	);
}
add_action( 'admin_init', 'peerboard_settings_init' );

function peerboard_show_readme() {
	$calendly_link = "<a href='https://peerboard.org/integration-call' target='_blank'>calendly link</a>";
	$contact_email = "<a href='mailto:integrations@peerboard.com' target='_blank'>integrations@peerboard.com</a>";
	echo "<br/><br/>If you experienced any problems during the setup, please don't hesitate to contact us at $contact_email or book a time with our specialist using this $calendly_link";
}


function peerboard_options_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated"
		add_settings_error( 'peerboard_messages', 'peerboard_message', __( 'Settings Saved', 'circles' ), 'updated' );
	}

	// show error/update messages
	settings_errors( 'peerboard_messages' );
	?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
			<?php
				settings_fields( 'circles' );
				do_settings_sections( 'circles' );
				echo "For more information please check our ";
				echo "<a href='https://community.peerboard.com/post/396436794' target='_blank'>How-To guide for WordPress</a><br/><br/>";
				peerboard_show_readme();
				submit_button( 'Save Settings' );
			?>
			</form>
			<form action="options.php" method="post">
			<?php
				settings_fields( 'peerboard_users_count' );
				do_settings_sections( 'peerboard_users_count' );
				$users_count = (count_users())['total_users'];
				$option_count = get_option('peerboard_users_count');
				$sync_enabled = get_option('peerboard_users_sync_enabled');
				if ($sync_enabled === '0') {
					// 0 is a flag value for sync disable
					$option_count = 0;
				}
				if ($option_count === false || $option_count === 0) {
					// initial run - show button
					submit_button( 'Activate Automatic Import' );
				} else {
					// auto import enabled
					submit_button( 'Deactivate Automatic Import' );
				}
			?>
		</div>
	<?php
}
function peerboard_options_page() {
	add_menu_page(
		'',
		'PeerBoard',
		'manage_options',
		'peerboard',
		'peerboard_options_page_html'
	);
}
add_action( 'admin_menu', 'peerboard_options_page' );
