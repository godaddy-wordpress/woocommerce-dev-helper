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

defined( 'ABSPATH' ) or exit;

/**
 * Tools & Utilities handler.
 *
 * @since 0.9.0
 */
class WC_Dev_Helper_Tools {


	/** @var \WC_Dev_Helper_Use_Forwarded_URLs instance */
	private $use_forwarded_urls;

	/** @var \WC_Dev_Helper_Import_Export_Options instance */
	private $import_export_options;


	/**
	 * Tools handler constructor.
	 *
	 * @since 0.9.0
	 *
	 * @param string $plugin_path helper's main file path
	 */
	public function __construct( $plugin_path ) {

		// use forwarded URLs: this needs to be loaded as early as possible in order to set the $_SERVER['HTTPS'] var
		require_once( $plugin_path . '/includes/tools/class-wc-dev-helper-use-forwarded-urls.php' );

		$this->use_forwarded_urls = new WC_Dev_Helper_Use_Forwarded_URLs();

		// use forwarded URLs: this needs to be done as early as possible in order to set the $_SERVER['HTTPS'] var
		require_once( $plugin_path . '/includes/tools/class-wc-dev-helper-options-importer-exporter.php' );

		$this->import_export_options = new WC_Dev_Helper_Import_Export_Options();
	}


	/**
	 * Returns the forwarded URLs handler instance.
	 *
	 * @since 0.9.0
	 *
	 * @return \WC_Dev_Helper_Use_Forwarded_URLs
	 */
	public function get_forwarded_urls_instance() {
		return $this->use_forwarded_urls;
	}


	/**
	 * Returns the import/export options handler instance.
	 *
	 * @since 0.9.0
	 *
	 * @return \WC_Dev_Helper_Import_Export_Options
	 */
	public function get_import_export_options_instance() {
		return $this->import_export_options;
	}


}
