/**
 * Selectors for meowseo/data Redux Store
 * 
 * Optimized selectors with memoization for performance.
 * Requirements: 16.6
 */

import { MeowSEOState, AnalysisResult, ContentSnapshot, TabType } from './types';

// Simple selectors (no memoization needed for primitive values)
export const getSeoScore = (state: MeowSEOState): number => {
  return state.seoScore;
};

export const getReadabilityScore = (state: MeowSEOState): number => {
  return state.readabilityScore;
};

export const getActiveTab = (state: MeowSEOState): TabType => {
  return state.activeTab;
};

export const getIsAnalyzing = (state: MeowSEOState): boolean => {
  return state.isAnalyzing;
};

// Direct selectors for complex objects/arrays
// WordPress's @wordpress/data automatically memoizes selectors
export const getAnalysisResults = (state: MeowSEOState): AnalysisResult[] => {
  return state.analysisResults;
};

export const getContentSnapshot = (state: MeowSEOState): ContentSnapshot => {
  return state.contentSnapshot;
};

// Derived selectors with manual memoization
let cachedAnalysisResultsByType: { good: AnalysisResult[]; ok: AnalysisResult[]; problem: AnalysisResult[] } | null = null;
let lastAnalysisResults: AnalysisResult[] | null = null;

export const getAnalysisResultsByType = (state: MeowSEOState) => {
  // Manual memoization: only recalculate if analysisResults changed
  if (state.analysisResults !== lastAnalysisResults) {
    lastAnalysisResults = state.analysisResults;
    cachedAnalysisResultsByType = {
      good: state.analysisResults.filter(r => r.type === 'good'),
      ok: state.analysisResults.filter(r => r.type === 'ok'),
      problem: state.analysisResults.filter(r => r.type === 'problem'),
    };
  }
  return cachedAnalysisResultsByType!;
};

// Derived selector: Get score color
let cachedSeoScoreColor: string | null = null;
let lastSeoScore: number | null = null;

export const getSeoScoreColor = (state: MeowSEOState): string => {
  // Manual memoization: only recalculate if score changed
  if (state.seoScore !== lastSeoScore) {
    lastSeoScore = state.seoScore;
    if (state.seoScore < 40) {
      cachedSeoScoreColor = 'red';
    } else if (state.seoScore < 70) {
      cachedSeoScoreColor = 'orange';
    } else {
      cachedSeoScoreColor = 'green';
    }
  }
  return cachedSeoScoreColor!;
};

let cachedReadabilityScoreColor: string | null = null;
let lastReadabilityScore: number | null = null;

export const getReadabilityScoreColor = (state: MeowSEOState): string => {
  // Manual memoization: only recalculate if score changed
  if (state.readabilityScore !== lastReadabilityScore) {
    lastReadabilityScore = state.readabilityScore;
    if (state.readabilityScore < 40) {
      cachedReadabilityScoreColor = 'red';
    } else if (state.readabilityScore < 70) {
      cachedReadabilityScoreColor = 'orange';
    } else {
      cachedReadabilityScoreColor = 'green';
    }
  }
  return cachedReadabilityScoreColor!;
};
