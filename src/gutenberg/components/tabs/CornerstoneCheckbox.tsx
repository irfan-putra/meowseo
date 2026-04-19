/**
 * CornerstoneCheckbox Component
 *
 * Checkbox control for marking posts as cornerstone content.
 *
 * Requirements: 6.1
 */

import { useSelect, useDispatch } from '@wordpress/data';
import { CheckboxControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useEffect } from '@wordpress/element';

/**
 * CornerstoneCheckbox Component
 *
 * Requirement 6.1: Checkbox control for cornerstone content designation
 */
const CornerstoneCheckbox: React.FC = () => {
	const postType = useSelect((select: any) => {
		return select('core/editor').getCurrentPostType();
	}, []);

	const postId = useSelect((select: any) => {
		return select('core/editor').getCurrentPostId();
	}, []);

	// Get cornerstone meta value
	const [isCornerstone, setIsCornerstone] = useEntityProp(
		'postType',
		postType,
		'meta',
		postId
	);

	const cornerstoneValue = isCornerstone?._meowseo_is_cornerstone || '';

	// Handle checkbox change
	const handleChange = (checked: boolean) => {
		setIsCornerstone({
			...isCornerstone,
			_meowseo_is_cornerstone: checked ? '1' : '',
		});
	};

	return (
		<div className="meowseo-cornerstone-control">
			<CheckboxControl
				label="Mark as Cornerstone Content"
				help="Cornerstone content represents your most important pages. These will be prioritized in internal link suggestions."
				checked={cornerstoneValue === '1'}
				onChange={handleChange}
			/>
		</div>
	);
};

export default CornerstoneCheckbox;
