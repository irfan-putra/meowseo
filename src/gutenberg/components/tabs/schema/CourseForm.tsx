/**
 * CourseForm Component
 *
 * Form for Course schema configuration.
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

interface CourseSchema {
	name: string;
	description: string;
	provider: string;
	courseCode?: string;
	courseMode?: string;
	courseWorkload?: string;
}

const CourseForm: React.FC = () => {
	const [ schemaConfigJson, setSchemaConfigJson ] = useEntityPropBinding(
		'_meowseo_schema_config'
	);

	const schemaConfig: CourseSchema = schemaConfigJson
		? ( () => {
				try {
					return JSON.parse( schemaConfigJson );
				} catch {
					return {
						name: '',
						description: '',
						provider: '',
						courseCode: '',
						courseMode: 'online',
						courseWorkload: '',
					};
				}
		  } )()
		: {
				name: '',
				description: '',
				provider: '',
				courseCode: '',
				courseMode: 'online',
				courseWorkload: '',
		  };

	const updateField = useCallback(
		( field: keyof CourseSchema, value: string ) => {
			const updatedConfig = { ...schemaConfig, [ field ]: value };
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	const courseModeOptions = [
		{ label: __( 'Online', 'meowseo' ), value: 'online' },
		{ label: __( 'Offline', 'meowseo' ), value: 'offline' },
		{ label: __( 'Mixed', 'meowseo' ), value: 'mixed' },
	];

	return (
		<div className="meowseo-schema-form">
			<TextControl
				label={ __( 'Course Name', 'meowseo' ) }
				value={ schemaConfig.name }
				onChange={ ( value ) => updateField( 'name', value ) }
				required
			/>

			<TextareaControl
				label={ __( 'Description', 'meowseo' ) }
				value={ schemaConfig.description }
				onChange={ ( value ) => updateField( 'description', value ) }
				rows={ 3 }
				required
			/>

			<TextControl
				label={ __( 'Provider Name', 'meowseo' ) }
				value={ schemaConfig.provider }
				onChange={ ( value ) => updateField( 'provider', value ) }
				required
				help={ __( 'Organization or institution offering the course', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'Course Code', 'meowseo' ) }
				value={ schemaConfig.courseCode || '' }
				onChange={ ( value ) => updateField( 'courseCode', value ) }
				help={ __( 'e.g., CS101', 'meowseo' ) }
			/>

			<SelectControl
				label={ __( 'Course Mode', 'meowseo' ) }
				value={ schemaConfig.courseMode || 'online' }
				options={ courseModeOptions }
				onChange={ ( value ) => updateField( 'courseMode', value ) }
			/>

			<TextControl
				label={ __( 'Course Workload (ISO 8601)', 'meowseo' ) }
				value={ schemaConfig.courseWorkload || '' }
				onChange={ ( value ) => updateField( 'courseWorkload', value ) }
				help={ __( 'Format: PT40H (40 hours)', 'meowseo' ) }
			/>
		</div>
	);
};

export default CourseForm;
