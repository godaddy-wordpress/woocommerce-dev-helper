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

defined( 'ABSPATH' ) || exit;

/**
 * Subscriptions Class
 *
 * This provides some helpers for developing extensions (like payment gateways)
 * that integrate with the WooCommerce Subscriptions extension
 *
 * @since 0.1.0
 */
class WC_Dev_Helper_Subscriptions {


	/**
	 * Setup:
	 *
	 * 1) An easy-to-use action for triggering subscription renewals, useful for gateway testing
	 *
	 * @since 0.1.0
	 */
	public function __construct() {

		// Without this, and when using forwarded URLs, WC Subscriptions believes the site to be "duplicate" and disables updating the payment method
		add_filter( 'woocommerce_subscriptions_is_duplicate_site', '__return_false' );

		// add the "renew" action to the Subscriptions list table
		add_filter( 'woocommerce_subscriptions_list_table_actions', array( $this, 'add_renew_action' ), 10, 2 );

		// process the "renew" action
		add_action( 'load-woocommerce_page_subscriptions', array( $this, 'process_renew_action' ) );
	}


	/**
	 * Add the "renew" action link to the Subscriptions list table
	 *
	 * @since 0.1.0
	 * @param array $actions subscription actions
	 * @param array $item subscription item
	 * @return mixed
	 */
	public function add_renew_action( $actions, $item ) {

		$renew_url = add_query_arg(
			array(
				'page'         => $_REQUEST['page'],
				'user'         => $item['user_id'],
				'subscription' => $item['subscription_key'],
				'action'       => 'renew',
				'_wpnonce'     => wp_create_nonce( $item['subscription_key'] )
			)
		);

		$actions['renew'] = sprintf( '<a href="%s">%s</a>', esc_url( $renew_url ), __( 'Renew' ) );

		return $actions;
	}


	/**
	 * Process the renewal action from the Subscriptions list table
	 *
	 * @since 0.1.0
	 */
	public function process_renew_action() {

		// data check
		if ( empty( $_GET['action'] ) || 'renew' !== $_GET['action'] || empty( $_GET['user'] ) || empty( $_GET['subscription'] ) ) {
			return;
		}

		// nonce check
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], $_GET['subscription'] ) ) {
			wp_die( __( 'Action Failed, Invalid Nonce' ) );
		}

		// load gateways
		WC()->payment_gateways();

		// trigger the renewal payment
		WC_Subscriptions_Payment_Gateways::gateway_scheduled_subscription_payment( absint( $_GET['user'] ), $_GET['subscription'] );

		add_filter( 'woocommerce_subscriptions_list_table_pre_process_actions', array( $this, 'maybe_render_renewal_success_message' ) );
	}


	/**
	 * Render a success message when the subscription renewal action has been
	 * processed
	 *
	 * @since 0.1.0
	 * @param array $args
	 * @return mixed
	 */
	public function maybe_render_renewal_success_message( $args ) {

		if ( empty( $_GET['action'] ) || 'renew' !== $_GET['action'] ) {
			return $args;
		}

		$args['custom_action'] = true;
		$args['messages'] = array( __( 'Subscription Renewal Processed' ) );

		return $args;
	}


}
