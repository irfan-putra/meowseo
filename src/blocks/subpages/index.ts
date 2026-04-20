/**
 * Subpages Block
 *
 * Displays child pages of the current page.
 * Requirements: 9.7, 9.8
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Edit } from './edit';
import { Save } from './save';

export interface SubpagesAttributes {
	depth: number;
	showThumbnails: boolean;
}

export const SubpagesBlock = {
	register() {
		registerBlockType( 'meowseo/subpages', {
			title: __( 'Subpages', 'meowseo' ),
			description: __(
				'Display child pages of the current page',
				'meowseo'
			),
			category: 'meowseo',
			icon: 'list-view',
			keywords: [
				__( 'subpages', 'meowseo' ),
				__( 'children', 'meowseo' ),
				__( 'seo', 'meowseo' ),
			],
			attributes: {
				depth: {
					type: 'number',
					default: 1,
				},
				showThumbnails: {
					type: 'boolean',
					default: true,
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
