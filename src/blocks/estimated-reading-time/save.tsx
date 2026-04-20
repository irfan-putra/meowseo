/**
 * Estimated Reading Time Block - Save Component
 *
 * Requirements: 9.1, 9.2, 9.3, 9.9, 9.10
 */

import React from 'react';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { ReadingTimeAttributes } from './index';
import { calculateReadingTime } from './utils';

interface SaveProps {
	attributes: ReadingTimeAttributes;
}

export const Save: React.FC< SaveProps > = ( { attributes } ) => {
	const blockProps = useBlockProps.save();
	const { wordsPerMinute, showIcon, customText } = attributes;

	// Get post content from the block's context
	// Note: In save, we need to calculate based on the post content
	// This will be handled by server-side rendering
	const readingTime = '{{ reading_time }}'; // Placeholder for server-side calculation

	return (
		<div { ...blockProps }>
			<div className="meowseo-reading-time" role="status">
				{ showIcon && (
					<span className="meowseo-reading-time__icon">🕐</span>
				) }
				<span className="meowseo-reading-time__text">
					{ customText } { readingTime }{ ' ' }
					{ __( 'min read', 'meowseo' ) }
				</span>
			</div>
		</div>
	);
};
