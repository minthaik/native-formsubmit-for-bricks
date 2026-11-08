<?php
/** Attachment security and real loopback multipart parsing tests. */
use FormSubmitForBricks\Attachments;
use FormSubmitForBricks\Client;
use FormSubmitForBricks\Multipart;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || wp_get_environment_type() !== 'local' || home_url() !== 'http://127.0.0.1:8796' ) {
	throw new RuntimeException( 'Dedicated local plugin QA environment required.' );
}
$root = dirname( __DIR__ );
foreach ( array( 'configuration', 'attachments', 'multipart', 'client' ) as $component ) {
	require_once WP_PLUGIN_DIR . '/native-formsubmit-for-bricks/includes/class-' . $component . '.php';
}
$checks = array();
$assert = static function( $test, $name ) use ( &$checks ) {
	if ( ! $test ) { throw new RuntimeException( $name ); }
	$checks[] = $name;
};
$directory = wp_upload_dir()['basedir'] . '/fsbr-attachment-qa-' . wp_generate_password( 8, false, false );
wp_mkdir_p( $directory );
$created = array();
$make = static function( $name, $bytes ) use ( $directory, &$created ) {
	$p = $directory . '/' . $name;
	file_put_contents( $p, $bytes );
	$created[] = $p;
	return array( 'file' => $p, 'name' => $name, 'type' => wp_check_filetype( $name )['type'], 'size' => strlen( $bytes ) );
};
$http = array();
$capture = null;
$receipt_mode = 'accepted';
$intercept = static function( $pre, $args, $url ) use ( &$http, &$capture, &$receipt_mode ) {
	if ( strpos( $url, 'https://formsubmit.co/' ) !== 0 ) { return $pre; }
	$http[] = array( 'url' => $url, 'args' => $args );
	if ( $receipt_mode !== 'accepted' ) {
		preg_match( '/name="_next"\r\n\r\n([^\r\n]+)/', $args['body'], $next );
		return array( 'response' => array( 'code' => $receipt_mode === 'html' ? 200 : ( $receipt_mode === '307' ? 307 : 302 ) ), 'headers' => array( 'location' => $receipt_mode === '307' ? ( $next[1] ?? '' ) : 'https://example.com/untrusted' ), 'body' => $receipt_mode === 'html' ? '<h1>Thank you</h1>' : '' );
	}
	if ( getenv( 'FSBR_TEST_CAPTURE' ) === '1' ) {
		$response = wp_remote_post( 'http://127.0.0.1:8797/http-capture.php', array( 'headers' => $args['headers'], 'body' => $args['body'], 'timeout' => 10 ) );
		if ( is_wp_error( $response ) ) { throw new RuntimeException( 'Loopback capture failed' ); }
		$capture = json_decode( wp_remote_retrieve_body( $response ), true );
		return array( 'response' => array( 'code' => 302 ), 'headers' => array( 'location' => $capture['fields']['_next'] ?? '' ), 'body' => '' );
	}
	preg_match( '/name="_next"\r\n\r\n([^\r\n]+)/', $args['body'], $next );
	return array( 'response' => array( 'code' => 302 ), 'headers' => array( 'location' => $next[1] ?? '' ), 'body' => '' );
};
add_filter( 'pre_http_request', $intercept, PHP_INT_MAX, 3 );
try {
	$png = $make( 'synthetic-image.png', file_get_contents( $root . '/tests/fixtures/synthetic-image.png' ) );
	$pdf = $make( 'synthetic-document.pdf', file_get_contents( $root . '/tests/fixtures/synthetic-document.pdf' ) );
	$uploads = array( 'form-field-files1' => array( $png, $pdf ) );
	$settings = array( 'fsbrAttachments' => true, 'fields' => array( array( 'id' => 'files1', 'type' => 'file', 'fileUploadAllowedTypes' => 'png,pdf', 'fileUploadLimit' => 3, 'fileUploadSize' => 10 ) ) );
	$service = new Attachments();
	$files = $service->collect( $settings, $uploads );
	$assert( ! is_wp_error( $files ) && count( $files ) === 2, 'Native Files field can produce both PNG and PDF attachments' );
	$assert( $files[0]['type'] === 'image/png' && $files[1]['type'] === 'application/pdf', 'Attachment MIME types are derived from WordPress-approved filename types' );
	$assert( $service->collect( array( 'fields' => $settings['fields'] ), $uploads ) === array(), 'Old forms do not attach files without explicit opt-in' );
	$assert( $service->collect( $settings, array() ) === array(), 'Optional empty Files fields are accepted' );
	$extra = $uploads + array( 'form-field-unknown' => array( array( 'file' => '/private/secret.txt' ) ) );
	$assert( count( $service->collect( $settings, $extra ) ) === 2, 'Unknown upload-map keys are never forwarded' );
	$custom = $settings;
	$custom['fields'][0]['name'] = 'custom_upload_name';
	$assert( count( $service->collect( $custom, array( 'custom_upload_name' => array( $png ) ) ) ) === 1, 'Native Files custom input names are supported' );
	$selected = $settings;
	$selected['fsbrFileFields'] = array( 'missing' );
	$assert( is_wp_error( $service->collect( $selected, $uploads ) ), 'Stale selected attachment fields fail closed' );
	$selected['fsbrFileFields'] = array( array( 'nested' ) );
	$assert( is_wp_error( $service->collect( $selected, $uploads ) ), 'Malformed selected attachment IDs are rejected' );
	$dupe = $settings;
	$dupe['fields'][] = $dupe['fields'][0];
	$assert( is_wp_error( $service->collect( $dupe, $uploads ) ), 'Duplicate configured file identities rejected' );
	$outside = array( 'file' => $root . '/tests/fixtures/synthetic-document.pdf', 'name' => 'synthetic-document.pdf' );
	$error = $service->collect( $settings, array( 'form-field-files1' => array( $outside ) ) );
	$assert( is_wp_error( $error ) && strpos( $error->get_error_message(), $root ) === false, 'Files outside the uploads root rejected without disclosing a path' );
	$bad = array( 'file' => 'https://example.com/private.pdf', 'name' => 'private.pdf' );
	$assert( is_wp_error( $service->collect( $settings, array( 'form-field-files1' => array( $bad ) ) ) ), 'Remote URL attachment injection rejected' );
	$assert( is_wp_error( $service->collect( $settings, array( 'form-field-files1' => array( $png, $png ) ) ) ), 'Duplicate physical file attachment rejected' );
	$fake = $png;
	$fake['name'] = 'shell.php';
	$assert( is_wp_error( $service->collect( $settings, array( 'form-field-files1' => array( $fake ) ) ) ), 'Executable extensions rejected by the approved MIME allowlist' );
	$only_pdf = $settings;
	$only_pdf['fields'][0]['fileUploadAllowedTypes'] = 'pdf';
	$assert( is_wp_error( $service->collect( $only_pdf, $uploads ) ), 'Per-field allowed formats rechecked' );
	$single = $settings;
	$single['fields'][0]['fileUploadLimit'] = 1;
	$assert( is_wp_error( $service->collect( $single, $uploads ) ), 'Per-field count rechecked' );
	$small = $settings;
	$small['fields'][0]['fileUploadSize'] = 0.000001;
	$assert( is_wp_error( $service->collect( $small, $uploads ) ), 'Per-field maximum bytes rechecked against the real file' );
	$empty = $make( 'empty.pdf', '' );
	$assert( is_wp_error( $service->collect( $settings, array( 'form-field-files1' => array( $empty ) ) ) ), 'Empty files rejected' );
	$big = $make( 'oversize.pdf', str_repeat( 'x', 10000001 ) );
	$assert( is_wp_error( $service->collect( $settings, array( 'form-field-files1' => array( $big ) ) ) ), 'Provider aggregate 10 MB maximum enforced' );
	$boundary = $make( 'boundary.pdf', str_repeat( 'x', 10000000 ) );
	$assert( count( $service->collect( $settings, array( 'form-field-files1' => array( $boundary ) ) ) ) === 1, 'Exactly 10 MB is accepted' );
	$assert( is_wp_error( $service->collect( $settings, array( 'form-field-files1' => array( $boundary, $png ) ) ) ), 'Provider maximum applies to the sum, not each file separately' );
	$many = array();
	for ( $i = 0; $i < 21; ++$i ) { $many[] = $make( 'many-' . $i . '.pdf', '%PDF-test' ); }
	$unlimited = $settings;
	unset( $unlimited['fields'][0]['fileUploadLimit'] );
	$assert( is_wp_error( $service->collect( $unlimited, array( 'form-field-files1' => $many ) ) ), 'Total file count bounded to 20' );
	$payload = array( 'message' => "Synthetic line one\nline two", '_subject' => 'Multipart QA', '_captcha' => 'false' );
	$encoded = ( new Multipart() )->encode( $payload, $files );
	$assert( ! is_wp_error( $encoded ) && strpos( $encoded['contentType'], 'multipart/form-data; boundary=' ) === 0, 'Multipart body declares a generated boundary' );
	$assert( strpos( $encoded['body'], 'name="attachment"; filename="synthetic-image.png"' ) !== false && strpos( $encoded['body'], 'name="attachment2"; filename="synthetic-document.pdf"' ) !== false, 'Multiple provider attachment parts have distinct names' );
	$assert( strpos( $encoded['body'], file_get_contents( $png['file'] ) ) !== false && strpos( $encoded['body'], file_get_contents( $pdf['file'] ) ) !== false, 'Binary PNG and PDF bytes preserved in multipart body' );
	$assert( strpos( $encoded['body'], $directory ) === false && strpos( $encoded['body'], 'https://example.com/private.pdf' ) === false, 'No local paths or remote upload URLs exposed in multipart' );
	$client = new Client();
	$assert( true === $client->send( 'owner@example.test', $payload, $files ), 'Attachment transport accepts an explicit provider success' );
	$request = end( $http );
	$assert( $request['url'] === 'https://formsubmit.co/owner%40example.test' && $request['args']['headers']['Accept'] === 'text/html', 'Attachments use the documented normal endpoint rather than AJAX' );
	$assert( $request['args']['timeout'] === 30 && $request['args']['headers']['Referer'] === home_url( '/' ) && strpos( $request['args']['headers']['Content-Type'], 'multipart/form-data;' ) === 0, 'Attachment request uses multipart, bounded timeout and clean home URL Referer' );
	if ( getenv( 'FSBR_TEST_CAPTURE' ) === '1' ) {
		$assert( count( $capture['files'] ) === 2 && $capture['fields']['message'] === $payload['message'], 'Actual HTTP/PHP multipart parser receives two files and text fields' );
		$assert( $capture['files']['attachment']['sha256'] === hash_file( 'sha256', $png['file'] ) && $capture['files']['attachment2']['sha256'] === hash_file( 'sha256', $pdf['file'] ), 'Actual HTTP transport preserves both attachment hashes' );
	}
	foreach ( array( 'wrong-location', 'html', '307' ) as $receipt_mode ) {
		$assert( is_wp_error( $client->send( 'owner@example.test', $payload, $files ) ), 'Unconfirmed or wrong attachment redirect fails closed: ' . $receipt_mode );
	}
	$receipt_mode = 'accepted';
	$changed = $files;
	$changed[0]['size']++;
	$assert( is_wp_error( ( new Multipart() )->encode( $payload, $changed ) ), 'File changes between validation and reading fail closed' );
	file_put_contents( $root . '/qa/attachments.json', wp_json_encode( array( 'passed' => count( $checks ), 'checks' => $checks, 'loopback_http_capture' => getenv( 'FSBR_TEST_CAPTURE' ) === '1', 'real_provider_calls' => 0 ), JSON_PRETTY_PRINT ) . "\n" );
	WP_CLI::success( count( $checks ) . ' attachment checks passed; real provider calls: 0.' );
} finally {
	remove_filter( 'pre_http_request', $intercept, PHP_INT_MAX );
	foreach ( $created as $file ) { if ( file_exists( $file ) ) { unlink( $file ); } }
	if ( is_dir( $directory ) ) { rmdir( $directory ); }
}
