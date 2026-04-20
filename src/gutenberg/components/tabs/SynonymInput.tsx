/**
 * SynonymInput Component
 *
 * Provides an input field for keyword synonyms with validation.
 *
 * Requirements: 11.1, 11.2, 11.7
 */

import { memo } from '@wordpress/element';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../hooks/useEntityPropBinding';

/**
 * SynonymInput Component
 *
 * Displays a text input for keyword synonyms with automatic postmeta persistence.
 * Supports up to 5 comma-separated synonyms.
 *
 * Requirements:
 * - 11.1: Display synonym input field in General tab
 * - 11.2: Persist value to _meowseo_keyword_synonyms postmeta
 * - 11.7: Limit to 5 synonyms per post with validation
 */
const SynonymInput: React.FC = memo( () => {
	// Use useEntityPropBinding for automatic postmeta persistence
	const [ synonyms, setSynonyms ] = useEntityPropBinding(
		'_meowseo_keyword_synonyms'
	);

	// Parse synonyms from JSON string
	const parseSynonyms = ( value: string ): string[] => {
		if ( ! value ) {
			return [];
		}
		try {
			const parsed = JSON.parse( value );
			return Array.isArray( parsed ) ? parsed : [];
		} catch {
			return [];
		}
	};

	// Convert synonyms array to display string
	const synonymArray = parseSynonyms( synonyms );
	const displayValue = synonymArray.join( ', ' );

	// Handle input change
	const handleChange = ( value: string ) => {
		// Split by comma and trim
		const parts = value
			.split( ',' )
			.map( ( s ) => s.trim() )
			.filter( ( s ) => s.length > 0 );

		// Requirement 11.7: Limit to 5 synonyms
		const limited = parts.slice( 0, 5 );

		// Store as JSON array
		setSynonyms( JSON.stringify( limited ) );
	};

	return (
		<div className="meowseo-synonym-input">
			<TextControl
				label={ __( 'Keyword Synonyms', 'meowseo' ) }
				value={ displayValue }
				onChange={ handleChange }
				help={ __(
					'Enter up to 5 keyword synonyms separated by commas. These will be analyzed alongside your focus keyword.',
					'meowseo'
				) }
				placeholder={ __(
					'e.g., SEO plugin, search optimization, ranking tool',
					'meowseo'
				) }
			/>
			{ synonymArray.length > 0 && (
				<p className="meowseo-synonym-count">
					{ synonymArray.length } / 5 { __( 'synonyms', 'meowseo' ) }
				</p>
			) }
		</div>
	);
} );

export default SynonymInput;
