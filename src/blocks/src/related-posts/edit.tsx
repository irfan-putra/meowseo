import React, { useMemo } from 'react';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { apiFetch } from '@wordpress/api-fetch';
import {
	createAccessibleHeading,
	createAccessibleList,
} from '../utils/accessibility';
import './editor.css';

interface RelatedPost {
	id: number;
	title: string;
	excerpt: string;
	link: string;
	featured_media: number;
}

interface EditProps {
	attributes: {
		numberOfPosts: number;
		displayStyle: 'list' | 'grid';
		showExcerpt: boolean;
		showThumbnail: boolean;
		relationshipType: 'keyword' | 'category' | 'tag';
	};
	setAttributes: ( attrs: Record< string, any > ) => void;
}

const Edit: React.FC< EditProps > = ( { attributes, setAttributes } ) => {
	const {
		numberOfPosts,
		displayStyle,
		showExcerpt,
		showThumbnail,
		relationshipType,
	} = attributes;

	const blockProps = useBlockProps( {
		className: `meowseo-related-posts meowseo-related-posts-${ displayStyle }`,
	} );

	const currentPostId = useSelect( ( select ) => {
		const editor = select( 'core/editor' ) as any;
		return editor?.getCurrentPostId?.();
	}, [] );

	const [ relatedPosts, setRelatedPosts ] = React.useState< RelatedPost[] >(
		[]
	);
	const [ isLoading, setIsLoading ] = React.useState( false );

	// Fetch related posts
	React.useEffect( () => {
		if ( ! currentPostId ) {
			return;
		}

		setIsLoading( true );
		apiFetch( {
			path: `/meowseo/v1/related-posts?post_id=${ currentPostId }&type=${ relationshipType }&limit=${ numberOfPosts }`,
		} )
			.then( ( posts: RelatedPost[] ) => {
				setRelatedPosts( posts );
			} )
			.catch( () => {
				setRelatedPosts( [] );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [ currentPostId, relationshipType, numberOfPosts ] );

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
							'How many related posts to display',
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
								label: __( 'Keyword', 'meowseo' ),
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
				{ createAccessibleHeading(
					2,
					__( 'Related Posts', 'meowseo' )
				) }

				{ isLoading && <Spinner /> }

				{ ! isLoading && relatedPosts.length === 0 && (
					<p className="meowseo-related-posts-empty">
						{ __( 'No related posts found', 'meowseo' ) }
					</p>
				) }

				{ ! isLoading && relatedPosts.length > 0 && (
					<div
						className={ `meowseo-related-posts-container meowseo-related-posts-${ displayStyle }` }
					>
						{ relatedPosts.map( ( post ) => (
							<article
								key={ post.id }
								className="meowseo-related-post-item"
								role="article"
							>
								{ showThumbnail && post.featured_media && (
									<div className="meowseo-related-post-thumbnail">
										<img
											src={ `/wp-json/wp/v2/media/${ post.featured_media }` }
											alt={ post.title }
										/>
									</div>
								) }
								<div className="meowseo-related-post-content">
									<h3 className="meowseo-related-post-title">
										<a href={ post.link }>{ post.title }</a>
									</h3>
									{ showExcerpt && post.excerpt && (
										<p className="meowseo-related-post-excerpt">
											{ post.excerpt }
										</p>
									) }
								</div>
							</article>
						) ) }
					</div>
				) }
			</div>
		</>
	);
};

export default Edit;
