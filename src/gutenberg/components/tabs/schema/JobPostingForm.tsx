/**
 * JobPostingForm Component
 *
 * Form for JobPosting schema configuration.
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

interface JobPostingSchema {
	title: string;
	description: string;
	datePosted: string;
	hiringOrganization: string;
	validThrough?: string;
	employmentType?: string;
	jobLocation?: string;
	baseSalary?: {
		currency?: string;
		value?: string;
		unitText?: string;
	};
}

const JobPostingForm: React.FC = () => {
	const [ schemaConfigJson, setSchemaConfigJson ] = useEntityPropBinding(
		'_meowseo_schema_config'
	);

	const schemaConfig: JobPostingSchema = schemaConfigJson
		? ( () => {
				try {
					return JSON.parse( schemaConfigJson );
				} catch {
					return {
						title: '',
						description: '',
						datePosted: '',
						hiringOrganization: '',
						validThrough: '',
						employmentType: 'FULL_TIME',
						jobLocation: '',
						baseSalary: {},
					};
				}
		  } )()
		: {
				title: '',
				description: '',
				datePosted: '',
				hiringOrganization: '',
				validThrough: '',
				employmentType: 'FULL_TIME',
				jobLocation: '',
				baseSalary: {},
		  };

	if ( ! schemaConfig.baseSalary ) {
		schemaConfig.baseSalary = {};
	}

	const updateField = useCallback(
		( field: keyof JobPostingSchema, value: string ) => {
			const updatedConfig = { ...schemaConfig, [ field ]: value };
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	const updateSalaryField = useCallback(
		( field: string, value: string ) => {
			const updatedConfig = {
				...schemaConfig,
				baseSalary: { ...schemaConfig.baseSalary, [ field ]: value },
			};
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	const employmentTypeOptions = [
		{ label: __( 'Full-Time', 'meowseo' ), value: 'FULL_TIME' },
		{ label: __( 'Part-Time', 'meowseo' ), value: 'PART_TIME' },
		{ label: __( 'Contract', 'meowseo' ), value: 'CONTRACTOR' },
		{ label: __( 'Temporary', 'meowseo' ), value: 'TEMPORARY' },
		{ label: __( 'Internship', 'meowseo' ), value: 'INTERN' },
		{ label: __( 'Seasonal', 'meowseo' ), value: 'SEASONAL' },
	];

	const salaryUnitOptions = [
		{ label: __( 'Per Year', 'meowseo' ), value: 'YEAR' },
		{ label: __( 'Per Month', 'meowseo' ), value: 'MONTH' },
		{ label: __( 'Per Week', 'meowseo' ), value: 'WEEK' },
		{ label: __( 'Per Hour', 'meowseo' ), value: 'HOUR' },
	];

	return (
		<div className="meowseo-schema-form">
			<TextControl
				label={ __( 'Job Title', 'meowseo' ) }
				value={ schemaConfig.title }
				onChange={ ( value ) => updateField( 'title', value ) }
				required
			/>

			<TextareaControl
				label={ __( 'Job Description', 'meowseo' ) }
				value={ schemaConfig.description }
				onChange={ ( value ) => updateField( 'description', value ) }
				rows={ 4 }
				required
			/>

			<TextControl
				label={ __( 'Date Posted (ISO 8601)', 'meowseo' ) }
				value={ schemaConfig.datePosted }
				onChange={ ( value ) => updateField( 'datePosted', value ) }
				type="date"
				required
			/>

			<TextControl
				label={ __( 'Valid Through (ISO 8601)', 'meowseo' ) }
				value={ schemaConfig.validThrough || '' }
				onChange={ ( value ) => updateField( 'validThrough', value ) }
				type="date"
				help={ __( 'Application deadline', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'Hiring Organization', 'meowseo' ) }
				value={ schemaConfig.hiringOrganization }
				onChange={ ( value ) =>
					updateField( 'hiringOrganization', value )
				}
				required
				help={ __( 'Company name', 'meowseo' ) }
			/>

			<SelectControl
				label={ __( 'Employment Type', 'meowseo' ) }
				value={ schemaConfig.employmentType || 'FULL_TIME' }
				options={ employmentTypeOptions }
				onChange={ ( value ) => updateField( 'employmentType', value ) }
			/>

			<TextControl
				label={ __( 'Job Location', 'meowseo' ) }
				value={ schemaConfig.jobLocation || '' }
				onChange={ ( value ) => updateField( 'jobLocation', value ) }
				help={ __( 'City, state, or remote', 'meowseo' ) }
			/>

			<h3>{ __( 'Base Salary', 'meowseo' ) }</h3>

			<TextControl
				label={ __( 'Salary Amount', 'meowseo' ) }
				value={ schemaConfig.baseSalary?.value || '' }
				onChange={ ( value ) => updateSalaryField( 'value', value ) }
				type="number"
				step="0.01"
			/>

			<TextControl
				label={ __( 'Currency', 'meowseo' ) }
				value={ schemaConfig.baseSalary?.currency || 'USD' }
				onChange={ ( value ) => updateSalaryField( 'currency', value ) }
				help={ __( 'e.g., USD, EUR, GBP', 'meowseo' ) }
			/>

			<SelectControl
				label={ __( 'Salary Unit', 'meowseo' ) }
				value={ schemaConfig.baseSalary?.unitText || 'YEAR' }
				options={ salaryUnitOptions }
				onChange={ ( value ) => updateSalaryField( 'unitText', value ) }
			/>
		</div>
	);
};

export default JobPostingForm;
