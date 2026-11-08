<?php
/**
 * WordPress-native setup, privacy disclosure and deliberate test requests.
 *
 * @package FormSubmitForBricks
 */

namespace FormSubmitForBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin integration component.
 */
final class Admin {
	/**
	 * Register admin-only entry points.
	 *
	 * @param string $file Main plugin file.
	 * @return void
	 */
	public function register( $file ) {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
		add_action( 'admin_post_fsbr_test', array( $this, 'send_test' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( $file ), array( $this, 'links' ) );
	}

	/**
	 * Add a core Settings screen.
	 *
	 * @return void
	 */
	public function menu() {
		add_options_page( __( 'Native FormSubmit for Bricks', 'native-formsubmit-for-bricks' ), __( 'Native FormSubmit for Bricks', 'native-formsubmit-for-bricks' ), 'manage_options', 'native-formsubmit-for-bricks', array( $this, 'render' ) );
	}

	/**
	 * Register the option and privacy-policy suggestion.
	 *
	 * @return void
	 */
	public function settings() {
		register_setting(
			'fsbr_settings',
			Configuration::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);
		wp_add_privacy_policy_content(
			__( 'Native FormSubmit for Bricks', 'native-formsubmit-for-bricks' ),
			'<p>' . esc_html__( 'Suggested text: When you submit a contact form, we send the information and enabled file attachments you provide to FormSubmit.co to process and deliver your message by email. FormSubmit states that submissions are retained in its archive for 30 days. Our website and email provider may retain messages separately. Contact us for details about our retention policy. This plugin does not store submission records. Replace this text with a notice appropriate to your use.', 'native-formsubmit-for-bricks' ) . '</p><p><a href="https://formsubmit.co/privacy.pdf">' . esc_html__( 'FormSubmit privacy policy', 'native-formsubmit-for-bricks' ) . '</a></p>'
		);
	}

	/**
	 * Validate settings without converting a typo into a different recipient.
	 *
	 * @param mixed $input Incoming option.
	 * @return array
	 */
	public function sanitize( $input ) {
		$value = is_array( $input ) ? ( $input['recipient'] ?? '' ) : null;
		$email = Configuration::email( $value );
		if ( ! is_string( $value ) || ( '' !== trim( $value ) && '' === $email ) ) {
			add_settings_error( Configuration::OPTION, 'fsbr_recipient', __( 'Enter one valid recipient email address. Your previous setting has been kept.', 'native-formsubmit-for-bricks' ) );
			return array(
				'schema_version' => 1,
				'recipient'      => Configuration::default_recipient(),
			);
		}
		return array(
			'schema_version' => 1,
			'recipient'      => $email,
		);
	}

	/**
	 * Add a setup link beside the plugin entry.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=native-formsubmit-for-bricks' ) ) . '">' . esc_html__( 'Setup', 'native-formsubmit-for-bricks' ) . '</a>' );
		return $links;
	}

	/**
	 * Explain an inactive integration without crashing other themes.
	 *
	 * @return void
	 */
	public function dependency_notice() {
		if ( ! current_user_can( 'manage_options' ) || Plugin::compatible() ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Native FormSubmit for Bricks needs an active Bricks theme, version 1.12.2 or later. Its form action is currently unavailable.', 'native-formsubmit-for-bricks' ) . '</p></div>';
	}

	/**
	 * Render core controls and a short, explicit activation workflow.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Native FormSubmit for Bricks', 'native-formsubmit-for-bricks' ); ?></h1>
			<p><?php esc_html_e( 'Send native Bricks form messages through FormSubmit. No API key or custom code is needed.', 'native-formsubmit-for-bricks' ); ?></p>
			<?php
			settings_errors();
			// This code selects static notices only; it never establishes delivery or activation state.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only redirect result; no write or trusted state.
			$fsbr_result  = isset( $_GET['fsbr_result'] ) && is_string( $_GET['fsbr_result'] ) ? sanitize_key( wp_unslash( $_GET['fsbr_result'] ) ) : '';
			$fsbr_notices = array(
				'accepted' => __( 'FormSubmit accepted the test request. Check your inbox and spam folder. If an activation email arrives, confirm it and send another test. Inbox delivery has not been verified by this plugin.', 'native-formsubmit-for-bricks' ),
				'failed'   => __( 'FormSubmit did not confirm the test. Check the recipient, confirm any activation email, and check whether your host allows outgoing HTTPS requests to formsubmit.co.', 'native-formsubmit-for-bricks' ),
				'missing'  => __( 'Save a valid default recipient before sending a test.', 'native-formsubmit-for-bricks' ),
			);
			if ( isset( $fsbr_notices[ $fsbr_result ] ) ) {
				echo '<div class="notice notice-info"><p>' . esc_html( $fsbr_notices[ $fsbr_result ] ) . '</p></div>';
			}
			?>
			<h2><?php esc_html_e( '1. Choose your recipient', 'native-formsubmit-for-bricks' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'fsbr_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fsbr-recipient"><?php esc_html_e( 'Default recipient email', 'native-formsubmit-for-bricks' ); ?></label></th>
						<td>
							<input type="email" id="fsbr-recipient" name="fsbr_settings[recipient]" class="regular-text" maxlength="254" value="<?php echo esc_attr( Configuration::default_recipient() ); ?>" aria-describedby="fsbr-recipient-help" autocomplete="email">
							<p class="description" id="fsbr-recipient-help"><?php esc_html_e( 'Optional. Forms can override this address in Bricks. Only forms with the FormSubmit action send messages.', 'native-formsubmit-for-bricks' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save recipient', 'native-formsubmit-for-bricks' ) ); ?>
			</form>
			<h2><?php esc_html_e( '2. Confirm and test', 'native-formsubmit-for-bricks' ); ?></h2>
			<p><?php esc_html_e( 'This button sends a sample message and your site home URL to FormSubmit using your saved default recipient. Confirm the activation email if this is the first submission. Then send another test and check that it reaches your inbox.', 'native-formsubmit-for-bricks' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="fsbr_test">
				<?php
				wp_nonce_field( 'fsbr_send_test' );
				submit_button( __( 'Send test to saved recipient', 'native-formsubmit-for-bricks' ), 'secondary', 'submit', false, '' === Configuration::default_recipient() ? array( 'disabled' => 'disabled' ) : array() );
				?>
			</form>
			<h2><?php esc_html_e( '3. Connect your Bricks form', 'native-formsubmit-for-bricks' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Edit a Form element in Bricks. Under Actions, add FormSubmit.', 'native-formsubmit-for-bricks' ); ?></li>
				<li><?php esc_html_e( 'Open the FormSubmit group. Use the site default or enter a recipient, set a subject, and optionally choose the reply-to field and included fields.', 'native-formsubmit-for-bricks' ); ?></li>
				<li><?php esc_html_e( 'Remove the Email action if FormSubmit is your only delivery service. Set the native Bricks Success message and optional Redirect action.', 'native-formsubmit-for-bricks' ); ?></li>
				<li><?php esc_html_e( 'Enable Bricks spam protection, save, and test the published frontend form. Forms do not submit inside the builder.', 'native-formsubmit-for-bricks' ); ?></li>
			</ol>
			<h2><?php esc_html_e( 'Privacy and service details', 'native-formsubmit-for-bricks' ); ?></h2>
			<p><?php esc_html_e( 'Enabling this action sends included field values, enabled attachments, the recipient address and the site home URL to FormSubmit.co. Optional CC recipients and webhooks receive submission data too. The plugin stores no submission records, adds no frontend scripts, and does not send messages until you run a configured form or press the test button. FormSubmit states that it retains submissions for 30 days. Update your privacy notice before collecting messages.', 'native-formsubmit-for-bricks' ); ?></p>
			<p><?php esc_html_e( 'Passwords, hidden fields and honeypots are excluded. Attachments are opt-in, use native Bricks Files fields, require Bricks 2.4.3+, and have a combined 10 MB limit. Bricks controls upload storage and cleanup. FormSubmit browser CAPTCHA and autoresponses are not available through AJAX; configure native Bricks spam protection and confirmation email if needed. Request acceptance does not guarantee inbox delivery. A timed-out request may already have reached the service; retrying could send a duplicate.', 'native-formsubmit-for-bricks' ); ?></p>
			<p><a href="https://formsubmit.co/documentation"><?php esc_html_e( 'FormSubmit documentation', 'native-formsubmit-for-bricks' ); ?></a> | <a href="https://formsubmit.co/privacy.pdf"><?php esc_html_e( 'Privacy policy and terms of service', 'native-formsubmit-for-bricks' ); ?></a></p>
		</div>
		<?php
	}

	/**
	 * Send an administrator-requested synthetic test with capability and CSRF checks.
	 *
	 * @return void
	 */
	public function send_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to send a test.', 'native-formsubmit-for-bricks' ), '', array( 'response' => 403 ) );
		}
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			wp_die( esc_html__( 'Use the test button on the setup screen.', 'native-formsubmit-for-bricks' ), '', array( 'response' => 405 ) );
		}
		check_admin_referer( 'fsbr_send_test' );
		$recipient = Configuration::default_recipient();
		$result    = 'missing';
		if ( '' !== $recipient ) {
			require_once __DIR__ . '/class-client.php';
			$sent   = ( new Client() )->send(
				$recipient,
				array(
					'_subject'  => __( 'Native FormSubmit for Bricks: test message', 'native-formsubmit-for-bricks' ),
					'message'   => __( 'This is a test requested by your website administrator. If you received this email, delivery worked for this test.', 'native-formsubmit-for-bricks' ),
					'website'   => home_url( '/' ),
					'_template' => 'table',
					'_captcha'  => 'false',
				)
			);
			$result = is_wp_error( $sent ) ? 'failed' : 'accepted';
		}
		wp_safe_redirect( add_query_arg( 'fsbr_result', $result, admin_url( 'options-general.php?page=native-formsubmit-for-bricks' ) ) );
		exit;
	}
}
