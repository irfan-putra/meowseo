/**
 * RecipeForm Component
 *
 * Form for Recipe schema configuration.
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

interface RecipeSchema {
	name: string;
	description: string;
	recipeIngredient: string;
	recipeInstructions: string;
	prepTime?: string;
	cookTime?: string;
	totalTime?: string;
	recipeYield?: string;
	recipeCategory?: string;
	recipeCuisine?: string;
	nutrition?: {
		calories?: string;
		fatContent?: string;
		carbohydrateContent?: string;
		proteinContent?: string;
	};
}

const RecipeForm: React.FC = () => {
	const [ schemaConfigJson, setSchemaConfigJson ] = useEntityPropBinding(
		'_meowseo_schema_config'
	);

	const schemaConfig: RecipeSchema = schemaConfigJson
		? ( () => {
				try {
					return JSON.parse( schemaConfigJson );
				} catch {
					return {
						name: '',
						description: '',
						recipeIngredient: '',
						recipeInstructions: '',
						prepTime: '',
						cookTime: '',
						totalTime: '',
						recipeYield: '',
						recipeCategory: '',
						recipeCuisine: '',
						nutrition: {},
					};
				}
		  } )()
		: {
				name: '',
				description: '',
				recipeIngredient: '',
				recipeInstructions: '',
				prepTime: '',
				cookTime: '',
				totalTime: '',
				recipeYield: '',
				recipeCategory: '',
				recipeCuisine: '',
				nutrition: {},
		  };

	if ( ! schemaConfig.nutrition ) {
		schemaConfig.nutrition = {};
	}

	const updateField = useCallback(
		( field: keyof RecipeSchema, value: string ) => {
			const updatedConfig = { ...schemaConfig, [ field ]: value };
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	const updateNutritionField = useCallback(
		( field: string, value: string ) => {
			const updatedConfig = {
				...schemaConfig,
				nutrition: { ...schemaConfig.nutrition, [ field ]: value },
			};
			setSchemaConfigJson( JSON.stringify( updatedConfig ) );
		},
		[ schemaConfig, setSchemaConfigJson ]
	);

	return (
		<div className="meowseo-schema-form">
			<TextControl
				label={ __( 'Recipe Name', 'meowseo' ) }
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

			<TextareaControl
				label={ __( 'Ingredients (one per line)', 'meowseo' ) }
				value={ schemaConfig.recipeIngredient }
				onChange={ ( value ) =>
					updateField( 'recipeIngredient', value )
				}
				rows={ 4 }
				required
			/>

			<TextareaControl
				label={ __( 'Instructions (one per line)', 'meowseo' ) }
				value={ schemaConfig.recipeInstructions }
				onChange={ ( value ) =>
					updateField( 'recipeInstructions', value )
				}
				rows={ 4 }
				required
			/>

			<h3>{ __( 'Cooking Times', 'meowseo' ) }</h3>

			<TextControl
				label={ __( 'Prep Time (ISO 8601, e.g., PT15M)', 'meowseo' ) }
				value={ schemaConfig.prepTime || '' }
				onChange={ ( value ) => updateField( 'prepTime', value ) }
				help={ __( 'Format: PT[hours]H[minutes]M', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'Cook Time (ISO 8601, e.g., PT30M)', 'meowseo' ) }
				value={ schemaConfig.cookTime || '' }
				onChange={ ( value ) => updateField( 'cookTime', value ) }
				help={ __( 'Format: PT[hours]H[minutes]M', 'meowseo' ) }
			/>

			<TextControl
				label={ __( 'Total Time (ISO 8601, e.g., PT45M)', 'meowseo' ) }
				value={ schemaConfig.totalTime || '' }
				onChange={ ( value ) => updateField( 'totalTime', value ) }
				help={ __( 'Format: PT[hours]H[minutes]M', 'meowseo' ) }
			/>

			<h3>{ __( 'Recipe Details', 'meowseo' ) }</h3>

			<TextControl
				label={ __( 'Yield (e.g., 4 servings)', 'meowseo' ) }
				value={ schemaConfig.recipeYield || '' }
				onChange={ ( value ) => updateField( 'recipeYield', value ) }
			/>

			<TextControl
				label={ __( 'Category (e.g., Main Course)', 'meowseo' ) }
				value={ schemaConfig.recipeCategory || '' }
				onChange={ ( value ) => updateField( 'recipeCategory', value ) }
			/>

			<TextControl
				label={ __( 'Cuisine (e.g., Italian)', 'meowseo' ) }
				value={ schemaConfig.recipeCuisine || '' }
				onChange={ ( value ) => updateField( 'recipeCuisine', value ) }
			/>

			<h3>{ __( 'Nutrition Information', 'meowseo' ) }</h3>

			<TextControl
				label={ __( 'Calories', 'meowseo' ) }
				value={ schemaConfig.nutrition?.calories || '' }
				onChange={ ( value ) =>
					updateNutritionField( 'calories', value )
				}
			/>

			<TextControl
				label={ __( 'Fat Content (e.g., 10g)', 'meowseo' ) }
				value={ schemaConfig.nutrition?.fatContent || '' }
				onChange={ ( value ) =>
					updateNutritionField( 'fatContent', value )
				}
			/>

			<TextControl
				label={ __( 'Carbohydrate Content (e.g., 30g)', 'meowseo' ) }
				value={ schemaConfig.nutrition?.carbohydrateContent || '' }
				onChange={ ( value ) =>
					updateNutritionField( 'carbohydrateContent', value )
				}
			/>

			<TextControl
				label={ __( 'Protein Content (e.g., 8g)', 'meowseo' ) }
				value={ schemaConfig.nutrition?.proteinContent || '' }
				onChange={ ( value ) =>
					updateNutritionField( 'proteinContent', value )
				}
			/>
		</div>
	);
};

export default RecipeForm;
