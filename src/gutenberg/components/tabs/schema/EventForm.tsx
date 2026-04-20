/**
 * EventForm Component
 *
 * Form for Event schema configuration.
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

interface EventSchema {
	name: string;
	startDate: string;
	location: string;
	endDate?: string;
	description?: string;
	eventStatus?: string;
	eventAttendanceMode?: string;
	organizer?: string;
	offers?: {
		url?: string;
		price?: string;
		priceCurrency?: string;
	};
}

const EventForm: React.FC = () => {
	const [ schemaConfigJson, setSchemaConfigJson ] = useEntityPropBinding(
		'_meowseo_schema_config'
	);

	const schemaConfig: EventSchema = schemaConfigJson
		? ( () => {
				try {
					return JSON.parse( schemaConfigJson );
				} catch {
					return {
						name: '',
						startDate: '',
						location: '',
						endDate: '',
						description: '',
						eventStatus: 'EventScheduled',
						eventAttendanceMode: 'OfflineEventAttendanceMode',
						organizer: '',
						offers: {},
					};
				}
		  } )()
		: {
				name: '',
				startDate: '',
				location: '',
				endDate: '',
				description: '',
				eventStatus: 'EventScheduled',
				eventAttendanceMode: 'OfflineEventAttendanceMode',
				organizer: '',
				offers: {},
		  };

	if ( ! schemaConfig.offers ) {
		schemaConfig.offers = {};
	}

	const updateField = useCallback(
		( field: keyof EventSchema, value: string ) => {
			const updatedConfig = { ...schemaConfig, [ field ]: value };
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	const updateOffersField = useCallback(
		( field: string, value: string ) => {
			const updatedConfig = {
				...schemaConfig,
				offers: { ...schemaConfig.offers, [ field ]: value },
			};
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	const eventStatusOptions = [
		{ label: __( 'Scheduled', 'meowseo' ), value: 'EventScheduled' },
		{ label: __( 'Cancelled', 'meowseo' ), value: 'EventCancelled' },
		{ label: __( 'Postponed', 'meowseo' ), value: 'EventPostponed' },
		{ label: __( 'Rescheduled', 'meowseo' ), value: 'EventRescheduled' },
	];

	const attendanceModeOptions = [
		{
			label: __( 'Offline', 'meowseo' ),
			value: 'OfflineEventAttendanceMode',
		},
		{
			label: __( 'Online', 'meowseo' ),
			value: 'OnlineEventAttendanceMode',
		},
		{ label: __( 'Mixed', 'meowseo' ), value: 'MixedEventAttendanceMode' },
	];

	return (
		<div className="meowseo-schema-form">
			<TextControl
				label={ __( 'Event Name', 'meowseo' ) }
				value={ schemaConfig.name }
				onChange={ ( value ) => updateField( 'name', value ) }
				required
			/>

			<TextareaControl
				label={ __( 'Description', 'meowseo' ) }
				value={ schemaConfig.description || '' }
				onChange={ ( value ) => updateField( 'description', value ) }
				rows={ 3 }
			/>

			<TextControl
				label={ __( 'Start Date (ISO 8601)', 'meowseo' ) }
				value={ schemaConfig.startDate }
				onChange={ ( value ) => updateField( 'startDate', value ) }
				type="datetime-local"
				required
				help={ __( 'Format: 2024-06-15T19:00:00', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'End Date (ISO 8601)', 'meowseo' ) }
				value={ schemaConfig.endDate || '' }
				onChange={ ( value ) => updateField( 'endDate', value ) }
				type="datetime-local"
				help={ __( 'Format: 2024-06-15T22:00:00', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'Location', 'meowseo' ) }
				value={ schemaConfig.location }
				onChange={ ( value ) => updateField( 'location', value ) }
				required
				help={ __( 'Venue name or address', 'meowseo' ) }
			/>

			<SelectControl
				label={ __( 'Event Status', 'meowseo' ) }
				value={ schemaConfig.eventStatus || 'EventScheduled' }
				options={ eventStatusOptions }
				onChange={ ( value ) => updateField( 'eventStatus', value ) }
			/>

			<SelectControl
				label={ __( 'Attendance Mode', 'meowseo' ) }
				value={
					schemaConfig.eventAttendanceMode ||
					'OfflineEventAttendanceMode'
				}
				options={ attendanceModeOptions }
				onChange={ ( value ) =>
					updateField( 'eventAttendanceMode', value )
				}
			/>

			<TextControl
				label={ __( 'Organizer Name', 'meowseo' ) }
				value={ schemaConfig.organizer || '' }
				onChange={ ( value ) => updateField( 'organizer', value ) }
			/>

			<h3>{ __( 'Ticket Information', 'meowseo' ) }</h3>

			<TextControl
				label={ __( 'Ticket URL', 'meowseo' ) }
				value={ schemaConfig.offers?.url || '' }
				onChange={ ( value ) => updateOffersField( 'url', value ) }
				type="url"
			/>

			<TextControl
				label={ __( 'Ticket Price', 'meowseo' ) }
				value={ schemaConfig.offers?.price || '' }
				onChange={ ( value ) => updateOffersField( 'price', value ) }
				type="number"
				step="0.01"
			/>

			<TextControl
				label={ __( 'Currency', 'meowseo' ) }
				value={ schemaConfig.offers?.priceCurrency || 'USD' }
				onChange={ ( value ) =>
					updateOffersField( 'priceCurrency', value )
				}
				help={ __( 'e.g., USD, EUR, GBP', 'meowseo' ) }
			/>
		</div>
	);
};

export default EventForm;
