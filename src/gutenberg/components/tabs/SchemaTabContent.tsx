/**
 * SchemaTabContent Component
 * 
 * Main content for the Schema tab with lazy-loaded schema forms.
 * Validates schema configuration before saving.
 * 
 * Requirements: 13.3, 13.9, 13.10, 16.4
 */

import { lazy, Suspense } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../hooks/useEntityPropBinding';
import SchemaTypeSelector from './schema/SchemaTypeSelector';

// Lazy load schema forms for code splitting (Requirement 16.4)
const ArticleForm = lazy(() => import('./schema/ArticleForm'));
const FAQPageForm = lazy(() => import('./schema/FAQPageForm'));
const HowToForm = lazy(() => import('./schema/HowToForm'));
const LocalBusinessForm = lazy(() => import('./schema/LocalBusinessForm'));
const ProductForm = lazy(() => import('./schema/ProductForm'));

/**
 * Validate schema configuration against selected schema type
 * 
 * Requirements:
 * - 13.10: Validate schema configuration before saving
 */
const validateSchemaConfig = (schemaType: string, configJson: string): boolean => {
  if (!schemaType || !configJson) {
    return true; // Empty is valid
  }
  
  try {
    const config = JSON.parse(configJson);
    
    // Basic validation based on schema type
    switch (schemaType) {
      case 'Article':
        return typeof config.headline === 'string';
      case 'FAQPage':
        return Array.isArray(config.questions);
      case 'HowTo':
        return typeof config.name === 'string' && Array.isArray(config.steps);
      case 'LocalBusiness':
        return typeof config.name === 'string' && typeof config.address === 'object';
      case 'Product':
        return typeof config.name === 'string' && typeof config.offers === 'object';
      default:
        return true;
    }
  } catch (e) {
    return false;
  }
};

/**
 * SchemaTabContent Component
 * 
 * Requirements:
 * - 13.3: Display form specific to selected schema type
 * - 13.9: Persist schema configuration to postmeta
 * - 13.10: Validate schema configuration before saving
 * - 16.4: Lazy load schema forms based on selected type
 */
const SchemaTabContent: React.FC = () => {
  const [schemaType] = useEntityPropBinding('_meowseo_schema_type');
  const [schemaConfig] = useEntityPropBinding('_meowseo_schema_config');
  
  // Validate current configuration
  const isValid = validateSchemaConfig(schemaType, schemaConfig);
  
  // Render the appropriate schema form based on selected type
  const renderSchemaForm = () => {
    if (!schemaType) {
      return (
        <p className="meowseo-schema-help">
          {__('Select a schema type above to configure structured data for this content.', 'meowseo')}
        </p>
      );
    }
    
    // Lazy load the appropriate form component
    return (
      <Suspense fallback={<Spinner />}>
        {schemaType === 'Article' && <ArticleForm />}
        {schemaType === 'FAQPage' && <FAQPageForm />}
        {schemaType === 'HowTo' && <HowToForm />}
        {schemaType === 'LocalBusiness' && <LocalBusinessForm />}
        {schemaType === 'Product' && <ProductForm />}
      </Suspense>
    );
  };
  
  return (
    <div className="meowseo-schema-tab">
      <SchemaTypeSelector />
      
      {!isValid && schemaConfig && (
        <div className="meowseo-schema-validation-error">
          {__('Invalid schema configuration. Please check your inputs.', 'meowseo')}
        </div>
      )}
      
      {renderSchemaForm()}
    </div>
  );
};

export default SchemaTabContent;
