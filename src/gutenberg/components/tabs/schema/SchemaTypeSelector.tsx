/**
 * SchemaTypeSelector Component
 * 
 * Displays a SelectControl for choosing schema type.
 * Uses useEntityPropBinding for _meowseo_schema_type postmeta.
 * 
 * Requirements: 13.1, 13.2
 */

import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../../hooks/useEntityPropBinding';

/**
 * SchemaTypeSelector Component
 * 
 * Requirements:
 * - 13.1: Display schema type selector
 * - 13.2: Support Article, FAQPage, HowTo, LocalBusiness, Product schema types
 */
const SchemaTypeSelector: React.FC = () => {
  const [schemaType, setSchemaType] = useEntityPropBinding('_meowseo_schema_type');
  
  const schemaOptions = [
    { label: __('None', 'meowseo'), value: '' },
    { label: __('Article', 'meowseo'), value: 'Article' },
    { label: __('FAQPage', 'meowseo'), value: 'FAQPage' },
    { label: __('HowTo', 'meowseo'), value: 'HowTo' },
    { label: __('LocalBusiness', 'meowseo'), value: 'LocalBusiness' },
    { label: __('Product', 'meowseo'), value: 'Product' },
  ];
  
  return (
    <SelectControl
      label={__('Schema Type', 'meowseo')}
      value={schemaType}
      options={schemaOptions}
      onChange={setSchemaType}
      help={__('Select the structured data type for this content', 'meowseo')}
    />
  );
};

export default SchemaTypeSelector;
