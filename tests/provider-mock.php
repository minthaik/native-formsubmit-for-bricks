<?php
/** Isolated QA HTTP mock. Never shipped in the installable plugin. */
if ( wp_get_environment_type() !== 'local' || home_url() !== 'http://127.0.0.1:8796' ) {
	return;
}
add_filter( 'pre_http_request', static function( $pre, $args, $url ) {
	if ( strpos( $url, 'https://formsubmit.co/' ) === 0 ) {
		$payload = json_decode( $args['body'] ?? '', true );
		$attachments = array();
		if ( strpos( $args['headers']['Content-Type'] ?? '', 'multipart/form-data;' ) === 0 ) {
			$payload = array();
			preg_match( '/boundary=([^;]+)/', $args['headers']['Content-Type'], $boundary );
			foreach ( explode( '--' . $boundary[1], $args['body'] ) as $part ) {
				if ( ! preg_match( '/^\r\n(.*?)\r\n\r\n(.*)\r\n$/s', $part, $match ) ) { continue; }
				preg_match( '/name="([^"]+)"/', $match[1], $field );
				if ( preg_match( '/filename="([^"]+)"/', $match[1], $file ) ) {
					$attachments[] = array( 'name' => $file[1], 'bytes' => strlen( $match[2] ), 'sha256' => hash( 'sha256', $match[2] ) );
				} else {
					$payload[ $field[1] ] = $match[2];
				}
			}
		}
		$reject = strpos( $args['body'] ?? '', 'FSBR_QA_REJECT' ) !== false;
		$record = WP_CONTENT_DIR . '/../../browser-capture.json';
		$old = file_exists( $record ) ? json_decode( file_get_contents( $record ), true ) : array();
		file_put_contents( $record, wp_json_encode( array( 'url' => $url, 'payload' => $payload, 'attachments' => $attachments, 'rejected' => $reject, 'count' => ( $old['count'] ?? 0 ) + 1 ) ) );
		if ( $attachments && ! $reject ) {
			return array( 'headers' => array( 'location' => $payload['_next'] ?? '' ), 'response' => array( 'code' => 302 ), 'body' => '', 'cookies' => array(), 'filename' => null );
		}
		return array( 'headers' => array(), 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( array( 'success' => ! $reject ) ), 'cookies' => array(), 'filename' => null );
	}
	// Avoid unrelated outbound licensing/update requests during browser QA.
	return new WP_Error( 'fsbr_qa_network_blocked', 'Network disabled in isolated QA.' );
}, PHP_INT_MAX, 3 );
