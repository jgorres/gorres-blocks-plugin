<?php
/**
 * Uninstall: removes every piece of data created by the plugin.
 *
 * Only executed when the plugin is deleted from the admin screen. Block content
 * lives in the posts themselves and is deliberately left untouched.
 *
 * @package Gorres_Blocks
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Options created by the plugin.
 *
 * Every key starts with jgor_gblk_ and is listed here explicitly.
 * jgor_gblk_enabled_blocks holds the list of blocks switched on in the
 * settings screen.
 */
$jgor_gblk_options = array(
	'jgor_gblk_enabled_blocks',
);

foreach ( $jgor_gblk_options as $jgor_gblk_option ) {
	delete_option( $jgor_gblk_option );
	delete_site_option( $jgor_gblk_option );
}
