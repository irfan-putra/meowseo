import React from 'react';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	ToggleControl,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { apiFetch } from '@wordpress/api-fetch';
import { createAccessibleHeading } from '../utils/accessibility';
import './editor.css';

interface Subpage {
	id: number;
	title: string;
	link: string;
	featured_media: number;
	depth: number;
}

interface EditProps {
	attributes: {
		depth: number;
		showThumbnails: boolean;
	};
	setAttributes: ( attrs: Record< string, any > ) => void;
}

const Edit: React.FC< EditProps > = ( { attributes, setAttributes } ) => {
	const { depth, showThumbnails } = attributes;

	const blockProps = useBlockProps( {
		className: 'meowseo-subpages',
	} );

	const currentPostId = useSelect( ( select ) => {
		const editor = select( 'core/editor' ) as any;
		return editor?.getCurrentPostId?.();
	}, [] );

	const [ subpages, setSubpages ] = React.useState< Subpage[] >( [] );
	const [ isLoading, setIsLoading ] = React.useState( false );

	// Fetch subpages
	React.useEffect( () => {
		if ( ! currentPostId ) {
			return;
		}

		setIsLoading( true );
		apiFetch( {
			path: `/meowseo/v1/subpages?post_id=${ currentPostId }&depth=${ depth }`,
		} )
			.then( ( pages: Subpage[] ) => {
				setSubpages( pages );
			} )
			.catch( () => {
				setSubpages( [] );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [ currentPostId, depth ] );

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
							'How many levels deep to display subpages',
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
				{ createAccessibleHeading( 2, __( 'Subpages', 'meowseo' ) ) }

				{ isLoading && <Spinner /> }

				{ ! isLoading && subpages.length === 0 && (
					<p className="meowseo-subpages-empty">
						{ __( 'No subpages found', 'meowseo' ) }
					</p>
				) }

				{ ! isLoading && subpages.length > 0 && (
					<nav
						className="meowseo-subpages-list"
						aria-label={ __( 'Subpages', 'meowseo' ) }
					>
						<ul role="list">
							{ subpages.map( ( subpage ) => (
								<li
									key={ subpage.id }
									className="meowseo-subpage-item"
									style={ {
										marginLeft: `${
											( subpage.depth - 1 ) * 1.5
										}rem`,
									} }
								>
									{ showThumbnails &&
										subpage.featured_media && (
											<div className="meowseo-subpage-thumbnail">
												<img
													src={ `/wp-json/wp/v2/media/${ subpage.featured_media }` }
													alt={ subpage.title }
												/>
											</div>
										) }
									<a
										href={ subpage.link }
										className="meowseo-subpage-link"
									>
										{ subpage.title }
									</a>
								</li>
							) ) }
						</ul>
					</nav>
				) }
			</div>
		</>
	);
};

export default Edit;
