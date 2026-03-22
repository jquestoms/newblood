/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import TextArrayInput from './TextArrayInput';

/**
 * Register the custom parameters generator component.
 */

wp.hooks.addFilter(
	'barn2_components_fields_mapping',
	'barn2-wmer-filter',
	( components ) => {

		const newComponents = {
			'labels': TextArrayInput
		}

		return { ...components, ...newComponents }
	}
)
