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
 * Handler of WooCommerce extensions helpers.
 *
 * @since 0.9.0
 */
class WC_Dev_Helper_Extensions {


	/** @var \WC_Dev_Helper_Memberships */
	private $memberships;

	/** @var \WC_Dev_Helper_Subscriptions */
	private $subscriptions;


	/**
	 * WC Extensions handler constructor.
	 *
	 * @since 0.9.0
	 */
	public function __construct() {

		if ( wc_dev_helper()->is_plugin_active( 'woocommerce-subscriptions.php' ) ) {

			require_once( wc_dev_helper()->get_plugin_path() . '/includes/extensions/subscriptions/class-wc-dev-helper-subscriptions.php' );

			$this->subscriptions = new WC_Dev_Helper_Subscriptions();
		}

		if ( wc_dev_helper()->is_plugin_active( 'woocommerce-memberships.php' ) ) {

			require_once( wc_dev_helper()->get_plugin_path() . '/includes/extensions/memberships/class-wc-dev-helper-memberships.php' );

			$this->memberships = new WC_Dev_Helper_Memberships();
		}
	}


	/**
	 * Returns the memberships instance.
	 *
	 * @since 0.9.0
	 *
	 * @return null|\WC_Dev_Helper_Memberships
	 */
	public function get_memberships_instance() {
		return $this->memberships;
	}


	/**
	 * Returns the subscriptions instance.
	 *
	 * @since 0.9.0
	 *
	 * @return null|\WC_Dev_Helper_Subscriptions
	 */
	public function get_subscriptions_instance() {
		return $this->subscriptions;
	}


}
