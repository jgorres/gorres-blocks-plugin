<?php
/**
 * PHPStan bootstrap: constants the plugin defines at runtime.
 *
 * This file is only loaded by PHPStan, never by WordPress. The values are
 * placeholders; all that matters is that the constants count as defined
 * during analysis.
 *
 * @package Gorres_Blocks
 */

// Plugin Gorres Blocks (defined in plugin/gorres-blocks.php).
if ( ! defined( 'JGOR_GBLK_VERSION' ) ) {
	define( 'JGOR_GBLK_VERSION', '1.1.0' );
}
if ( ! defined( 'JGOR_GBLK_MIN_PHP' ) ) {
	define( 'JGOR_GBLK_MIN_PHP', '8.1' );
}
if ( ! defined( 'JGOR_GBLK_FILE' ) ) {
	define( 'JGOR_GBLK_FILE', __DIR__ . '/plugin/gorres-blocks.php' );
}
if ( ! defined( 'JGOR_GBLK_PATH' ) ) {
	define( 'JGOR_GBLK_PATH', __DIR__ . '/plugin/' );
}
if ( ! defined( 'JGOR_GBLK_URL' ) ) {
	define( 'JGOR_GBLK_URL', 'https://example.local/wp-content/plugins/gorres-blocks/' );
}
