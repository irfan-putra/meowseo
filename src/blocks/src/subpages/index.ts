import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { sitemap } from '@wordpress/icons';
import Edit from './edit';
import Save from './save';

registerBlockType( 'meowseo/subpages', {
	title: __( 'Subpages', 'meowseo' ),
	description: __( 'Display child pages of the current page', 'meowseo' ),
	category: 'meowseo',
	icon: sitemap,
	keywords: [
		__( 'subpages', 'meowseo' ),
		__( 'children', 'meowseo' ),
		__( 'navigation', 'meowseo' ),
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
		align: [ 'wide', 'full' ],
		html: false,
	},
	edit: Edit,
	save: Save,
} );
