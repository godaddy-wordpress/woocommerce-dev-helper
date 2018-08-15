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
 * AJAX handler.
 *
 * @since 1.0.0
 */
class AJAX {


	/**
	 * Adds AJAX actions.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// send WooCommerce session data to console JS request
		add_action( 'wp_ajax_wc_dev_helper_get_session',        array( $this, 'get_session_data' ) );
		add_action( 'wp_ajax_nopriv_wc_dev_helper_get_session', array( $this, 'get_session_data' ) );

		// memberships bulk generation tool handling
		add_action( 'wp_ajax_wc_dev_helper_memberships_bulk_generate',               array( $this, 'start_bulk_generate_memberships' ) );
		add_action( 'wp_ajax_wc_dev_helper_get_memberships_bulk_generation_status',  array( $this, 'get_memberships_bulk_generation_status' ) );
		add_action( 'wp_ajax_wc_dev_helper_memberships_bulk_destroy',                array( $this, 'start_bulk_destroy_memberships' ) );
		add_action( 'wp_ajax_wc_dev_helper_get_memberships_bulk_destruction_status', array( $this, 'get_memberships_bulk_destruction_status' ) );
	}


	/**
	 * Sends session data from WooCommerce for the current user.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function get_session_data() {

		/* @type \WC_Session_Handler $session_handler */
		$session_handler = wc()->session;

		wp_send_json( $session_handler->get_session_data() );
	}


	/**
	 * Creates a background job and starts a memberships bulk generation process.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function start_bulk_generate_memberships() {

		check_ajax_referer( 'start-memberships-bulk-generation', 'security' );

		if ( isset( $_POST['members_to_generate'] ) && is_numeric( $_POST['members_to_generate'] ) && $_POST['members_to_generate'] > 0 ) {

			$generator = wc_dev_helper()->get_tools_instance()->get_memberships_bulk_generator_instance();

			if ( $generator ) {

				$existing_job = $generator->get_job();

				if ( ! $existing_job ) {

					$limit = max( 1, (int) $_POST['members_to_generate'] );
					$job   = $generator->create_job( array(
						'users' => $generator->get_users_slugs( $limit ),
						'count' => range( 0, $limit ),
					) );

					// dispatch the background processor
					$generator->dispatch();

					// send results
					wp_send_json_success( $job );
				}

				wp_send_json_error( __( 'There is an existing job in the queue that has not completed yet.', 'woocommerce-dev-helper' ) );
			}

			wp_send_json_error( __( 'Could not load the generator.', 'woocommerce-dev-helper' ) );
		}

		wp_send_json_error( __( 'Invalid or missing options.', 'woocommerce-dev-helper' ) );
	}


	/**
	 * Gets the status of a bulk memberships generation job.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function get_memberships_bulk_generation_status() {

		check_ajax_referer( 'get-memberships-bulk-generation-status', 'security' );

	}


	/**
	 * Creates a background job and starts a memberships bulk destruction process.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function start_bulk_destroy_memberships() {

		check_ajax_referer( 'start-memberships-bulk-destruction', 'security' );

	}


	/**
	 * Gets the status of a bulk memberships destruction job.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function get_memberships_bulk_destruction_status() {

		check_ajax_referer( 'get-memberships-bulk-destruction-status', 'security' );

	}


}
