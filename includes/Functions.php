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
 * @copyright Copyright (c) 2015-2021, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;


if ( ! function_exists( 'wp_debug_backtrace' ) ) :

	/**
	 * Helper function for the PHP debug_backtrace() function.
	 * This is based on the Magento mageDebugBacktrace() function.
	 *
	 * Example usage: error_log( wp_debug_backtrace() );
	 *
	 * @since 0.1.0
	 * @param bool $return if true, returns the output, otherwise echo's
	 * @param bool $html if true, output is formatted as HTML, otherwise plaintext
	 * @param bool $show_first if false, the line that contains the
	 *        wp_debug_backtrace call is not included in the trace
	 * @return mixed Returns string if $return is true, void otherwise
	 */
	function wp_debug_backtrace( $return = true, $html = false, $show_first = true ) {

		$d   = debug_backtrace();
		$out = '';
		if ( $html ) {
			$out .= "<pre>";
		}
		foreach ( $d as $i => $r ) {
			if ( ! $show_first && $i == 0 ) {
				continue;
			}
			// sometimes there is undefined index 'file'
			@$out .= "[$i] {$r['file']}:{$r['line']}\n";
		}
		if ( $html ) {
			$out .= "</pre>";
		}
		if ( $return ) {
			return $out;
		} else {
			echo $out;
		}
	}

endif;


if ( ! function_exists( 'wp_var_dump' ) ) :

	/**
	 * Helper function for the PHP var_dump() function, allowing you to return
	 * the output, rather than printing.  Useful for logging.
	 *
	 * Example usage: error_log( wp_var_dump( $foo ) );
	 *
	 * @since 0.1.0
	 * @param mixed $var the variable to dump
	 * @param bool $return if true, returns the vardump; defaults to true
	 * @param bool $html_errors true or false enables or disables the html_errors
	 *        directive, null leaves it untouched.  Useful when dumping variables
	 *        to the command line with Xdebug installed and html formatting is
	 *        not desired.
	 * @return void|string Returns a string if $return is true, void otherwise
	 */
	function wp_var_dump( $var, $return = true, $html_errors = false ) {

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

endif;


if ( ! function_exists( 'wp_var_log' ) ) :

	/**
	 * print_r or var_dump a variable to the error log, useful for logging.
	 *
	 * example usage: wp_var_log( $var );
	 *
	 * @since 0.1.0
	 * @param mixed $var variable to log
	 * @param bool $dump use wp_var_dump() instead of print_r(), default false
	 * @return string
	 */
	function wp_var_log( $var, $dump = false ) {

		if ( $dump ) {
			error_log( wp_var_dump( $var ) );
		} else {
			error_log( print_r( $var, true ) );
		}
	}

endif;


if ( ! function_exists( 'wp_print_r' ) ) :

	/**
	 * Print human-readable information about a variable
	 * wrapping it in pre-formatted HTML tags
	 *
	 * example usage: wp_print_r( $var );
	 *
	 * @since 0.4.0
	 * @param $var
	 */
	function wp_print_r( $var ) {

		echo '<pre>'; print_r( $var ); echo '</pre>';
	}

endif;
