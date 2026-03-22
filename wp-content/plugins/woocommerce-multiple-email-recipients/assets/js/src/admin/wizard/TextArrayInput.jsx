/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';
import { map, isEmpty } from 'lodash';
import { useState } from 'react';
import { useDidUpdate, useEffectOnceWhen, useGetIsMounted } from 'rooks';

const TextArrayInput = (
    {
		name,
		label,
		onChange,
		allValues,
		...props
	}
) => {

	// List of all values.
	const [values, setValues] = useState([])

	// Grab the number of addresses from the form state.
	const numberAddresses = Number( allValues?.customer_email_addresses )

	/**
	 * Save the input values into the state.
	 *
	 * @param {*} value
	 * @param {*} i
	 */
	const handleChange = ( value, i ) => {
		let vals = [ ...values ];
		vals[ i ] = value;
		setValues( vals );
	}

	useEffectOnceWhen( () => {
		const currentValues = values
		const preloadedValues = allValues?.email_labels

		if ( isEmpty( currentValues ) ) {
			setValues( Object.values( preloadedValues ) )
		}
	}, useGetIsMounted() )

	useDidUpdate( () => {
		onChange(values)
	}, [ values ] )

    return (
		<>
            { numberAddresses > 1 && <div className="barn2-inline-label">{ label }</div> }

			{ map( Array.from( { length: numberAddresses -1 } ), ( v, i ) => {
				return (
					<TextControl
						value={ values[i] ?? '' }
						key={ 'label-' + i }
						label={ '' }
						placeholder={ "Email label " + ( i + 2 ) }
						onChange={ ( value ) => handleChange( value, i ) }
					/>
				)
			} ) }
			
		</>
	)

}

export default TextArrayInput