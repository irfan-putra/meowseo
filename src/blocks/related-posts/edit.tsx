/**
 * Related Posts Block - Edit Component
 *
 * Requirements: 9.4, 9.5, 9.6, 9.9, 9.10
 */

import React, { useEffect, useState } from 'react';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	Spinner,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { RelatedPostsAttributes } from './index';
import { RelatedPostsList } from './components/related-posts-list';

interface EditProps {
	attributes: RelatedPostsAttributes;
	setAttributes: ( attrs: Partial< RelatedPostsAttributes > ) => void;
}

interface RelatedPost {
	id: number;
	title: string;
	excerpt: string;
	featured_media: number;
	link: string;
}

export const Edit: React.FC< EditProps > = ( {
	attributes,
	setAttributes,
} ) => {
	const blockProps = useBlockProps();
	const [ relatedPosts, setRelatedPosts ] = useState< RelatedPost[] >( [] );
	const [ isLoading, setIsLoading ] = useState( false );

	const {
		numberOfPosts,
		displayStyle,
		showExcerpt,
		showThumbnail,
		relationshipType,
	} = attributes;

	// Get current post ID and focus keyword
	const { postId, focusKeyword } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return {
			postId: editor?.getCurrentPostId?.(),
			focusKeyword:
				editor?.getEditedPostAttribute?.( '_meowseo_focus_keyword' ) ||
				'',
		};
	}, [] );

	// Fetch related posts
	useEffect( () => {
		if ( ! postId ) {
			return;
		}

		setIsLoading( true );

		apiFetch( {
			path: `/meowseo/v1/related-posts`,
			method: 'POST',
			data: {
				post_id: postId,
				number: numberOfPosts,
				relationship_type: relationshipType,
				focus_keyword: focusKeyword,
			},
		} )
			.then( ( posts: RelatedPost[] ) => {
				setRelatedPosts( posts );
				setIsLoading( false );
			} )
			.catch( () => {
				setIsLoading( false );
			} );
	}, [ postId, numberOfPosts, relationshipType, focusKeyword ] );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Related Posts Settings', 'meowseo' ) }>
					<RangeControl
						label={ __( 'Number of Posts', 'meowseo' ) }
						value={ numberOfPosts }
						onChange={ ( value ) =>
							setAttributes( { numberOfPosts: value } )
						}
						min={ 1 }
						max={ 10 }
						help={ __(
							'How many related posts to display (1-10)',
							'meowseo'
						) }
					/>
					<SelectControl
						label={ __( 'Display Style', 'meowseo' ) }
						value={ displayStyle }
						options={ [
							{ label: __( 'List', 'meowseo' ), value: 'list' },
							{ label: __( 'Grid', 'meowseo' ), value: 'grid' },
						] }
						onChange={ ( value ) =>
							setAttributes( {
								displayStyle: value as 'list' | 'grid',
							} )
						}
					/>
					<SelectControl
						label={ __( 'Relationship Type', 'meowseo' ) }
						value={ relationshipType }
						options={ [
							{
								label: __( 'Focus Keyword', 'meowseo' ),
								value: 'keyword',
							},
							{
								label: __( 'Category', 'meowseo' ),
								value: 'category',
							},
							{ label: __( 'Tag', 'meowseo' ), value: 'tag' },
						] }
						onChange={ ( value ) =>
							setAttributes( {
								relationshipType: value as
									| 'keyword'
									| 'category'
									| 'tag',
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Show Excerpt', 'meowseo' ) }
						checked={ showExcerpt }
						onChange={ ( value ) =>
							setAttributes( { showExcerpt: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Thumbnail', 'meowseo' ) }
						checked={ showThumbnail }
						onChange={ ( value ) =>
							setAttributes( { showThumbnail: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div
					className={ `meowseo-related-posts meowseo-related-posts--${ displayStyle }` }
				>
					<h2 className="meowseo-related-posts__title">
						{ __( 'Related Posts', 'meowseo' ) }
					</h2>
					{ isLoading ? (
						<div className="meowseo-related-posts__loading">
							<Spinner />
						</div>
					) : (
						<RelatedPostsList
							posts={ relatedPosts }
							displayStyle={ displayStyle }
							showExcerpt={ showExcerpt }
							showThumbnail={ showThumbnail }
						/>
					) }
				</div>
			</div>
		</>
	);
};
