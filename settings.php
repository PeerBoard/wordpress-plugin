<?php
function peerboard_integration_readme() {
	global $peerboard_options;
	$url_parts = explode('://', get_home_url());
	$domain = $url_parts[1];
	$prefix = $peerboard_options['prefix'];
	echo "You can find those values in your board settings in Integrations tab. If you don't have a board created yet, please visit ";
	echo "<a href='https://peerboard.io/getstarted?wordpressDomain=$domain&pathPrefix=$prefix' target='_blank'>peerboard.io</a>";
}
function peerboard_field_prefix_cb( $args ) {
	global $peerboard_options;
	$prefix = $peerboard_options['prefix'];
	echo "<input name='peerboard_options[prefix]' value='$prefix' />";
	echo "  PeerBoard will be live at " . get_home_url() . '/' . $prefix;
}

function peerboard_field_community_id_cb( $args ) {
	$options = get_option( 'peerboard_options' );
	$community_id = $options['community_id'];
	echo "<input name='peerboard_options[community_id]' value='$community_id' />";

	$embed_script_url = $options['embed_script_url'];
	$hidden_style = 'display: none;';
	if ($embed_script_url != NULL && $embed_script_url != '') {
		$hidden_style = 'width: 50%;';
	}
	echo "<input name='peerboard_options[embed_script_url]' value='$embed_script_url' style='$hidden_style'/>";
}

function peerboard_field_token_cb( $args ) {
	$options = get_option( 'peerboard_options' );
	$token = $options['auth_token'];
	echo "<input name='peerboard_options[auth_token]' value='$token' />";
}

function peerboard_field_expose_cb( $args ) {
	$options = get_option( 'peerboard_options', array() );
	$checked = (array_key_exists('expose_user_data', $options)) ? checked( '1', $options['expose_user_data'], false) : '';
	echo "<input name='peerboard_options[expose_user_data]' type='checkbox' value='1' $checked/>";
}

function peerboard_settings_init() {
	register_setting( 'circles', 'peerboard_options' );
	add_settings_section(
		'peerboard_section_integration',
		'Integration Settings',
		'peerboard_integration_readme',
		'circles'
	);

	add_settings_field(
		'community_id',
		'Board ID',
		'peerboard_field_community_id_cb',
		'circles',
		'peerboard_section_integration'
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
		'peerboard_section_integration'
	);
}
add_action( 'admin_init', 'peerboard_settings_init' );


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
	echo "<a href='https://community.peerboard.io/post/396436794' target='_blank'>How-To guide for WordPress</a>";
	submit_button( 'Save Settings' );
	?>
		</form>
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
