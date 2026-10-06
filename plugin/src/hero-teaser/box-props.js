/**
 * Teaser box of the hero block in the editor.
 *
 * Colours, border, shadow and padding of the block belong to its teaser box,
 * not to the full-screen wrapper. block.json keeps the editor from putting
 * them on the wrapper; this hook turns the same attributes into class and
 * style for the box. On the front end jgor_gblk_hero_box_attributes() does the
 * same in PHP.
 */

/*
 * WordPress offers no stable helpers for block supports that skip
 * serialization. Its own blocks, the button for one, use these four. Should
 * one of them go away, the fallbacks below keep the editor working; only the
 * preview of the box then misses that part of the styling.
 */
/* eslint-disable @wordpress/no-unsafe-wp-apis */
import {
	__experimentalGetShadowClassesAndStyles as getShadowProps,
	__experimentalGetSpacingClassesAndStyles as getSpacingProps,
	__experimentalUseBorderProps as useBorderPropsCore,
	__experimentalUseColorProps as useColorPropsCore,
} from '@wordpress/block-editor';
/* eslint-enable @wordpress/no-unsafe-wp-apis */

const EMPTY_PROPS = {};

/**
 * Stands in for a helper that WordPress does not provide.
 *
 * @return {Object} Props without class and style.
 */
const noProps = () => EMPTY_PROPS;

const useBorderProps = useBorderPropsCore ?? noProps;
const useColorProps = useColorPropsCore ?? noProps;
const getShadow = getShadowProps ?? noProps;
const getSpacing = getSpacingProps ?? noProps;

/**
 * Returns class and style of the teaser box.
 *
 * @param {Object} attributes Attributes of the hero teaser block.
 * @return {{className: string, style: Object}} Props for the teaser box.
 */
export function useBoxProps( attributes ) {
	const { style } = attributes;

	const colorProps = useColorProps( attributes );
	const borderProps = useBorderProps( attributes );

	// Only the padding belongs to the box, the margin stays on the wrapper.
	const spacingProps = getSpacing( {
		style: { spacing: { padding: style?.spacing?.padding } },
	} );
	const shadowProps = getShadow( attributes );

	return {
		className: [
			'jgor-gblk-hero__box',
			colorProps.className,
			borderProps.className,
		]
			.filter( Boolean )
			.join( ' ' ),
		style: {
			...borderProps.style,
			...colorProps.style,
			...spacingProps.style,
			...shadowProps.style,
		},
	};
}
