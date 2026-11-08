<?php
/**
 * Validate authored FormSubmit options separately from submitted visitor values.
 *
 * @package FormSubmitForBricks
 */

namespace FormSubmitForBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compatible FormSubmit submission options.
 */
final class Provider_Options {
	/**
	 * Return validated optional directives.
	 *
	 * @param array $settings Saved Bricks form settings.
	 * @return array|\WP_Error
	 */
	public function build( $settings ) {
		$result = array();
		$cc     = $settings['fsbrCc'] ?? '';
		$terms  = $settings['fsbrBlacklist'] ?? '';
		$url    = $settings['fsbrWebhook'] ?? '';
		if ( ! is_string( $cc ) || ! is_string( $terms ) || ! is_string( $url ) || strlen( $cc ) > 5000 || strlen( $terms ) > 2000 || strlen( $url ) > 2000 ) {
			return $this->error();
		}
		if ( '' !== trim( $cc ) ) {
			$emails = array_map( 'trim', explode( ',', $cc ) );
			if ( count( $emails ) > 20 ) {
				return $this->error();
			}
			foreach ( $emails as $email ) {
				if ( '' === Configuration::email( $email ) ) {
					return $this->error();
				}
			}
			$result['_cc'] = implode( ',', array_unique( $emails ) );
		}
		if ( '' !== trim( $terms ) ) {
			$phrases = array_filter( array_map( 'trim', preg_split( '/[,\r\n]+/', sanitize_textarea_field( $terms ) ) ), 'strlen' );
			if ( count( $phrases ) > 20 ) {
				return $this->error();
			}
			$result['_blacklist'] = implode( ',', array_unique( $phrases ) );
		}
		if ( '' !== trim( $url ) ) {
			$url   = trim( $url );
			$parts = wp_parse_url( $url );
			if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['fragment'] ) || ! wp_http_validate_url( $url ) ) {
				return $this->error();
			}
			$host = trim( $parts['host'] ?? '', '[]' );
			if ( filter_var( $host, FILTER_VALIDATE_IP ) && ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return $this->error();
			}
			$result['_webhook'] = $url;
		}
		return $result;
	}

	/**
	 * Avoid leaking configuration into public form errors.
	 *
	 * @return \WP_Error
	 */
	private function error() {
		return new \WP_Error( 'fsbr_options', __( 'This form is not ready to send. Please contact the website owner directly.', 'native-formsubmit-for-bricks' ) );
	}
}
