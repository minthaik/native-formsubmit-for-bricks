<?php
/** Loopback-only multipart parser fixture. Never distributed or deployed. */
if ( ! in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) ) {
	http_response_code( 403 );
	exit;
}
$files = array();
foreach ( $_FILES as $key => $file ) {
	$files[ $key ] = array( 'name' => $file['name'], 'type' => $file['type'], 'error' => $file['error'], 'bytes' => filesize( $file['tmp_name'] ), 'sha256' => hash_file( 'sha256', $file['tmp_name'] ) );
}
header( 'Content-Type: application/json' );
echo json_encode( array( 'success' => true, 'files' => $files, 'fields' => $_POST ) );
