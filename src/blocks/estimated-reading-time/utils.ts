/**
 * Estimated Reading Time Block - Utilities
 *
 * Requirements: 9.1, 9.2, 9.3
 */

/**
 * Calculate reading time based on word count and reading speed
 *
 * Formula: Math.ceil(wordCount / wordsPerMinute)
 *
 * @param content        - The content to analyze
 * @param wordsPerMinute - Average reading speed (150-300)
 * @return Reading time in minutes
 */
export function calculateReadingTime(
	content: string,
	wordsPerMinute: number = 200
): number {
	if ( ! content ) {
		return 0;
	}

	// Remove HTML tags
	const plainText = content.replace( /<[^>]*>/g, '' );

	// Count words (split by whitespace)
	const wordCount = plainText
		.trim()
		.split( /\s+/ )
		.filter( ( word ) => word.length > 0 ).length;

	// Calculate reading time
	return Math.ceil( wordCount / wordsPerMinute );
}

/**
 * Format reading time for display
 *
 * @param minutes - Reading time in minutes
 * @return Formatted string
 */
export function formatReadingTime( minutes: number ): string {
	if ( minutes < 1 ) {
		return '< 1';
	}
	return minutes.toString();
}
