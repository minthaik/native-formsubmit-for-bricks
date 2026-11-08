<?php
/**
 * Bounded multipart encoding for WordPress's portable HTTP transport.
 *
 * @package FormSubmitForBricks
 */

namespace FormSubmitForBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encode validated metadata and local upload bytes; never fetch file URLs.
 */
final class Multipart {
	/**
	 * Encode scalar fields and validated file descriptors.
	 *
	 * @param array $payload Validated scalar provider payload.
	 * @param array $files Validated attachment descriptors.
	 * @return array|\WP_Error
	 */
	public function encode( $payload, $files ) {
		$boundary = 'fsbr_' . wp_generate_password( 40, false, false );
		$body     = '';
		foreach ( $payload as $key => $value ) {
			$body .= '--' . $boundary . "\r\nContent-Disposition: form-data; name=\"" . $this->header( $key ) . "\"\r\n\r\n" . $value . "\r\n";
		}
		foreach ( $files as $index => $file ) {
			// Suppress low-level read warnings so a file race cannot expose server paths.
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Validated local upload path only; never a remote URL. Read is bounded and errors are sanitized.
			$bytes = @file_get_contents( $file['path'], false, null, 0, $file['size'] + 1 );
			if ( false === $bytes || strlen( $bytes ) !== $file['size'] ) {
				return new \WP_Error( 'fsbr_files_read', __( 'An attachment changed or could not be read. Please choose the files again.', 'native-formsubmit-for-bricks' ) );
			}
			$name  = 0 === $index ? 'attachment' : 'attachment' . ( $index + 1 );
			$body .= '--' . $boundary . "\r\nContent-Disposition: form-data; name=\"" . $name . '"; filename="' . $this->header( $file['name'] ) . "\"\r\nContent-Type: " . $this->header( $file['type'] ) . "\r\n\r\n" . $bytes . "\r\n";
		}
		$body .= '--' . $boundary . "--\r\n";
		return array(
			'body'        => $body,
			'contentType' => 'multipart/form-data; boundary=' . $boundary,
		);
	}

	/**
	 * Prevent CRLF, quote and backslash injection into multipart headers.
	 *
	 * @param string $value Header parameter.
	 * @return string
	 */
	private function header( $value ) {
		return str_replace( array( "\r", "\n", '"', '\\', "\0" ), '_', (string) $value );
	}
}
