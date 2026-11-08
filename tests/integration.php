<?php
/**
 * Dedicated local WordPress/Bricks integration checks. Never run on a customer site.
 * Run with: wp eval-file tests/integration.php --path=.../.tools/wordpress
 */

use FormSubmitForBricks\Action;
use FormSubmitForBricks\Admin;
use FormSubmitForBricks\Client;
use FormSubmitForBricks\Configuration;
use FormSubmitForBricks\Payload;
use FormSubmitForBricks\Attachments;
use FormSubmitForBricks\Provider_Options;
use FormSubmitForBricks\Multipart;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || wp_get_environment_type() !== 'local' || home_url() !== 'http://127.0.0.1:8796' ) {
	throw new RuntimeException( 'Only run against the dedicated loopback plugin QA environment.' );
}

$root = dirname( __DIR__ );
foreach ( array( 'configuration', 'payload', 'client', 'action', 'admin', 'attachments', 'provider-options', 'multipart' ) as $component ) {
	require_once WP_PLUGIN_DIR . '/native-formsubmit-for-bricks/includes/class-' . $component . '.php';
}

final class FSBR_Test_Form extends \Bricks\Integrations\Form\Init {
	public function __construct( $settings, $values, $uploads = array() ) {
		$this->form_settings = $settings;
		$this->form_fields   = $values;
		$this->results       = array();
		$this->uploaded_files = $uploads;
	}
}

$checks = array();
$assert = static function ( $condition, $label ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( $label );
	}
	$checks[] = $label;
};
$http = array();
$provider = array( 'response' => array( 'code' => 200 ), 'body' => '{"success":"true"}' );
$intercept = static function ( $pre, $args, $url ) use ( &$http, &$provider ) {
	$http[] = array( 'url' => $url, 'args' => $args );
	return strpos( $url, 'https://formsubmit.co/ajax/' ) === 0 ? $provider : new WP_Error( 'test_network_blocked' );
};
add_filter( 'pre_http_request', $intercept, PHP_INT_MAX, 3 );
$previous = get_option( Configuration::OPTION, null );

