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
 * @copyright Copyright (c) 2015-2023, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\DevHelper;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) or exit;

/**
 * Adds support for our testing gateway to WooCommerce Checkout Block.
 *
 * @since 1.1.0
 */
class Bogus_Gateway_Blocks_Support extends AbstractPaymentMethodType {


	/** @var WC_Payment_Gateway|Bogus_Gateway */
	protected WC_Payment_Gateway $gateway;

	/** @var string */
	protected $name = 'bogus_gateway';


	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 *
	 * @param Bogus_Gateway $gateway
	 */
	public function __construct( WC_Payment_Gateway $gateway) {

		$this->gateway  = $gateway;
	}

	/**
	 * Initializes the payment method blocks support.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function initialize() {

		$this->settings = get_option( 'bogus_gateway_settings', [] );
	}


	/**
	 * Determines if it's active.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function is_active() : bool {

		// default to yes if no settings are set
		return ! $this->settings || $this->get_setting( 'enabled' ) === 'yes';
	}


	/**
	 * Registers any handles of supporting scripts for the payment method.
	 *
	 * @since 1.1.0
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() : array {

		wp_register_script(
			'wc-bogus-gateway-blocks-integration',
			trailingslashit( wc_dev_helper()->get_plugin_url() ) . 'assets/js/blocks/block-checkout.js',
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			Plugin::VERSION,
			true
		);

		wp_set_script_translations( 'wc-bogus-gateway-blocks-integration');

		return [
			'wc-bogus-gateway-blocks-integration'
		];
	}


	/**
	 * Registers any scripts for the payment method to be loaded in admin only.
	 *
	 * @since 1.1.0
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles_for_admin() : array {

		return [];
	}


	/**
	 * Gets the payment method data.
	 *
	 * @return array<string, string>
	 */
	public function get_payment_method_data() : array {

		return [
			'title'       => $this->gateway->method_title,
			'description' => $this->gateway->method_description,
			'supports'    => $this->gateway->supports,
		];
	}


}