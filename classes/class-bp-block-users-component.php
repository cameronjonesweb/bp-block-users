<?php

/**
 * BP Block Users Component class.
 *
 * @package BP_Block_Users
 * @subpackage Component
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'BP_Component' ) ) {

	/**
	 * The BP Block Users Component Class.
	 *
	 * @since 0.2.0
	 */
	class BP_Block_Users_Component extends BP_Component {

		/**
		 * The path to BP Block Users includes.
		 *
		 * @since 0.2.0
		 *
		 * @var string $includes_dir
		 */
		public $includes_dir = '';

		/**
		 * The path to BP Block Users classes.
		 *
		 * @since 0.2.0
		 *
		 * @var string $classes_dir
		 */
		public $classes_dir = '';

		/** Methods ***************************************************************/

		/**
		 * Constructor.
		 *
		 * @since 0.2.0
		 *
		 * @uses plugin_dir_path() BP Block Users directory.
		 * @uses trailingslashit() To add a trailingslash.
		 *
		 * @param string $file The main BP Block Users file.
		 */
		public function __construct( $file = '' ) {

			// Let's start the show!
			parent::start(
				'block_users',
				__( 'Block Users', 'bp-block-users' ),
				plugin_dir_path( $file ),
				array()
			);

			// Extra directory properties.
			$this->includes_dir = $this->path . 'includes/';
			$this->classes_dir  = $this->path . 'classes/';

			// Include our files.
			$this->includes();

			// Setup actions.
			$this->setup_actions();
		}

		/**
		 * Include required files.
		 *
		 * @since 0.2.0
		 *
		 * @param array $includes An array of file names, or file name chunks,
		 *                        to be parsed and then included.
		 *
		 * @return void
		 */
		public function includes( $includes = array() ) {

			require $this->includes_dir . 'functions.php';
			require $this->includes_dir . 'template.php';
			require $this->includes_dir . 'theme-compat.php';

			if ( is_admin() ) {
				require $this->includes_dir . 'admin.php';
			}

			parent::includes( $includes );
		}

		/**
		 * Set up the actions.
		 *
		 * @since 0.2.0
		 *
		 * @uses add_action() To add various actions.
		 * @uses BP_Component::setup_actions() Calls `bp_block_users_setup_actions`.
		 */
		public function setup_actions() {

			/** Filters ***********************************************************/

			// Set all notification emails to "no".
			add_filter( 'get_user_metadata', array( $this, 'block_notifications' ), 10, 4 );

			// Add the BP Block Users template to template stack.
			add_filter( 'bp_located_template', 'tba_bp_block_user_settings_load_template_filter', 10, 2 );

			/** Actions ***********************************************************/

			// Add block user settings sub nav.
			add_action( 'bp_settings_setup_nav', array( $this, 'setup_settings_sub_nav' ) );

			// Add the our admin bar link.
			add_action( 'admin_bar_menu', array( $this, 'setup_settings_admin_bar' ), 100 );

			// Prevent the login of a blocked user.
			add_action( 'authenticate', array( $this, 'prevent_blocked_user_login' ), 40 );

			// Block/unblock user when editing from profile.
			add_action( 'bp_actions', array( $this, 'block_user_settings_action' ) );


			parent::setup_actions();
		}

		/** Navigation ************************************************************/

		/**
		 * Add the BP Block Users settings sub nav.
		 *
		 * @since 0.2.0
		 *
		 * @uses bp_current_user_can() To check the `bp_moderate` capability.
		 * @uses bp_is_my_profile() To check if logged in user is viewing own profile.
		 * @uses bp_displayed_user_domain() To get the displayed user domain.
		 * @uses bp_get_settings_slug() To get the BP settings slug.
		 * @uses trailingslashit() To add a trailingslash to the settings link.
		 * @uses bp_displayed_user_id() To get the displayed user id.
		 * @uses is_super_admin() To check if current user is super admin.
		 * @uses bp_core_new_subnav_item() To add the `block-users` sub-nav.
		 *
		 * @return void
		 */
		public function setup_settings_sub_nav() {

			// Only show for those with `bp_moderate` or if you're not on your own profile.
			if ( ! bp_current_user_can( 'bp_moderate' ) || bp_is_my_profile() ) {
				return;
			}

			// Get the displayed user domain, or bail.
			if ( bp_displayed_user_domain() ) {
				$user_domain = bp_displayed_user_domain();
			} else {
				return;
			}

			// Set up the settings link.
			$slug          = bp_get_settings_slug();
			$settings_link = trailingslashit( $user_domain . $slug );

			// Set up the sub nav args array.
			$nav = array(
				'name'            => __( 'Block User', 'bp-block-users' ),
				'slug'            => 'block-user',
				'parent_url'      => $settings_link,
				'parent_slug'     => $slug,
				'screen_function' => 'tba_bp_settings_screen_block_user',
				'position'        => 85,
				'user_has_access' => ! is_super_admin( bp_displayed_user_id() ),
			);

			// Add the sub nav.
			bp_core_new_subnav_item( $nav );
		}

		/**
		 * Add the `Block User` link to the WP Admin Bar.
		 *
		 * @since 0.2.0
		 *
		 * @uses bp_is_user() To check if we're viewing a user page.
		 * @uses bp_current_user_can() To check the `bp_moderate` capability.
		 * @uses bp_is_my_profile() To check if logged in user is viewing own profile.
		 * @uses buddypress() To get the BP object.
		 * @uses bp_is_active() To check if the `settings` component is active.
		 * @uses WP_Admin_Bar::add_menu() To add the `Block User` link to the WP Admin Bar.
		 * @uses bp_displayed_user_domain() To get the displayed user domain.
		 *
		 * @return void
		 */
		public function setup_settings_admin_bar() {

			// Only show if viewing a user.
			if ( ! bp_is_user() ) {
				return;
			}

			// Don't show this menu to non site admins or if you're viewing your own profile.
			if ( ! bp_current_user_can( 'bp_moderate' ) || bp_is_my_profile() ) {
				return;
			}

			global $wp_admin_bar;

			// Set up the BP global.
			$user_admin_menu_id = buddypress()->user_admin_menu_id;

			// Add our `Block User` link to the WP admin bar.
			if ( bp_is_active( 'settings' ) ) {
				// User Admin > Block User.
				$wp_admin_bar->add_menu( array(
					'parent' => $user_admin_menu_id,
					'id'     => $user_admin_menu_id . '-block-user',
					'title'  => __( 'Block User', 'bp-block-users' ),
					'href'   => bp_displayed_user_domain() . 'settings/block-user/'
				) );
			}
		}

		/** Cache *****************************************************************/

		/**
		 * Setup cache groups
		 *
		 * @since 0.2.0
		 */
		public function setup_cache_groups() {

			// Global groups.
			wp_cache_add_global_groups( array(
				'bp_block_users'
			) );

			parent::setup_cache_groups();
		}

		/** Notification Emails ***************************************************/

		/**
		 * Prevent email notifications for blocked users.
		 *
		 * @since 0.2.0
		 *
		 * @param mixed  $retval   Null or new short-circuited meta value.
		 * @param int    $user_id  The user id.
		 * @param string $meta_key The meta key.
		 * @param bool   $single   Whether to return an array, or the the meta value.
		 *
		 * @uses apply_filters() To call the `tba_bp_block_users_block_notifications_meta_keys`
		 *                       and `tba_bp_block_users_block_notifications_value` filters.
		 * @uses bp_get_user_meta_key() To get a filtered version of the meta key.
		 * @uses tba_bp_is_user_blocked() To check if specified user is blocked.
		 *
		 * @return mixed `no` if blocking a user email notification.
		 */
		public function block_notifications( $retval, $user_id, $meta_key, $single ) {

			// Bail early if we have no user id or meta key.
			if ( empty( $user_id ) || empty( $meta_key ) ) {
				return $retval;
			}

			/**
			 * Filters the array of notification meta keys to block.
			 *
			 * @since 0.1.0
			 *
			 * @param array $keys MySQL expiration timestamp. Unix if `$int` is
			 */
			$keys = apply_filters(
				'tba_bp_block_users_block_notifications_meta_keys',
				array_map( 'bp_get_user_meta_key', array(
					'notification_activity_new_mention',
					'notification_activity_new_reply',
					'notification_friends_friendship_request',
					'notification_friends_friendship_accepted',
					'notification_groups_invite',
					'notification_groups_group_updated',
					'notification_groups_admin_promotion',
					'notification_groups_membership_request',
					'notification_messages_new_message',
				) )
			);

			// Bail if we're not checking a notification key.
			if ( ! in_array( $meta_key, $keys ) ) {
				return $retval;
			}

			// If the value is not already `no` and the user is blocked, set to `no`.
			if ( 'no' !== $retval && tba_bp_is_user_blocked( $user_id ) ) {
				$retval = 'no';
			}

			/**
			 * Filters the return of the notification meta value.
			 *
			 * @since 0.1.0
			 *
			 * @param mixed  $retval   Null or new short-circuited meta value.
			 * @param int    $user_id  The user id.
			 * @param string $meta_key The meta key.
			 * @param bool   $single   Whether to return an array, or the the meta value.
			 */
			return apply_filters( 'tba_bp_block_users_block_notifications_value', $retval, $user_id, $meta_key, $single );
		}

		/** Authentication ********************************************************/

		/**
		 * Prevents the login of a blocked user.
		 *
		 * @since 0.2.0
		 *
		 * @param null|WP_User $user The WP_User object being authenticated.
		 *
		 * @uses is_wp_error() To for a WP_Error object.
		 * @uses tba_bp_is_user_blocked() To check if specified user is blocked.
		 * @uses tba_bp_get_blocked_user_expiration() To get the blocked user expiration time.
		 * @uses WP_Error() To add the `tba_bp_authentication_blocked` error message.
		 * @uses apply_filters() To call the `tba_bp_prevent_blocked_user_login` hook.
		 *
		 * @return WP_User|WP_Error WP_User object if not blocked. WP_Error object,
		 *                          otherwise. Passed by reference.
		 */
		public function prevent_blocked_user_login( $user = null ) {

			// Bail early if login has already failed.
			if ( is_wp_error( $user ) || empty( $user ) ) {
				return $user;
			}

			// Bail if no user id.
			if ( ! ( $user instanceof WP_User ) ) {
				return $user;
			}

			// Set the user id.
			$user_id = (int) $user->ID;

			// If the user is blocked, set the wp-login.php error message.
			if ( tba_bp_is_user_blocked( $user_id ) ) {

				// Set the default message.
				$message = __( '<strong>ERROR</strong>: This account has been blocked.', 'bp-block-users' );

				// Check to see if this is a temporary block.
				$expiration = tba_bp_get_blocked_user_expiration( $user_id, true );
				if ( ! empty( $expiration ) ) {
					$message = __( '<strong>ERROR</strong>: This account has been temporarily blocked.', 'bp-block-users' );
				}

				// Set an error object to short-circuit the authentication process.
				$user = new WP_Error( 'tba_bp_authentication_blocked', $message );
			}

			/**
			 * Filters the return of the authenticating user object.
			 *
			 * @since 0.2.0
			 *
			 * @param WP_User|WP_Error $user    WP_User object if not blocked. WP_Error
			 *                                  object, otherwise.
			 * @param int              $user_id Whether this is a user update.
			 */
			return apply_filters( 'tba_bp_prevent_blocked_user_login', $user, $user_id );
		}

		/** Settings Actions ******************************************************/

		/**
		 * Block/unblock a user when editing from a BP profile page.
		 *
		 * @since 0.2.0
		 *
		 * @uses bp_is_settings_component() To check if we're on a settings component page.
		 * @uses bp_is_current_action() To check if we're on the `block-user` action page.
		 * @uses bp_action_variables() To check if there are extra action variables.
		 * @uses bp_do_404() To trigger a 404.
		 * @uses bp_current_user_can() To check the `bp_moderate` capability.
		 * @uses bp_is_my_profile() To check if logged in user is viewing own profile.
		 * @uses check_admin_referer() To check the `block-user` nonce.
		 * @uses do_action() To call the `tba_bp_settings_block_user_before_save` and
		 *                   `tba_bp_settings_block_user_after_save` hooks.
		 * @uses sanitize_key() To sanitize the `block-user-unit` $_POST key.
		 * @uses bp_displayed_user_id() To get the displayed user id.
		 * @uses tba_bp_block_user() To block the specified user.
		 * @uses tba_bp_unblock_user() To unblock the specified user.
		 *
		 * @return void
		 */
		public function block_user_settings_action() {

			// Bail if not a POST action.
			if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
				return;
			}

			// Bail if no submit action.
			if ( ! isset( $_POST['block-user-submit'] ) ) {
				return;
			}

			// Bail if not in settings or the `block-user` action.
			if ( ! bp_is_settings_component() || ! bp_is_current_action( 'block-user' ) ) {
				return;
			}

			// 404 if there are any additional action variables attached.
			if ( bp_action_variables() ) {
				bp_do_404();
				return;
			}

			// If can't `bp_moderate` or on own profile, bail.
			if ( ! bp_current_user_can( 'bp_moderate' ) || bp_is_my_profile() ) {
				return;
			}

			// Nonce check.
			check_admin_referer( 'block-user' );

			/**
			 * Fires before the block user settings have been saved.
			 *
			 * @since 0.1.0
			 */
			do_action( 'tba_bp_settings_block_user_before_save' );

			// Sanitize our $_POST variables.
			$block  = isset( $_POST['block-user'] ) ? absint( $_POST['block-user'] ) : 0;
			$length = isset( $_POST['block-user-length'] ) ? absint( $_POST['block-user-length'] ) : 0;
			$unit   = isset( $_POST['block-user-unit'] ) ? sanitize_key( $_POST['block-user-unit'] ) : 'indefintely';

			// Block/unblock the user.
			if ( ! empty( $block ) ) {
				tba_bp_block_user( bp_displayed_user_id(), $length, $unit );
			} else {
				tba_bp_unblock_user( bp_displayed_user_id() );
			}

			/**
			 * Fires after the block user settings have been saved.
			 *
			 * @since 0.1.0
			 */
			do_action( 'tba_bp_settings_block_user_after_save' );
		}
	}
} // End class exists.