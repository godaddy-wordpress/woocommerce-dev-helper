<?php
/**
 * Plugin Name: WooCommerce Dev Helper
 * Plugin URI: https://github.com/skyverge/woocommerce-dev-helper/
 * Description: A simple plugin for helping develop/debug WooCommerce & extensions
 * Author: SkyVerge
 * Author URI: http://www.skyverge.com
 * Version: 0.2.0
 * Text Domain: woocommerce-dev-helper
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2015 SkyVerge [info@skyverge.com]
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WooCommerce-Dev-Helper
 * @author    SkyVerge
 * @category  Development
 * @copyright Copyright (c) 2012-2015, SkyVerge
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class WC_Dev_Helper {


	/** @var \WC_Dev_Helper instance */
	protected static $instance;

	/** @var \WC_Dev_Helper_Use_Forwarded_URLs instance */
	protected $use_forwarded_urls;

	/** @var \WC_Dev_Helper_Subscriptions instance */
	protected $subscriptions;


	/**
	 * Bootstrap class
	 *
	 * @since 0.1.0
	 */
	public function __construct() {

		// global functions
		require_once( $this->get_plugin_path() . '/includes/wc-dev-helper-functions.php' );

		// remove woo updater notice
		add_action( 'admin_init', array( $this, 'muzzle_woo_updater' ) );

		// class includes
		add_action( 'plugins_loaded', array( $this, 'includes' ) );

		// maybe log actions/filters
		add_action( 'shutdown', array( $this, 'maybe_log_hooks' ) );
	}


	/**
	 * Removes the "Please install Woo Updater" notice when an official WC extension
	 * is active but the Woo Updater plugin is not
	 *
	 * @since 0.1.0
	 */
	public function muzzle_woo_updater() {
		remove_action( 'admin_notices', 'woothemes_updater_notice' );
	}


	/**
	 * Include required files
	 *
	 * @since 0.1.0
	 */
	public function includes() {

		// use forwarded URLs
		require_once( $this->get_plugin_path() . '/includes/class-wc-dev-helper-use-forwarded-urls.php' );
		$this->use_forwarded_urls = new WC_Dev_Helper_Use_Forwarded_URLs();

		if ( class_exists( 'WC_Subscriptions' ) ) {
			// Subscriptions helper
			require_once( $this->get_plugin_path() . '/includes/class-wc-dev-helper-subscriptions.php' );
			$this->subscriptions = new WC_Dev_Helper_Subscriptions();
		}
	}


	/**
	 * A simple way to log the actions/filers fired, simply add this query string:
	 *
	 * ?wcdh_hooks=actions|filters|all
	 *
	 * And the desired hook names will be saved to the error log along with a fired count
	 *
	 * If you want more advanced hook logging, use https://wordpress.org/plugins/debug-bar-actions-and-filters-addon/
	 *
	 * @since 0.1.0
	 */
	function maybe_log_hooks() {

		$hooks = array();

		if ( empty( $_GET['wcdh_hooks'] ) ) {
			return;
		}

		switch( $_GET['wcdh_hooks'] ) {

			case 'actions':
				$hooks = $GLOBALS['wp_actions'];
			break;

			case 'filters':
				$hooks = $GLOBALS['wp_filter'];
			break;

			case 'all':
				$hooks = array_merge( $GLOBALS['wp_actions'], $GLOBALS['wp_filter'] );
			break;
		}

		foreach ( $hooks as $hook => $count ) {
			error_log( sprintf( '%s (%d)' . PHP_EOL, $hook, $count ) );
		}
	}


	/**
	 * Return the plugin path
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_plugin_path() {

		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}


	/** Instance Getters ******************************************************/


	/**
	 * Return the Use_Forwarded_URLs class instance
	 *
	 * @since 0.1.0
	 * @return \WC_Dev_Helper_Use_Forwarded_URLs
	 */
	public function use_forwarded_urls() {
		return $this->use_forwarded_urls;
	}


	/**
	 * Return the Subscriptions class instance
	 *
	 * @since 0.1.0
	 * @return \WC_Dev_Helper_Subscriptions
	 */
	public function subscriptions() {
		return $this->subscriptions;
	}


	/** Housekeeping **********************************************************/


	/**
	 * Main WC Dev Helper Instance, ensures only one instance is/can be loaded
	 *
	 * @since 0.1.0
	 * @see wc_dev_helper()
	 * @return \WC_Dev_Helper
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 0.1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'You cannot clone instances of WooCommerce Dev Helper.', 'woocommerce-dev-helper' ), '0.1.0' );
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 0.1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'You cannot unserialize instances of WooCommerce Dev Helper.', 'woocommerce-dev-helper' ), '0.1.0' );
	}


}  // end \WC_Dev_Helper class


/**
 * Returns the One True Instance of WC Dev Helper
 *
 * @since 0.1.0
 * @return \WC_Dev_Helper instance
 */
function wc_dev_helper() {
	return WC_Dev_Helper::instance();
}

// fire it up!
wc_dev_helper();
