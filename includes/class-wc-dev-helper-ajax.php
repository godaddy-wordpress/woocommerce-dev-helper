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
 * AJAX handler.
 *
 * @since 0.5.0
 */
class WC_Dev_Helper_Ajax {


	/**
	 * Adds AJAX actions.
	 *
	 * @since 0.5.0
	 */
	public function __construct() {

		add_action( 'wp_ajax_wc_dev_helper_get_session', array( $this, 'get_session_data' ) );
		add_action( 'wp_ajax_nopriv_wc_dev_helper_get_session', array( $this, 'get_session_data' ) );
	}


	/**
	 * Gets session data from WooCommerce for the current user.
	 *
	 * @since 0.5.0
	 */
	public function get_session_data() {

		/* @type \WC_Session_Handler $session_handler */
		$session_handler = wc()->session;

		wp_send_json( $session_handler->get_session_data() );
	}


}
