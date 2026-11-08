<?php
/** Isolated bootstrap check: no WordPress or Bricks installation required. */
define( 'ABSPATH', __DIR__ . '/' );
$hooks = array();
function is_admin() { return false; }
function add_action( $name, $callback, $priority = 10 ) { global $hooks; $hooks[ $name ][] = $callback; }
function add_filter( $name, $callback, $priority = 10 ) { add_action( $name, $callback, $priority ); }
$version = $argv[1] ?? 'none';
if ( 'none' !== $version ) { define( 'BRICKS_VERSION', $version ); }
require dirname( __DIR__ ) . '/native-formsubmit-for-bricks.php';
foreach ( $hooks['after_setup_theme'] as $callback ) { call_user_func( $callback ); }
$expected = $version !== 'none' && version_compare( $version, '1.12.2', '>=' );
if ( isset( $hooks['bricks/form/action/fsbr-formsubmit'] ) !== $expected ) { throw new RuntimeException( 'Dependency gate failed' ); }
if ( class_exists( 'FormSubmitForBricks\\Client', false ) || class_exists( 'FormSubmitForBricks\\Payload', false ) ) { throw new RuntimeException( 'Unrelated bootstrap eagerly loaded submission services' ); }
echo json_encode( array( 'version' => $version, 'integration_registered' => $expected, 'lazy_submission_services' => true ) ) . "\n";
