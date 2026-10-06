<?php
/**
 * Settings screen: switches the blocks of the plugin on and off.
 *
 * The screen lives under Settings > Gorres Blocks and uses the Settings API,
 * which takes care of the nonce (settings_fields()) and of the capability
 * check in options.php. The render callback checks the capability once more
 * as a second layer.
 *
 * @package Gorres_Blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Slug of the settings screen and name of the settings group.
 */
const JGOR_GBLK_SETTINGS_PAGE  = 'gorres-blocks';
const JGOR_GBLK_SETTINGS_GROUP = 'jgor_gblk_settings';

/**
 * Registers the option that stores the switched-off blocks.
 *
 * Runs on "init" rather than "admin_init" so the sanitize callback also
 * applies when the option is changed outside the admin (WP-CLI, code).
 *
 * @return void
 */
function jgor_gblk_register_setting() {
	register_setting(
		JGOR_GBLK_SETTINGS_GROUP,
		JGOR_GBLK_OPTION_DISABLED,
		array(
			'type'              => 'array',
			'description'       => __( 'Blocks of Gorres Blocks that are switched off.', 'gorres-blocks' ),
			'sanitize_callback' => 'jgor_gblk_sanitize_disabled_blocks',
			'default'           => array(),
			'show_in_rest'      => false,
		)
	);
}
add_action( 'init', 'jgor_gblk_register_setting' );

/**
 * Sanitizes the list of switched-off blocks.
 *
 * Accepts two shapes:
 * - the settings form, which sends the switched-on blocks as
 *   array( 'submitted' => '1', 'enabled' => array( 'folder', ... ) ); every
 *   available block that is not in "enabled" is switched off. The hidden
 *   "submitted" field makes sure the value arrives even when no checkbox is
 *   ticked;
 * - a plain list of folder names, as passed by update_option() in code.
 *
 * Only folder names of blocks that ship with the plugin survive.
 *
 * @param mixed $value Raw value.
 * @return string[] Folder names of the switched-off blocks.
 */
function jgor_gblk_sanitize_disabled_blocks( $value ) {
	$available = array_keys( jgor_gblk_get_available_blocks() );

	if ( ! is_array( $value ) ) {
		return array();
	}

	if ( isset( $value['submitted'] ) ) {
		$enabled = isset( $value['enabled'] ) && is_array( $value['enabled'] )
			? array_map( 'sanitize_key', array_filter( $value['enabled'], 'is_string' ) )
			: array();

		return array_values( array_diff( $available, $enabled ) );
	}

	$disabled = array_map( 'sanitize_key', array_filter( $value, 'is_string' ) );

	return array_values( array_intersect( $available, $disabled ) );
}

/**
 * Adds the settings screen below Settings.
 *
 * @return void
 */
function jgor_gblk_add_settings_page() {
	add_options_page(
		__( 'Gorres Blocks', 'gorres-blocks' ),
		__( 'Gorres Blocks', 'gorres-blocks' ),
		'manage_options',
		JGOR_GBLK_SETTINGS_PAGE,
		'jgor_gblk_render_settings_page'
	);
}
add_action( 'admin_menu', 'jgor_gblk_add_settings_page' );

/**
 * Adds a "Settings" link to the plugin row on the Plugins screen.
 *
 * @param string[] $links Action links of the plugin row.
 * @return string[] Action links with the settings link in front.
 */
function jgor_gblk_plugin_action_links( $links ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return $links;
	}

	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'options-general.php?page=' . JGOR_GBLK_SETTINGS_PAGE ) ),
		esc_html__( 'Settings', 'gorres-blocks' )
	);

	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( JGOR_GBLK_FILE ), 'jgor_gblk_plugin_action_links' );

/**
 * Returns the translated title or description of a block.
 *
 * Switched-off blocks are not registered, so their strings are not translated
 * by the block registry. The same gettext context as in register_block_type()
 * applies, so the translations from translate.wordpress.org match.
 *
 * @param array<string, mixed> $metadata Block metadata from the manifest.
 * @param string               $field    "title" or "description".
 * @return string Translated text, empty if the field is missing.
 */
function jgor_gblk_block_text( $metadata, $field ) {
	if ( empty( $metadata[ $field ] ) || ! is_string( $metadata[ $field ] ) ) {
		return '';
	}

	$context = 'title' === $field ? 'block title' : 'block description';

	// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction, WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralContext -- Same call as core uses for block.json strings; they are extracted by wp i18n make-pot.
	return translate_with_gettext_context( $metadata[ $field ], $context, 'gorres-blocks' );
}

/**
 * Renders the settings screen.
 *
 * @return void
 */
function jgor_gblk_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to manage options for this site.', 'gorres-blocks' ) );
	}

	$blocks   = jgor_gblk_get_available_blocks();
	$disabled = jgor_gblk_get_disabled_blocks();
	$field    = JGOR_GBLK_OPTION_DISABLED;
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<?php if ( empty( $blocks ) ) : ?>
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'No blocks found. The build of the plugin seems to be missing.', 'gorres-blocks' ); ?></p>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'Choose the blocks you want to use. A block that is switched off loads no styles or scripts and does not appear in the block inserter.', 'gorres-blocks' ); ?></p>
			<p><?php esc_html_e( 'Content already built with a block that is switched off stays on the front end unchanged. The editor shows such a block as unsupported until you switch it on again.', 'gorres-blocks' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php settings_fields( JGOR_GBLK_SETTINGS_GROUP ); ?>
				<input type="hidden" name="<?php echo esc_attr( $field . '[submitted]' ); ?>" value="1">

				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Blocks', 'gorres-blocks' ); ?></legend>
					<table class="form-table" role="presentation">
						<tbody>
							<?php
							foreach ( $blocks as $folder => $metadata ) :
								$input_id    = 'jgor-gblk-block-' . $folder;
								$title       = jgor_gblk_block_text( $metadata, 'title' );
								$description = jgor_gblk_block_text( $metadata, 'description' );
								?>
								<tr>
									<th scope="row">
										<label for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( '' !== $title ? $title : $folder ); ?></label>
									</th>
									<td>
										<label>
											<input
												type="checkbox"
												id="<?php echo esc_attr( $input_id ); ?>"
												name="<?php echo esc_attr( $field . '[enabled][]' ); ?>"
												value="<?php echo esc_attr( $folder ); ?>"
												<?php checked( ! in_array( $folder, $disabled, true ) ); ?>
												<?php echo '' !== $description ? 'aria-describedby="' . esc_attr( $input_id . '-description' ) . '"' : ''; ?>
											>
											<?php esc_html_e( 'Enabled', 'gorres-blocks' ); ?>
										</label>
										<?php if ( '' !== $description ) : ?>
											<p class="description" id="<?php echo esc_attr( $input_id . '-description' ); ?>"><?php echo esc_html( $description ); ?></p>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</fieldset>

				<?php submit_button(); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}
