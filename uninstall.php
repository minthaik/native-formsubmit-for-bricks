<?php
/**
 * Remove the plugin's single site option; leave Bricks-owned documents untouched.
 *
 * @package FormSubmitForBricks
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

( static function () {
	if ( is_multisite() ) {
		$formsubmit_for_bricks_offset = 0;
		do {
			$formsubmit_for_bricks_sites = get_sites(
				array(
					'fields' => 'ids',
					'number' => 100,
					'offset' => $formsubmit_for_bricks_offset,
				)
			);
			foreach ( $formsubmit_for_bricks_sites as $formsubmit_for_bricks_site_id ) {
				switch_to_blog( $formsubmit_for_bricks_site_id );
				delete_option( 'fsbr_settings' );
				restore_current_blog();
			}
			$formsubmit_for_bricks_offset += 100;
			$formsubmit_for_bricks_count   = count( $formsubmit_for_bricks_sites );
		} while ( 100 === $formsubmit_for_bricks_count );
	} else {
		delete_option( 'fsbr_settings' );
	}
} )();
