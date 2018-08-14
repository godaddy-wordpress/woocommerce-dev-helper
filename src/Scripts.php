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
 * Adds scripts and tweaks existing ones.
 *
 * @since 1.0.0
 */
class Scripts {


	/**
	 * Scripts constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// add inline JS
		add_action( 'wp_footer', function() {

			// implements a `wc_dev_get_session()` JS function to return WooCommerce Session data
			?>
			<script type="text/javascript">
				function wc_dev_get_session() {
					jQuery.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', { action: 'wc_dev_helper_get_session' }, function( response ) {
						console.log( response );
					} );
				}
			</script>
			<?php

		} );

		// removes WooCommerce strong password requirements
		add_action( 'wp_print_scripts', function() {

			wp_dequeue_script( 'wc-password-strength-meter' );

		}, 100 );
	}


}
