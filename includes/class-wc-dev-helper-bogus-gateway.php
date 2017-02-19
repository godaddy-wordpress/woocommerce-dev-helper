<?php
/**
 * WooCommerce Dev Helper
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * @package   WC-Dev-Helper/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2015-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Adds a testing gateway that calls the WooCommerce payment_complete() method.
 *
 * @since 0.6.0
 */

class WC_Bogus_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 *
	 * @since 0.6.0
	 */
	public function __construct() {

		$this->id                 = 'bogus_gateway';
		$this->icon               = apply_filters('woocommerce_bogus_icon', '');
		$this->has_fields         = false;
		$this->method_title       = __( 'Bogus', 'woocommerce-dev-helper' );
		$this->method_description = __( 'A testing gateway that calls "payment complete" to simulate credit card transactions.', 'woocommerce-dev-helper' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}


	/**
	 * Initialize gateway settings form fields.
	 *
	 * @since 0.6.0
	 */
	public function init_form_fields() {

		$this->form_fields = apply_filters( 'wc_offline_form_fields', array(

			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-dev-helper' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Bogus Gateway', 'woocommerce-dev-helper' ),
				'default' => 'yes'
			),

			'title' => array(
				'title'       => __( 'Title', 'woocommerce-dev-helper' ),
				'type'        => 'text',
				'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'woocommerce-dev-helper' ),
				'default'     => __( 'Bogus (Test)', 'woocommerce-dev-helper' ),
				'desc_tip'    => true,
			),

			'description' => array(
				'title'       => __( 'Description', 'woocommerce-dev-helper' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-dev-helper' ),
				'default'     => __( 'Nothingtodohere &#128640;', 'woocommerce-dev-helper' ),
				'desc_tip'    => true,
			),

		) );
	}


	/**
	 * Process the payment and return the result.
	 *
	 * @since 0.6.0
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		// Update order status and add a transaction note
		$order->payment_complete();
		$order->add_order_note( __( 'Bogus is always approved &#128526;', 'woocommerce-dev-helper' ) );

		// Remove cart
		WC()->cart->empty_cart();

		// Return thank you redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order )
		);
	}


}
