import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { clock } from '@wordpress/icons';
import Edit from './edit';
import Save from './save';

registerBlockType( 'meowseo/estimated-reading-time', {
	title: __( 'Estimated Reading Time', 'meowseo' ),
	description: __(
		'Display estimated reading time for the current post',
		'meowseo'
	),
	category: 'meowseo',
	icon: clock,
	keywords: [
		__( 'reading time', 'meowseo' ),
		__( 'estimated', 'meowseo' ),
		__( 'seo', 'meowseo' ),
	],
	attributes: {
		wordsPerMinute: {
			type: 'number',
			default: 200,
		},
		showIcon: {
			type: 'boolean',
			default: true,
		},
		customText: {
			type: 'string',
			default: '',
		},
		alignment: {
			type: 'string',
			default: 'left',
		},
	},
	supports: {
		align: [ 'left', 'center', 'right' ],
		html: false,
	},
	edit: Edit,
	save: Save,
} );
