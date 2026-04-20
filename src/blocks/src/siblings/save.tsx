import React from 'react';
import { useBlockProps } from '@wordpress/block-editor';

const Save: React.FC = () => {
	const blockProps = useBlockProps.save( {
		className: 'meowseo-siblings',
	} );

	return <div { ...blockProps }>{ /* Server-side rendered content */ }</div>;
};

export default Save;
