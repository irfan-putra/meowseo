import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { sitemap } from '@wordpress/icons';
import Edit from './edit';
import Save from './save';

registerBlockType( 'meowseo/siblings', {
	title: __( 'Sibling Pages', 'meowseo' ),
	description: __( 'Display pages with the same parent page', 'meowseo' ),
	category: 'meowseo',
	icon: sitemap,
	keywords: [
		__( 'siblings', 'meowseo' ),
		__( 'pages', 'meowseo' ),
		__( 'navigation', 'meowseo' ),
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
		align: [ 'wide', 'full' ],
		html: false,
	},
	edit: Edit,
	save: Save,
} );
