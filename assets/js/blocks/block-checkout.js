const settings = window.wc.wcSettings.getSetting( 'bogus_gateway_data', {} );

const label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'Bogus', 'woocommerce-dev-helper' );

const Content = () => {
	return window.wp.htmlEntities.decodeEntities( settings.description || '' );
};

const BogusGateway = {
	name: 'bogus_gateway',
	label: label,
	content: Object( window.wp.element.createElement )( Content, null ),
	edit: Object( window.wp.element.createElement )( Content, null ),
	canMakePayment: () => true,
	placeOrderButtonLabel: window.wp.i18n.__( 'Continue', 'woocommerce-dev-helper' ),
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

window.wc.wcBlocksRegistry.registerPaymentMethod( BogusGateway );