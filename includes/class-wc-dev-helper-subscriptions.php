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
	 * Setup an easy-to-use action for triggering subscription renewals, useful for gateway testing
	 *
	 * @since 0.1.0
	 */
	public function __construct() {

		// Without this, and when using forwarded URLs, WC Subscriptions believes the site to be "duplicate" and disables updating the payment method
		add_filter( 'woocommerce_subscriptions_is_duplicate_site', '__return_false' );

		// add the renew action to the Subscriptions list table
		if ( $this->is_subs_gte_2_0() ) {
			add_filter( 'woocommerce_subscription_list_table_actions', array( $this, 'add_renew_action' ), 10, 2 );
		} else {
			add_filter( 'woocommerce_subscriptions_list_table_actions', array( $this, 'add_renew_action' ), 10, 2 );
		}

		// process the renewa action
		if ( $this->is_subs_gte_2_0() ) {
			add_action( 'load-edit.php', array( $this, 'process_renew_action' ) );
			add_action( 'admin_notices', array( $this, 'maybe_render_renewal_success_message' ) );
		} else {
			add_action( 'load-woocommerce_page_subscriptions', array( $this, 'process_pre_subs_2_0_renew_action' ) );
		}
	}


	/**
	 * Add the "renew" action link to the Subscriptions list table
	 *
	 * @since 0.1.0
	 * @param array $actions subscription actions
	 * @param array|\WC_Subscription $subscription item
	 * @return mixed
	 */
	public function add_renew_action( $actions, $subscription ) {

		if ( $this->is_subs_gte_2_0() ) {

			$renew_url = add_query_arg(
				array(
					'post'     => $subscription->id,
					'action'   => 'renew',
					'_wpnonce' => wp_create_nonce( 'bulk-posts' ),
				)
			);

		} else {
			$renew_url = add_query_arg(
				array(
					'page'         => $_REQUEST['page'],
					'user'         => $subscription['user_id'],
					'subscription' => $subscription['subscription_key'],
					'action'       => 'renew',
					'_wpnonce'     => wp_create_nonce( $subscription['subscription_key'] ),
				)
			);
		}

		$actions['renew'] = sprintf( '<a href="%s">%s</a>', esc_url( $renew_url ), __( 'Renew', 'woocommerce-dev-helper' ) );

		return $actions;
	}


	/**
	 * Process the renewal action from the Subscriptions list table
	 *
	 * @since 0.1.0
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
		WC()->payment_gateways();

		$subscription_id = absint( $_REQUEST['post'] );

		// trigger the renewal
		do_action( 'woocommerce_scheduled_subscription_payment', $subscription_id );

		wp_redirect( remove_query_arg( 'action', add_query_arg( array( 'post_type' => 'shop_subscription', 'wcdh_subs_renew' => true, 'id' => $subscription_id ) ) ) );

		exit();
	}


	/**
	 * Maybe render a renewal success message
	 *
	 * @since 0.2.0
	 */
	public function maybe_render_renewal_success_message() {
		global $post_type, $pagenow;

		if ( 'edit.php' !== $pagenow || 'shop_subscription' !== $post_type || empty( $_REQUEST['wcdh_subs_renew'] ) || empty( $_REQUEST['id'] ) ) {
			return;
		}

		$subscription = wcs_get_subscription( absint( $_REQUEST['id'] ) );

		if ( $subscription instanceof WC_Subscription ) {
			echo '<div class="updated"><p>' . sprintf( esc_html__( 'Subscription renewal processed. %sView Renewal Order%s' ), '<a href="' . wcs_get_edit_post_link( $subscription->get_last_order() ) . '">', ' &#8594;</a>' ) . '</p></div>';
		}
	}


	/** Pre Subs 2.0 **********************************************************/


	/**
	 * Process the renewal action from the Subscriptions list table for
	 * 1.5.x
	 *
	 * @since 0.2.0
	 */
	public function process_pre_subs_2_0_renew_action() {

		// data check
		if ( empty( $_GET['action'] ) || empty( $_GET['_wpnonce'] ) || 'renew' !== $_GET['action'] || empty( $_GET['user'] ) || empty( $_GET['subscription'] ) ) {
			return;
		}

		// nonce check
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], $_GET['subscription'] ) ) {
			wp_die( __( 'Action Failed, Invalid Nonce', 'woocommerce-dev-helper' ) );
		}

		// load gateways
		WC()->payment_gateways();

		// trigger the renewal payment
		WC_Subscriptions_Payment_Gateways::gateway_scheduled_subscription_payment( absint( $_GET['user'] ), $_GET['subscription'] );

		// success message
		add_filter( 'woocommerce_subscriptions_list_table_pre_process_actions', array( $this, 'maybe_render_pre_subs_2_0_renewal_success_message' ) );
	}


	/**
	 * Render a success message when the subscription renewal action has been
	 * processed
	 *
	 * @since 0.1.0
	 * @param array $args
	 * @return mixed
	 */
	public function maybe_render_pre_subs_2_0_renewal_success_message( $args ) {

		if ( empty( $_GET['action'] ) || 'renew' !== $_GET['action'] ) {
			return $args;
		}

		$args['custom_action'] = true;
		$args['messages'] = array( __( 'Subscription Renewal Processed', 'woocommerce-dev-helper' ) );

		return $args;
	}


	/**
	 * Returns true if the active version of Subscriptions is 2.0+
	 *
	 * @since 0.2.0
	 * @return mixed
	 */
	protected function is_subs_gte_2_0() {

		return version_compare( WC_Subscriptions::$version, '1.6.0', '>' );
	}


}
