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
						'members' => $generator->generate_users_slugs( $limit ),
					) );

					// dispatch the background processor
					$generator->dispatch();

					// send the job as result
					wp_send_json_success( $job );
				}

				wp_send_json_error( __( 'There is an existing job in the queue that has not completed yet.', 'woocommerce-dev-helper' ) );
			}

			wp_send_json_error( __( 'Could not load the background job handler.', 'woocommerce-dev-helper' ) );
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

		$this->get_background_process_status( 'get-memberships-bulk-generation-status' );
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

		$destructor = wc_dev_helper()->get_tools_instance()->get_memberships_bulk_destroyer_instance();

		if ( $destructor ) {

			$existing_job = $destructor->get_job();

			if ( ! $existing_job ) {

				$total = $destructor->get_objects_to_be_destroyed_count();

				if ( $total > 0 ) {

					$job = $destructor->create_job( array(
						'loops' => range( 0, $total ),
					) );

					// dispatch the background processor
					$destructor->dispatch();

					// send the job as result
					wp_send_json_success( $job );
				}

				wp_send_json_error( __( 'There seem to be no objects to remove.', 'wc-dev-helper' ) );
			}

			wp_send_json_error( __( 'There is an existing job in the queue that has not completed yet.', 'woocommerce-dev-helper' ) );
		}

		wp_send_json_error( __( 'Could not load the background job handler.', 'woocommerce-dev-helper' ) );
	}


	/**
	 * Gets the status of a bulk memberships destruction job.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function get_memberships_bulk_destruction_status() {

		$this->get_background_process_status( 'get-memberships-bulk-destruction-status' );
	}


	/**
	 * Fetches a job in progress with its status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $which_job job and action identifier
	 */
	private function get_background_process_status( $which_job ) {

		check_ajax_referer( $which_job, 'security' );

		if ( isset( $_POST['job_id'] ) ) {

			if ( 'get-memberships-bulk-generation-status' === $which_job ) {
				$generator = wc_dev_helper()->get_tools_instance()->get_memberships_bulk_generator_instance();
			} elseif ( 'get-memberships-bulk-destruction-status' === $which_job ) {
				$generator = wc_dev_helper()->get_tools_instance()->get_memberships_bulk_destroyer_instance();
			}

			if ( ! empty( $generator ) ) {

				$job = $generator->get_job( $_POST['job_id'] );

				if ( ! $job ) {
					/* translators: Placeholder: %s - job ID */
					wp_send_json_error( sprintf( esc_html__( 'The background job with ID %s could not be found.', 'woocommerce-dev-helper' ), sanitize_title( $_POST['job_id'] ) ) );
				}

				// if loopback connections aren't supported, manually process the job
				if ( 'completed' !== $job->status && ! $generator->test_connection() ) {

					try {
						$job = $generator->process_job( $job );
					} catch ( \Exception $e ) {
						wp_send_json_error( $e->getMessage() );
					}
				}

				// delete the job once complete
				if ( 'completed' === $job->status ) {
					$generator->delete_job( $job );
				}

				// send the job, along with the stats, as result
				wp_send_json_success( $job );
			}

			wp_send_json_error( __( 'Could not load the background job handler.', 'woocommerce-dev-helper' ) );
		}

		wp_send_json_error( __( 'Undefined background job ID.', 'woocommerce-dev-helper' ) );
	}


}
