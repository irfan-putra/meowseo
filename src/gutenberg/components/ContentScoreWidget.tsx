/**
 * ContentScoreWidget Component
 * 
 * Displays SEO and readability scores with color-coded indicators
 * and provides an analyze button to trigger content analysis.
 * 
 * Optimized with React.memo and useCallback for performance.
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 5.1, 5.2, 5.3, 5.4, 5.5, 16.7, 16.8
 */

import { memo, useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { STORE_NAME } from '../store';
import './ContentScoreWidget.css';

/**
 * Get color based on score
 * - Red: score < 40
 * - Orange: score 40-69
 * - Green: score >= 70
 */
const getScoreColor = (score: number): string => {
  if (score < 40) {
    return '#dc3232'; // Red
  } else if (score < 70) {
    return '#f56e28'; // Orange
  } else {
    return '#46b450'; // Green
  }
};

/**
 * ContentScoreWidget Component
 * 
 * Requirement 16.7: Use React.memo for pure components
 */
export const ContentScoreWidget: React.FC = memo(() => {
  const { seoScore, readabilityScore, isAnalyzing } = useSelect((select) => {
    try {
      const store = select(STORE_NAME) as any;
      if (!store) {
        console.warn('MeowSEO: meowseo/data store not available in ContentScoreWidget');
        return {
          seoScore: 0,
          readabilityScore: 0,
          isAnalyzing: false,
        };
      }
      return {
        seoScore: store.getSeoScore(),
        readabilityScore: store.getReadabilityScore(),
        isAnalyzing: store.getIsAnalyzing(),
      };
    } catch (error) {
      console.error('MeowSEO: Error reading from meowseo/data store:', error);
      return {
        seoScore: 0,
        readabilityScore: 0,
        isAnalyzing: false,
      };
    }
  }, []);

  const { analyzeContent } = useDispatch(STORE_NAME) as any;

  // Requirement 16.8: Use useCallback for event handlers
  const handleAnalyze = useCallback(() => {
    analyzeContent();
  }, [analyzeContent]);

  return (
    <div className="meowseo-content-score-widget">
      <div className="meowseo-scores">
        <div className="meowseo-score-item">
          <div className="meowseo-score-label">
            {__('SEO Score', 'meowseo')}
          </div>
          <div
            className="meowseo-score-value"
            style={{ color: getScoreColor(seoScore) }}
            data-testid="seo-score"
          >
            {seoScore}
          </div>
        </div>
        <div className="meowseo-score-item">
          <div className="meowseo-score-label">
            {__('Readability Score', 'meowseo')}
          </div>
          <div
            className="meowseo-score-value"
            style={{ color: getScoreColor(readabilityScore) }}
            data-testid="readability-score"
          >
            {readabilityScore}
          </div>
        </div>
      </div>
      <div className="meowseo-analyze-button-wrapper">
        <Button
          variant="primary"
          onClick={handleAnalyze}
          disabled={isAnalyzing}
          data-testid="analyze-button"
        >
          {isAnalyzing ? (
            <>
              <Spinner />
              {__('Analyzing...', 'meowseo')}
            </>
          ) : (
            __('Analyze', 'meowseo')
          )}
        </Button>
      </div>
    </div>
  );
});
