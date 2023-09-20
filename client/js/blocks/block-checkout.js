import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { createElement, useState, useEffect } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting('bogus_gateway_data', {});
const label = decodeEntities(settings.title) || __('Bogus', 'woocommerce-dev-helper');
const elementId = 'wc-blocks-payment-gateways-bogus-gateway-content';

const Description = () => decodeEntities(settings.description || '');

const Content = ( props ) => {
	const [result, setResult] = useState('approved');
	const options = settings.result_options

	const { eventRegistration, emitResponse } = props;
	const { onPaymentProcessing } = eventRegistration;

	useEffect( () => {
		const unsubscribe = onPaymentProcessing( async () => {

			// validate input
			let inputIsValid = false;
			for ( const index of options ){
				inputIsValid = ( index['value'] === result );
				if (inputIsValid === true) break;
			}

			// return result for server-side gateway processing
			if ( inputIsValid ) {
				return {
					type: emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							'bogus_gateway_payment_result': result
						},
					},
				};
			}

			return {
				type: emitResponse.responseTypes.ERROR,
				message: 'Invalid input. Please use one of the options provided.',
			};
		} );
		// Unsubscribes when this component is unmounted.
		return () => {
			unsubscribe();
		};
	}, [
		emitResponse.responseTypes.ERROR,
		emitResponse.responseTypes.SUCCESS,
		onPaymentProcessing,
		result,
	] );

	const selectOptions = options.map((option) => <option value={option.value} key={option.value}>{option.label}</option>)

	return (
		<div id={elementId}>
			<Description />
			<div className="wc-block-components-text-input is-active">
				<label htmlFor="result" key="label">{__('Result', 'woocommerce-dev-helper')}</label>
				<select name="result" onChange={(e) => setResult(e.target.value)} value={result}>
					{selectOptions}
				</select>
			</div>
		</div>
	)
}

const BogusGateway = {
	name: 'bogus_gateway',
	label: label,
	content: <Content />,
	edit: Object(createElement)(Description, null),
	canMakePayment: () => true,
	placeOrderButtonLabel: __( 'Continue', 'woocommerce-dev-helper' ),
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod(BogusGateway);
