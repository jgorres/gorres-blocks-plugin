<?php
/**
 * Helper functions of the hero teaser block.
 *
 * The file render.php is included once per block instance and must not
 * declare functions, so they live here and are loaded with require_once.
 *
 * @package Gorres_Blocks
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'jgor_gblk_hero_clamp' ) ) {
	/**
	 * Clamps a numeric attribute to a range.
	 *
	 * @param mixed $value    Raw attribute value.
	 * @param int   $min      Lower bound.
	 * @param int   $max      Upper bound.
	 * @param int   $fallback Value for anything that is not numeric.
	 * @return int Value within the bounds.
	 */
	function jgor_gblk_hero_clamp( $value, $min, $max, $fallback ) {
		if ( ! is_numeric( $value ) ) {
			return $fallback;
		}

		return max( $min, min( $max, (int) round( (float) $value ) ) );
	}
}

if ( ! function_exists( 'jgor_gblk_hero_wrapper_style' ) ) {
	/**
	 * Builds the custom properties the stylesheet reads.
	 *
	 * Every value is an integer within fixed bounds, so the result is safe for a
	 * style attribute; it is escaped on output nonetheless.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string Inline style.
	 */
	function jgor_gblk_hero_wrapper_style( $attributes ) {
		$style = sprintf(
			'--jgor-gblk-hero-offset:%1$dpx;--jgor-gblk-hero-dim:%2$s;--jgor-gblk-hero-start:%3$s;--jgor-gblk-hero-box-width:%4$d%%;',
			jgor_gblk_hero_clamp( $attributes['topOffset'] ?? 0, 0, 300, 0 ),
			jgor_gblk_hero_clamp( $attributes['dim'] ?? 20, 0, 90, 20 ) / 100,
			jgor_gblk_hero_clamp( $attributes['boxStart'] ?? 50, 0, 90, 50 ) / 100,
			jgor_gblk_hero_clamp( $attributes['boxWidth'] ?? 40, 20, 100, 40 )
		);

		// Focal point is stored as floats between 0 and 1 and becomes object-position.
		if ( isset( $attributes['focalPoint']['x'], $attributes['focalPoint']['y'] ) && is_numeric( $attributes['focalPoint']['x'] ) && is_numeric( $attributes['focalPoint']['y'] ) ) {
			$style .= sprintf(
				'--jgor-gblk-hero-focal-x:%1$s%%;--jgor-gblk-hero-focal-y:%2$s%%;',
				round( max( 0.0, min( 1.0, (float) $attributes['focalPoint']['x'] ) ) * 100, 2 ),
				round( max( 0.0, min( 1.0, (float) $attributes['focalPoint']['y'] ) ) * 100, 2 )
			);
		}

		return $style;
	}
}

if ( ! function_exists( 'jgor_gblk_hero_image' ) ) {
	/**
	 * Returns the markup of the hero image.
	 *
	 * The image is the largest element on screen when the page loads, so it is
	 * loaded eagerly with high priority. Because it covers a screen-high area,
	 * the sizes attribute accounts for the aspect ratio: a portrait screen crops
	 * a landscape image and needs a file wider than the screen.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string Image markup, empty without an image.
	 */
	function jgor_gblk_hero_image( $attributes ) {
		$media_id = isset( $attributes['mediaId'] ) ? absint( $attributes['mediaId'] ) : 0;
		$alt      = isset( $attributes['mediaAlt'] ) && is_string( $attributes['mediaAlt'] ) ? $attributes['mediaAlt'] : '';

		if ( $media_id < 1 || ! wp_attachment_is_image( $media_id ) ) {
			return '';
		}

		$sizes = '100vw';
		$meta  = wp_get_attachment_metadata( $media_id );

		if ( is_array( $meta ) && ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
			$sizes = sprintf( 'max(100vw, %dvh)', (int) round( $meta['width'] / $meta['height'] * 100 ) );
		}

		$image_attr = array(
			'class'         => 'jgor-gblk-hero__image',
			'loading'       => false,
			'fetchpriority' => 'high',
			'decoding'      => 'async',
			'sizes'         => $sizes,
		);

		// An empty alt text falls back to the one stored in the media library.
		if ( '' !== $alt ) {
			$image_attr['alt'] = $alt;
		}

		return wp_get_attachment_image( $media_id, 'full', false, $image_attr );
	}
}

