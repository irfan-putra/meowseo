/**
 * Siblings Block - Save Component
 *
 * Requirements: 9.7, 9.8, 9.9, 9.10
 */

import React from 'react';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { SiblingsAttributes } from './index';

interface SaveProps {
	attributes: SiblingsAttributes;
}

export const Save: React.FC< SaveProps > = ( { attributes } ) => {
	const blockProps = useBlockProps.save();
	const { showThumbnails } = attributes;

	return (
		<div { ...blockProps }>
			<div
				className="meowseo-siblings"
				data-show-thumbnails={ showThumbnails }
			>
				<h2 className="meowseo-siblings__title">
					{ __( 'Related Pages', 'meowseo' ) }
				</h2>
				<div className="meowseo-siblings__list" role="list">
					{ /* Siblings will be rendered server-side */ }
				</div>
			</div>
		</div>
	);
};
