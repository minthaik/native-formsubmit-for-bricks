<?php
/** Remove synthetic browser content and settings from the dedicated QA site. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || wp_get_environment_type() !== 'local' || home_url() !== 'http://127.0.0.1:8796' ) {
	throw new RuntimeException( 'Dedicated loopback QA environment required.' );
}
wp_set_current_user( username_exists( 'fsbqa' ) );
$fixture = dirname( __DIR__ ) . '/.tools/browser-fixture.json';
if ( file_exists( $fixture ) ) {
	$data = json_decode( file_get_contents( $fixture ), true );
	if ( isset( $data['pageId'] ) ) {
		wp_delete_post( (int) $data['pageId'], true );
	}
}
delete_option( 'fsbr_settings' );
WP_CLI::success( 'Synthetic page and recipient setting removed.' );
