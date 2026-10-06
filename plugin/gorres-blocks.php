<?php
/**
 * Plugin Name:       Gorres Blocks
 * Plugin URI:        https://github.com/jgorres/gorres-blocks-plugin
 * Description:       A collection of lightweight blocks for the block editor that can be enabled individually.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      8.1
 * Author:            Jörn Gorres
 * Author URI:        https://joern.gorres.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gorres-blocks
 * Domain Path:       /languages
 *
 * @package Gorres_Blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin constants.
 *
 * JGOR_GBLK_VERSION must be kept in sync with the "Version" header above on
 * every release. The block assets take their cache-busting version from the
 * "version" field of each block.json, which has to be raised as well.
 */
define( 'JGOR_GBLK_VERSION', '1.0.0' );
define( 'JGOR_GBLK_MIN_PHP', '8.1' );
define( 'JGOR_GBLK_FILE', __FILE__ );
define( 'JGOR_GBLK_PATH', plugin_dir_path( __FILE__ ) );
define( 'JGOR_GBLK_URL', plugin_dir_url( __FILE__ ) );

/**
 * Includes the module files from includes/.
 *
 * Add new modules here; every file covers exactly one area of responsibility.
 * The file_exists() check keeps a partial deployment from fataling the site.
 *
 * @return void
 */
function jgor_gblk_includes() {
	$files = array(
		'blocks.php',
		'admin.php',
	);

	foreach ( $files as $file ) {
		$path = JGOR_GBLK_PATH . 'includes/' . $file;
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
}
jgor_gblk_includes();

/**
 * Runs on plugin activation.
 *
 * WordPress already honours the "Requires PHP" header, the explicit check is a
 * second layer for installations that bypass the plugin screen (WP-CLI, code).
 *
 * @return void
 */
function jgor_gblk_activate() {
	if ( version_compare( PHP_VERSION, JGOR_GBLK_MIN_PHP, '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html(
				sprintf(
					/* translators: %s: required PHP version */
					__( 'This plugin requires PHP %s or newer.', 'gorres-blocks' ),
					JGOR_GBLK_MIN_PHP
				)
			),
			esc_html__( 'Plugin activation stopped', 'gorres-blocks' ),
			array( 'back_link' => true )
		);
	}
}
register_activation_hook( __FILE__, 'jgor_gblk_activate' );
