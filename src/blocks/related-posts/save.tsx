/**
 * Related Posts Block - Save Component
 *
 * Requirements: 9.4, 9.5, 9.6, 9.9, 9.10
 */

import React from 'react';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { RelatedPostsAttributes } from './index';

interface SaveProps {
	attributes: RelatedPostsAttributes;
}

export const Save: React.FC< SaveProps > = ( { attributes } ) => {
	const blockProps = useBlockProps.save();
	const { displayStyle, showExcerpt, showThumbnail } = attributes;

	return (
		<div { ...blockProps }>
			<div
				className={ `meowseo-related-posts meowseo-related-posts--${ displayStyle }` }
				data-show-excerpt={ showExcerpt }
				data-show-thumbnail={ showThumbnail }
			>
				<h2 className="meowseo-related-posts__title">
					{ __( 'Related Posts', 'meowseo' ) }
				</h2>
				<div className="meowseo-related-posts__list" role="list">
					{ /* Related posts will be rendered server-side */ }
				</div>
			</div>
		</div>
	);
};
