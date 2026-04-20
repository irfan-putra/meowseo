/**
 * Siblings Block - Edit Component
 *
 * Requirements: 9.7, 9.8, 9.9, 9.10
 */

import React, { useEffect, useState } from 'react';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	Spinner,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { SiblingsAttributes } from './index';
import { SiblingsList } from './components/siblings-list';

interface EditProps {
	attributes: SiblingsAttributes;
	setAttributes: ( attrs: Partial< SiblingsAttributes > ) => void;
}

interface SiblingPost {
	id: number;
	title: string;
	link: string;
	featured_image_url?: string;
}

export const Edit: React.FC< EditProps > = ( {
	attributes,
	setAttributes,
} ) => {
	const blockProps = useBlockProps();
	const [ siblings, setSiblings ] = useState< SiblingPost[] >( [] );
	const [ isLoading, setIsLoading ] = useState( false );

	const { showThumbnails, orderBy } = attributes;

	// Get current post ID
	const postId = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return editor?.getCurrentPostId?.();
	}, [] );

	// Fetch sibling posts
	useEffect( () => {
		if ( ! postId ) {
			return;
		}

		setIsLoading( true );

		apiFetch( {
			path: `/meowseo/v1/siblings`,
			method: 'POST',
			data: {
				post_id: postId,
				order_by: orderBy,
			},
		} )
			.then( ( posts: SiblingPost[] ) => {
				setSiblings( posts );
				setIsLoading( false );
			} )
			.catch( () => {
				setIsLoading( false );
			} );
	}, [ postId, orderBy ] );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Siblings Settings', 'meowseo' ) }>
					<SelectControl
						label={ __( 'Order By', 'meowseo' ) }
						value={ orderBy }
						options={ [
							{
								label: __( 'Menu Order', 'meowseo' ),
								value: 'menu_order',
							},
							{ label: __( 'Title', 'meowseo' ), value: 'title' },
							{ label: __( 'Date', 'meowseo' ), value: 'date' },
						] }
						onChange={ ( value ) =>
							setAttributes( {
								orderBy: value as
									| 'menu_order'
									| 'title'
									| 'date',
							} )
						}
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
				<div className="meowseo-siblings">
					<h2 className="meowseo-siblings__title">
						{ __( 'Related Pages', 'meowseo' ) }
					</h2>
					{ isLoading ? (
						<div className="meowseo-siblings__loading">
							<Spinner />
						</div>
					) : (
						<SiblingsList
							posts={ siblings }
							showThumbnails={ showThumbnails }
						/>
					) }
				</div>
			</div>
		</>
	);
};
