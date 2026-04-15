/**
 * SocialTabContent Component
 * 
 * Main content for the Social tab, including Facebook and Twitter sub-tabs.
 * Allows customization of social media metadata for optimal sharing appearance.
 * 
 * Requirements: 12.1, 12.9
 */

import { useState } from '@wordpress/element';
import { Button, ButtonGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import FacebookSubTab from './FacebookSubTab';
import TwitterSubTab from './TwitterSubTab';
import './SocialTabContent.css';

type SocialSubTab = 'facebook' | 'twitter';

/**
 * SocialTabContent Component
 * 
 * Requirements:
 * - 12.1: Display Facebook and Twitter sub-tabs
 * - 12.9: Implement sub-tab navigation
 */
const SocialTabContent: React.FC = () => {
  // Requirement 12.9: Implement sub-tab navigation
  const [activeSubTab, setActiveSubTab] = useState<SocialSubTab>('facebook');
  
  return (
    <div className="meowseo-social-tab">
      {/* Requirement 12.1: Display Facebook and Twitter sub-tabs */}
      <div className="meowseo-social-subtab-navigation">
        <ButtonGroup>
          <Button
            variant={activeSubTab === 'facebook' ? 'primary' : 'secondary'}
            onClick={() => setActiveSubTab('facebook')}
          >
            {__('Facebook', 'meowseo')}
          </Button>
          <Button
            variant={activeSubTab === 'twitter' ? 'primary' : 'secondary'}
            onClick={() => setActiveSubTab('twitter')}
          >
            {__('Twitter', 'meowseo')}
          </Button>
        </ButtonGroup>
      </div>
      
      {/* Requirement 12.9: Render active sub-tab content */}
      <div className="meowseo-social-subtab-content">
        {activeSubTab === 'facebook' && <FacebookSubTab />}
        {activeSubTab === 'twitter' && <TwitterSubTab />}
      </div>
    </div>
  );
};

export default SocialTabContent;
