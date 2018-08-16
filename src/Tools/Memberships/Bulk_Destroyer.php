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

namespace SkyVerge\WooCommerce\Dev_Helper\Tools\Memberships;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_2_0 as Framework;

/**
 * Memberships Bulk Destroyer.
 *
 * Tool to remove memberships related objects in bulk.
 *
 * When launched, a background process will destroy all content created by the generator counterpart
 *
 * @see Bulk_Generator counterpart
 *
 * @since 1.0.0
 *
 * @method \stdClass update_job( $job )
 */
class Bulk_Destroyer extends Framework\SV_WP_Background_Job_Handler {


	/**
	 * Background job constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->prefix   = 'wc_dev_helper';
		$this->action   = 'memberships_bulk_destruction';
		$this->data_key = 'loops';

		parent::__construct();
	}


	/**
	 * Returns the total amount of objects to be destroyed.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_objects_to_be_destroyed_count() {

		$count = 0;

		if ( $generator = wc_dev_helper()->get_tools_instance()->get_memberships_bulk_generator_instance() ) {

			$objects = $generator->get_generated_objects_ids();

			if ( ! empty( $objects ) && is_array( $objects ) ) {

				foreach ( $objects as $object_ids ) {

					if ( ! empty( $object_ids ) && is_array( $object_ids ) ) {

						$count += count( $object_ids );
					}
				}
			}
		}

		return $count;
	}


	/**
	 * Completes the job and removes the generated object IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param false|object|\stdClass $job job object
	 * @return false|\stdClass
	 */
	public function complete_job( $job ) {

		$this->delete_generated_object_ids();

		return parent::complete_job( $job );
	}


	/**
	 * Deletes the job and the generated object IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param \stdClass
	 */
	public function delete_job( $job ) {

		parent::delete_job( $job );

		$this->delete_generated_object_ids();
	}


	/**
	 * Deletes the option with the generated object IDs to be removed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function delete_generated_object_ids() {

		$success = false;
		$handler = wc_dev_helper()->get_tools_instance()->get_memberships_bulk_generator_instance();

		if ( $handler ) {
			$success = $handler->delete_generated_objects_ids();
		}

		return $success;
	}


	/**
	 * Processes a job.
	 *
	 * @since 1.0.0
	 *
	 * @param \stdClass $job job object
	 * @param int $items_per_batch number of items to process in a single request (defaults to unlimited)
	 * @return \stdClass $job
	 * @throws Framework\SV_WC_Plugin_Exception when job data is incorrect or an error occurred
	 */
	public function process_job( $job, $items_per_batch = null ) {

		if ( ! $this->start_time ) {
			$this->start_time = time();
		}

		// indicates that the job has started processing
		if ( 'processing' !== $job->status ) {

			$job->status                = 'processing';
			$job->started_processing_at = current_time( 'mysql' );

			$job = $this->update_job( $job );
		}

		$data_key = $this->data_key;

		if ( ! isset( $job->{$data_key} ) ) {
			throw new Framework\SV_WC_Plugin_Exception( sprintf( __( 'Job data key "%s" not set', 'woocommerce-dev-helper' ), $data_key ) );
		}

		if ( ! is_array( $job->{$data_key} ) ) {
			throw new Framework\SV_WC_Plugin_Exception( sprintf( __( 'Job data key "%s" is not an array', 'woocommerce-dev-helper' ), $data_key ) );
		}

		$data = $job->{$data_key};

		$job->total = count( $data );

		// progress indicates how many items have been processed, it
		// does NOT indicate the processed item key in any way
		if ( ! isset( $job->progress ) ) {
			$job->progress = 0;
		}

		// skip already processed items
		if ( $job->progress && ! empty( $data ) ) {
			$data = array_slice( $data, $job->progress, null, true );
		}

		// loop over unprocessed items and process them
		if ( ! empty( $data ) ) {

			$processed       = 0;
			$items_per_batch = (int) $items_per_batch;

			foreach ( $data as $item ) {

				// process the item (may throw exception)
				$job = $this->process_item( $item, $job );

				$processed++;
				$job->progress++;

				// update job progress
				$job = $this->update_job( $job );

				// job limits reached
				if ( ( $items_per_batch && $processed >= $items_per_batch ) || $this->time_exceeded() || $this->memory_exceeded() ) {
					break;
				}
			}
		}

		// complete current job
		if ( $job->progress >= count( $job->{$data_key} ) ) {
			$job = $this->complete_job( $job );
		}

		return $job;
	}


	/**
	 * Processes an item for removal.
	 *
	 * @since 1.0.0
	 *
	 * @param int $object_count just a mock ID that marks the position of all IDs being deleted
	 * @param \stdClass $job job object
	 * @return \stdClass
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function process_item( $object_count, $job ) {

		$handler = wc_dev_helper()->get_tools_instance()->get_memberships_bulk_generator_instance();

		if ( ! $handler ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Could not query generated object IDs to remove.', 'woocommerce-dev-helper' ) );
		}

		if ( $objects = $handler->get_generated_objects_ids() ) {

			$object_names = $handler->get_objects_keys();

			foreach ( $object_names as $object_name ) {

				if ( ! empty( $objects[ $object_name ] ) && is_array( $objects[ $object_name ] ) ) {

					$index = key( $objects[ $object_name ] );
					$id    = current( $objects[ $object_name ] );

					switch ( $object_name ) {

						case 'users' :
							wp_delete_user( $id );
						break;

						case 'memberships' :
						case 'posts' :
						case 'plans' :
						case 'products' :
							wp_delete_post( $id );
						break;

						case 'categories' :
							wp_delete_category( $id );
						break;

						case 'product_cats' :
							wp_delete_term( $id, 'product_cat' );
						break;
					}

					// remove from array, so it's no longer processed in the next iteration
					unset( $objects[ $object_name ][ $index ] );

					// process only one item at the time
					break;
				}
			}

			// update the option
			$handler->set_generated_objects_ids( $objects );
		}

		return $job;
	}


}
