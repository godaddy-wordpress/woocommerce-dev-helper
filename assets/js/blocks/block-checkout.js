const { __ } = wp.i18n;

const settings = window.wc.wcSettings.getSetting( 'bogus_gateway_data', {} );

const label = window.wp.htmlEntities.decodeEntities( settings.title ) || __( 'Bogus', 'woocommerce-dev-helper' );

const Description = () => {
	return window.wp.htmlEntities.decodeEntities( settings.description || '' );
};

const el = wp.element.createElement;

const BogusGateway = {
	name: 'bogus_gateway',
	label: label,
	content: el(
		'div',
		null,
		[
			el(
				Description,
			),
			el(
				window.wc.blocksCheckout.TextInput,
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

window.wc.wcBlocksRegistry.registerPaymentMethod( BogusGateway );
