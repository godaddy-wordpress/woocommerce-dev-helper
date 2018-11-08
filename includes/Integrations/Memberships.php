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

namespace SkyVerge\WooCommerce\DevHelper\Integrations;

defined( 'ABSPATH' ) or exit;

/**
 * Memberships Class
 *
 * This provides some helpers for development work on WooCommerce Memberships
 *
 * @since 0.4.0
 */
class Memberships {


	/**
	 * Memberships helper
	 *
	 * @since 0.4.0
	 */
	public function __construct() {

		// add support for minutes and hours-long membership plans
		add_filter( 'wc_memberships_plan_access_period_options', array( $this, 'add_membership_plan_access_period_options' ) );

		// filter the human access length information so it can work with minutes and hours
		add_filter( 'wc_memberships_membership_plan_human_access_length', array( $this, 'filter_membership_human_access_length' ), 10, 2 );
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


	/**
	 * Display a human friendly format for the access length when it's minutes or hours-long
	 *
	 * @since 0.4.2
	 * @param string $human_length The human length
	 * @param string $standard_length The length in the standard machine-friendly format
	 * @return string
	 */
	public function filter_membership_human_access_length( $human_length, $standard_length ) {

		$has_minutes = strpos( $standard_length, 'minute' ) !== false;
		$has_hours   = strpos( $standard_length, 'hour' )   !== false;

		if ( $has_minutes || $has_hours ) {

			$human_length = $standard_length;
		}

		return $human_length;
	}


}
