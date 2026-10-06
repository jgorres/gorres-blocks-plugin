<?php
/**
 * Block registration.
 *
 * Every block lives in its own folder below src/ and is compiled into build/
 * by wp-scripts, which also writes build/blocks-manifest.php: an array of all
 * block.json files, keyed by folder name. The manifest is registered as a
 * metadata collection, so WordPress reads block metadata from that single PHP
 * file instead of parsing one block.json per block.
 *
 * Unlike wp_register_block_types_from_metadata_collection(), which registers
 * every block of a collection, this module registers only the blocks that are
 * switched on in the settings screen (see includes/admin.php). A block that is
 * switched off is not registered at all: no styles, no scripts, no entry in
 * the block inserter.
 *
 * @package Gorres_Blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Name of the option that holds the folder names of all switched-off blocks.
 *
 * The plugin stores the blocks that are off rather than the blocks that are on,
 * so a block added in a later plugin version is available right away.
 */
const JGOR_GBLK_OPTION_DISABLED = 'jgor_gblk_disabled_blocks';

/**
 * Returns the absolute path of the build directory.
 *
 * @return string Path with trailing slash.
 */
function jgor_gblk_build_path() {
	return JGOR_GBLK_PATH . 'build/';
}

/**
 * Returns the metadata of every block that ships with the plugin.
 *
 * Reads build/blocks-manifest.php once per request. Entries whose key is not a
 * plain folder name or whose metadata lacks a block name are skipped, so a
 * damaged manifest can never point registration outside the build directory.
 *
 * @return array<string, array<string, mixed>> Block metadata keyed by folder name,
 *                                             empty if the build is missing.
 */
function jgor_gblk_get_available_blocks() {
	static $blocks = null;

	if ( null !== $blocks ) {
		return $blocks;
	}

	$blocks   = array();
	$manifest = jgor_gblk_build_path() . 'blocks-manifest.php';

	if ( ! is_readable( $manifest ) ) {
		return $blocks;
	}

	$data = require $manifest;

	if ( ! is_array( $data ) ) {
		return $blocks;
	}

	foreach ( $data as $folder => $metadata ) {
		if (
			! is_string( $folder )
			|| 1 !== preg_match( '/^[a-z0-9-]+$/', $folder )
			|| ! is_array( $metadata )
			|| empty( $metadata['name'] )
			|| ! is_string( $metadata['name'] )
		) {
			continue;
		}

		$blocks[ $folder ] = $metadata;
	}

	ksort( $blocks );

	return $blocks;
}

/**
 * Returns the folder names of all blocks that are switched off.
 *
 * Only names of blocks that still ship with the plugin are returned; stale
 * entries from removed blocks are ignored.
 *
 * @return string[] Folder names.
 */
function jgor_gblk_get_disabled_blocks() {
	$disabled = get_option( JGOR_GBLK_OPTION_DISABLED, array() );

	if ( ! is_array( $disabled ) ) {
		return array();
	}

	return array_values(
		array_intersect(
			array_keys( jgor_gblk_get_available_blocks() ),
			$disabled
		)
	);
}

/**
 * Returns the folder names of all blocks that are switched on.
 *
 * @return string[] Folder names.
 */
function jgor_gblk_get_enabled_blocks() {
	return array_values(
		array_diff(
			array_keys( jgor_gblk_get_available_blocks() ),
			jgor_gblk_get_disabled_blocks()
		)
	);
}

/**
 * Registers the block metadata collection and every switched-on block.
 *
 * @return void
 */
function jgor_gblk_register_blocks() {
	$blocks = jgor_gblk_get_available_blocks();

	if ( empty( $blocks ) ) {
		return;
	}

	$build_path = jgor_gblk_build_path();

	wp_register_block_metadata_collection(
		untrailingslashit( $build_path ),
		$build_path . 'blocks-manifest.php'
	);

	foreach ( jgor_gblk_get_enabled_blocks() as $folder ) {
		register_block_type( $build_path . $folder );
	}
}
add_action( 'init', 'jgor_gblk_register_blocks' );
