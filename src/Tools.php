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
use SkyVerge\WooCommerce\Dev_Helper\Tools\Memberships\Bulk_Generator as Memberships_Bulk_Generator;
use SkyVerge\WooCommerce\Dev_Helper\Tools\Memberships\Bulk_Destroyer as Memberships_Bulk_Destroyer;

/**
 * Tools handler.
 *
 * @since 1.0.0
 */
class Tools {


	/** @var \SkyVerge\WooCommerce\Dev_Helper\Tools\Memberships\Bulk_Generator instance */
	private $memberships_bulk_generator;

	/** @var \SkyVerge\WooCommerce\Dev_Helper\Tools\Memberships\Bulk_Destroyer instance */
	private $memberships_bulk_destroyer;


	/**
	 * Tools initializer.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( wc_dev_helper()->is_memberships_active() ) {

			$this->memberships_bulk_generator = new Memberships_Bulk_Generator();
			$this->memberships_bulk_destroyer = new Memberships_Bulk_Destroyer();
		}
	}


	/**
	 * Returns the Memberships Bulk Generator tool instance.
	 *
	 * @since 1.0.0
	 *
	 * @return null|Memberships_Bulk_Generator
	 */
	public function get_memberships_bulk_generator_instance() {

		return $this->memberships_bulk_generator;
	}


	/**
	 * Returns the Memberships Bulk Destroyer tool instance.
	 *
	 * @since 1.0.0
	 *
	 * @return null|Memberships_Bulk_Destroyer
	 */
	public function get_memberships_bulk_destroyer_instance() {

		return $this->memberships_bulk_destroyer;
	}


}
