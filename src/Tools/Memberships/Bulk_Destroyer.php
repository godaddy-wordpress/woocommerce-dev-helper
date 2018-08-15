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
 * @see \SkyVerge\WooCommerce\Dev_Helper\Tools\Memberships\Bulk_Generator
 *
 * @since 1.0.0
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
		$this->data_key = 'objects';

		parent::__construct();
	}


	/**
	 * Process an item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $user_login item to process
	 * @param \stdClass $job job object
	 */
	public function process_item( $user_login, $job ) {


	}


	/**
	 * Deletes membership plans created by the bulk generator.
	 *
	 * @since 1.0.0
	 */
	private function delete_membership_plans() {

		if ( $generator =wc_dev_helper()->get_tools_instance()->get_memberships_bulk_generator_instance() ) {

			foreach ( $generator->get_membership_plans_slugs() as $plan_slug ) {

				if ( $plan = wc_memberships_get_membership_plan( $plan_slug ) ) {

					wp_delete_post( $plan->get_id() );
				}
			}
		}
	}


}
