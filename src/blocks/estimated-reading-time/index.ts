/**
 * Estimated Reading Time Block
 *
 * Displays calculated reading time based on word count.
 * Requirements: 9.1, 9.2, 9.3
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	ToggleControl,
	TextControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { Edit } from './edit';
import { Save } from './save';

export interface ReadingTimeAttributes {
	wordsPerMinute: number;
	showIcon: boolean;
	customText: string;
}

export const EstimatedReadingTimeBlock = {
	register() {
		registerBlockType( 'meowseo/estimated-reading-time', {
			title: __( 'Estimated Reading Time', 'meowseo' ),
			description: __(
				'Display estimated reading time based on content length',
				'meowseo'
			),
			category: 'meowseo',
			icon: 'clock',
			keywords: [
				__( 'reading', 'meowseo' ),
				__( 'time', 'meowseo' ),
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
					default: __( 'Estimated reading time:', 'meowseo' ),
				},
			},
			supports: {
				html: false,
				align: [ 'left', 'center', 'right' ],
			},
			edit: Edit,
			save: Save,
		} );
	},
};
