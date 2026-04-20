/**
 * AI Suggestion Button Component
 *
 * Displays an "AI Suggestion" button next to failing SEO checks
 * and shows AI-powered suggestions in a collapsible panel.
 *
 * @package
 * @since 1.0.0
 */

import { useState } from '@wordpress/element';
import { Button, Spinner, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * AI Suggestion Button Component
 *
 * @param {Object} props           Component props
 * @param {string} props.checkName Check name (e.g., 'keyword_in_title')
 * @param {string} props.content   Current content excerpt
 * @param {string} props.keyword   Focus keyword
 * @param {number} props.postId    Post ID
 * @return {JSX.Element} AI Suggestion Button
 */
export default function AiSuggestionButton( {
	checkName,
	content,
	keyword,
	postId,
} ) {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ suggestion, setSuggestion ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ isExpanded, setIsExpanded ] = useState( false );

	/**
	 * Fetch AI suggestion from REST API
	 */
	const fetchSuggestion = async () => {
		setIsLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: '/meowseo/v1/ai/suggestion',
				method: 'POST',
				data: {
					post_id: postId,
					check_name: checkName,
					content,
					keyword,
				},
			} );

			if ( response.success ) {
				setSuggestion( response.suggestion );
				setIsExpanded( true );
			} else {
				setError( __( 'Failed to generate suggestion.', 'meowseo' ) );
			}
		} catch ( err ) {
			setError(
				err.message ||
					__(
						'An error occurred while generating suggestion.',
						'meowseo'
					)
			);
		} finally {
			setIsLoading( false );
		}
	};

	/**
	 * Handle button click
	 */
	const handleClick = () => {
		if ( suggestion ) {
			setIsExpanded( ! isExpanded );
		} else {
			fetchSuggestion();
		}
	};

	return (
		<div className="meowseo-ai-suggestion">
			<Button
				variant="secondary"
				size="small"
				onClick={ handleClick }
				disabled={ isLoading }
				className="meowseo-ai-suggestion-button"
			>
				{ isLoading && <Spinner /> }
				{ ! isLoading && (
					<>
						<span className="dashicons dashicons-lightbulb" />
						{ __( 'AI Suggestion', 'meowseo' ) }
					</>
				) }
			</Button>

			{ error && (
				<Notice
					status="error"
					isDismissible={ false }
					className="meowseo-ai-suggestion-error"
				>
					{ error }
				</Notice>
			) }

			{ suggestion && isExpanded && (
				<div className="meowseo-ai-suggestion-panel">
					<div className="meowseo-ai-suggestion-content">
						{ suggestion }
					</div>
				</div>
			) }
		</div>
	);
}
