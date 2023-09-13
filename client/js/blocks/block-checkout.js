import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { TextInput } from '@woocommerce/blocks-checkout';
import {
	BillingCountryInput,
	ShippingCountryInput,
} from '@woocommerce/base-components/country-input';

const settings = window.wc.wcSettings.getSetting( 'bogus_gateway_data', {} );

const label = window.wp.htmlEntities.decodeEntities( settings.title ) || __( 'Bogus', 'woocommerce-dev-helper' );

const el = wp.element.createElement;
// const useState = wp.element.useState;
const elementId = 'wc-blocks-payment-gateways-bogus-gateway-content';

const Description = () => {
	return window.wp.htmlEntities.decodeEntities( settings.description || '' );
};

const Content = () => {
	// const [ result, setResult ] = useState( 'approved' );

	return (
		el(
			'div',
			{ id: elementId },
			[
				el(
					Description,
				),
				el(
					// window.wp.components.SelectControl,
					// {
					// 	label: __( 'Result', 'woocommerce-dev-helper'),
					// 	value: result ?? 'approved',
					// 	options: [
					// 		{ label: 'Approved', value: 'approved'},
					// 		{ label: 'Declined', value: 'declined'},
					// 		{ label: 'Held', value: 'held'},
					// 	],
					// 	onChange: setResult( result ?? 'approved' ),
					// }
					window.wc.blocksCheckout.TextInput,
					{
						label: __( 'Result', 'woocommerce-dev-helper' ),
						key: 'result',
					},
				),
			]
		)
	);
}

const BogusGateway = {
	name: 'bogus_gateway',
	label: label,
	content: el(
		'div',
		null,
		[
			el(
				Description,
				{
					key: 'description',
				}
			),
			el(
				BillingCountryInput,
				{
					label: __( 'Result', 'woocommerce-dev-helper' ),
					key: 'result',
				}
			),
		]
	),
	edit: Object( window.wp.element.createElement )( Description, null ),
	canMakePayment: () => true,
	placeOrderButtonLabel: __( 'Continue', 'woocommerce-dev-helper' ),
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod(BogusGateway);
