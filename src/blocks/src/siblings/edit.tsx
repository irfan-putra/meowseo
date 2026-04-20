import React, { useMemo } from 'react';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { apiFetch } from '@wordpress/api-fetch';
import { createAccessibleHeading } from '../utils/accessibility';
import './editor.css';

interface SiblingPage {
	id: number;
	title: string;
	link: string;
	featured_media: number;
	menu_order: number;
}

interface EditProps {
	attributes: {
		showThumbnails: boolean;
		orderBy: 'menu_order' | 'title' | 'date';
	};
	setAttributes: ( attrs: Record< string, any > ) => void;
}

const Edit: React.FC< EditProps > = ( { attributes, setAttributes } ) => {
	const { showThumbnails, orderBy } = attributes;

	const blockProps = useBlockProps( {
		className: 'meowseo-siblings',
	} );

	const currentPostId = useSelect( ( select ) => {
		const editor = select( 'core/editor' ) as any;
		return editor?.getCurrentPostId?.();
	}, [] );

	const [ siblings, setSiblings ] = React.useState< SiblingPage[] >( [] );
	const [ isLoading, setIsLoading ] = React.useState( false );

	// Fetch sibling pages
	React.useEffect( () => {
		if ( ! currentPostId ) {
			return;
		}

		setIsLoading( true );
		apiFetch( {
			path: `/meowseo/v1/siblings?post_id=${ currentPostId }&order_by=${ orderBy }`,
		} )
			.then( ( pages: SiblingPage[] ) => {
				setSiblings( pages );
			} )
			.catch( () => {
				setSiblings( [] );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [ currentPostId, orderBy ] );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Sibling Pages Settings', 'meowseo' ) }>
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
				{ createAccessibleHeading(
					2,
					__( 'Sibling Pages', 'meowseo' )
				) }

				{ isLoading && <Spinner /> }

				{ ! isLoading && siblings.length === 0 && (
					<p className="meowseo-siblings-empty">
						{ __( 'No sibling pages found', 'meowseo' ) }
					</p>
				) }

				{ ! isLoading && siblings.length > 0 && (
					<nav
						className="meowseo-siblings-list"
						aria-label={ __( 'Sibling pages', 'meowseo' ) }
					>
						<ul role="list">
							{ siblings.map( ( sibling ) => (
								<li
									key={ sibling.id }
									className="meowseo-sibling-item"
								>
									{ showThumbnails &&
										sibling.featured_media && (
											<div className="meowseo-sibling-thumbnail">
												<img
													src={ `/wp-json/wp/v2/media/${ sibling.featured_media }` }
													alt={ sibling.title }
												/>
											</div>
										) }
									<a
										href={ sibling.link }
										className="meowseo-sibling-link"
									>
										{ sibling.title }
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
