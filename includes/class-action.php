<?php
/**
 * Bridge a validated Bricks action to the provider.
 *
 * @package FormSubmitForBricks
 */

namespace FormSubmitForBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action integration component.
 */
final class Action {
	/**
	 * Send only from a form explicitly configured for this action.
	 *
	 * @param \Bricks\Integrations\Form\Init $form Validated Bricks form.
	 * @return void
	 */
	public function run( $form ) {
		$settings = $form->get_settings();
		if ( ! is_array( $settings ) || ! in_array( 'fsbr-formsubmit', $settings['actions'] ?? array(), true ) ) {
			return;
		}
		$payload = ( new Payload() )->build( $settings, $form->get_fields() );
		$options = ( new Provider_Options() )->build( $settings );
		$uploads = method_exists( $form, 'get_uploaded_files' ) ? $form->get_uploaded_files() : array();
		$files   = ( new Attachments() )->collect( $settings, $uploads ?? array() );
		$result  = $payload;
		if ( ! is_wp_error( $result ) ) {
			$result = $options;
		}
		if ( ! is_wp_error( $result ) ) {
			$result = $files;
		}
		if ( ! is_wp_error( $result ) ) {
			$result = ( new Client() )->send( Configuration::destination( $settings ), $payload + $options, $files );
		}
		if ( is_wp_error( $result ) ) {
			$form->set_result(
				array(
					'action'  => 'fsbr-formsubmit',
					'type'    => 'error',
					'message' => $result->get_error_message(),
				)
			);
			return;
		}
		$form->set_result(
			array(
				'action' => 'fsbr-formsubmit',
				'type'   => 'success',
			)
		);
	}
}
