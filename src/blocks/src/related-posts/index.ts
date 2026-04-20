import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { postList } from '@wordpress/icons';
import Edit from './edit';
import Save from './save';

registerBlockType( 'meowseo/related-posts', {
	title: __( 'Related Posts', 'meowseo' ),
	description: __(
		'Display posts related by keyword, category, or tag',
		'meowseo'
	),
	category: 'meowseo',
	icon: postList,
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
		align: [ 'wide', 'full' ],
		html: false,
	},
	edit: Edit,
	save: Save,
} );
