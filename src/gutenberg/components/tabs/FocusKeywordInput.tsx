/**
 * FocusKeywordInput Component
 * 
 * Provides an input field for the focus keyword that persists to postmeta.
 * The focus keyword is used by the SEO analysis to check if content is optimized
 * for the target keyword.
 * 
 * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 15.5
 */

import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEntityPropBinding } from '../../hooks/useEntityPropBinding';

/**
 * FocusKeywordInput Component
 * 
 * Displays a text input for the focus keyword with automatic postmeta persistence.
 * 
 * Requirements:
 * - 9.1: Display focus keyword input field
 * - 9.2: Persist value to _meowseo_focus_keyword postmeta
 * - 9.3: Use Entity_Prop to read and write the focus keyword
 * - 9.4: Trigger WordPress auto-save on change
 * - 9.5: Display previously saved focus keyword on reload
 * - 15.5: Persist focus keyword to _meowseo_focus_keyword
 */
const FocusKeywordInput: React.FC = () => {
  // Use useEntityPropBinding for automatic postmeta persistence
  // Requirements: 9.2, 9.3, 9.4, 9.5, 15.5
  const [focusKeyword, setFocusKeyword] = useEntityPropBinding('_meowseo_focus_keyword');
  
  return (
    <TextControl
      label={__('Focus Keyword', 'meowseo')}
      value={focusKeyword}
      onChange={setFocusKeyword}
      help={__('Enter the main keyword you want to optimize this content for. The SEO analysis will check if your content is properly optimized for this keyword.', 'meowseo')}
      placeholder={__('e.g., wordpress seo', 'meowseo')}
    />
  );
};

export default FocusKeywordInput;
