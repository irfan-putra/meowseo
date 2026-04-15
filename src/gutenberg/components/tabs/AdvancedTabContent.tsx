/**
 * AdvancedTabContent Component
 * 
 * Main content for the Advanced tab, including robots toggles,
 * canonical URL input, and Google Search Console integration.
 * 
 * Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 14.7, 14.8
 */

import RobotsToggles from './RobotsToggles';
import CanonicalURLInput from './CanonicalURLInput';
import GSCIntegration from './GSCIntegration';

/**
 * AdvancedTabContent Component
 * 
 * Requirements:
 * - 14.1, 14.2, 14.3: Render RobotsToggles for noindex/nofollow
 * - 14.4, 14.5, 14.6: Render CanonicalURLInput for canonical URL
 * - 14.7, 14.8: Render GSCIntegration for Google Search Console
 */
const AdvancedTabContent: React.FC = () => {
  return (
    <div className="meowseo-advanced-tab">
      <RobotsToggles />
      <CanonicalURLInput />
      <GSCIntegration />
    </div>
  );
};

export default AdvancedTabContent;
