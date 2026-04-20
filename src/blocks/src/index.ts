import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Register MeowSEO block category
 */
if ( typeof wp !== 'undefined' && wp.blocks ) {
	wp.blocks.registerBlockCollection( 'meowseo', {
		title: __( 'MeowSEO', 'meowseo' ),
		icon: 'smiley',
	} );
}

// Import and register individual blocks
import './estimated-reading-time';
import './related-posts';
import './siblings';
import './subpages';
