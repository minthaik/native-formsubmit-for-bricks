<?php
/**
 * Build a bounded payload from configured, supported fields only.
 *
 * @package FormSubmitForBricks
 */

namespace FormSubmitForBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payload integration component.
 */
final class Payload {
	const TYPES = array( 'text', 'email', 'tel', 'number', 'url', 'textarea', 'richtext', 'checkbox', 'select', 'radio', 'datepicker' );

	/**
	 * Create the provider payload, excluding transport data and unsafe field types.
	 *
	 * @param array $settings Trusted form settings.
	 * @param array $values Validated form values keyed by canonical field ID.
	 * @return array|\WP_Error
	 */
	public function build( $settings, $values ) {
		$fields   = $settings['fields'] ?? array();
		$selected = $settings['fsbrFields'] ?? array();
		$reply_id = $settings['fsbrReplyTo'] ?? '';
		if ( ! is_array( $fields ) || count( $fields ) > 100 || ! is_array( $selected ) || ! is_string( $reply_id ) ) {
			return $this->configuration_error();
		}
		foreach ( $selected as $selected_id ) {
			if ( ! is_string( $selected_id ) ) {
				return $this->configuration_error();
			}
		}
		$payload  = array();
		$included = array();
		$reply    = '';
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || ! is_string( $field['id'] ?? null ) || ! is_string( $field['type'] ?? null ) ) {
				continue;
			}
			$id = $field['id'];
			if ( '' === $id || isset( $field['isHoneypot'] ) || ! in_array( $field['type'], self::TYPES, true ) || ( $selected && ! in_array( $id, $selected, true ) ) ) {
				continue;
			}
			if ( isset( $included[ $id ] ) ) {
				return $this->configuration_error();
			}
			$value = $this->value( $values[ 'form-field-' . $id ] ?? '' );
			if ( is_wp_error( $value ) ) {
				return $value;
			}
			$included[ $id ] = true;
			$label           = is_string( $field['label'] ?? null ) ? sanitize_text_field( $field['label'] ) : '';
			$label           = mb_substr( $label, 0, 100 );
			// Prevent labels from becoming provider directives or the special email field.
			if ( '' === $label || '_' === substr( $label, 0, 1 ) || 'email' === strtolower( $label ) || preg_match( '/^attachment[0-9]*$/i', $label ) ) {
				/* translators: %s: Bricks field ID. */
				$label = sprintf( __( 'Field %s', 'native-formsubmit-for-bricks' ), $id );
			}
			$key    = $label;
			$suffix = 2;
			while ( array_key_exists( $key, $payload ) ) {
				$key = $label . ' (' . $suffix . ')';
				++$suffix;
			}
			$payload[ $key ] = $value;
			if ( 'email' === $field['type'] && ( $reply_id === $id || ( '' === $reply_id && '' === $reply ) ) ) {
				$candidate = Configuration::email( $value );
				if ( '' !== $value && '' === $candidate ) {
					return new \WP_Error( 'fsbr_email', __( 'Please enter a valid email address.', 'native-formsubmit-for-bricks' ) );
				}
				$reply = $candidate;
			}
		}
		if ( ! $included || array_diff( $selected, array_keys( $included ) ) || ( '' !== $reply_id && ( ! isset( $included[ $reply_id ] ) || '' === $reply ) ) ) {
			return $this->configuration_error();
		}
		$subject = $settings['fsbrSubject'] ?? '';
		$layout  = $settings['fsbrTemplate'] ?? 'table';
		if ( ! is_string( $subject ) || ! is_string( $layout ) || ! in_array( $layout, array( '', 'table', 'basic', 'box' ), true ) ) {
			return $this->configuration_error();
		}
		$payload['_subject']  = mb_substr( sanitize_text_field( $subject ), 0, 200 );
		$payload['_subject']  = '' === $payload['_subject'] ? __( 'New website enquiry', 'native-formsubmit-for-bricks' ) : $payload['_subject'];
		$payload['_template'] = '' === $layout ? 'table' : $layout;
		// A server-side AJAX endpoint cannot present the provider's browser CAPTCHA.
		$payload['_captcha'] = 'false';
		if ( '' !== $reply ) {
			$payload['email']    = $reply;
			$payload['_replyto'] = $reply;
		}
		return $payload;
	}

	/**
	 * Normalize scalar or flat multi-select values, preserving line breaks and zero.
	 *
	 * @param mixed $value Submitted value.
	 * @return string|\WP_Error
	 */
	private function value( $value ) {
		$values = is_array( $value ) ? $value : array( $value );
		if ( count( $values ) > 100 ) {
			return $this->configuration_error();
		}
		$clean = array();
		foreach ( $values as $item ) {
			if ( ! is_scalar( $item ) && null !== $item ) {
				return $this->configuration_error();
			}
			$clean[] = sanitize_textarea_field( (string) $item );
		}
		$result = implode( ', ', $clean );
		if ( strlen( $result ) > 10000 ) {
			return new \WP_Error( 'fsbr_size', __( 'Your message is too long. Please shorten it and try again.', 'native-formsubmit-for-bricks' ) );
		}
		return $result;
	}

	/**
	 * Return a safe public error without revealing internal settings.
	 *
	 * @return \WP_Error
	 */
	private function configuration_error() {
		return new \WP_Error( 'fsbr_configuration', __( 'This form is not ready to send. Please contact the website owner directly.', 'native-formsubmit-for-bricks' ) );
	}
}
