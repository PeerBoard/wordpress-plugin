<?php
function circles_section_integration_cb( $args ) {
	echo '<p>Please set required options</p>';
}
 
function circles_field_prefix_cb( $args ) {
	$options = get_option( 'circles_options' );
	$prefix = $options['prefix'];
	echo "<input name='circles_options[prefix]' value='$prefix' />";
}
function circles_settings_init() {
	register_setting( 'circles', 'circles_options' );
	add_settings_section(
	'circles_section_integration',
	'Forum integration options',
	'circles_section_integration_cb',
	'circles'
	);

	add_settings_field(
	'prefix',
	'Path prefix',
	'circles_field_prefix_cb',
	'circles',
	'circles_section_integration'
	);
}
add_action( 'admin_init', 'circles_settings_init' );


function circles_options_page_html() {
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
	submit_button( 'Save Settings' );
	?>
		</form>
		</div>
	<?php
}
function circles_options_page() {
	add_menu_page(
		'circles',
		'Сircles Options',
		'manage_options',
		'circles',
		'circles_options_page_html'
	);
}
add_action( 'admin_menu', 'circles_options_page' );

