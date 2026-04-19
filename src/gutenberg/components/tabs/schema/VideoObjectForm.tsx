/**
 * VideoObjectForm Component
 *
 * Form for VideoObject schema configuration.
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

interface VideoObjectSchema {
	name: string;
	description: string;
	thumbnailUrl: string;
	uploadDate: string;
	duration?: string;
	contentUrl?: string;
	embedUrl?: string;
}

const VideoObjectForm: React.FC = () => {
	const [ schemaConfigJson, setSchemaConfigJson ] = useEntityPropBinding(
		'_meowseo_schema_config'
	);

	const schemaConfig: VideoObjectSchema = schemaConfigJson
		? ( () => {
				try {
					return JSON.parse( schemaConfigJson );
				} catch {
					return {
						name: '',
						description: '',
						thumbnailUrl: '',
						uploadDate: '',
						duration: '',
						contentUrl: '',
						embedUrl: '',
					};
				}
		  } )()
		: {
				name: '',
				description: '',
				thumbnailUrl: '',
				uploadDate: '',
				duration: '',
				contentUrl: '',
				embedUrl: '',
		  };

	const updateField = useCallback(
		( field: keyof VideoObjectSchema, value: string ) => {
			const updatedConfig = { ...schemaConfig, [ field ]: value };
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	return (
		<div className="meowseo-schema-form">
			<TextControl
				label={ __( 'Video Title', 'meowseo' ) }
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
				label={ __( 'Thumbnail URL', 'meowseo' ) }
				value={ schemaConfig.thumbnailUrl }
				onChange={ ( value ) => updateField( 'thumbnailUrl', value ) }
				type="url"
				required
				help={ __( 'URL to the video thumbnail image', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'Upload Date (ISO 8601)', 'meowseo' ) }
				value={ schemaConfig.uploadDate }
				onChange={ ( value ) => updateField( 'uploadDate', value ) }
				type="datetime-local"
				required
				help={ __( 'Format: 2024-01-20T10:00:00Z', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'Duration (ISO 8601)', 'meowseo' ) }
				value={ schemaConfig.duration || '' }
				onChange={ ( value ) => updateField( 'duration', value ) }
				help={ __( 'Format: PT5M30S (5 minutes 30 seconds)', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'Content URL', 'meowseo' ) }
				value={ schemaConfig.contentUrl || '' }
				onChange={ ( value ) => updateField( 'contentUrl', value ) }
				type="url"
				help={ __( 'Direct URL to the video file', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'Embed URL', 'meowseo' ) }
				value={ schemaConfig.embedUrl || '' }
				onChange={ ( value ) => updateField( 'embedUrl', value ) }
				type="url"
				help={ __( 'URL for embedding the video (e.g., YouTube embed URL)', 'meowseo' ) }
			/>
		</div>
	);
};

export default VideoObjectForm;
