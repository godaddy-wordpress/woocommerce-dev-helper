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
 * Subscriptions helper.
 *
 * This provides some helpers for developing extensions (like payment gateways),
 * that integrate with the WooCommerce Subscriptions extension.
 *
 * @since 1.0.0
 */
class Subscriptions {


	/**
	 * Adds hooks.
	 *
	 * Sets up an easy-to-use action for triggering subscription renewals, useful for gateway testing.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// without this, and when using Forwarded URLs, Subscriptions believes the site to be "duplicate" and disables updating the payment method
		add_filter( 'woocommerce_subscriptions_is_duplicate_site', '__return_false' );

		// add the renew action to the Subscriptions list table
		add_filter( 'woocommerce_subscription_list_table_actions', array( $this, 'add_renew_action' ), 10, 2 );

		// process the renewal action
		add_action( 'load-edit.php', array( $this, 'process_renew_action' ) );
		add_action( 'admin_notices', array( $this, 'output_renewal_success_message' ) );

		// add support for minutes and hours-long Subscription period for quicker testing
		add_filter( 'woocommerce_subscription_available_time_periods', array( $this, 'add_new_subscription_periods' ) );
		add_filter( 'woocommerce_subscription_periods',                array( $this, 'add_new_subscription_periods' ) );
		add_filter( 'woocommerce_subscription_trial_periods',          array( $this, 'add_new_subscription_periods' ) );
		add_filter( 'woocommerce_subscription_lengths',                array( $this, 'add_new_subscription_lengths' ) );
	}


	/**
	 * Adds the "renew" action link to the Subscriptions list table.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions subscription actions
	 * @param array|\WC_Subscription $subscription item
	 * @return mixed
	 */
	public function add_renew_action( $actions, $subscription ) {

		$actions['renew'] = sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg(
			array(
				'post'     => Framework\SV_WC_Order_Compatibility::get_prop( $subscription, 'id' ),
				'action'   => 'renew',
				'_wpnonce' => wp_create_nonce( 'bulk-posts' ),
			)
		) ), __( 'Renew', 'woocommerce-dev-helper' ) );

		return $actions;
	}


	/**
	 * Processes the renewal action from the Subscriptions list table.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function process_renew_action() {

		// only subscriptions
		if ( ! isset( $_REQUEST['post_type'] ) || 'shop_subscription' !== $_REQUEST['post_type'] ) {
			return;
		}

		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );

		if ( 'renew' !== $wp_list_table->current_action() ) {
			return;
		}

		// load gateways
		wc()->payment_gateways();

		$subscription_id = absint( $_REQUEST['post'] );

		// trigger the renewal
		do_action( 'woocommerce_scheduled_subscription_payment', $subscription_id );

		wp_redirect( remove_query_arg( 'action', add_query_arg( array( 'post_type' => 'shop_subscription', 'wcdh_subs_renew' => true, 'id' => $subscription_id ) ) ) );

		exit();
	}


	/**
	 * Maybe renders a renewal success message
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function output_renewal_success_message() {
		global $post_type, $pagenow;

		if ( 'edit.php' !== $pagenow || 'shop_subscription' !== $post_type || empty( $_REQUEST['wcdh_subs_renew'] ) || empty( $_REQUEST['id'] ) ) {
			return;
		}

		$subscription = wcs_get_subscription( absint( $_REQUEST['id'] ) );

		if ( $subscription instanceof \WC_Subscription ) {
			echo '<div class="updated"><p>' . sprintf( esc_html__( 'Subscription renewal processed. %sView Renewal Order%s', 'woocommerce-dev-helper' ), '<a href="' . wcs_get_edit_post_link( $subscription->get_last_order() ) . '">', ' &#8594;</a>' ) . '</p></div>';
		}
	}


	/**
	 * Adds the minute / hour into available subscription period options.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $subscription_periods associative array of available periods
	 * @return array with updated periods
	 */
	public function add_new_subscription_periods( $subscription_periods ) {

		$new_periods = array(
			'minute' => 'minute',
			'hour'   => 'hour',
		);

		return array_merge( $new_periods, $subscription_periods);
	}


	/**
	 * Adds subscription lengths for our new "minute" and "hour" period
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $lengths associative array of available lengths
	 * @return array - updated lengths
	 */
	public function add_new_subscription_lengths( $lengths ) {

		// start range with 0 => all time
		$minute_durations = array( 'all time', '1 minute' );
		$minute_steps     = range( 5, 60, 5 );

		// add possible steps for subscription duration
		foreach( $minute_steps as $number ) {
			$minute_durations[ $number ] = $number . ' minutes';
		}

		$hour_durations = array( 'all time', '1 hour' );
		$hour_steps     = range( 2, 6 );

		foreach ( $hour_steps as $number ) {
			$hour_durations[ $number ] = $number . ' hours';
		}

		$lengths['minute'] = $minute_durations;
		$lengths['hour']   = $hour_durations;

		return $lengths;
	}


}
