/**
 * Saves the inner blocks only.
 *
 * Image, track and wrapper are created server side in render.php.
 */

import { InnerBlocks } from '@wordpress/block-editor';

export default function save() {
	return <InnerBlocks.Content />;
}
