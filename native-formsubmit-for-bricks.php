<?php
/**
 * Plugin Name: Native FormSubmit for Bricks
 * Plugin URI: https://github.com/minthaik/native-formsubmit-for-bricks
 * Description: Connect native Bricks forms to FormSubmit with simple recipient and field controls.
 * Version: 0.2.1
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: BricksTemplates
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: native-formsubmit-for-bricks
 * Domain Path: /languages
 *
 * @package FormSubmitForBricks
 */

namespace FormSubmitForBricks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-plugin.php';
Plugin::boot( __FILE__ );
