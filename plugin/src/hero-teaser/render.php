<?php
/**
 * Front end markup of the hero teaser block.
 *
 * Three layers inside the block wrapper:
 * - the image, sticky and exactly one screen high;
 * - the track, pulled up over the image by one screen height. It starts with
 *   an empty stretch (the start position of the box), then holds the teaser
 *   box, then another screen height of empty space;
 * - the teaser box itself, inside the track.
 *
 * The wrapper is as tall as the track, so the image stays in place for exactly
 * as long as it takes the box to leave the screen at the top. Then the image
 * scrolls away and the post follows. Everything is plain CSS, see style.scss.
 *
 * @package Gorres_Blocks
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Rendered markup of the inner blocks.
 * @var WP_Block             $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/functions.php';

$jgor_gblk_box_align = isset( $attributes['boxAlign'] ) && in_array( $attributes['boxAlign'], array( 'left', 'center', 'right' ), true )
	? $attributes['boxAlign']
	: 'left';

$jgor_gblk_image = jgor_gblk_hero_image( $attributes );

$jgor_gblk_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'jgor-gblk-hero is-box-' . $jgor_gblk_box_align . ( '' === $jgor_gblk_image ? ' has-no-image' : '' ),
		'style' => jgor_gblk_hero_wrapper_style( $attributes ),
	)
);

$jgor_gblk_box = jgor_gblk_hero_box_attributes( $attributes );

// Attributes of the teaser box, escaped here and echoed as they are.
$jgor_gblk_box_attr = sprintf( 'class="%s"', esc_attr( trim( 'jgor-gblk-hero__box ' . $jgor_gblk_box['class'] ) ) );

if ( '' !== $jgor_gblk_box['style'] ) {
	$jgor_gblk_box_attr .= sprintf( ' style="%s"', esc_attr( $jgor_gblk_box['style'] ) );
}
?>
<div <?php echo $jgor_gblk_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by get_block_wrapper_attributes(). ?>>
	<div class="jgor-gblk-hero__media">
		<?php echo $jgor_gblk_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by wp_get_attachment_image(). ?>
	</div>
	<div class="jgor-gblk-hero__track">
		<div <?php echo $jgor_gblk_box_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped with esc_attr() above. ?>>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already rendered block content. ?>
		</div>
	</div>
</div>
