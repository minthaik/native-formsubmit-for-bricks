<?php
/** Dedicated QA fixture. Never distributed. No real provider requests. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || wp_get_environment_type() !== 'local' || home_url() !== 'http://127.0.0.1:8796' ) {
	throw new RuntimeException( 'Dedicated loopback QA environment required.' );
}
delete_option( 'fsbr_settings' );
wp_set_current_user( username_exists( 'fsbqa' ) );
$prior_fixture = dirname( __DIR__ ) . '/.tools/browser-fixture.json';
if ( file_exists( $prior_fixture ) ) {
	$prior = json_decode( file_get_contents( $prior_fixture ), true );
	if ( isset( $prior['pageId'] ) ) {
		wp_delete_post( (int) $prior['pageId'], true );
	}
}
$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'FormSubmit synthetic QA', 'post_content' => '' ) );
update_post_meta( $id, '_bricks_editor_mode', 'bricks' );
update_post_meta( $id, '_bricks_page_content_2', array(
	array( 'id' => 'qatest', 'name' => 'section', 'parent' => 0, 'children' => array( 'qawrap' ), 'settings' => array( '_padding' => array( 'top' => '48', 'right' => '24', 'bottom' => '48', 'left' => '24' ) ) ),
	array( 'id' => 'qawrap', 'name' => 'container', 'parent' => 'qatest', 'children' => array( 'qahead', 'qaform' ), 'settings' => array( '_width' => '640' ) ),
	array( 'id' => 'qahead', 'name' => 'heading', 'parent' => 'qawrap', 'children' => array(), 'settings' => array( 'text' => 'Synthetic plugin QA', 'tag' => 'h1' ) ),
	array( 'id' => 'qaform', 'name' => 'form', 'parent' => 'qawrap', 'children' => array(), 'label' => 'FormSubmit QA form', 'settings' => array(
		'fields' => array(
			array( 'id' => 'name01', 'type' => 'text', 'label' => 'Name', 'required' => true ),
			array( 'id' => 'email1', 'type' => 'email', 'label' => 'Email', 'required' => true ),
			array( 'id' => 'msg001', 'type' => 'textarea', 'label' => 'Message', 'required' => true ),
			array( 'id' => 'secret', 'type' => 'hidden', 'label' => 'Private metadata', 'value' => 'FSBR_HIDDEN_EXCLUDED' ),
			array( 'id' => 'honey1', 'type' => 'text', 'label' => 'Leave empty', 'isHoneypot' => true ),
			array( 'id' => 'files1', 'type' => 'file', 'label' => 'QA attachments', 'fileUploadButtonText' => 'Choose QA files', 'fileUploadAllowedTypes' => 'png,pdf', 'fileUploadLimit' => 3, 'fileUploadSize' => 10 ),
		),
		'showLabels' => true,
		'actions' => array( 'fsbr-formsubmit' ),
		'fsbrRecipient' => 'qa-recipient@example.test',
		'fsbrSubject' => 'Synthetic browser test',
		'fsbrReplyTo' => 'email1',
		'fsbrAttachments' => true,
		'fsbrFileFields' => array( 'files1' ),
		'submitButtonText' => 'Send QA message',
		'successMessage' => 'FSBR QA request accepted',
	) ),
) );
wp_set_current_user( 0 );
file_put_contents( dirname(__DIR__) . '/.tools/browser-fixture.json', wp_json_encode( array( 'pageId' => $id, 'url' => get_permalink( $id ), 'nonce' => wp_create_nonce( 'bricks-nonce-form' ) ) ) );
WP_CLI::success( 'Created isolated synthetic page ' . $id );
