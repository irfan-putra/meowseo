/**
 * Related Posts List Component
 *
 * Requirements: 9.4, 9.5, 9.6, 9.9, 9.10
 */

import React from 'react';
import { __ } from '@wordpress/i18n';

interface RelatedPost {
	id: number;
	title: string;
	excerpt: string;
	featured_media: number;
	link: string;
	featured_image_url?: string;
}

interface RelatedPostsListProps {
	posts: RelatedPost[];
	displayStyle: 'list' | 'grid';
	showExcerpt: boolean;
	showThumbnail: boolean;
}

export const RelatedPostsList: React.FC< RelatedPostsListProps > = ( {
	posts,
	displayStyle,
	showExcerpt,
	showThumbnail,
} ) => {
	if ( posts.length === 0 ) {
		return (
			<div className="meowseo-related-posts__empty">
				<p>{ __( 'No related posts found.', 'meowseo' ) }</p>
			</div>
		);
	}

	return (
		<div
			className={ `meowseo-related-posts__list meowseo-related-posts__list--${ displayStyle }` }
			role="list"
		>
			{ posts.map( ( post ) => (
				<article
					key={ post.id }
					className="meowseo-related-posts__item"
					role="listitem"
				>
					{ showThumbnail && post.featured_image_url && (
						<div className="meowseo-related-posts__thumbnail">
							<img
								src={ post.featured_image_url }
								alt={ post.title }
								loading="lazy"
							/>
						</div>
					) }
					<div className="meowseo-related-posts__content">
						<h3 className="meowseo-related-posts__post-title">
							<a href={ post.link } rel="bookmark">
								{ post.title }
							</a>
						</h3>
						{ showExcerpt && post.excerpt && (
							<div
								className="meowseo-related-posts__excerpt"
								dangerouslySetInnerHTML={ {
									__html: post.excerpt,
								} }
							/>
						) }
					</div>
				</article>
			) ) }
		</div>
	);
};
