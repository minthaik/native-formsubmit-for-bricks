<?php
/**
 * Fixed-destination HTTP transport and strict provider response handling.
 *
 * @package FormSubmitForBricks
 */

namespace FormSubmitForBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Client integration component.
 */
final class Client {
	/**
	 * Send once, without redirects or automatic retries.
	 *
	 * @param string $recipient Literal recipient email or provider-issued token.
	 * @param array  $payload Provider payload.
	 * @param array  $files Validated attachment descriptors.
	 * @return true|\WP_Error Acceptance, not proof of inbox delivery.
	 */
	public function send( $recipient, $payload, $files = array() ) {
		if ( '' === Configuration::email( $recipient ) && '' === Configuration::token( $recipient ) ) {
			return new \WP_Error( 'fsbr_recipient', __( 'This form is not ready to send. Please contact the website owner directly.', 'native-formsubmit-for-bricks' ) );
		}
		$receipt = '';
		if ( $files ) {
			// Normal multipart endpoint supports files; its AJAX endpoint drops file parts.
			// Never follow this URL. An exact unique Location confirms provider acceptance.
			$receipt          = add_query_arg( 'fsbr_receipt', wp_generate_password( 32, false, false ), home_url( '/' ) );
			$payload['_next'] = $receipt;
		}
		$body = wp_json_encode( $payload );
		if ( false === $body || strlen( $body ) > 65536 ) {
			return new \WP_Error( 'fsbr_size', __( 'Your message is too long. Please shorten it and try again.', 'native-formsubmit-for-bricks' ) );
		}
		$content_type = 'application/json; charset=utf-8';
		if ( $files ) {
			$multipart = ( new Multipart() )->encode( $payload, $files );
			if ( is_wp_error( $multipart ) ) {
				return $multipart;
			}
			$body         = $multipart['body'];
			$content_type = $multipart['contentType'];
		}
		$response = wp_safe_remote_post(
			'https://formsubmit.co/' . ( $files ? '' : 'ajax/' ) . rawurlencode( $recipient ),
			array(
				'headers'             => array(
					'Content-Type' => $content_type,
					'Accept'       => $files ? 'text/html' : 'application/json',
					'Referer'      => home_url( '/' ),
				),
				'body'                => $body,
				'timeout'             => $files ? 30 : 15,
				'redirection'         => 0,
				'sslverify'           => true,
				'limit_response_size' => 16384,
			)
		);
		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'fsbr_transport', __( 'The message could not be confirmed. Please try again later or contact us directly.', 'native-formsubmit-for-bricks' ) );
		}
		$status = wp_remote_retrieve_response_code( $response );
		if ( $files ) {
			if ( in_array( $status, array( 302, 303 ), true ) && wp_remote_retrieve_header( $response, 'location' ) === $receipt ) {
				return true;
			}
			return new \WP_Error( 'fsbr_files_rejected', __( 'The attachment message could not be accepted. Confirm the recipient activation and try again, or contact us directly.', 'native-formsubmit-for-bricks' ) );
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 || ! is_array( $data ) || ! in_array( $data['success'] ?? null, array( true, 'true' ), true ) ) {
			return new \WP_Error( 'fsbr_rejected', __( 'The message could not be accepted. Please contact us directly if this continues.', 'native-formsubmit-for-bricks' ) );
		}
		return true;
	}
}
