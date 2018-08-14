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


	/** @var AJAX handler instance */
	protected $ajax;

	/** @var Forwarded_URLs instance */
	protected $forwarded_urls;

	/** @var Subscriptions helper instance */
	protected $subscriptions;

	/** @var Memberships helper instance */
	protected $memberships;

	/** @var Gateways helper instance */
	private $gateways;


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

		// load classes
		add_action( 'plugins_loaded', array( $this, 'includes' ) );

		// logs actions/filters upon request
		add_action( 'shutdown', function() {

			wc_dev_helper()::log_hooks();

		} );

		// removes the Woo Updater notice when deactivated
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
	 * Include required files
	 *
	 * @since 0.1.0
	 */
	public function includes() {

		// AJAX handler
		if ( defined( 'DOING_AJAX' ) ) {
			$this->ajax = new AJAX();
		}

		// Gateways handler
		$this->gateways = new Gateways();

		// Subscriptions helper
		if ( $this->is_plugin_active( 'woocommerce-subscriptions.php' ) ) {
			$this->subscriptions = new Subscriptions();
		}

		// Memberships helper
		if ( $this->is_plugin_active( 'woocommerce-memberships.php' ) ) {
			$this->memberships = new Memberships();
		}
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

			if ( ! $show_first && $i == 0 ) {
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
	 * @since 1.0.0
	 *
	 * @param mixed $var variable
	 */
	public static function print_r( $var ) {

		echo '<pre>'; print_r( $var ); echo '</pre>';
	}


}
