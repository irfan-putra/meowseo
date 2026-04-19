/**
 * BookForm Component
 *
 * Form for Book schema configuration.
 * Uses useEntityPropBinding for _meowseo_schema_config postmeta.
 *
 * Requirements: 1.10
 */

import {
	TextControl,
	TextareaControl,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';
import { useCallback } from '@wordpress/element';

interface BookSchema {
	name: string;
	author: string;
	isbn?: string;
	numberOfPages?: string;
	publisher?: string;
	datePublished?: string;
	bookFormat?: string;
}

const BookForm: React.FC = () => {
	const [ schemaConfigJson, setSchemaConfigJson ] = useEntityPropBinding(
		'_meowseo_schema_config'
	);

	const schemaConfig: BookSchema = schemaConfigJson
		? ( () => {
				try {
					return JSON.parse( schemaConfigJson );
				} catch {
					return {
						name: '',
						author: '',
						isbn: '',
						numberOfPages: '',
						publisher: '',
						datePublished: '',
						bookFormat: 'Hardcover',
					};
				}
		  } )()
		: {
				name: '',
				author: '',
				isbn: '',
				numberOfPages: '',
				publisher: '',
				datePublished: '',
				bookFormat: 'Hardcover',
		  };

	const updateField = useCallback(
		( field: keyof BookSchema, value: string ) => {
			const updatedConfig = { ...schemaConfig, [ field ]: value };
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	const bookFormatOptions = [
		{ label: __( 'Hardcover', 'meowseo' ), value: 'Hardcover' },
		{ label: __( 'Paperback', 'meowseo' ), value: 'Paperback' },
		{ label: __( 'eBook', 'meowseo' ), value: 'EBook' },
		{ label: __( 'Audiobook', 'meowseo' ), value: 'Audiobook' },
	];

	return (
		<div className="meowseo-schema-form">
			<TextControl
				label={ __( 'Book Title', 'meowseo' ) }
				value={ schemaConfig.name }
				onChange={ ( value ) => updateField( 'name', value ) }
				required
			/>

			<TextControl
				label={ __( 'Author Name', 'meowseo' ) }
				value={ schemaConfig.author }
				onChange={ ( value ) => updateField( 'author', value ) }
				required
			/>

			<TextControl
				label={ __( 'ISBN', 'meowseo' ) }
				value={ schemaConfig.isbn || '' }
				onChange={ ( value ) => updateField( 'isbn', value ) }
				help={ __( 'International Standard Book Number', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'Number of Pages', 'meowseo' ) }
				value={ schemaConfig.numberOfPages || '' }
				onChange={ ( value ) => updateField( 'numberOfPages', value ) }
				type="number"
			/>

			<TextControl
				label={ __( 'Publisher', 'meowseo' ) }
				value={ schemaConfig.publisher || '' }
				onChange={ ( value ) => updateField( 'publisher', value ) }
			/>

			<TextControl
				label={ __( 'Date Published (ISO 8601)', 'meowseo' ) }
				value={ schemaConfig.datePublished || '' }
				onChange={ ( value ) => updateField( 'datePublished', value ) }
				type="date"
			/>

			<SelectControl
				label={ __( 'Book Format', 'meowseo' ) }
				value={ schemaConfig.bookFormat || 'Hardcover' }
				options={ bookFormatOptions }
				onChange={ ( value ) => updateField( 'bookFormat', value ) }
			/>
		</div>
	);
};

export default BookForm;
