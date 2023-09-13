import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { createElement, useState } from '@wordpress/element';
import { TextInput } from '@woocommerce/blocks-checkout';
import { SelectControl } from 'wordpress-components';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting('bogus_gateway_data', {});

const label = decodeEntities(settings.title) || __('Bogus', 'woocommerce-dev-helper');

// const useState = wp.element.useState;
const elementId = 'wc-blocks-payment-gateways-bogus-gateway-content';

const Description = () => {
	return decodeEntities(settings.description || '');
};

const Content = () => {
	const [result, setResult] = useState('approved');

	const options = settings.result_options

	return (
		<div id={elementId}>
			<Description />
			<SelectControl
				label={__('Result', 'woocommerce-dev-helper')}
				key="result"
				value={result}
				onChange={setResult}
				options={options}
			/>
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
