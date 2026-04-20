import React from 'react';
import {
	InspectorControls,
	BlockControls,
	AlignmentToolbar,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	ToggleControl,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { calculateReadingTime, formatReadingTime } from '../utils/content';
import { generateUniqueId } from '../utils/accessibility';
import './editor.css';

interface EditProps {
	attributes: {
		wordsPerMinute: number;
		showIcon: boolean;
		customText: string;
		alignment: string;
	};
	setAttributes: ( attrs: Record< string, any > ) => void;
}

const Edit: React.FC< EditProps > = ( { attributes, setAttributes } ) => {
	const { wordsPerMinute, showIcon, customText, alignment } = attributes;
	const blockProps = useBlockProps( {
		className: `align${ alignment }`,
	} );

	// Get current post content
	const postContent = useSelect( ( select ) => {
		const editor = select( 'core/editor' ) as any;
		return editor?.getEditedPostAttribute?.( 'content' ) || '';
	}, [] );

	const readingTime = calculateReadingTime( postContent, wordsPerMinute );
	const readingTimeText = formatReadingTime( readingTime );
	const labelId = generateUniqueId( 'reading-time' );

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ alignment }
					onChange={ ( newAlignment ) =>
						setAttributes( { alignment: newAlignment } )
					}
				/>
			</BlockControls>

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
						placeholder={ __(
							'e.g., "Estimated reading time:"',
							'meowseo'
						) }
						help={ __(
							'Optional text to display before the reading time',
							'meowseo'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div
					className="meowseo-reading-time"
					id={ labelId }
					role="status"
					aria-live="polite"
				>
					{ showIcon && (
						<span className="meowseo-reading-time-icon">🕐</span>
					) }
					{ customText && (
						<span className="meowseo-reading-time-label">
							{ customText }
						</span>
					) }
					<span
						className="meowseo-reading-time-value"
						aria-label={ readingTimeText }
					>
						{ readingTimeText }
					</span>
				</div>
			</div>
		</>
	);
};

export default Edit;
