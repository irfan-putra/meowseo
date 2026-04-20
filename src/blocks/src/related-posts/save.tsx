import React from 'react';
import { useBlockProps } from '@wordpress/block-editor';

interface SaveProps {
	attributes: {
		numberOfPosts: number;
		displayStyle: 'list' | 'grid';
		showExcerpt: boolean;
		showThumbnail: boolean;
		relationshipType: 'keyword' | 'category' | 'tag';
	};
}

const Save: React.FC< SaveProps > = ( { attributes } ) => {
	const { displayStyle } = attributes;
	const blockProps = useBlockProps.save( {
		className: `meowseo-related-posts meowseo-related-posts-${ displayStyle }`,
	} );

	return <div { ...blockProps }>{ /* Server-side rendered content */ }</div>;
};

export default Save;
