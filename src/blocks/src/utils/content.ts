/**
 * Content analysis utilities for MeowSEO blocks
 */

/**
 * Calculate reading time based on word count
 * @param content
 * @param wordsPerMinute
 */
export const calculateReadingTime = (
	content: string,
	wordsPerMinute: number = 200
): number => {
	if ( ! content ) {
		return 0;
	}

	// Remove HTML tags
	const plainText = content.replace( /<[^>]*>/g, '' );

	// Count words
	const words = plainText
		.trim()
		.split( /\s+/ )
		.filter( ( word ) => word.length > 0 ).length;

	// Calculate reading time (round up)
	return Math.ceil( words / wordsPerMinute );
};

/**
 * Format reading time for display
 * @param minutes
 */
export const formatReadingTime = ( minutes: number ): string => {
	if ( minutes < 1 ) {
		return 'Less than 1 minute';
	}
	if ( minutes === 1 ) {
		return '1 minute';
	}
	return `${ minutes } minutes`;
};

/**
 * Extract keywords from content
 * @param content
 * @param limit
 */
export const extractKeywords = (
	content: string,
	limit: number = 10
): string[] => {
	if ( ! content ) {
		return [];
	}

	const plainText = content.replace( /<[^>]*>/g, '' ).toLowerCase();
	const words = plainText
		.split( /\s+/ )
		.filter( ( word ) => word.length > 3 )
		.filter( ( word ) => ! isStopWord( word ) );

	// Count word frequency
	const frequency: Record< string, number > = {};
	words.forEach( ( word ) => {
		frequency[ word ] = ( frequency[ word ] || 0 ) + 1;
	} );

	// Sort by frequency and return top keywords
	return Object.entries( frequency )
		.sort( ( [ , a ], [ , b ] ) => b - a )
		.slice( 0, limit )
		.map( ( [ word ] ) => word );
};

/**
 * Common English stop words
 */
const STOP_WORDS = new Set( [
	'the',
	'a',
	'an',
	'and',
	'or',
	'but',
	'in',
	'on',
	'at',
	'to',
	'for',
	'of',
	'with',
	'by',
	'from',
	'is',
	'are',
	'was',
	'were',
	'be',
	'been',
	'being',
	'have',
	'has',
	'had',
	'do',
	'does',
	'did',
	'will',
	'would',
	'could',
	'should',
	'may',
	'might',
	'must',
	'can',
	'this',
	'that',
	'these',
	'those',
	'i',
	'you',
	'he',
	'she',
	'it',
	'we',
	'they',
] );

/**
 * Check if word is a stop word
 * @param word
 */
const isStopWord = ( word: string ): boolean => {
	return STOP_WORDS.has( word.toLowerCase() );
};

/**
 * Calculate content similarity based on keyword overlap
 * @param content1
 * @param content2
 */
export const calculateSimilarity = (
	content1: string,
	content2: string
): number => {
	const keywords1 = new Set( extractKeywords( content1, 20 ) );
	const keywords2 = new Set( extractKeywords( content2, 20 ) );

	if ( keywords1.size === 0 || keywords2.size === 0 ) {
		return 0;
	}

	const intersection = new Set(
		[ ...keywords1 ].filter( ( k ) => keywords2.has( k ) )
	);

	const union = new Set( [ ...keywords1, ...keywords2 ] );

	return intersection.size / union.size;
};
