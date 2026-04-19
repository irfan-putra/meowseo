/**
 * PersonForm Component
 *
 * Form for Person schema configuration.
 * Uses useEntityPropBinding for _meowseo_schema_config postmeta.
 *
 * Requirements: 1.10
 */

import {
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';
import { useCallback } from '@wordpress/element';

interface PersonSchema {
	name: string;
	jobTitle?: string;
	description?: string;
	image?: string;
	url?: string;
	sameAs?: string;
}

const PersonForm: React.FC = () => {
	const [ schemaConfigJson, setSchemaConfigJson ] = useEntityPropBinding(
		'_meowseo_schema_config'
	);

	const schemaConfig: PersonSchema = schemaConfigJson
		? ( () => {
				try {
					return JSON.parse( schemaConfigJson );
				} catch {
					return {
						name: '',
						jobTitle: '',
						description: '',
						image: '',
						url: '',
						sameAs: '',
					};
				}
		  } )()
		: {
				name: '',
				jobTitle: '',
				description: '',
				image: '',
				url: '',
				sameAs: '',
		  };

	const updateField = useCallback(
		( field: keyof PersonSchema, value: string ) => {
			const updatedConfig = { ...schemaConfig, [ field ]: value };
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	return (
		<div className="meowseo-schema-form">
			<TextControl
				label={ __( 'Person Name', 'meowseo' ) }
				value={ schemaConfig.name }
				onChange={ ( value ) => updateField( 'name', value ) }
				required
			/>

			<TextControl
				label={ __( 'Job Title', 'meowseo' ) }
				value={ schemaConfig.jobTitle || '' }
				onChange={ ( value ) => updateField( 'jobTitle', value ) }
			/>

			<TextareaControl
				label={ __( 'Bio/Description', 'meowseo' ) }
				value={ schemaConfig.description || '' }
				onChange={ ( value ) => updateField( 'description', value ) }
				rows={ 3 }
			/>

			<TextControl
				label={ __( 'Profile Image URL', 'meowseo' ) }
				value={ schemaConfig.image || '' }
				onChange={ ( value ) => updateField( 'image', value ) }
				type="url"
			/>

			<TextControl
				label={ __( 'Website URL', 'meowseo' ) }
				value={ schemaConfig.url || '' }
				onChange={ ( value ) => updateField( 'url', value ) }
				type="url"
			/>

			<TextControl
				label={ __( 'Social Media Profiles (comma-separated URLs)', 'meowseo' ) }
				value={ schemaConfig.sameAs || '' }
				onChange={ ( value ) => updateField( 'sameAs', value ) }
				help={ __( 'e.g., https://twitter.com/handle, https://linkedin.com/in/profile', 'meowseo' ) }
			/>
		</div>
	);
};

export default PersonForm;
