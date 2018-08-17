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

		// adds admin scripts
		add_action( 'admin_enqueue_scripts', function() {

			wc_dev_helper()->get_scripts_instance()->load_admin_scripts();

		} );
	}


	/**
	 * Loads admin-only scripts where needed.
	 *
	 * @since 1.0.0
	 */
	private function load_admin_scripts() {

		$memberships = wc_dev_helper()->get_memberships_instance();

		// add script for handling the memberships bulk generator
		if ( $memberships && $memberships->is_bulk_generation_screen() ) {

			$generator = wc_dev_helper()->get_tools_instance()->get_memberships_bulk_generator_instance();
			$generator = $generator ? $generator->get_job() : null;
			$destroyer = wc_dev_helper()->get_tools_instance()->get_memberships_bulk_destroyer_instance();
			$destroyer = $destroyer ? $destroyer->get_job() : null;

			wp_enqueue_script( 'wc-dev-helper-memberships-bulk-generation', wc_dev_helper()->get_plugin_url() . '/assets/js/admin/wc-dev-helper-memberships-bulk-generation.min.js', array( 'jquery' ), Plugin::VERSION, true );

			wp_localize_script( 'wc-dev-helper-memberships-bulk-generation', 'wc_dev_helper_memberships_bulk_generation', array(

				'ajax_url'                                      => admin_url( 'admin-ajax.php' ),
				'is_bulk_generation_screen'                     => $memberships->is_bulk_generation_screen( 'generate' ),
				'is_bulk_destruction_screen'                    => $memberships->is_bulk_generation_screen( 'destroy' ),
				'bulk_generation_job_in_progress'               => $generator ? $generator->id : false,
				'bulk_destruction_job_in_progress'              => $destroyer ? $destroyer->id : false,
				'start_memberships_bulk_generation_nonce'       => wp_create_nonce( 'start-memberships-bulk-generation' ),
				'get_memberships_bulk_generation_status_nonce'  => wp_create_nonce( 'get-memberships-bulk-generation-status' ),
				'start_memberships_bulk_destruction_nonce'      => wp_create_nonce( 'start-memberships-bulk-destruction' ),
				'get_memberships_bulk_destruction_status_nonce' => wp_create_nonce( 'get-memberships-bulk-destruction-status' ),

				'i18n' => array(
					'generation_in_progress'  => __( 'Members generation in progress...', 'woocommerce-dev-helper' ),
					'generation_success'      => __( 'Members generation complete!', 'woocommerce-dev-helper' ),
					'generation_failure'      => __( 'Members generation failed!', 'woocommerce-dev-helper' ),
					'destruction_in_progress' => __( 'Removing membership objects...', 'woocommerce-dev-helper' ),
					'destruction_success'     => __( 'Membership objects successfully removed.', 'woocommerce-dev-helper' ),
					'destruction_failure'     => __( 'An error occurred while removing membership objects.', 'woocommerce-dev-helper' ),
				),

			) );
		}
	}


}
