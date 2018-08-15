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

use SkyVerge\WooCommerce\PluginFramework\v5_2_0 as Framework;

/**
 * Memberships helper.
 *
 * This provides some helpers for development work on WooCommerce Memberships.
 *
 * @since 1.0.0
 */
class Memberships {


	/**
	 * Memberships helper constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// adds support for minutes and hours-long membership plans
		add_filter( 'wc_memberships_plan_access_period_options', function( $periods ) {

			$new_periods = array(
				'minutes' => __( 'minute(s)', 'woocommerce-dev-helper' ),
				'hours'   => __( 'hour(s)', 'woocommerce-dev-helper' ),
			);

			return array_merge( $new_periods, $periods );

		}, 1 );

		// filters the human access length information so it can work with minutes and hours
		add_filter( 'wc_memberships_membership_plan_human_access_length', function( $human_length, $standard_length ) {

			$has_minutes = strpos( $standard_length, 'minute' ) !== false;
			$has_hours   = strpos( $standard_length, 'hour' )   !== false;

			if ( $has_minutes || $has_hours ) {
				$human_length = $standard_length;
			}

			return $human_length;

		}, 1, 2 );
	}


}
