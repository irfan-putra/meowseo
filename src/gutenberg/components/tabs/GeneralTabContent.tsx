/**
 * GeneralTabContent Component
 * 
 * Main content for the General tab, including focus keyword input,
 * SERP preview, direct answer field, and internal link suggestions.
 * 
 * Requirements: 1.7, 9.6
 */

import SerpPreview from './SerpPreview';
import FocusKeywordInput from './FocusKeywordInput';
import DirectAnswerField from './DirectAnswerField';
import InternalLinkSuggestions from './InternalLinkSuggestions';
import './SerpPreview.css';
import './InternalLinkSuggestions.css';

/**
 * GeneralTabContent Component
 * 
 * Requirements:
 * - 1.7: Render General tab with all components
 * - 9.6: Wire General tab components together
 */
const GeneralTabContent: React.FC = () => {
  return (
    <div className="meowseo-general-tab">
      <SerpPreview />
      <FocusKeywordInput />
      <DirectAnswerField />
      <InternalLinkSuggestions />
    </div>
  );
};

export default GeneralTabContent;
