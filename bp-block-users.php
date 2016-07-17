<?php
/**
 * Plugin Name:     BP Block Users
 * Plugin URI:      https://github.com/thebrandonallen/bp-block-users
 * Description:     Allows BuddyPress administrators to block users indefinitely, or for a specified period of time.
 * Author:          Brandon Allen
 * Author URI:      https://github.com/thebrandonallen
 * Text Domain:     bp-block-users
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package BP_Block_Users
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Only load the plugin code if BuddyPress is activated.
 */
function tba_bp_block_users_init() {

	// Only supported in BP 2.1.2+
	if ( version_compare( bp_get_version(), '2.1.2', '>=' ) ) {

		require plugin_dir_path( __FILE__ ) . 'classes/class-bp-block-users-component.php';

		add_action( 'bp_loaded', 'bp_block_users_setup_component' );

	// Show admin notice for users on BP 2.1.1 and below.
	} else {

		$older_version_notice = sprintf( __( 'Hey! BP Block Users requires BuddyPress 2.1.2 or higher.', 'bp-block-users' ) );
		add_action( 'admin_notices', create_function( '', "
			echo '<div class=\"error\"><p>" . $older_version_notice . "</p></div>';
		" ) );
		return;
	}
}
add_action( 'bp_include', 'tba_bp_block_users_init' );

/**
 * Load the translation file for current language. Checks the BP Block Users
 * languages folder first, then inside the default WP language plugins folder.
 *
 * Note that custom translation files inside the BP Block Users plugin folder
 * will be removed on BP Block Users updates. If you're creating custom
 * translation files, please use the global language folder (ie - wp-content/languages/plugins).
 *
 * @return void
 */
function tba_bp_block_users_load_textdomain() {

	// Look in wp-content/plugins/bp-block-users/languages first
	// fallback to wp-content/languages/plugins
	load_plugin_textdomain( 'bp-block-users', false, dirname( __FILE__ ) . '/languages/' );
}
add_action( 'plugins_loaded', 'tba_bp_block_users_load_textdomain' );

/**
 * Loads the Block Users component into the $bp global.
 *
 * @since 0.2.0
 *
 * @return void
 */
function bp_block_users_setup_component() {

	buddypress()->block_users = new BP_Block_Users_Component( __FILE__ );

	/**
	 * Fires after the BP Block Users component is loaded.
	 *
	 * @since 0.2.0
	 */
	do_action( 'bp_block_users_loaded' );
}
