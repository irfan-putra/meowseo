/**
 * Subpages Block - Edit Component
 *
 * Requirements: 9.7, 9.8, 9.9, 9.10
 */

import React, { useEffect, useState } from 'react';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	ToggleControl,
	Spinner,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { SubpagesAttributes } from './index';
import { SubpagesList } from './components/subpages-list';

interface EditProps {
	attributes: SubpagesAttributes;
	setAttributes: ( attrs: Partial< SubpagesAttributes > ) => void;
}

interface SubPage {
	id: number;
	title: string;
	link: string;
	featured_image_url?: string;
	children?: SubPage[];
}

export const Edit: React.FC< EditProps > = ( {
	attributes,
	setAttributes,
} ) => {
	const blockProps = useBlockProps();
	const [ subpages, setSubpages ] = useState< SubPage[] >( [] );
	const [ isLoading, setIsLoading ] = useState( false );

	const { depth, showThumbnails } = attributes;

	// Get current post ID
	const postId = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return editor?.getCurrentPostId?.();
	}, [] );

	// Fetch subpages
	useEffect( () => {
		if ( ! postId ) {
			return;
		}

		setIsLoading( true );

		apiFetch( {
			path: `/meowseo/v1/subpages`,
			method: 'POST',
			data: {
				post_id: postId,
				depth,
			},
		} )
			.then( ( pages: SubPage[] ) => {
				setSubpages( pages );
				setIsLoading( false );
			} )
			.catch( () => {
				setIsLoading( false );
			} );
	}, [ postId, depth ] );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Subpages Settings', 'meowseo' ) }>
					<RangeControl
						label={ __( 'Depth', 'meowseo' ) }
						value={ depth }
						onChange={ ( value ) =>
							setAttributes( { depth: value } )
						}
						min={ 1 }
						max={ 3 }
						help={ __(
							'How many levels deep to display (1-3)',
							'meowseo'
						) }
					/>
					<ToggleControl
						label={ __( 'Show Thumbnails', 'meowseo' ) }
						checked={ showThumbnails }
						onChange={ ( value ) =>
							setAttributes( { showThumbnails: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="meowseo-subpages">
					<h2 className="meowseo-subpages__title">
						{ __( 'Subpages', 'meowseo' ) }
					</h2>
					{ isLoading ? (
						<div className="meowseo-subpages__loading">
							<Spinner />
						</div>
					) : (
						<SubpagesList
							pages={ subpages }
							showThumbnails={ showThumbnails }
						/>
					) }
				</div>
			</div>
		</>
	);
};
