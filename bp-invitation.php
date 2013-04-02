<?php
/*
Plugin Name: BP Invitation
Plugin URI: https://github.com/imath/bp-invitation
Description: Restrict BuddyPress registration with an invitation code
Version: 1.0
Requires at least: 3.5.1
Tested up to: 3.5.1
License: GNU/GPL 2
Author: imath
Author URI: http://imathi.eu/
Network: true
Text Domain: bp-invitation
Domain Path: /languages/
*/

/**
* 
*/
class BP_Invitation
{
	
	public $plugin_dir;
	
	function __construct()
	{
		$this->plugin_dir = plugin_dir_path( __FILE__ );
		$this->bp_invitation();
	}
	
	function bp_invitation() {
		add_action( 'bp_before_account_details_fields', array( $this, 'invitation_field'             ), 10 );
		add_action( 'bp_signup_validate',               array( $this, 'check_invitation'             ), 10 );
		add_action( 'bp_register_admin_settings',       array( $this, 'register_invitation_settings' ), 99 );
		add_action( 'init',                             array( $this, 'load_textdomain'              ), 10 );
	}
	
	function invitation_field() {
		$code = get_option( '_bp_invitation_code' );
		
		// displaying the field only if it has been set in the admin
		if( !empty( $code ) ) {
			?>
			<div class="register-section" id="invitation-code">
				<p style="margin-bottom:1em">
					<label for="bp-invit-code"><?php _e( 'Registering to this website requires an invitation code', 'bp-invitation' );?></label>
					<?php do_action( 'bp_invitation_code_errors' ); ?>
					<input type="password" name="_bp_invitation_code" id="bp-invit-code" style="width:50%"/> <?php _e( '(required)', 'bp-invitation' ); ?>
				</p>
			</div>
			<?php
		}
		
	}
	
	function check_invitation() {
		global $bp;
		
		$code = get_option( '_bp_invitation_code' );
		
		// if empty, then registrations do not need an invitation code
		if( empty( $code ) )
			return true;
			
		$user_code = $_POST['_bp_invitation_code'];
		
		if( empty( $user_code ) || $user_code != $code ) {
			$bp->signup->errors['invitation_code'] = __( 'Your invitation code is not valid.', 'bp-invitation' );
		}
	}
	
	function register_invitation_settings() {
		add_settings_section( 'bp_invitation', __( 'Restrict registration with an invitation code', 'bp-invitation' ), array( &$this, 'bp_invitation_settings_section_cb'), 'buddypress' );

		add_settings_field( '_bp_invitation_code', __( 'Invitation code', 'bp-invitation' ), array( &$this, 'bp_invitation_code_cb'), 'buddypress', 'bp_invitation' );
		
		register_setting( 'buddypress', '_bp_invitation_code', array( &$this, 'bp_invitation_sanitize_code') );
	}
	
	function bp_invitation_settings_section_cb() {
		?>
		<p><?php _e( 'By adding an invitation code, you restrict registrations to users that have it.', 'bp-invitation' )?></p>
		<?php
	}
	
	function bp_invitation_code_cb() {
		$invitation_code = get_option( '_bp_invitation_code' );
		$invitation_code = wp_kses( $invitation_code, array() );
		?>
		<input type="text" name="_bp_invitation_code" id="_bp_invitation_code" value="<?php echo $invitation_code;?>">
		<?php
	}
	
	function bp_invitation_sanitize_code( $option ) {
		$invitation_code = $_POST['_bp_invitation_code'];
		
		if( !empty( $invitation_code ) )
			$invitation_code = wp_kses( $invitation_code, array() );
			
		return $invitation_code;
	}
	
	function load_textdomain() {

		// try to get locale
		$locale = apply_filters( 'bp_invitation_load_textdomain_get_locale', get_locale() );

		// if we found a locale, try to load .mo file
		if ( !empty( $locale ) ) {
			// default .mo file path
			$mofile_default = sprintf( '%s/languages/%s-%s.mo', $this->plugin_dir, 'bp-invitation', $locale );
			// final filtered file path
			$mofile = apply_filters( 'bp_invitation_textdomain_mofile', $mofile_default );
			// make sure file exists, and load it
			if ( file_exists( $mofile ) ) {
				load_textdomain( 'bp-invitation', $mofile );
			}
		}
	}
}



function bp_invitation_loader() {
	return new BP_Invitation();
}

add_action( 'bp_include', 'bp_invitation_loader' );