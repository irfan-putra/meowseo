/**
 * Estimated Reading Time Block - Edit Component
 *
 * Requirements: 9.1, 9.2, 9.3, 9.9, 9.10
 */

import React from 'react';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	ToggleControl,
	TextControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { ReadingTimeAttributes } from './index';
import { calculateReadingTime } from './utils';

interface EditProps {
	attributes: ReadingTimeAttributes;
	setAttributes: ( attrs: Partial< ReadingTimeAttributes > ) => void;
}

export const Edit: React.FC< EditProps > = ( {
	attributes,
	setAttributes,
} ) => {
	const blockProps = useBlockProps();
	const { wordsPerMinute, showIcon, customText } = attributes;

	// Get current post content
	const postContent = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return editor?.getEditedPostContent?.() || '';
	}, [] );

	// Calculate reading time
	const readingTime = calculateReadingTime( postContent, wordsPerMinute );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Reading Time Settings', 'meowseo' ) }>
					<RangeControl
						label={ __( 'Words Per Minute', 'meowseo' ) }
						value={ wordsPerMinute }
						onChange={ ( value ) =>
							setAttributes( { wordsPerMinute: value } )
						}
						min={ 150 }
						max={ 300 }
						step={ 10 }
						help={ __(
							'Average reading speed (150-300 words per minute)',
							'meowseo'
						) }
					/>
					<ToggleControl
						label={ __( 'Show Icon', 'meowseo' ) }
						checked={ showIcon }
						onChange={ ( value ) =>
							setAttributes( { showIcon: value } )
						}
						help={ __(
							'Display a clock icon before the reading time',
							'meowseo'
						) }
					/>
					<TextControl
						label={ __( 'Custom Text', 'meowseo' ) }
						value={ customText }
						onChange={ ( value ) =>
							setAttributes( { customText: value } )
						}
						help={ __(
							'Text to display before the reading time',
							'meowseo'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div
					className="meowseo-reading-time"
					role="status"
					aria-live="polite"
				>
					{ showIcon && (
						<span className="meowseo-reading-time__icon">🕐</span>
					) }
					<span className="meowseo-reading-time__text">
						{ customText } { readingTime }{ ' ' }
						{ __( 'min read', 'meowseo' ) }
					</span>
				</div>
			</div>
		</>
	);
};
