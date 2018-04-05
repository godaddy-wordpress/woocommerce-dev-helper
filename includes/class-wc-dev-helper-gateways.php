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
 * @copyright Copyright (c) 2015-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * WooCommerce Gateways helper.
 *
 * @since 0.9.0
 */
class WC_Dev_Helper_Gateways {


	/** @var \WC_Bogus_Gateway instance */
	private $bogus;


	/**
	 * WC Gateways handler constructor.
	 *
	 * @since 0.9.0
	 */
	public function __construct() {

		if ( wc_dev_helper()->is_woocommerce_active() ) {

			require_once( wc_dev_helper()->get_plugin_path() . '/includes/extensions/gateways/class-wc-dev-helper-bogus-gateway.php' );

			$this->bogus = new WC_Bogus_Gateway();

			// add a testing gateway
			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_bogus_gateway' ) );
			add_action( 'wp_head',                      array( $this, 'bogus_gateway_styles' ) );

			// filter default Elavon test card
			add_filter( 'woocommerce_elavon_credit_card_default_values', array( $this, 'change_elavon_test_values' ), 10, 2 );
		}
	}


	/**
	 * Returns the bugs gateway instance.
	 *
	 * @since 0.9.0
	 *
	 * @return null|\WC_Bogus_Gateway
	 */
	public function get_bogus_instance() {
		return $this->bogus;
	}


	/**
	 * Add the bogus gateway to WC available gateways.
	 *
	 * @internal
	 *
	 * @since 0.9.0
	 *
	 * @param string[] $gateways all available WC gateways
	 * @return string[] updated gateways
	 */
	public function add_bogus_gateway( $gateways ) {

		$gateways[] = 'WC_Bogus_Gateway';

		return $gateways;
	}


	/**
	 * Adjust Bogus Gateway styles
	 *
	 * @internal
	 *
	 * @since 0.9.0
	 */
	public function bogus_gateway_styles() {

		if ( is_checkout() || is_checkout_pay_page() ) {
			echo '<style type="text/css">
			#payment .payment_methods li.payment_method_bogus_gateway img {
				float: none !important;
			}
			</style>';
		}
	}


	/**
	 * Changes the Elavon default payment form values.
	 *
	 * @internal
	 *
	 * @since 0.9.0
	 *
	 * @param string[] $defaults the gateway form defaults
	 * @param \WC_Gateway_Elavon_Converge_Credit_Card $gateway gateway instance
	 * @return string[] updated default values
	 */
	public function change_elavon_test_values( $defaults, $gateway ) {

		if ( $gateway->is_test_environment() ) {

			$defaults['expiry']         = '12/19';
			$defaults['account-number'] = '4124939999999990';
		}

		return $defaults;
	}


}
