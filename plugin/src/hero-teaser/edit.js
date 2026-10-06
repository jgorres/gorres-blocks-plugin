/**
 * Editor view of the hero teaser block.
 *
 * Shows the same three layers as the front end (image, track, teaser box), so
 * the effect can be tried out by scrolling the editor canvas. Without an
 * image the block shows the media placeholder in place of the image; the box
 * stays editable.
 */

import { __ } from '@wordpress/i18n';
import {
	BlockControls,
	InspectorControls,
	MediaPlaceholder,
	MediaReplaceFlow,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	FocalPointPicker,
	PanelBody,
	RangeControl,
	TextareaControl,
	ToolbarButton,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Core blocks use it for the same purpose; no stable alternative exists.
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- See above.
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

import { useBoxProps } from './box-props';

const ALLOWED_MEDIA_TYPES = [ 'image' ];

const TEMPLATE = [
	[
		'core/heading',
		{
			level: 2,
			placeholder: __( 'Teaser headline', 'gorres-blocks' ),
		},
	],
	[
		'core/paragraph',
		{
			placeholder: __(
				'A few sentences that make people read on …',
				'gorres-blocks'
			),
		},
	],
];

const BOX_ALIGN_OPTIONS = [
	{ value: 'left', label: __( 'Left', 'gorres-blocks' ) },
	{ value: 'center', label: __( 'Center', 'gorres-blocks' ) },
	{ value: 'right', label: __( 'Right', 'gorres-blocks' ) },
];

/**
 * Clamps a number to a range, mirroring jgor_gblk_hero_clamp() in PHP.
 *
 * @param {*}      value    Raw value.
 * @param {number} min      Lower bound.
 * @param {number} max      Upper bound.
 * @param {number} fallback Value for anything that is not a number.
 * @return {number} Value within the bounds.
 */
function clamp( value, min, max, fallback ) {
	const number = Number( value );

	if ( ! Number.isFinite( number ) ) {
		return fallback;
	}

	return Math.max( min, Math.min( max, Math.round( number ) ) );
}

/**
 * Renders the editor view.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} Editor markup.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		mediaId,
		mediaUrl,
		mediaAlt,
		focalPoint,
		dim,
		boxStart,
		boxAlign,
		boxWidth,
		topOffset,
	} = attributes;

	const hasImage = mediaId > 0 && '' !== mediaUrl;
	const align = [ 'left', 'center', 'right' ].includes( boxAlign )
		? boxAlign
		: 'left';

	const style = {
		'--jgor-gblk-hero-offset': `${ clamp( topOffset, 0, 300, 0 ) }px`,
		'--jgor-gblk-hero-dim': clamp( dim, 0, 90, 20 ) / 100,
		'--jgor-gblk-hero-start': clamp( boxStart, 0, 90, 50 ) / 100,
		'--jgor-gblk-hero-box-width': `${ clamp( boxWidth, 20, 100, 40 ) }%`,
	};

	if ( focalPoint ) {
		style[ '--jgor-gblk-hero-focal-x' ] = `${ focalPoint.x * 100 }%`;
		style[ '--jgor-gblk-hero-focal-y' ] = `${ focalPoint.y * 100 }%`;
	}

	const blockProps = useBlockProps( {
		className: [
			'jgor-gblk-hero',
			`is-box-${ align }`,
			hasImage ? '' : 'has-no-image',
		]
			.filter( Boolean )
			.join( ' ' ),
		style,
	} );

	const boxProps = useBoxProps( attributes );
	const innerBlocksProps = useInnerBlocksProps( boxProps, {
		template: TEMPLATE,
	} );

	/**
	 * Stores the chosen image. Only images from the media library are
	 * accepted: the front end needs the attachment ID for srcset and sizes.
	 *
	 * @param {Object} media Media object from the media modal.
	 */
	const onSelectImage = ( media ) => {
		if ( ! media?.id || ! media?.url ) {
			return;
		}

		setAttributes( {
			mediaId: media.id,
			mediaUrl: media.url,
			mediaAlt: '',
			focalPoint: undefined,
		} );
	};

	const onRemoveImage = () => {
		setAttributes( {
			mediaId: 0,
			mediaUrl: '',
			mediaAlt: '',
			focalPoint: undefined,
		} );
	};

	return (
		<>
			{ hasImage && (
				<BlockControls group="other">
					<MediaReplaceFlow
						mediaId={ mediaId }
						mediaURL={ mediaUrl }
						allowedTypes={ ALLOWED_MEDIA_TYPES }
						accept="image/*"
						onSelect={ onSelectImage }
						name={ __( 'Replace image', 'gorres-blocks' ) }
					/>
					<ToolbarButton onClick={ onRemoveImage }>
						{ __( 'Remove image', 'gorres-blocks' ) }
					</ToolbarButton>
				</BlockControls>
			) }

			<InspectorControls>
				<PanelBody title={ __( 'Image', 'gorres-blocks' ) }>
					{ hasImage && (
						<>
							<FocalPointPicker
								__nextHasNoMarginBottom
								label={ __( 'Focal point', 'gorres-blocks' ) }
								url={ mediaUrl }
								value={ focalPoint }
								onChange={ ( value ) =>
									setAttributes( { focalPoint: value } )
								}
							/>
							<TextareaControl
								__nextHasNoMarginBottom
								label={ __(
									'Alternative text',
									'gorres-blocks'
								) }
								help={ __(
									'Leave empty to use the alternative text from the media library.',
									'gorres-blocks'
								) }
								value={ mediaAlt }
								onChange={ ( value ) =>
									setAttributes( { mediaAlt: value } )
								}
							/>
						</>
					) }
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Dimming (%)', 'gorres-blocks' ) }
						value={ dim }
						min={ 0 }
						max={ 90 }
						step={ 5 }
						onChange={ ( value ) =>
							setAttributes( { dim: clamp( value, 0, 90, 20 ) } )
						}
					/>
				</PanelBody>

				<PanelBody title={ __( 'Teaser box', 'gorres-blocks' ) }>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						isBlock
						label={ __( 'Horizontal position', 'gorres-blocks' ) }
						value={ align }
						onChange={ ( value ) =>
							setAttributes( { boxAlign: value } )
						}
					>
						{ BOX_ALIGN_OPTIONS.map( ( option ) => (
							<ToggleGroupControlOption
								key={ option.value }
								value={ option.value }
								label={ option.label }
							/>
						) ) }
					</ToggleGroupControl>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Start position (%)', 'gorres-blocks' ) }
						help={ __(
							'Distance of the box from the top edge of the image when the page loads, as a share of the screen height.',
							'gorres-blocks'
						) }
						value={ boxStart }
						min={ 0 }
						max={ 90 }
						step={ 5 }
						onChange={ ( value ) =>
							setAttributes( {
								boxStart: clamp( value, 0, 90, 50 ),
							} )
						}
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Width (%)', 'gorres-blocks' ) }
						help={ __(
							'Share of the available width. On narrow screens the box uses at least 20rem or the full width.',
							'gorres-blocks'
						) }
						value={ boxWidth }
						min={ 20 }
						max={ 100 }
						step={ 5 }
						onChange={ ( value ) =>
							setAttributes( {
								boxWidth: clamp( value, 20, 100, 40 ),
							} )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Fixed header', 'gorres-blocks' ) }
					initialOpen={ false }
				>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __(
							'Offset from the top (px)',
							'gorres-blocks'
						) }
						help={ __(
							'Height of a fixed header of the theme, so the image sticks right below it. The admin bar is taken into account automatically.',
							'gorres-blocks'
						) }
						value={ topOffset }
						min={ 0 }
						max={ 300 }
						onChange={ ( value ) =>
							setAttributes( {
								topOffset: clamp( value, 0, 300, 0 ),
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="jgor-gblk-hero__media">
					{ hasImage ? (
						<img
							className="jgor-gblk-hero__image"
							src={ mediaUrl }
							alt=""
						/>
					) : (
						<MediaPlaceholder
							icon="cover-image"
							labels={ {
								title: __( 'Hero image', 'gorres-blocks' ),
								instructions: __(
									'Choose an image from the media library or upload one.',
									'gorres-blocks'
								),
							} }
							allowedTypes={ ALLOWED_MEDIA_TYPES }
							accept="image/*"
							onSelect={ onSelectImage }
						/>
					) }
				</div>
				<div className="jgor-gblk-hero__track">
					<div { ...innerBlocksProps } />
				</div>
			</div>
		</>
	);
}
