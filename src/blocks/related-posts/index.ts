/**
 * Related Posts Block
 *
 * Displays related posts based on keyword, category, or tag matching.
 * Requirements: 9.4, 9.5, 9.6
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Edit } from './edit';
import { Save } from './save';

export interface RelatedPostsAttributes {
	numberOfPosts: number;
	displayStyle: 'list' | 'grid';
	showExcerpt: boolean;
	showThumbnail: boolean;
	relationshipType: 'keyword' | 'category' | 'tag';
}

export const RelatedPostsBlock = {
	register() {
		registerBlockType( 'meowseo/related-posts', {
			title: __( 'Related Posts', 'meowseo' ),
			description: __(
				'Display posts related by keyword, category, or tag',
				'meowseo'
			),
			category: 'meowseo',
			icon: 'link',
			keywords: [
				__( 'related', 'meowseo' ),
				__( 'posts', 'meowseo' ),
				__( 'seo', 'meowseo' ),
			],
			attributes: {
				numberOfPosts: {
					type: 'number',
					default: 3,
				},
				displayStyle: {
					type: 'string',
					default: 'list',
					enum: [ 'list', 'grid' ],
				},
				showExcerpt: {
					type: 'boolean',
					default: true,
				},
				showThumbnail: {
					type: 'boolean',
					default: true,
				},
				relationshipType: {
					type: 'string',
					default: 'keyword',
					enum: [ 'keyword', 'category', 'tag' ],
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
