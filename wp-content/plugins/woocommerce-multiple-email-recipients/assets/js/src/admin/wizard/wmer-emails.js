/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CheckboxControl,
	__experimentalNumberControl as NumberControl,
	FlexItem as MaybeFlexItem,
	TextareaControl,
	TextControl,
	Notice,
	RadioControl,
} from '@wordpress/components';
import { Form, SelectControl } from '@woocommerce/components';
import { isEmpty, map, has } from 'lodash';
import parse from 'html-react-parser';
import classNames from 'classnames';

import WizardHeader from '../../../../../vendor/barn2/setup-wizard/resources/js/components/header';

import WithForm from '../../../../../vendor/barn2/setup-wizard/resources/js/steps/with-form';

// FlexItem is not available until WP version 5.5. This code is safe to remove
// once the minimum WP supported version becomes 5.5.
const FlextItemSubstitute = ( { children, align } ) => {
	const style = {
		display: 'flex',
		'justify-content': align ? 'center' : 'flex-start',
	};
	return <div style={ style }>{ children }</div>;
};
const FlexItem = MaybeFlexItem || FlextItemSubstitute;

export default class WMEREmails extends WithForm {

	constructor( props ) {
		super( props )
		this.state.textValues = []
	}

	handleChange(value, i) {
		const {
			updateValue
		} = this.props

		this.props.step.fields.email_labels.value[ i + 1 ] = value

		updateValue( 'email_labels', this.props.step.fields.email_labels.value )

	}

	render() {

		const {
			step,
			goToPrevStep,
		} = this.props;

		const {
			customer_email_addresses,
			email_labels,
			title,
			account,
			checkout
		} = step.fields

		const {
			is_submitting,
			error_message,
			continue_btn_text
		} = this.state

		const instance = this

		return (
			<div className="woocommerce-profile-wizard__store-details">
				<div className="woocommerce-profile-wizard__step-header">
					<WizardHeader step={ step }/>
				</div>

				<Form
					initialValues={ this.initialValues }
					onSubmit={ this.onSubmit }
					validate={ this.validateForm }
					onChange={ this.onChange }
				>
					{ ( {
						getInputProps,
						handleSubmit,
						values,
						setValue,
					} ) => (
						<Card>

							{ ! isEmpty( error_message ) && ! is_submitting &&
								<CardBody className="form-error-message-container">
									<Notice status="error" isDismissible={ false }>{ parse( error_message ) }</Notice>
								</CardBody>
							}

							<CardBody key="customer_email_addresses" className="">
								<NumberControl
									{ ...getInputProps( 'customer_email_addresses' ) }
									isShiftStepEnabled={ true }
									isDragEnabled={ false }
									shiftStep={ 10 }
									label={ customer_email_addresses.label }
									disabled={ is_submitting }
									className={ 'muriel-component' }
									min="1"
								/>
								{ has( customer_email_addresses, 'description' ) && ! isEmpty( customer_email_addresses.description ) &&
									<p className="input-description">
										{ parse( customer_email_addresses.description ) }
									</p>
								}
							</CardBody>

							{ instance.isFieldVisible( email_labels, 'email_labels', values ) &&

								<CardBody key={ 'email-labels' } className="text-control">
									<label
										className="components-base-control__label"
										style={{display:'block'}}
									>
										{ email_labels.label }
									</label>
									{ map( Array.from({length: values.customer_email_addresses-1}, (v, i) => i + 1), ( v, i ) => {
										const labels = getInputProps( 'email_labels' ).value
										return (
											<TextControl
												value={ labels[i+1] }
												key={ 'label-' + i }
												label={ '' }
												disabled={ is_submitting }
												placeholder={ "Email label " + ( i + 2 ) }
												onChange={ ( value ) => instance.handleChange( value, i ) }
											/>
										)
									} ) }
								</CardBody>
							}

							{ instance.isFieldVisible( email_labels, 'email_labels', values ) &&

								<div className={ classNames( 'b2-wizard-heading', 'title' ) } key="title">
									<h4>{ title.title }</h4>
								</div>

							}

							{ instance.isFieldVisible( account, 'account', values ) &&

								<CardBody key="account" className="checkbox-wrapper" style={{ paddingBottom: '1px'}}>
									<FlexItem>
										<div className="woocommerce-profile-wizard__client">
											<CheckboxControl
												{ ...getInputProps( 'account' ) }
												label={ account.label }
												disabled={ is_submitting }
												checked={ values[ 'account' ] || false }
											/>
										</div>
									</FlexItem>
								</CardBody>
							
							}

							{ instance.isFieldVisible( checkout, 'checkout', values ) &&

								<CardBody key="checkout" className="checkbox-wrapper">
									<FlexItem>
										<div className="woocommerce-profile-wizard__client">
											<CheckboxControl
												{ ...getInputProps( 'checkout' ) }
												label={ checkout.label }
												disabled={ is_submitting }
												checked={ values[ 'checkout' ] || false }
											/>
										</div>
									</FlexItem>
								</CardBody>
							
							}

							<CardFooter justify="center">
								<Button
										onClick={ () => goToPrevStep() }
										isSecondary
										isBusy={ this.state.is_submitting }
										disabled={ this.state.is_submitting }
									>
										{ __( 'Back', 'woocommerce-admin' ) }
								</Button>

								<Button
									isPrimary
									onClick={ handleSubmit }
									isBusy={ this.state.is_submitting }
									disabled={ this.state.is_submitting }
									>
									{ continue_btn_text }
								</Button>
							</CardFooter>
						</Card>
					) }
				</Form>

			</div>
		);
	}
} 