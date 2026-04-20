import React from 'react';
import { useBlockProps } from '@wordpress/block-editor';
import { calculateReadingTime, formatReadingTime } from '../utils/content';

interface SaveProps {
	attributes: {
		wordsPerMinute: number;
		showIcon: boolean;
		customText: string;
		alignment: string;
	};
}

const Save: React.FC< SaveProps > = ( { attributes } ) => {
	const { wordsPerMinute, showIcon, customText, alignment } = attributes;
	const blockProps = useBlockProps.save( {
		className: `align${ alignment }`,
	} );

	// Note: Reading time is calculated server-side in PHP
	// This is a placeholder that will be replaced by server-side rendering

	return (
		<div { ...blockProps }>
			<div className="meowseo-reading-time">
				{ showIcon && (
					<span className="meowseo-reading-time-icon">🕐</span>
				) }
				{ customText && (
					<span className="meowseo-reading-time-label">
						{ customText }
					</span>
				) }
				<span className="meowseo-reading-time-value">
					{ /* Server-side rendered content */ }
				</span>
			</div>
		</div>
	);
};

export default Save;
