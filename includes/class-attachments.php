<?php
/**
 * Select bounded, server-validated native Bricks uploads.
 *
 * @package FormSubmitForBricks
 */

namespace FormSubmitForBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Attachment selection and local file boundary.
 */
final class Attachments {
	const MAX_BYTES = 10000000;
	const MAX_FILES = 20;

	/**
	 * Read only upload metadata produced by Bricks for configured Files fields.
	 *
	 * @param array $settings Saved form settings.
	 * @param array $uploads Bricks get_uploaded_files() result, never request values.
	 * @return array|\WP_Error
	 */
	public function collect( $settings, $uploads ) {
		if ( empty( $settings['fsbrAttachments'] ) ) {
			return array();
		}
		if ( ! defined( 'BRICKS_VERSION' ) || version_compare( BRICKS_VERSION, '2.4.3', '<' ) ) {
			return new \WP_Error( 'fsbr_files_version', __( 'File attachments require Bricks 2.4.3 or later.', 'native-formsubmit-for-bricks' ) );
		}
		$selected = $settings['fsbrFileFields'] ?? array();
		$fields   = $settings['fields'] ?? array();
		if ( ! is_array( $uploads ) || ! is_array( $selected ) || ! is_array( $fields ) || count( $fields ) > 100 || count( $selected ) > 100 ) {
			return $this->error();
		}
		foreach ( $selected as $id ) {
			if ( ! is_string( $id ) ) {
				return $this->error();
			}
		}
		$directory = wp_upload_dir();
		$root      = realpath( $directory['basedir'] );
		if ( false === $root ) {
			return $this->error();
		}
		$root       = trailingslashit( wp_normalize_path( $root ) );
		$result     = array();
		$included   = array();
		$seen_paths = array();
		$seen_names = array();
		$total      = 0;
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || 'file' !== ( $field['type'] ?? '' ) || isset( $field['isHoneypot'] ) || ! is_string( $field['id'] ?? null ) ) {
				continue;
			}
			$id = $field['id'];
			if ( $selected && ! in_array( $id, $selected, true ) ) {
				continue;
			}
			$name = ! empty( $field['name'] ) ? $field['name'] : 'form-field-' . $id;
			if ( ! is_string( $name ) || isset( $seen_names[ $name ] ) || isset( $included[ $id ] ) ) {
				return $this->error();
			}
			$seen_names[ $name ] = true;
			$included[ $id ]     = true;
			$files               = $uploads[ $name ] ?? array();
			if ( ! is_array( $files ) || ( ! empty( $field['fileUploadLimit'] ) && count( $files ) > (int) $field['fileUploadLimit'] ) ) {
				return $this->error();
			}
			foreach ( $files as $file ) {
				if ( ! is_array( $file ) || ! is_string( $file['file'] ?? null ) || ! is_string( $file['name'] ?? null ) ) {
					return $this->error();
				}
				$path = realpath( $file['file'] );
				if ( false === $path || 0 !== strpos( wp_normalize_path( $path ), $root ) || ! is_file( $path ) || ! is_readable( $path ) || isset( $seen_paths[ $path ] ) ) {
					return $this->error();
				}
				$filename = sanitize_file_name( wp_basename( str_replace( '\\', '/', $file['name'] ) ) );
				$type     = wp_check_filetype( $filename );
				$size     = filesize( $path );
				if ( '' === $filename || empty( $type['ext'] ) || empty( $type['type'] ) || false === $size || $size <= 0 ) {
					return $this->error();
				}
				if ( ! empty( $field['fileUploadAllowedTypes'] ) ) {
					if ( ! is_string( $field['fileUploadAllowedTypes'] ) ) {
						return $this->error();
					}
					$allowed = array_map( 'strtolower', array_map( 'trim', explode( ',', $field['fileUploadAllowedTypes'] ) ) );
					if ( in_array( 'jpg', $allowed, true ) ) {
						$allowed[] = 'jpeg';
					}
					if ( ! in_array( strtolower( $type['ext'] ), $allowed, true ) ) {
						return $this->error();
					}
				}
				$total += $size;
				if ( $total > self::MAX_BYTES || count( $result ) >= self::MAX_FILES || ( ! empty( $field['fileUploadSize'] ) && $size > (float) $field['fileUploadSize'] * MB_IN_BYTES ) ) {
					return new \WP_Error( 'fsbr_files_size', __( 'Attachments exceed the allowed size or count. Use fewer or smaller files and try again.', 'native-formsubmit-for-bricks' ) );
				}
				$seen_paths[ $path ] = true;
				$result[]            = array(
					'path' => $path,
					'name' => $filename,
					'type' => $type['type'],
					'size' => $size,
				);
			}
		}
		if ( array_diff( $selected, array_keys( $included ) ) ) {
			return $this->error();
		}
		return $result;
	}

	/**
	 * Safe error with no server paths or file contents.
	 *
	 * @return \WP_Error
	 */
	private function error() {
		return new \WP_Error( 'fsbr_files', __( 'An attachment could not be prepared. Please choose the files again or contact us directly.', 'native-formsubmit-for-bricks' ) );
	}
}
