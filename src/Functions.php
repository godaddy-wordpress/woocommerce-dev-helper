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

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_2_0 as Framework;


/**
 * Returns the One True Instance of WC Dev Helper.
 *
 * @since 1.0.0
 *
 * @return \SkyVerge\WooCommerce\Dev_Helper\Plugin instance
 */
function wc_dev_helper() {

	return \SkyVerge\WooCommerce\Dev_Helper\Plugin::instance();
}


if ( ! function_exists( 'wp_debug_backtrace' ) ) :

	/**
	 * Helper function for the PHP debug_backtrace() function.
	 *
	 * Example usage: error_log( wp_debug_backtrace() );
	 *
	 * @see Functions::debug_backtrace()
	 *
	 * @since 0.1.0
	 *
	 * @param bool $return if true, returns the output, otherwise echo's
	 * @param bool $html if true, output is formatted as HTML, otherwise plaintext
	 * @param bool $show_first if false, the line that contains the wp_debug_backtrace call is not included in the trace
	 * @return mixed Returns string if $return is true, void otherwise
	 */
	function wp_debug_backtrace( $return = true, $html = false, $show_first = true ) {

		return \SkyVerge\WooCommerce\Dev_Helper\Plugin::debug_backtrace( $return, $html, $show_first );
	}

endif;


if ( ! function_exists( 'wp_var_dump' ) ) :

	/**
	 * Helper function for the PHP var_dump() function.
	 *
	 * Example usage: wp_var_dump( $foo );
	 *
	 * @see Functions::var_dump()
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $var the variable to dump
	 * @param bool $return if true, returns the variable dump; defaults to true
	 * @param bool $html_errors true or false enables or disables the html_errors directive, null leaves it untouched.  Useful when dumping variables to the command line with Xdebug installed and html formatting is not desired.
	 * @return void|string returns a string if $return is true, void otherwise
	 */
	function wp_var_dump( $var, $return = true, $html_errors = false ) {

		return \SkyVerge\WooCommerce\Dev_Helper\Plugin::var_dump( $var, $return, $html_errors );
	}

endif;


if ( ! function_exists( 'wp_var_log' ) ) :

	/**
	 * Executes print_r or var_dump on a variable to the error log, useful for logging.
	 *
	 * Example usage: wp_var_log( $var );)
	 *
	 * @see Functions::var_log()
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $var variable to log
	 * @param bool $dump use wp_var_dump() instead of print_r(), default false
	 * @return string
	 */
	function wp_var_log( $var, $dump = false ) {

		\SkyVerge\WooCommerce\Dev_Helper\Plugin::var_log( $var, $dump );
	}

endif;


if ( ! function_exists( 'wp_print_r' ) ) :

	/**
	 * Prints human-readable information about a variable wrapping it in pre-formatted HTML tags.
	 *
	 * Example usage: wp_print_r( $var );
	 *
	 * @see Functions::print_r()
	 *
	 * @since 0.4.0
	 *
	 * @param mixed $var variable
	 */
	function wp_print_r( $var ) {

		\SkyVerge\WooCommerce\Dev_Helper\Plugin::print_r( $var );
	}

endif;
