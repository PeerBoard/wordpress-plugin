<?php
function circles_readme() {
	global $circles_options;
	$url_parts = explode('://', get_home_url());
	$domain = $url_parts[1];
	$prefix = $circles_options['prefix'];
	echo "You can find those values in your board settings in Integrations tab. If you don't have a board created yet, please visit ";
	echo "<a href='https://peerboard.io/getstarted?wordpressDomain=$domain&pathPrefix=$prefix' target='_blank'>peerboard.io</a>";
}
function circles_field_prefix_cb( $args ) {
	global $circles_options;
	$prefix = $circles_options['prefix'];
	echo "<input name='circles_options[prefix]' value='$prefix' />";
	echo "  PeerBoard will be live at " . get_home_url() . '/' . $prefix;
}

function circles_field_community_id_cb( $args ) {
	$options = get_option( 'circles_options' );
	$community_id = $options['community_id'];
	echo "<input name='circles_options[community_id]' value='$community_id' />";

	$embed_script_url = $options['embed_script_url'];
	$hidden_style = 'display: none;';
	if ($embed_script_url != NULL && $embed_script_url != '') {
		$hidden_style = 'width: 50%;';
	}
	echo "<input name='circles_options[embed_script_url]' value='$embed_script_url' style='$hidden_style'/>";
}

function circles_field_token_cb( $args ) {
	$options = get_option( 'circles_options' );
	$token = $options['auth_token'];
	echo "<input name='circles_options[auth_token]' value='$token' />";
}

function circles_field_expose_cb( $args ) {
	$options = get_option( 'circles_options', array() );
	$checked = (array_key_exists('expose_user_data', $options)) ? checked( '1', $options['expose_user_data'], false) : '';
	echo "<input name='circles_options[expose_user_data]' type='checkbox' value='1' $checked/>";
}

function circles_settings_init() {
	register_setting( 'circles', 'circles_options' );
	add_settings_section(
		'circles_section_integration',
		'Integration Settings',
		'circles_readme',
		'circles'
	);

	add_settings_field(
		'community_id',
		'Board ID',
		'circles_field_community_id_cb',
		'circles',
		'circles_section_integration'
	);

	add_settings_field(
		'auth_token',
		'Auth token',
		'circles_field_token_cb',
		'circles',
		'circles_section_integration'
	);

	add_settings_field(
		'prefix',
		'Board path',
		'circles_field_prefix_cb',
		'circles',
		'circles_section_integration'
	);

	add_settings_field(
		'expose_user_data',
		'Automatically import first and last names',
		'circles_field_expose_cb',
		'circles',
		'circles_section_integration'
	);
}
add_action( 'admin_init', 'circles_settings_init' );


function peerboard_options_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated"
		add_settings_error( 'circles_messages', 'circles_message', __( 'Settings Saved', 'circles' ), 'updated' );
	}

	// show error/update messages
	settings_errors( 'circles_messages' );
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
function circles_options_page() {
	add_menu_page(
		'',
		'PeerBoard',
		'manage_options',
		'peerboard',
		'peerboard_options_page_html'
	);
}
add_action( 'admin_menu', 'circles_options_page' );
