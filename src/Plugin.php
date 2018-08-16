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
 * Dev Helper main class.
 *
 * @since 1.0.0
 */
class Plugin extends Framework\SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '1.0.0';

	/** plugin ID */
	const PLUGIN_ID = 'woocommerce-dev-helper';


	/** @var null|bool flags whether WooCommerce core is active */
	private $woocommerce_active;

	/** @var Scripts handler instance */
	private $scripts;

	/** @var AJAX handler instance */
	private $ajax;

	/** @var Gateways helper instance */
	private $gateways;

	/** @var Tools handler instance */
	private $tools;

	/** @var Forwarded_URLs handler instance */
	private $forwarded_urls;

	/** @var Subscriptions helper instance */
	private $subscriptions;

	/** @var null|bool flags whether WooCommerce Subscriptions is active */
	private $subscriptions_active;

	/** @var Memberships helper instance */
	private $memberships;

	/** @var null|bool flags whether WooCommerce Memberships is active */
	private $memberships_active;


	/**
	 * WooCommerce Dev Helper constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			array(
				'text_domain' => 'woocommerce-dev-helper',
			)
		);

		// use forwarded URLs: this needs to be done as early as possible in order to set the $_SERVER['HTTPS'] var
		$this->forwarded_urls = new Forwarded_URLs();

		// logs actions/filters upon request
		add_action( 'shutdown', function() {

			wc_dev_helper()::log_hooks();

		} );

		// removes the Woo Updater notice when the updater is deactivated
		add_action( 'admin_init', function() {

			remove_action( 'admin_notices', 'woothemes_updater_notice' );

			add_filter( 'woocommerce_helper_suppress_admin_notices', '__return_true' );

		}, 100 );
	}


	/**
	 * Returns the full path and filename of the plugin file.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_file() {

		return __FILE__;
	}


	/**
	 * Returns the plugin name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_plugin_name() {

		return __( 'A WooCommerce Dev Helper', 'woocommerce-dev-helper' );
	}


	/**
	 * Returns the plugin URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_plugin_url() {

		return \WC_Dev_Helper_Loader::get_plugin_url();
	}


	/**
	 * Initializes the plugin.
	 *
	 * @since 1.0.0
	 */
	public function init_plugin() {

		$this->includes();
	}


	/**
	 * Include required files
	 *
	 * @since 1.0.0
	 */
	private function includes() {

		$this->scripts = new Scripts();

		// AJAX handler
		if ( is_ajax() ) {
			$this->ajax = new AJAX();
		}

		// Gateways handler
		$this->gateways = new Gateways();

		// Subscriptions helper
		if ( $this->is_subscriptions_active() ) {
			$this->subscriptions = new Subscriptions();
		}

		// Memberships helper
		if ( $this->is_memberships_active() ) {
			$this->memberships = new Memberships();
		}

		// Tools handler
		$this->tools = new Tools();
	}


	/**
	 * Determines whether WooCommerce is installed and active.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_woocommerce_active() {

		if ( null === $this->woocommerce_active ) {

			$this->woocommerce_active = $this->is_plugin_active( 'woocommerce.php' );
		}

		return $this->woocommerce_active;
	}


	/**
	 * Determines whether WooCommerce Subscriptions is installed and active.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_subscriptions_active() {

		if ( null === $this->subscriptions_active && $this->is_woocommerce_active() ) {

			$this->subscriptions_active = $this->is_plugin_active( 'woocommerce-subscriptions.php' );
		}

		return (bool) $this->subscriptions_active;
	}


	/**
	 * Determines whether WooCommerce Memberships is installed and active.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_memberships_active() {

		if ( null === $this->memberships_active && $this->is_woocommerce_active() ) {

			$this->memberships_active = $this->is_plugin_active( 'woocommerce-memberships.php' );
		}

		return (bool) $this->memberships_active;
	}


	/**
	 * Returns the scripts handler instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Scripts instance
	 */
	public function get_scripts_instance() {

		return $this->scripts;
	}


	/**
	 * Returns the AJAX handler instance.
	 *
	 * @since 1.0.0
	 *
	 * @return AJAX instance
	 */
	public function get_ajax_instance() {

		return $this->ajax;
	}


	/**
	 * Returns the Forwarded URLs handler instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Forwarded_URLs instance
	 */
	public function get_forwarded_urls_instance() {

		return $this->forwarded_urls;
	}


	/**
	 * Returns the gateways handler instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Gateways instance
	 */
	public function get_gateways_instance() {

		return $this->gateways;
	}


	/**
	 * Returns the tools handler instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Tools instance
	 */
	public function get_tools_instance() {

		return $this->tools;
	}


	/**
	 * Returns the Subscriptions helper instance
	 *
	 * @since 1.0.0
	 *
	 * @return null|Subscriptions instance
	 */
	public function get_subscriptions_instance() {

		return $this->subscriptions;
	}


	/**
	 * Returns the Memberships helper instance.
	 *
	 * @since 1.0.0
	 *
	 * @return null|Memberships instance
	 */
	public function get_memberships_instance() {

		return $this->memberships;
	}


	/**
	 * Returns the plugin main instance.
	 *
	 * Ensures only one instance can be loaded and is loaded at any time.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Dev_Helper\Plugin main instance
	 */
	public static function instance() {

		if ( null === self::$instance ) {

			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Implements a simple way to log the actions/filers fired, simply add this query string:
	 *
	 * `?wcdh_hooks=actions|filters|all`
	 *
	 * And the desired hook names will be saved to the error log along with a fired count
	 *
	 * If you want more advanced hook logging, use:
	 * @link https://wordpress.org/plugins/debug-bar-actions-and-filters-addon/
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 */
	public static function log_hooks( $key = 'wcdh_hooks' ) {

		if ( ! empty( $_GET[ $key ] ) ) {

			$hooks = array();

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
	}


	/**
	 * Helper method for the PHP debug_backtrace() function.
	 *
	 * This is based on the Magento mageDebugBacktrace() function.
	 *
	 * @see debug_backtrace()
	 *
	 * @since 1.0.0
	 *
	 * @param bool $return if true, returns the output, otherwise echoes it
	 * @param bool $html if true, output is formatted as HTML, otherwise plaintext
	 * @param bool $show_first if false, the line that contains the wp_debug_backtrace call is not included in the trace
	 * @return void|string (depending on $return value, default returns a string)
	 */
	public static function debug_backtrace( $return = true, $html = false, $show_first = true ) {

		$d   = debug_backtrace();
		$out = '';

		if ( $html ) {
			$out .= '<pre>';
		}

		foreach ( $d as $i => $r ) {

			if ( ! $show_first && (int) $i === 0 ) {
				continue;
			}

			// sometimes there is undefined index 'file'
			@$out .= "[$i] {$r['file']}:{$r['line']}\n";
		}

		if ( $html ) {
			$out .= '</pre>';
		}

		if ( $return ) {
			return $out;
		} else {
			echo $out;
		}
	}


	/**
	 * Sends the result print_r() or var_dump() for a variable to the error log.
	 *
	 * @see print_r()
	 * @see var_dump()
	 *
	 * For alternative logging in browser console, consider:
	 * @link https://wordpress.org/plugins/wp-php-console/
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $var variable to log
	 * @param bool $dump use wp_var_dump() instead of print_r(), default false
	 */
	public static function var_log( $var, $dump = false ) {

		if ( $dump ) {
			error_log( wp_var_dump( $var ) );
		} else {
			error_log( print_r( $var, true ) );
		}
	}


	/**
	 * Helper function for the PHP var_dump() function.
	 *
	 * Allows to return the output, rather than printing.  Useful for logging.
	 *
	 * @see var_dump()
	 *
	 * For alternative logging in browser console, consider:
	 * @link https://wordpress.org/plugins/wp-php-console/
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $var the variable to dump
	 * @param bool $return if true, returns the variable dump; defaults to true
	 * @param bool $html_errors true or false enables or disables the html_errors directive, null leaves it untouched.  Useful when dumping variables to the command line with Xdebug installed and html formatting is not desired.
	 * @return void|string returns a string if $return is true, void otherwise
	 */
	public static function var_dump( $var, $return = true, $html_errors = false ) {

		$old_html_errors = '';

		if ( is_bool( $html_errors ) && extension_loaded( 'xdebug' ) ) {
			// disable html_errors and save the current setting
			$old_html_errors = ini_set( 'html_errors', $html_errors );
		}

		ob_start();

		var_dump( $var );

		$output = ob_get_clean();

		if ( is_bool( $html_errors ) && extension_loaded( 'xdebug' ) ) {
			// return html_errors to its original setting
			ini_set( 'html_errors', $old_html_errors );
		}

		if ( $return ) {
			return $output;
		} else {
			echo $output;
		}
	}


	/**
	 * Prints human-readable information about a variable wrapping it in pre-formatted HTML tags.
	 *
	 * @see print_r()
	 *
	 * For alternative logging in browser console, consider:
	 * @link https://wordpress.org/plugins/wp-php-console/
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $var variable
	 */
	public static function print_r( $var ) {

		echo '<pre>'; print_r( $var ); echo '</pre>';
	}


}
