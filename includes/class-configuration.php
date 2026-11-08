<?php
/**
 * Recipient configuration shared by setup and submission.
 *
 * @package FormSubmitForBricks
 */

namespace FormSubmitForBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configuration integration component.
 */
final class Configuration {
	const OPTION = 'fsbr_settings';

	/**
	 * Read a literal email without silently rewriting invalid input.
	 *
	 * @param mixed $value Email input.
	 * @return string
	 */
	public static function email( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$value = trim( $value );
		return strlen( $value ) <= 254 && is_email( $value ) ? $value : '';
	}

	/**
	 * Read the optional site default. No administrator-email fallback is used.
	 *
	 * @return string
	 */
	public static function default_recipient() {
		$settings = get_option( self::OPTION, array() );
		return is_array( $settings ) ? self::email( $settings['recipient'] ?? '' ) : '';
	}

	/**
	 * Resolve the authored form setting, never a submitted routing field.
	 *
	 * @param array $settings Trusted Bricks settings.
	 * @return string
	 */
	public static function recipient( $settings ) {
		$value = $settings['fsbrRecipient'] ?? '';
		if ( ! is_string( $value ) ) {
			return '';
		}
		return '' === trim( $value ) ? self::default_recipient() : self::email( $value );
	}

	/**
	 * Validate a provider-issued private recipient token.
	 *
	 * @param mixed $value Token input.
	 * @return string
	 */
	public static function token( $value ) {
		return is_string( $value ) && preg_match( '/^[a-zA-Z0-9_-]{8,200}$/D', $value ) ? $value : '';
	}

	/**
	 * Resolve a saved token override or the existing literal recipient.
	 *
	 * @param array $settings Saved settings.
	 * @return string
	 */
	public static function destination( $settings ) {
		$token = $settings['fsbrToken'] ?? '';
		return '' === $token ? self::recipient( $settings ) : self::token( $token );
	}
}
