/**
 * MeowSEO Gutenberg Blocks
 *
 * Entry point for registering all MeowSEO Gutenberg blocks.
 * Registers block category and initializes all block modules.
 *
 * Requirements: 9.1
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

// Import block modules
import { EstimatedReadingTimeBlock } from './estimated-reading-time';
import { RelatedPostsBlock } from './related-posts';
import { SiblingsBlock } from './siblings';
import { SubpagesBlock } from './subpages';

/**
 * Register MeowSEO block category
 * Requirements: 9.1
 */
export function registerBlockCategory() {
	// Block category registration happens via block.json
	// This is handled by WordPress automatically
}

/**
 * Initialize all MeowSEO blocks
 * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7, 9.8
 */
export function initializeBlocks() {
	// Register Estimated Reading Time block
	EstimatedReadingTimeBlock.register();

	// Register Related Posts block
	RelatedPostsBlock.register();

	// Register Siblings block
	SiblingsBlock.register();

	// Register Subpages block
	SubpagesBlock.register();
}

// Initialize blocks on document ready
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initializeBlocks );
} else {
	initializeBlocks();
}
