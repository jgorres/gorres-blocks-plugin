/**
 * Registration of the hero teaser block.
 *
 * A screen-high image with a teaser box on top: the box scrolls away first,
 * then the image follows and the post comes up.
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit: Edit,
	save,
} );
