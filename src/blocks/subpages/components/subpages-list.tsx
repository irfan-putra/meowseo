/**
 * Subpages List Component
 *
 * Requirements: 9.7, 9.8, 9.9, 9.10
 */

import React from 'react';
import { __ } from '@wordpress/i18n';

interface SubPage {
	id: number;
	title: string;
	link: string;
	featured_image_url?: string;
	children?: SubPage[];
}

interface SubpagesListProps {
	pages: SubPage[];
	showThumbnails: boolean;
	level?: number;
}

export const SubpagesList: React.FC< SubpagesListProps > = ( {
	pages,
	showThumbnails,
	level = 1,
} ) => {
	if ( pages.length === 0 ) {
		return (
			<div className="meowseo-subpages__empty">
				<p>{ __( 'No subpages found.', 'meowseo' ) }</p>
			</div>
		);
	}

	return (
		<ul
			className={ `meowseo-subpages__list meowseo-subpages__list--level-${ level }` }
			role="list"
		>
			{ pages.map( ( page ) => (
				<li
					key={ page.id }
					className="meowseo-subpages__item"
					role="listitem"
				>
					<div className="meowseo-subpages__page">
						{ showThumbnails && page.featured_image_url && (
							<div className="meowseo-subpages__thumbnail">
								<img
									src={ page.featured_image_url }
									alt={ page.title }
									loading="lazy"
								/>
							</div>
						) }
						<a
							href={ page.link }
							className="meowseo-subpages__link"
							rel="bookmark"
						>
							{ page.title }
						</a>
					</div>
					{ page.children && page.children.length > 0 && (
						<SubpagesList
							pages={ page.children }
							showThumbnails={ showThumbnails }
							level={ level + 1 }
						/>
					) }
				</li>
			) ) }
		</ul>
	);
};
