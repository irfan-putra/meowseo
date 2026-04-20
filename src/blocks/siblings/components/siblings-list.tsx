/**
 * Siblings List Component
 *
 * Requirements: 9.7, 9.8, 9.9, 9.10
 */

import React from 'react';
import { __ } from '@wordpress/i18n';

interface SiblingPost {
	id: number;
	title: string;
	link: string;
	featured_image_url?: string;
}

interface SiblingsListProps {
	posts: SiblingPost[];
	showThumbnails: boolean;
}

export const SiblingsList: React.FC< SiblingsListProps > = ( {
	posts,
	showThumbnails,
} ) => {
	if ( posts.length === 0 ) {
		return (
			<div className="meowseo-siblings__empty">
				<p>{ __( 'No sibling pages found.', 'meowseo' ) }</p>
			</div>
		);
	}

	return (
		<ul className="meowseo-siblings__list" role="list">
			{ posts.map( ( post ) => (
				<li
					key={ post.id }
					className="meowseo-siblings__item"
					role="listitem"
				>
					{ showThumbnails && post.featured_image_url && (
						<div className="meowseo-siblings__thumbnail">
							<img
								src={ post.featured_image_url }
								alt={ post.title }
								loading="lazy"
							/>
						</div>
					) }
					<a
						href={ post.link }
						className="meowseo-siblings__link"
						rel="bookmark"
					>
						{ post.title }
					</a>
				</li>
			) ) }
		</ul>
	);
};
