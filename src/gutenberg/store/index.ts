/**
 * Redux Store Registration for meowseo/data
 */

import { createReduxStore, register } from '@wordpress/data';
import { reducer, initialState } from './reducer';
import {
	updateContentSnapshot,
	setAnalyzing,
	setAnalysisResults,
	setActiveTab,
	analyzeContent,
} from './actions';
import * as selectors from './selectors';

const STORE_NAME = 'meowseo/data';

// Action creators object (excluding action type constants)
const actions = {
	updateContentSnapshot,
	setAnalyzing,
	setAnalysisResults,
	setActiveTab,
	analyzeContent,
};

// Create and register the store only if createReduxStore is available
// (in tests, this might be mocked or unavailable)
let store: any = null;

try {
	if ( typeof createReduxStore === 'function' ) {
		// Create the Redux store
		store = createReduxStore( STORE_NAME, {
			reducer,
			actions,
			selectors,
			initialState,
		} );

		// Register the store
		register( store );
	}
} catch ( error ) {
	console.error( 'MeowSEO: Failed to create Redux store:', error );
	
	// Provide fallback store implementation for graceful degradation
	// This ensures components can still function with reduced features
	try {
		// Create a minimal fallback store with safe defaults
		const fallbackStore = createReduxStore( STORE_NAME + '-fallback', {
			reducer: ( state = initialState ) => state,
			actions: {
				// Provide no-op actions that don't throw errors
				updateContentSnapshot: () => ( { type: 'NOOP' } ),
				setAnalyzing: () => ( { type: 'NOOP' } ),
				setAnalysisResults: () => ( { type: 'NOOP' } ),
				setActiveTab: () => ( { type: 'NOOP' } ),
				analyzeContent: () => ( { type: 'NOOP' } ),
			},
			selectors: {
				// Provide safe selectors that return default values
				getSeoScore: ( state: any ) => state?.seoScore || 0,
				getReadabilityScore: ( state: any ) => state?.readabilityScore || 0,
				getAnalysisResults: ( state: any ) => state?.analysisResults || [],
				getReadabilityResults: ( state: any ) => state?.readabilityResults || [],
				getWordCount: ( state: any ) => state?.wordCount || 0,
				getSentenceCount: ( state: any ) => state?.sentenceCount || 0,
				getParagraphCount: ( state: any ) => state?.paragraphCount || 0,
				getFleschScore: ( state: any ) => state?.fleschScore || 0,
				getKeywordDensity: ( state: any ) => state?.keywordDensity || 0,
				getAnalysisTimestamp: ( state: any ) => state?.analysisTimestamp || null,
				getActiveTab: ( state: any ) => state?.activeTab || 'general',
				isAnalyzing: ( state: any ) => state?.isAnalyzing || false,
				getContentSnapshot: ( state: any ) => state?.contentSnapshot || {
					title: '',
					content: '',
					excerpt: '',
					focusKeyword: '',
					postType: '',
					permalink: '',
				},
			},
			initialState,
		} );
		
		register( fallbackStore );
		
		// Display user-friendly error message
		console.warn( 
			'MeowSEO: Using fallback store due to registration failure. ' +
			'Analysis features may be limited. Please check browser console for details.'
		);
	} catch ( fallbackError ) {
		console.error( 'MeowSEO: Failed to register fallback store:', fallbackError );
		// At this point, components will need to handle missing store gracefully
		// Display user-friendly error message
		console.error(
			'MeowSEO: Store registration completely failed. ' +
			'The sidebar may not function correctly. Please refresh the page.'
		);
	}
}

export { STORE_NAME };
export * from './types';
export * from './actions';
export * from './selectors';