if ( ! function_exists( 'jgor_gblk_hero_box_attributes' ) ) {
	/**
	 * Returns class and style of the teaser box.
	 *
	 * Colours, border, shadow and padding of the block belong to the box, not to
	 * the full-screen wrapper. block.json keeps WordPress from putting them on
	 * the wrapper; this function turns the same attributes into class and style
	 * for the box, the way the block supports of core do it.
	 *
	 * The style engine sanitises every declaration; the class names are
	 * sanitised here once more, because they end up in a class attribute.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return array{class: string, style: string} Class names and inline style,
	 *                                             both possibly empty.
	 */
	function jgor_gblk_hero_box_attributes( $attributes ) {
		$style  = isset( $attributes['style'] ) && is_array( $attributes['style'] ) ? $attributes['style'] : array();
		$border = isset( $style['border'] ) && is_array( $style['border'] ) ? $style['border'] : array();

		// A colour from the palette is stored as a slug, a custom one in the style.
		$colors = array(
			'text'       => null,
			'background' => null,
		);

		foreach ( array(
			'text'       => 'textColor',
			'background' => 'backgroundColor',
		) as $property => $slug_attribute ) {
			if ( isset( $attributes[ $slug_attribute ] ) && is_string( $attributes[ $slug_attribute ] ) && '' !== $attributes[ $slug_attribute ] ) {
				$colors[ $property ] = 'var:preset|color|' . $attributes[ $slug_attribute ];
			} elseif ( isset( $style['color'][ $property ] ) ) {
				$colors[ $property ] = $style['color'][ $property ];
			}
		}

		$border_styles = array();

		foreach ( array( 'radius', 'width' ) as $property ) {
			if ( isset( $border[ $property ] ) ) {
				$border_styles[ $property ] = is_numeric( $border[ $property ] ) ? $border[ $property ] . 'px' : $border[ $property ];
			}
		}

		if ( isset( $border['style'] ) ) {
			$border_styles['style'] = $border['style'];
		}

		if ( isset( $attributes['borderColor'] ) && is_string( $attributes['borderColor'] ) && '' !== $attributes['borderColor'] ) {
			$border_styles['color'] = 'var:preset|color|' . $attributes['borderColor'];
		} elseif ( isset( $border['color'] ) ) {
			$border_styles['color'] = $border['color'];
		}

		// Borders that differ per side.
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			if ( isset( $border[ $side ] ) && is_array( $border[ $side ] ) ) {
				$border_styles[ $side ] = array(
					'width' => $border[ $side ]['width'] ?? null,
					'color' => $border[ $side ]['color'] ?? null,
					'style' => $border[ $side ]['style'] ?? null,
				);
			}
		}

		// One call per block support, with the options core uses for each of them.
		$parts = array(
			wp_style_engine_get_styles(
				array( 'color' => $colors ),
				array( 'convert_vars_to_classnames' => true )
			),
			wp_style_engine_get_styles( array( 'border' => $border_styles ) ),
			wp_style_engine_get_styles( array( 'spacing' => array( 'padding' => $style['spacing']['padding'] ?? null ) ) ),
			wp_style_engine_get_styles( array( 'shadow' => $style['shadow'] ?? null ) ),
		);

		$classes = array();
		$css     = '';

		foreach ( $parts as $part ) {
			if ( ! empty( $part['classnames'] ) ) {
				foreach ( explode( ' ', $part['classnames'] ) as $class ) {
					$class = sanitize_html_class( $class );

					if ( '' !== $class ) {
						$classes[] = $class;
					}
				}
			}

			if ( ! empty( $part['css'] ) ) {
				$css .= $part['css'];
			}
		}

		return array(
			'class' => implode( ' ', array_unique( $classes ) ),
			'style' => $css,
		);
	}
}
