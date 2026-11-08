<?php
/**
 * Native Bricks form action controls. No custom builder scripts.
 *
 * @package FormSubmitForBricks
 */

namespace FormSubmitForBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controls integration component.
 */
final class Controls {
	/**
	 * Add a group visible only for forms using FormSubmit.
	 *
	 * @param array $groups Existing groups.
	 * @return array
	 */
	public static function add_group( $groups ) {
		$groups['fsbrFormsubmit'] = array(
			'title'    => esc_html__( 'FormSubmit', 'native-formsubmit-for-bricks' ),
			'tab'      => 'content',
			'required' => array( 'actions', '=', 'fsbr-formsubmit' ),
		);
		return $groups;
	}

	/**
	 * Extend the existing action list and use Bricks field-mapping dropdowns.
	 *
	 * @param array $controls Existing controls.
	 * @return array
	 */
	public static function add_controls( $controls ) {
		$controls['actions']['options']['fsbr-formsubmit'] = esc_html__( 'FormSubmit', 'native-formsubmit-for-bricks' );
		$base                          = array(
			'tab'   => 'content',
			'group' => 'fsbrFormsubmit',
		);
		$controls['fsbrSetup']         = $base + array(
			'type'    => 'info',
			'content' => esc_html__( 'Enter a recipient or use the site default in Settings > Native FormSubmit for Bricks. Submit once, confirm the FormSubmit activation email, then submit again to check delivery. Enable Bricks spam protection. Remove the Email action if you only want FormSubmit delivery.', 'native-formsubmit-for-bricks' ),
		);
		$controls['fsbrRecipient']     = $base + array(
			'type'        => 'text',
			'label'       => esc_html__( 'Recipient email', 'native-formsubmit-for-bricks' ),
			'placeholder' => esc_html__( 'Use site default', 'native-formsubmit-for-bricks' ),
			'description' => esc_html__( 'One literal email address. Leave blank to use the site default. Dynamic tags and visitor-controlled recipients are not supported.', 'native-formsubmit-for-bricks' ),
		);
		$controls['fsbrSubject']       = $base + array(
			'type'        => 'text',
			'label'       => esc_html__( 'Email subject', 'native-formsubmit-for-bricks' ),
			'placeholder' => esc_html__( 'New website enquiry', 'native-formsubmit-for-bricks' ),
			'description' => esc_html__( 'Plain text, up to 200 characters.', 'native-formsubmit-for-bricks' ),
		);
		$controls['fsbrToken']         = $base + array(
			'type'        => 'text',
			'label'       => esc_html__( 'Private recipient token', 'native-formsubmit-for-bricks' ),
			'description' => esc_html__( 'Optional token from your FormSubmit activation email. Replaces the recipient email for this form. Keep it private and remove it before sharing exports.', 'native-formsubmit-for-bricks' ),
		);
		$controls['fsbrCc']            = $base + array(
			'type'        => 'text',
			'label'       => esc_html__( 'CC recipients', 'native-formsubmit-for-bricks' ),
			'description' => esc_html__( 'Optional comma-separated literal email addresses. Up to 20 recipients. These addresses receive copies of included values and attachments.', 'native-formsubmit-for-bricks' ),
		);
		$controls['fsbrReplyTo']       = $base + array(
			'type'        => 'select',
			'label'       => esc_html__( 'Reply-to field', 'native-formsubmit-for-bricks' ),
			'options'     => array(),
			'map_fields'  => array( 'type' => 'email' ),
			'placeholder' => esc_html__( 'First included email field', 'native-formsubmit-for-bricks' ),
			'description' => esc_html__( 'Optional. The selected email field must also be included below.', 'native-formsubmit-for-bricks' ),
		);
		$controls['fsbrFields']        = $base + array(
			'type'        => 'select',
			'label'       => esc_html__( 'Fields to include', 'native-formsubmit-for-bricks' ),
			'options'     => array(),
			'map_fields'  => true,
			'multiple'    => true,
			'placeholder' => esc_html__( 'All supported fields', 'native-formsubmit-for-bricks' ),
			'description' => esc_html__( 'Text, email, telephone, number, URL, textarea, rich text, checkbox, select, radio and date fields. Passwords, hidden fields, honeypots and media fields are never sent as text. Configure uploaded attachments separately below. Rich text is sent as plain text.', 'native-formsubmit-for-bricks' ),
		);
		$controls['fsbrTemplate']      = $base + array(
			'type'        => 'select',
			'label'       => esc_html__( 'Email layout', 'native-formsubmit-for-bricks' ),
			'placeholder' => esc_html__( 'Table', 'native-formsubmit-for-bricks' ),
			'options'     => array(
				'table' => esc_html__( 'Table', 'native-formsubmit-for-bricks' ),
				'basic' => esc_html__( 'Basic', 'native-formsubmit-for-bricks' ),
				'box'   => esc_html__( 'Box', 'native-formsubmit-for-bricks' ),
			),
		);
		$controls['fsbrAttachments']   = $base + array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Attach uploaded files', 'native-formsubmit-for-bricks' ),
			'description' => esc_html__( 'Opt in to sending files and images from native Bricks Files fields. Set allowed formats and limits on each Files field. Up to 10 MB combined and 20 files; your hosting limits also apply.', 'native-formsubmit-for-bricks' ),
		);
		$controls['fsbrFileFields']    = $base + array(
			'type'        => 'select',
			'label'       => esc_html__( 'Attachment fields', 'native-formsubmit-for-bricks' ),
			'options'     => array(),
			'map_fields'  => array( 'type' => 'file' ),
			'multiple'    => true,
			'placeholder' => esc_html__( 'All native Files fields', 'native-formsubmit-for-bricks' ),
			'required'    => array( 'fsbrAttachments', '=', true ),
			'description' => esc_html__( 'Leave empty to attach all native Files fields. Media-library Image and Gallery fields are not uploaded attachments. Bricks controls file storage and cleanup.', 'native-formsubmit-for-bricks' ),
		);
		$controls['fsbrBlacklist']     = $base + array(
			'type'        => 'textarea',
			'label'       => esc_html__( 'Blocked phrases', 'native-formsubmit-for-bricks' ),
			'description' => esc_html__( 'Optional FormSubmit blacklist: up to 20 phrases separated by commas or new lines. FormSubmit may silently discard matching submissions. Also enable native Bricks spam protection and honeypots.', 'native-formsubmit-for-bricks' ),
		);
		$controls['fsbrWebhook']       = $base + array(
			'type'        => 'text',
			'label'       => esc_html__( 'FormSubmit webhook URL', 'native-formsubmit-for-bricks' ),
			'description' => esc_html__( 'Optional public HTTPS endpoint. FormSubmit forwards submission data to this service. Credentials in URLs and private/local addresses are not accepted. Disclose the additional destination in your privacy notice.', 'native-formsubmit-for-bricks' ),
		);
		$controls['fsbrServiceLimits'] = $base + array(
			'type'    => 'info',
			'content' => esc_html__( 'Use Bricks native spam protection, honeypots, Success message and Redirect action. FormSubmit autoresponses require its hosted CAPTCHA flow and do not work with this AJAX integration. Its archive API is separate and does not retain uploaded files.', 'native-formsubmit-for-bricks' ),
		);
		$controls['fsbrPrivacy']       = $base + array(
			'type'    => 'info',
			'content' => esc_html__( 'Included values and enabled attachments are sent to FormSubmit.co, a third-party email service. Explain this in your privacy notice. This plugin stores no submissions. FormSubmit acceptance does not confirm inbox delivery. Use the native Bricks Success message and Redirect action for visitor feedback.', 'native-formsubmit-for-bricks' ),
		);
		return $controls;
	}
}
