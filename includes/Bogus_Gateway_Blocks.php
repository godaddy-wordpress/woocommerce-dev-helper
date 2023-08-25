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
 * @copyright Copyright (c) 2015-2022, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\DevHelper;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) or exit;

/**
 * Adds our testing gateway to the Checkout Block
 *
 * @since 0.6.0
 */
class Bogus_Gateway_Blocks_Support extends AbstractPaymentMethodType {

	private $gateway;
	protected $name = 'bogus';

	/**
	 * This function will get called during the server side initialization process 
	 * and is a good place to put any settings population etc. 
	 * Basically anything you need to do to initialize your gateway. 
	 * Note, this will be called on every request so don't put anything expensive here.
	 */
	public function initialize() {
		$this->settings = get_option( 'bogus_gateway_settings', [] );
		$this->gateway = new Bogus_Gateway;
	}

	public function is_active() {
		// return whether the payment method is active or not
		return $this->get_setting( 'enabled' ) === 'yes';
	}

	public function get_payment_method_script_handles(){
		// register your payment method scripts (using wp_register_script) and then return the script handles you registered with
		wp_register_script(
			'wc-bogus-gateway-blocks-integration',
			plugins_url( '/includes/block-checkout.js', __FILE__ ),
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			null,
			true
		);
		if( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-bogus-gateway-blocks-integration');
		}
		return [ 'wc-bogus-gateway-blocks-integration' ];

	}

	/**
	 * Include this if your payment method has a script you only want to load 
	 * in the editor context for the checkout block. 
	 * 
	 * Include here any script from get_payment_method_script_handles that is 
	 * also needed in the admin.
	 */
	public function get_payment_method_script_handles_for_admin(){
		return [];
	}

	public function get_payment_method_data(){
		// You can return from this function an associative array of data you want to be exposed for your payment method client side.
		return [
			'title'       => $this->gateway->method_title,
			'description' => $this->gateway->method_description,
			'supports'    => $this->gateway->supports,
		];
	}
}