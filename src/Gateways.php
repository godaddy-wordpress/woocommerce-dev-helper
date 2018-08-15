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
 * @author    SkyVerge
 * @copyright Copyright (c) 2015-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\Dev_Helper;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Dev_Helper\Gateways\Bogus;
use SkyVerge\WooCommerce\PluginFramework\v5_2_0 as Framework;

/**
 * Tweaks known WooCommerce gateways and implements a mock gateway for testing purposes.
 *
 * @since 1.0.0
 */
class Gateways {


	/** @var Bogus gateway instance */
	private $bogus;


	/**
	 * Gateways constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( wc_dev_helper()->is_woocommerce_active() ) {

			$this->add_bogus_gateway();

			// filters default Elavon test card
			add_filter( 'woocommerce_elavon_credit_card_default_values', function( $defaults, $gateway ) {

				if ( $gateway->is_test_environment() ) {
					$defaults['expiry']         = '12/19';
					$defaults['account-number'] = '4124939999999990';
				}

				return $defaults;

			}, 10, 2);
		}
	}


	/**
	 * Adds support for a "Bogus" gateway meant for checkout testing purposes.
	 *
	 * @since 1.0.0
	 */
	private function add_bogus_gateway() {

		$this->bogus = new Bogus();

		// adds a mock bogus gateway for testing checkout
		add_filter( 'woocommerce_payment_gateways', function( $gateways ) {
			$gateways[] = '\SkyVerge\WooCommerce\Dev_Helper\Gateways\Bogus';
			return $gateways;
		} );

		// adds frontend bogus gateway styles at checkout
		add_action( 'wp_head', function() {
			if ( is_checkout() || is_checkout_pay_page() ) :

				?>
				<style type="text/css">
					#payment .payment_methods li.payment_method_bogus_gateway img {
						float: none !important;
					}
				</style>
				<?php

			endif;
		} );
	}


	/**
	 * Returns the current Bogus gateway instance.
	 *
	 * @since 1.0.0
	 *
	 * @return null|Bogus
	 */
	public function get_bogus_instance() {

		return $this->bogus;
	}


}
