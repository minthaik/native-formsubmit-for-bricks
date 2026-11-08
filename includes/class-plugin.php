<?php
/**
 * Plugin composition and dependency handling.
 *
 * @package FormSubmitForBricks
 */

namespace FormSubmitForBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin integration component.
 */
final class Plugin {
	/**
	 * Register entry points without loading submission services on normal requests.
	 *
	 * @param string $file Main plugin file.
	 * @return void
	 */
	public static function boot( $file ) {
		add_action( 'after_setup_theme', array( __CLASS__, 'integrate' ), 20 );
		if ( is_admin() ) {
			require_once __DIR__ . '/class-configuration.php';
			require_once __DIR__ . '/class-admin.php';
			( new Admin() )->register( $file );
		}
	}

	/**
	 * Whether the active theme supports custom form actions.
	 *
	 * @return bool
	 */
	public static function compatible() {
		return defined( 'BRICKS_VERSION' ) && version_compare( BRICKS_VERSION, '1.12.2', '>=' );
	}

	/**
	 * Register native Bricks extension points after the theme has loaded.
	 *
	 * @return void
	 */
	public static function integrate() {
		if ( ! self::compatible() ) {
			return;
		}
		require_once __DIR__ . '/class-controls.php';
		add_filter( 'bricks/elements/form/controls', array( Controls::class, 'add_controls' ) );
		add_filter( 'bricks/elements/form/control_groups', array( Controls::class, 'add_group' ) );
		add_action( 'bricks/form/action/fsbr-formsubmit', array( __CLASS__, 'submit' ) );
	}

	/**
	 * Load services only when the configured action runs.
	 *
	 * @param \Bricks\Integrations\Form\Init $form Validated Bricks form.
	 * @return void
	 */
	public static function submit( $form ) {
		require_once __DIR__ . '/class-configuration.php';
		require_once __DIR__ . '/class-payload.php';
		require_once __DIR__ . '/class-provider-options.php';
		require_once __DIR__ . '/class-attachments.php';
		require_once __DIR__ . '/class-multipart.php';
		require_once __DIR__ . '/class-client.php';
		require_once __DIR__ . '/class-action.php';
		( new Action() )->run( $form );
	}
}