try {
	$native = new \Bricks\Element_Form();
	$native->control_options = array( 'buttonSizes' => array(), 'styles' => array(), 'iconPosition' => array() );
	$native->set_controls();
	$controls = apply_filters( 'bricks/elements/form/controls', $native->controls );
	$groups = apply_filters( 'bricks/elements/form/control_groups', array() );
	$assert( isset( $controls['actions']['options']['email'], $controls['actions']['options']['fsbr-formsubmit'] ), 'Native Email action remains and FormSubmit is added' );
	$assert( $groups['fsbrFormsubmit']['required'] === array( 'actions', '=', 'fsbr-formsubmit' ), 'Group is conditional on the selected action' );
	$assert( $controls['fsbrReplyTo']['map_fields'] === array( 'type' => 'email' ), 'Native reply-to dropdown maps email fields' );
	$assert( $controls['fsbrFields']['multiple'] && $controls['fsbrFields']['map_fields'], 'Native included-field multi-select is configured' );
	$assert( $controls['fsbrFileFields']['map_fields'] === array( 'type' => 'file' ) && $controls['fsbrFileFields']['multiple'], 'Native attachment selector maps Files fields only' );
	$assert( ! isset( $controls['fsbrAttachments']['default'] ), 'Attachments remain opt-in for older forms' );
	$assert( ! isset( $controls['fsbrRecipient']['default'] ), 'No recipient is silently preconfigured' );
	$assert( ! $http, 'Controls cause no remote requests' );

	delete_option( Configuration::OPTION );
	$assert( '' === Configuration::recipient( array() ), 'Missing recipient never falls back to administrator email' );
	update_option( Configuration::OPTION, array( 'schema_version' => 1, 'recipient' => 'default@example.test' ) );
	$assert( 'default@example.test' === Configuration::recipient( array() ), 'Site default is resolved' );
	$assert( 'override@example.test' === Configuration::recipient( array( 'fsbrRecipient' => 'override@example.test' ) ), 'Per-form literal recipient overrides default' );
	foreach ( array( '{{visitor}}', "ok@example.test\r\nBcc:x@example.test", 'a@example.test,b@example.test', 'https://localhost/', array( 'a@example.test' ) ) as $bad ) {
		$assert( '' === Configuration::recipient( array( 'fsbrRecipient' => $bad ) ), 'Invalid recipient cannot silently fall back: ' . gettype( $bad ) );
	}
	$admin = new Admin();
	$sanitized = $admin->sanitize( array( 'recipient' => 'not-email', 'other' => 'discard' ) );
	$assert( 'default@example.test' === $sanitized['recipient'] && ! isset( $sanitized['other'] ), 'Bad settings preserve prior recipient and reject unknown keys' );
	$assert( '' === $admin->sanitize( array( 'recipient' => '' ) )['recipient'], 'Default recipient can be cleared' );
	$assert( 'privateToken_1234' === Configuration::destination( array( 'fsbrToken' => 'privateToken_1234' ) ), 'Provider-issued private recipient token is supported' );
	$assert( '' === Configuration::destination( array( 'fsbrToken' => '../invalid', 'fsbrRecipient' => 'owner@example.test' ) ), 'Invalid token does not fall back to another recipient' );
	$options = new Provider_Options();
	$directives = $options->build( array( 'fsbrCc' => 'one@example.test, two@example.test,one@example.test', 'fsbrBlacklist' => "spam one\nspam two", 'fsbrWebhook' => 'https://93.184.216.34/form-hook' ) );
	$assert( ! is_wp_error( $directives ) && $directives['_cc'] === 'one@example.test,two@example.test' && $directives['_blacklist'] === 'spam one,spam two' && isset( $directives['_webhook'] ), 'CC, blacklist and public HTTPS webhook validated and deduplicated' );
	foreach ( array( 'http://example.com/hook', 'https://127.0.0.1/hook', 'https://10.0.0.1/hook', 'https://user:pass@example.com/hook', 'https://example.com/#fragment' ) as $url ) {
		$assert( is_wp_error( $options->build( array( 'fsbrWebhook' => $url ) ) ), 'Unsafe webhook destination rejected' );
	}
	$assert( is_wp_error( $options->build( array( 'fsbrCc' => "one@example.test\r\nCc:injected@example.test" ) ) ), 'CC header injection rejected' );
	$assert( is_wp_error( $options->build( array( 'fsbrBlacklist' => implode( ',', range( 1, 21 ) ) ) ) ), 'Blacklist bounded to 20 phrases' );

	$settings = array(
		'actions' => array( 'fsbr-formsubmit' ),
		'fields' => array(
			array( 'id' => 'name01', 'type' => 'text', 'label' => 'Name' ),
			array( 'id' => 'email1', 'type' => 'email', 'label' => 'Email' ),
			array( 'id' => 'msg001', 'type' => 'textarea', 'label' => 'Message' ),
			array( 'id' => 'check1', 'type' => 'checkbox', 'label' => 'Services' ),
			array( 'id' => 'zero01', 'type' => 'number', 'label' => 'Count' ),
			array( 'id' => 'dup001', 'type' => 'text', 'label' => 'Name' ),
			array( 'id' => 'cmd001', 'type' => 'text', 'label' => '_cc' ),
		),
	);
	$values = array(
		'form-field-name01' => 'Synthetic Tester',
		'form-field-email1' => 'visitor@example.test',
		'form-field-msg001' => "First line\nSecond line <script>alert(1)</script>",
		'form-field-check1' => array( 'One', 'Two' ),
		'form-field-zero01' => '0',
		'form-field-dup001' => 'Other value',
		'form-field-cmd001' => 'injected@example.test',
		'formId' => 'UNTRUSTED_ID', 'postId' => '2', 'referrer' => 'https://private.test/?secret=1',
		'nonce' => 'PRIVATE_NONCE', 'fsbrRecipient' => 'attacker@example.test', '_cc' => 'attacker@example.test',
	);
	foreach ( array( 'password', 'hidden', 'file', 'image', 'gallery', 'rememberme', 'html' ) as $type ) {
		$settings['fields'][] = array( 'id' => $type, 'type' => $type, 'label' => 'SECRET_' . $type );
		$values[ 'form-field-' . $type ] = 'DO_NOT_SEND';
	}
	$settings['fields'][] = array( 'id' => 'honey1', 'type' => 'text', 'label' => 'SECRET_honey', 'isHoneypot' => false );
	$values['form-field-honey1'] = 'DO_NOT_SEND';
	$payload = ( new Payload() )->build( $settings, $values );
	$assert( ! is_wp_error( $payload ), 'Automatic supported-field payload builds' );
	$assert( $payload['Name'] === 'Synthetic Tester' && $payload['Name (2)'] === 'Other value', 'Duplicate labels preserve both values' );
	$assert( $payload['Services'] === 'One, Two' && $payload['Count'] === '0', 'Multi-select and numeric zero survive normalization' );
	$assert( strpos( $payload['Message'], "First line\nSecond line" ) === 0 && strpos( $payload['Message'], '<script>' ) === false, 'Textarea line breaks retained and HTML removed' );
	$assert( $payload['email'] === 'visitor@example.test' && $payload['_replyto'] === 'visitor@example.test', 'Automatic reply-to maps an included email field' );
	$assert( ! isset( $payload['_cc'] ) && $payload['Field cmd001'] === 'injected@example.test', 'Provider directive labels cannot inject recipients' );
	$encoded = wp_json_encode( $payload );
	$assert( strpos( $encoded, 'DO_NOT_SEND' ) === false && strpos( $encoded, 'PRIVATE_NONCE' ) === false && strpos( $encoded, 'private.test' ) === false && strpos( $encoded, 'attacker@' ) === false, 'Sensitive fields, transport data, unknown keys and routing attacks are excluded' );
	$assert( $payload['_template'] === 'table' && $payload['_captcha'] === 'false', 'Provider options are server controlled' );
	$filtered = $settings;
	$filtered['fsbrFields'] = array( 'msg001' );
	$subset = ( new Payload() )->build( $filtered, $values );
	$assert( ! isset( $subset['email'], $subset['Name'] ) && isset( $subset['Message'] ), 'Field selection excludes all unselected visitor data including auto reply-to' );
	$filtered['fsbrReplyTo'] = 'email1';
	$assert( is_wp_error( ( new Payload() )->build( $filtered, $values ) ), 'Reply-to cannot bypass the field allowlist' );
	foreach ( array( array( 'password' ), array( 'missing' ), array( array( 'nested' ) ) ) as $selection ) {
		$invalid = $settings;
		$invalid['fsbrFields'] = $selection;
		$assert( is_wp_error( ( new Payload() )->build( $invalid, $values ) ), 'Unsupported, stale or malformed field selections fail closed' );
	}
	$bad_values = $values;
	$bad_values['form-field-check1'] = array( array( 'nested' ) );
	$assert( is_wp_error( ( new Payload() )->build( $settings, $bad_values ) ), 'Nested submitted arrays are rejected' );
	$bad_values['form-field-check1'] = array_fill( 0, 101, 'many' );
	$assert( is_wp_error( ( new Payload() )->build( $settings, $bad_values ) ), 'Multi-value count is bounded' );
	$bad_values = $values;
	$bad_values['form-field-msg001'] = str_repeat( 'x', 10001 );
	$assert( is_wp_error( ( new Payload() )->build( $settings, $bad_values ) ), 'Individual values are bounded' );
	$bad_values = $values;
	$bad_values['form-field-email1'] = "visitor@example.test\nCc: leak@example.test";
	$assert( is_wp_error( ( new Payload() )->build( $settings, $bad_values ) ), 'Reply-to header injection is rejected' );
	$invalid = $settings;
	$invalid['fsbrTemplate'] = 'malicious';
	$assert( is_wp_error( ( new Payload() )->build( $invalid, $values ) ), 'Unrecognized layout rejected' );
	$invalid = $settings;
	$invalid['fsbrSubject'] = '<b>Enquiry</b>' . "\r\nSubject: test";
	$subject = ( new Payload() )->build( $invalid, $values )['_subject'];
	$assert( strpos( $subject, '<b>' ) === false && strpos( $subject, "\n" ) === false, 'Subject markup and line breaks are removed' );

	$client = new Client();
	$assert( true === $client->send( 'owner+forms@example.test', $payload ), 'String true response is accepted' );
	$last = end( $http );
	$assert( $last['url'] === 'https://formsubmit.co/ajax/owner%2Bforms%40example.test', 'Destination is fixed and email path encoded' );
	$assert( $last['args']['sslverify'] && $last['args']['redirection'] === 0 && $last['args']['timeout'] === 15 && $last['args']['limit_response_size'] === 16384 && $last['args']['reject_unsafe_urls'], 'TLS, SSRF, redirect, timeout and response limits enforced' );
	foreach ( array( true, false, 'false', 1, '1', null, array() ) as $success ) {
		$provider = array( 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( array( 'success' => $success ) ) );
		$result = $client->send( 'owner@example.test', $payload );
		$assert( ( true === $success ) === ( true === $result ), 'Only explicit boolean/string true is accepted: ' . gettype( $success ) );
	}
	foreach ( array( 302, 400, 429, 500 ) as $status ) {
		$provider = array( 'response' => array( 'code' => $status ), 'body' => '{"success":true,"message":"RAW_PRIVATE_DETAILS"}' );
		$result = $client->send( 'owner@example.test', $payload );
		$assert( is_wp_error( $result ) && strpos( $result->get_error_message(), 'RAW_PRIVATE' ) === false, 'Non-2xx response fails without leaking provider text: ' . $status );
	}
	foreach ( array( '<html>challenge</html>', '{}', '[]', 'true', '' ) as $body ) {
		$provider = array( 'response' => array( 'code' => 200 ), 'body' => $body );
		$assert( is_wp_error( $client->send( 'owner@example.test', $payload ) ), 'Malformed or ambiguous response fails closed' );
	}
	$provider = new WP_Error( 'http_request_failed', 'RAW_PRIVATE_TRANSPORT_DATA' );
	$result = $client->send( 'owner@example.test', $payload );
	$assert( is_wp_error( $result ) && strpos( $result->get_error_message(), 'RAW_PRIVATE' ) === false, 'Transport errors are safely summarized' );
	$before = count( $http );
	$assert( is_wp_error( $client->send( '{{visitor}}', $payload ) ) && count( $http ) === $before, 'Invalid recipient makes no HTTP request' );
	$assert( is_wp_error( $client->send( 'owner@example.test', array( 'message' => str_repeat( 'x', 65537 ) ) ) ) && count( $http ) === $before, 'Oversized JSON makes no HTTP request' );

	$provider = array( 'response' => array( 'code' => 200 ), 'body' => '{"success":true}' );
	$form = new FSBR_Test_Form( $settings, $values );
	do_action( 'bricks/form/action/fsbr-formsubmit', $form );
	$assert( isset( $form->get_results()['success'][0] ), 'Real Bricks form receives native success result' );
	$last = end( $http );
	$assert( strpos( $last['url'], 'default%40example.test' ) !== false, 'Visitor cannot override delivery recipient through submitted fields' );
	$provider = array( 'response' => array( 'code' => 200 ), 'body' => '{"success":false}' );
	$form = new FSBR_Test_Form( $settings, $values );
	do_action( 'bricks/form/action/fsbr-formsubmit', $form );
	$assert( isset( $form->get_results()['error'][0] ) && ! isset( $form->get_results()['success'] ), 'Provider rejection enters Bricks error channel and blocks later actions' );
	$before = count( $http );
	$disabled = $settings;
	$disabled['actions'] = array( 'email' );
	$form = new FSBR_Test_Form( $disabled, $values );
	( new Action() )->run( $form );
	$assert( count( $http ) === $before && ! $form->get_results(), 'Forms without the custom action are untouched' );
	$provider = array( 'response' => array( 'code' => 200 ), 'body' => '{"success":true}' );
	$legacy = new class( $settings, $values ) {
		private $settings;
		private $values;
		public $results = array();
		public function __construct( $settings, $values ) { $this->settings = $settings; $this->values = $values; }
		public function get_settings() { return $this->settings; }
		public function get_fields() { return $this->values; }
		public function set_result( $result ) { $this->results[ $result['type'] ][] = $result; }
	};
	( new Action() )->run( $legacy );
	$assert( isset( $legacy->results['success'][0] ), 'Text forms work without the newer Bricks upload accessor' );

	wp_set_current_user( 0 );
	ob_start();
	$admin->render();
	$output = ob_get_clean();
	$assert( '' === $output, 'Setup reveals no settings to an unauthorized user' );
	$assert( false === has_action( 'admin_post_nopriv_fsbr_test' ), 'There is no anonymous test endpoint' );
	$assert( empty( $GLOBALS['wp_scripts']->registered['native-formsubmit-for-bricks'] ), 'No plugin frontend script is registered' );
	update_option( 'fsbr_qa_unrelated', 'keep' );
	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		define( 'WP_UNINSTALL_PLUGIN', 'native-formsubmit-for-bricks/native-formsubmit-for-bricks.php' );
	}
	require $root . '/uninstall.php';
	$assert( false === get_option( Configuration::OPTION ) && 'keep' === get_option( 'fsbr_qa_unrelated' ), 'Single-site uninstall removes only plugin-owned settings' );
	delete_option( 'fsbr_qa_unrelated' );
	$report = array( 'passed' => count( $checks ), 'checks' => $checks, 'provider_calls_mocked' => count( $http ), 'real_provider_calls' => 0, 'wordpress' => get_bloginfo( 'version' ), 'bricks' => BRICKS_VERSION, 'bricks_api_doubles' => getenv( 'FSBR_CONTRACT_TEST' ) === '1', 'php' => PHP_VERSION );
	if ( ! is_dir( $root . '/qa' ) ) {
		mkdir( $root . '/qa' );
	}
	file_put_contents( $root . '/qa/integration.json', wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
	WP_CLI::success( count( $checks ) . ' checks passed; real provider calls: 0.' );
} finally {
	remove_filter( 'pre_http_request', $intercept, PHP_INT_MAX );
	if ( null === $previous ) {
		delete_option( Configuration::OPTION );
	} else {
		update_option( Configuration::OPTION, $previous );
	}
}
