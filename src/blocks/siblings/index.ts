/**
 * Siblings Block
 *
 * Displays sibling posts (posts with the same parent).
 * Requirements: 9.7, 9.8
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Edit } from './edit';
import { Save } from './save';

export interface SiblingsAttributes {
	showThumbnails: boolean;
	orderBy: 'menu_order' | 'title' | 'date';
}

export const SiblingsBlock = {
	register() {
		registerBlockType( 'meowseo/siblings', {
			title: __( 'Sibling Posts', 'meowseo' ),
			description: __(
				'Display posts with the same parent page',
				'meowseo'
			),
			category: 'meowseo',
			icon: 'networking',
			keywords: [
				__( 'siblings', 'meowseo' ),
				__( 'posts', 'meowseo' ),
				__( 'seo', 'meowseo' ),
			],
			attributes: {
				showThumbnails: {
					type: 'boolean',
					default: true,
				},
				orderBy: {
					type: 'string',
					default: 'menu_order',
					enum: [ 'menu_order', 'title', 'date' ],
				},
			},
			supports: {
				html: false,
				align: [ 'full', 'wide' ],
			},
			edit: Edit,
			save: Save,
		} );
	},
};
