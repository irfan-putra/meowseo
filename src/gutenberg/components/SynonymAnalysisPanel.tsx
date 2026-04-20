/**
 * SynonymAnalysisPanel Component
 *
 * Displays analysis results for primary keyword and synonyms with combined score.
 *
 * Requirements: 11.4, 11.5, 11.6, 11.9
 */

import { memo, useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { STORE_NAME } from '../store';
import './SynonymAnalysisPanel.css';

/**
 * Get color based on score
 * @param score
 */
const getScoreColor = ( score: number ): string => {
	if ( score < 40 ) {
		return '#dc3232'; // Red
	} else if ( score < 70 ) {
		return '#f56e28'; // Orange
	}
	return '#46b450'; // Green
};

/**
 * Get score label based on score
 * @param score
 */
const getScoreLabel = ( score: number ): string => {
	if ( score < 40 ) {
		return __( 'Needs Improvement', 'meowseo' );
	} else if ( score < 70 ) {
		return __( 'Good', 'meowseo' );
	}
	return __( 'Excellent', 'meowseo' );
};

interface CheckResult {
	id: string;
	label: string;
	pass: boolean;
	value?: number;
}

interface KeywordResult {
	keyword?: string;
	synonym?: string;
	score: number;
	checks: CheckResult[];
}

interface AnalysisData {
	primary: KeywordResult;
	synonyms: KeywordResult[];
	combined_score: number;
}

/**
 * KeywordResultRow Component
 *
 * Displays analysis for a single keyword or synonym
 */
const KeywordResultRow: React.FC< {
	result: KeywordResult;
	isPrimary: boolean;
	highlightColor: string;
} > = memo( ( { result, isPrimary, highlightColor } ) => {
	const [ isExpanded, setIsExpanded ] = useState( false );
	const keyword = result.keyword || result.synonym || '';
	const scoreColor = getScoreColor( result.score );
	const scoreLabel = getScoreLabel( result.score );

	return (
		<div className="meowseo-synonym-result-row">
			<button
				type="button"
				className="meowseo-synonym-result-header"
				onClick={ () => setIsExpanded( ! isExpanded ) }
				aria-expanded={ isExpanded }
			>
				<div className="meowseo-synonym-info">
					<span
						className="meowseo-synonym-name"
						style={ { borderLeftColor: highlightColor } }
					>
						{ keyword }
						{ isPrimary && (
							<span className="meowseo-synonym-badge">
								{ __( 'Primary', 'meowseo' ) }
							</span>
						) }
					</span>
				</div>
				<div className="meowseo-synonym-score">
					<span
						className="meowseo-synonym-score-value"
						style={ { color: scoreColor } }
					>
						{ result.score }
					</span>
					<span
						className="meowseo-synonym-score-label"
						style={ { color: scoreColor } }
					>
						{ scoreLabel }
					</span>
				</div>
				<span className="meowseo-synonym-toggle">
					{ isExpanded ? '▲' : '▼' }
				</span>
			</button>

			{ isExpanded && (
				<div className="meowseo-synonym-details">
					{ result.checks.map( ( check ) => {
						const checkColor = check.pass ? '#46b450' : '#dc3232';
						const statusIcon = check.pass ? '✓' : '✗';

						return (
							<div
								key={ check.id }
								className="meowseo-synonym-check-row"
							>
								<span
									className="meowseo-synonym-check-icon"
									style={ { color: checkColor } }
								>
									{ statusIcon }
								</span>
								<span className="meowseo-synonym-check-label">
									{ check.label }
								</span>
								{ check.value !== undefined && (
									<span className="meowseo-synonym-check-value">
										{ check.value }%
									</span>
								) }
							</div>
						);
					} ) }
				</div>
			) }
		</div>
	);
} );

KeywordResultRow.displayName = 'KeywordResultRow';

/**
 * SynonymAnalysisPanel Component
 *
 * Requirements:
 * - 11.4: Display separate analysis results for primary keyword and each synonym
 * - 11.5: Show combined score
 * - 11.6: Highlight synonym matches in different color than primary keyword
 * - 11.9: Show summary of optimization status per synonym
 */
export const SynonymAnalysisPanel: React.FC = memo( () => {
	const [ analysisData, setAnalysisData ] = useState< AnalysisData | null >(
		null
	);
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	const { postId, synonyms } = useSelect( ( select ) => {
		try {
			const store = select( STORE_NAME ) as any;
			if ( ! store ) {
				return { postId: 0, synonyms: '' };
			}

			const editorSelect = select( 'core/editor' ) as any;
			const postId = editorSelect?.getCurrentPostId() || 0;

			// Get synonyms from entity prop
			const editedMeta = editorSelect?.getEditedPostAttribute( 'meta' );
			const synonymsJson = editedMeta?._meowseo_keyword_synonyms || '';

			return { postId, synonyms: synonymsJson };
		} catch ( error ) {
			console.error(
				'MeowSEO: Error reading from store in SynonymAnalysisPanel:',
				error
			);
			return { postId: 0, synonyms: '' };
		}
	}, [] );

	// Fetch analysis when synonyms change
	useEffect( () => {
		if ( ! postId || ! synonyms ) {
			setAnalysisData( null );
			return;
		}

		const fetchAnalysis = async () => {
			setIsLoading( true );
			setError( null );

			try {
				const data = await apiFetch< AnalysisData >( {
					path: `/meowseo/v1/synonyms/analyze/${ postId }`,
				} );

				setAnalysisData( data );
			} catch ( err ) {
				console.error( 'Failed to fetch synonym analysis:', err );
				setError(
					__(
						'Failed to load synonym analysis. Please try again.',
						'meowseo'
					)
				);
			} finally {
				setIsLoading( false );
			}
		};

		fetchAnalysis();
	}, [ postId, synonyms ] );

	if ( ! synonyms ) {
		return (
			<div className="meowseo-synonym-analysis-panel">
				<h3 className="meowseo-synonym-analysis-title">
					{ __( 'Synonym Analysis', 'meowseo' ) }
				</h3>
				<div className="meowseo-synonym-analysis-empty">
					{ __(
						'Add keyword synonyms to see analysis results.',
						'meowseo'
					) }
				</div>
			</div>
		);
	}

	return (
		<div className="meowseo-synonym-analysis-panel">
			<h3 className="meowseo-synonym-analysis-title">
				{ __( 'Synonym Analysis', 'meowseo' ) }
			</h3>

			{ isLoading ? (
				<div className="meowseo-synonym-analysis-loading">
					<Spinner />
					<span>{ __( 'Analyzing synonyms…', 'meowseo' ) }</span>
				</div>
			) : error ? (
				<div className="meowseo-synonym-analysis-error">{ error }</div>
			) : analysisData ? (
				<div className="meowseo-synonym-analysis-content">
					{ /* Combined Score */ }
					{ analysisData.combined_score > 0 && (
						<div className="meowseo-combined-score">
							<span className="meowseo-combined-score-label">
								{ __( 'Combined Score:', 'meowseo' ) }
							</span>
							<span
								className="meowseo-combined-score-value"
								style={ {
									color: getScoreColor(
										analysisData.combined_score
									),
								} }
							>
								{ Math.round( analysisData.combined_score ) }
							</span>
							<span
								className="meowseo-combined-score-status"
								style={ {
									color: getScoreColor(
										analysisData.combined_score
									),
								} }
							>
								{ getScoreLabel( analysisData.combined_score ) }
							</span>
						</div>
					) }

					{ /* Primary Keyword */ }
					{ analysisData.primary && (
						<KeywordResultRow
							result={ analysisData.primary }
							isPrimary={ true }
							highlightColor="#0073aa" // Blue for primary
						/>
					) }

					{ /* Synonyms */ }
					{ analysisData.synonyms.map( ( result, index ) => (
						<KeywordResultRow
							key={ index }
							result={ result }
							isPrimary={ false }
							highlightColor="#46b450" // Green for synonyms
						/>
					) ) }
				</div>
			) : null }
		</div>
	);
} );

SynonymAnalysisPanel.displayName = 'SynonymAnalysisPanel';
