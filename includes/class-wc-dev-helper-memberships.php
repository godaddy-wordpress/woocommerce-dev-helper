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
 * @copyright Copyright (c) 2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Memberships Class
 *
 * This provides some helpers for development work on WooCommerce Memberships
 *
 * @since 0.4.0
 */
class WC_Dev_Helper_Memberships {


	/**
	 * Memberships helper
	 *
	 * @since 0.4.0
	 */
	public function __construct() {

        add_filter( 'wc_memberships_plan_access_period_options', array( $this, 'add_membership_plan_access_period_options' ) );
	}


	/**
	 * Allow minutes and hours-long access period options for plans
	 *
	 * @since 0.4.0
	 * @param array $periods Associative array of period lengths
	 * @return array
	 */
	public function add_membership_plan_access_period_options( $periods ) {

		$new_periods = array(
			'minutes' => __( 'minute(s)', 'woocommerce-dev-helper' ),
			'hours'   => __( 'hour(s)', 'woocommerce-dev-helper' ),
		);

		return array_merge( $new_periods, $periods );
	}


}
