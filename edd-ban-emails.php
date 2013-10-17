<?php
/*
 Plugin Name: Easy Digital Downloads - Ban Emails
 Description: Allows you to place emails in a banned list. These emails will not be allowed to make purchases
 Author: Pippin Williamson
 License: GPL2+
 Version: 1.0
 */

class EDD_Ban_Emails {

	/**
	 * Get things going
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		if ( is_admin() ) {

			// Admin actions

			add_action( 'edd_tools_after', array( $this, 'admin' ) );
			add_action( 'edd_save_banned_emails', array( $this, 'save_emails' ) );

		}

		add_action( 'edd_checkout_error_checks', array( $this, 'check_purchase_email' ), 10, 2 );

	}

	/**
	 * Add a meta box to the Downloads > Tools page
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function admin() {
?>
		<div class="postbox">
			<h3><span><?php _e( 'Banned Emails', 'edd-banned-emails' ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Emails placed in the box below will not be allowed to make purchases.', 'edd-banned-emails' ); ?></p>
				<form method="post" action="<?php echo admin_url( 'edit.php?post_type=download&page=edd-tools' ); ?>">
					<p>
						<textarea name="banned_emails" rows="10" class="large-text"><?php echo implode( "\n", $this->get_banned_emails() ); ?></textarea>
						<span class="description"><?php _e( 'Enter emails to disallow, one per line', 'edd-banned-emails' ); ?></span>
					</p>
					<p>
						<input type="hidden" name="edd_action" value="save_banned_emails" />
						<?php wp_nonce_field( 'edd_banned_emails_nonce', 'edd_banned_emails_nonce' ); ?>
						<?php submit_button( __( 'Save', 'edd-banned-emails' ), 'secondary', 'submit', false ); ?>
					</p>
				</form>
			</div><!-- .inside -->
		</div><!-- .postbox -->
<?php
	}

	/**
	 * Retrieve an array of banned emails
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_banned_emails() {

		global $edd_options;

		$emails = ! empty( $edd_options['banned_emails'] ) ? $edd_options['banned_emails'] : array();

		return apply_filters( 'edd_get_banned_emails', $emails );
	}

	/**
	 * Save banned emails into EDD options
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function save_emails( $data ) {

		if ( ! wp_verify_nonce( $data['edd_banned_emails_nonce'], 'edd_banned_emails_nonce' ) )
			return;

		global $edd_options;

		// Sanitize the input
		$emails = array_map( 'trim', explode( "\n", $data['banned_emails'] ) );
		$emails = array_filter( array_map( 'is_email', $emails ) );

		$edd_options['banned_emails'] = $emails;
		update_option( 'edd_settings', $edd_options );

	}

	/**
	 * Check the purchase to ensure a banned email is not allowed through
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function check_purchase_email( $valid_data, $posted ) {

		$is_banned = false;
		$banned    = $this->get_banned_emails();

		if( empty( $banned ) )
			return; // No banned emails, get out

		if ( is_user_logged_in() ) {

			// The user is logged in, check that their account email is not banned
			$user_data = get_userdata( get_current_user_id() );
			if( in_array( $user_data->user_email, $banned ) ) {
				$is_banned = true;
			}

			if( in_array( $posted['edd_email'], $banned ) ) {
				$is_banned = true;
			}


		} elseif ( $posted['edd-purchase-var'] == 'needs-to-login' ) {

			// The user is logging in, check that their user account email is not banned
			$user_data = get_user_by( 'login', $posted['edd_user_login'] );
			if( $user_data && in_array( $user_data->user_email, $banned ) ) {
				$is_banned = true;
			}


		} else {

			// Guest purchase, check that the email is not banned
			if( in_array( $posted['edd_email'], $banned ) ) {
				$is_banned = true;
			}

		}

		if( $is_banned ) {
			// Set an error and give the customer a general error (don't alert them that they were banned)
			edd_set_error( 'email_banned', __( 'An internal error has occured, please try again or contact support.', 'edd-banned-emails' ) );
		}


	}

}
$edd_ban_emails = new EDD_Ban_emails;