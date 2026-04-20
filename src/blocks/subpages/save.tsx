/**
 * Subpages Block - Save Component
 *
 * Requirements: 9.7, 9.8, 9.9, 9.10
 */

import React from 'react';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { SubpagesAttributes } from './index';

interface SaveProps {
	attributes: SubpagesAttributes;
}

export const Save: React.FC< SaveProps > = ( { attributes } ) => {
	const blockProps = useBlockProps.save();
	const { depth, showThumbnails } = attributes;

	return (
		<div { ...blockProps }>
			<div
				className="meowseo-subpages"
				data-depth={ depth }
				data-show-thumbnails={ showThumbnails }
			>
				<h2 className="meowseo-subpages__title">
					{ __( 'Subpages', 'meowseo' ) }
				</h2>
				<div className="meowseo-subpages__list" role="list">
					{ /* Subpages will be rendered server-side */ }
				</div>
			</div>
		</div>
	);
};
