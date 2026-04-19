/**
 * Tests for AnalyzerResultItem fix_explanation rendering
 *
 * Tests that fix explanations are properly displayed in the component
 * Requirements: 6.15
 */

import { render, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import { AnalyzerResultItem } from '../AnalyzerResultItem';
import { AnalysisResult } from '../../store/types';

// Mock @wordpress/i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

describe( 'AnalyzerResultItem - Fix Explanation (Requirement 6.15)', () => {
	describe( 'Fix Explanation Display', () => {
		it( 'should display fix explanation when present', () => {
			const mockResult: AnalysisResult = {
				id: 'title_too_short',
				type: 'problem',
				message: 'SEO title is too short',
				score: 0,
				fix_explanation: 'Your SEO title should be between 30-60 characters. Currently it is 15 characters. Try adding more descriptive words to your title.',
			};

			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			// Toggle to expand
			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			) as HTMLButtonElement;
			fireEvent.click( toggleButton );

			// Check fix explanation is displayed
			const fixExplanation = container.querySelector(
				'.meowseo-analyzer-fix-explanation-content'
			);
			expect( fixExplanation ).toBeInTheDocument();
			expect( fixExplanation?.textContent ).toContain(
				'Your SEO title should be between 30-60 characters'
			);
		} );

		it( 'should not display fix explanation when not present', () => {
			const mockResult: AnalysisResult = {
				id: 'keyword-in-title',
				type: 'good',
				message: 'Focus keyword appears in SEO title',
				score: 100,
			};

			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const fixExplanation = container.querySelector(
				'.meowseo-analyzer-fix-explanation'
			);
			expect( fixExplanation ).not.toBeInTheDocument();
		} );

		it( 'should not display fix explanation when empty string', () => {
			const mockResult: AnalysisResult = {
				id: 'keyword-in-title',
				type: 'good',
				message: 'Focus keyword appears in SEO title',
				score: 100,
				fix_explanation: '',
			};

			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const fixExplanation = container.querySelector(
				'.meowseo-analyzer-fix-explanation'
			);
			expect( fixExplanation ).not.toBeInTheDocument();
		} );

		it( 'should not display fix explanation when only whitespace', () => {
			const mockResult: AnalysisResult = {
				id: 'keyword-in-title',
				type: 'good',
				message: 'Focus keyword appears in SEO title',
				score: 100,
				fix_explanation: '   \n\t  ',
			};

			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const fixExplanation = container.querySelector(
				'.meowseo-analyzer-fix-explanation'
			);
			expect( fixExplanation ).not.toBeInTheDocument();
		} );

		it( 'should show toggle button when fix explanation is present', () => {
			const mockResult: AnalysisResult = {
				id: 'title_too_short',
				type: 'problem',
				message: 'SEO title is too short',
				score: 0,
				fix_explanation: 'Your SEO title should be between 30-60 characters.',
			};

			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			);
			expect( toggleButton ).toBeInTheDocument();
		} );

		it( 'should display both details and fix explanation when both present', () => {
			const mockResult: AnalysisResult = {
				id: 'title_too_short',
				type: 'problem',
				message: 'SEO title is too short',
				score: 0,
				details: { currentLength: 15, minLength: 30 },
				fix_explanation: 'Your SEO title should be between 30-60 characters.',
			};

			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			// Toggle to expand
			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			) as HTMLButtonElement;
			fireEvent.click( toggleButton );

			// Check both sections are displayed
			const detailsSection = container.querySelector(
				'.meowseo-analyzer-details-section'
			);
			const fixExplanation = container.querySelector(
				'.meowseo-analyzer-fix-explanation'
			);

			expect( detailsSection ).toBeInTheDocument();
			expect( fixExplanation ).toBeInTheDocument();
		} );

		it( 'should preserve line breaks in fix explanation', () => {
			const mockResult: AnalysisResult = {
				id: 'title_too_short',
				type: 'problem',
				message: 'SEO title is too short',
				score: 0,
				fix_explanation: 'Line 1\nLine 2\nLine 3',
			};

			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			// Toggle to expand
			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			) as HTMLButtonElement;
			fireEvent.click( toggleButton );

			const fixExplanation = container.querySelector(
				'.meowseo-analyzer-fix-explanation-content'
			);
			// Verify the element has the class that applies white-space: pre-wrap
			expect( fixExplanation ).toHaveClass(
				'meowseo-analyzer-fix-explanation-content'
			);
			// Verify the content is preserved with line breaks
			expect( fixExplanation?.textContent ).toBe( 'Line 1\nLine 2\nLine 3' );
		} );
	} );

	describe( 'Expandable Behavior with Fix Explanation', () => {
		it( 'should toggle fix explanation visibility', () => {
			const mockResult: AnalysisResult = {
				id: 'title_too_short',
				type: 'problem',
				message: 'SEO title is too short',
				score: 0,
				fix_explanation: 'Your SEO title should be between 30-60 characters.',
			};

			const { container } = render(
				<AnalyzerResultItem result={ mockResult } />
			);

			const toggleButton = container.querySelector(
				'.meowseo-analyzer-details-toggle'
			) as HTMLButtonElement;

			// Initially collapsed
			let fixExplanation = container.querySelector(
				'.meowseo-analyzer-fix-explanation'
			);
			expect( fixExplanation ).not.toBeInTheDocument();

			// Expand
			fireEvent.click( toggleButton );
			fixExplanation = container.querySelector(
				'.meowseo-analyzer-fix-explanation'
			);
			expect( fixExplanation ).toBeInTheDocument();

			// Collapse
			fireEvent.click( toggleButton );
			fixExplanation = container.querySelector(
				'.meowseo-analyzer-fix-explanation'
			);
			expect( fixExplanation ).not.toBeInTheDocument();
		} );
	} );
} );
